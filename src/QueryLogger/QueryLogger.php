<?php

/*
 * The regular expression used in the `prepareQuery()` method and
 * the quote emulation used in the `quote()` method where extracted
 * from the "barryvdh/laravel-debugbar" package available at:
 *
 * https://github.com/barryvdh/laravel-debugbar/tree/6420113d90bb746423fa70b9940e9e7c26ebc121
 *
 * "barryvdh/laravel-debugbar" is licensed under MIT. License is available at:
 *
 * https://github.com/barryvdh/laravel-debugbar/blob/6420113d90bb746423fa70b9940e9e7c26ebc121/LICENSE
 */

namespace RodrigoPedra\QueryLogger;

use Illuminate\Database\Events\QueryExecuted;
use Psr\Log\LoggerInterface;

class QueryLogger
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(QueryExecuted $event)
    {
        $pdo = \method_exists($event->connection, 'getPdo')
            ? $event->connection->getPdo()
            : null;

        $bindings = $event->connection->prepareBindings($event->bindings);
        $bindings = \array_map(fn ($value) => $this->prepareValue($pdo, $value), $bindings);

        $query = $this->prepareQuery($event->sql, $bindings);

        $this->logger->info($query, [
            'bindings' => $event->bindings,
            'time' => $event->time,
            'connection' => $event->connectionName,
        ]);
    }

    protected function prepareQuery(string $query, array $bindings): string
    {
        foreach ($bindings as $key => $value) {
            $regex = \is_numeric($key)
                ? "/(?<!\?)\?(?=(?:[^'\\\']*'[^'\\']*')*[^'\\\']*$)(?!\?)/"
                : "/:$key(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

            $query = \preg_replace($regex, $value, $query, 1);
        }

        return $query;
    }

    protected function prepareValue($pdo, $value): string
    {
        if (\is_null($value)) {
            return 'NULL';
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (\is_int($value) || \is_float($value)) {
            return \strval($value);
        }

        if (\is_string($value) && ! \mb_check_encoding($value, 'UTF-8')) {
            return $this->quote($pdo, '[BINARY DATA]');
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            $value = \strval($value);
        }

        if (\is_object($value) && \method_exists($value, 'toString')) {
            $value = $value->toString();
        }

        if (\is_object($value) && \is_a($value, \DateTimeInterface::class)) {
            $value = $value->format('Y-m-d H:i:s');
        }

        // objects not implementing __toString() or toString() will fail here
        return $this->quote($pdo, \strval($value));
    }

    protected function quote($pdo, string $value): string
    {
        if ($pdo) {
            return $pdo->quote($value);
        }

        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . \str_replace($search, $replace, $value) . "'";
    }
}
