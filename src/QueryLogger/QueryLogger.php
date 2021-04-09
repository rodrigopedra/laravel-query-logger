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

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Events\QueryExecuted;
use Psr\Log\LoggerInterface;

class QueryLogger
{
    protected ConnectionInterface $connection;
    protected LoggerInterface $logger;
    protected $pdo = null;

    public function __construct(ConnectionInterface $connection, LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;

        if (\method_exists($connection, 'getPdo')) {
            $this->pdo = $connection->getPdo();
        }
    }

    public function handle(QueryExecuted $event)
    {
        $bindings = $this->connection->prepareBindings($event->bindings);
        $bindings = \array_map([$this, 'prepareValue'], $bindings);

        $query = $this->prepareQuery($event->sql, $bindings);

        $this->logger->info($query, ['bindings' => $event->bindings, 'time' => $event->time]);
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

    protected function prepareValue($value): string
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
            return $this->quote('[BINARY DATA]');
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            $value = \strval($value);
        }

        if (\is_object($value) && \method_exists($value, 'toString')) {
            $value = $value->toString();
        }

        if (\is_object($value) && \is_a($value, \DateTimeInterface::class)) {
            $value = $value->format('Y-m-d Hï:i:s');
        }

        // objects not implementing __toString() or toString() will fail here
        return $this->quote(\strval($value));
    }

    protected function quote(string $value): string
    {
        if ($this->pdo) {
            return $this->pdo->quote($value);
        }

        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . \str_replace($search, $replace, $value) . "'";
    }
}
