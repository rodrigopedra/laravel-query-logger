<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

final readonly class QueryLogger
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(QueryExecuted $event): void
    {
        $this->logger->debug($this->sql($event), [
            'time' => $event->time,
            'connection' => $event->connectionName,
            'database' => $event->connection->getDatabaseName(),
            'bindings' => $event->bindings,
            'callSpot' => $this->guessCallSpot(),
        ]);
    }

    protected function sql(QueryExecuted $event): string
    {
        try {
            return $event->toRawSql();
        } catch (\Throwable) {
            return $this->toSQL($event);
        }
    }

    public function toSQL(QueryExecuted $event): string
    {
        $pdo = \method_exists($event->connection, 'getPdo')
            ? $event->connection->getPdo()
            : null;

        $dateFormat = $event->connection->getQueryGrammar()->getDateFormat();

        $bindings = $event->connection->prepareBindings($event->bindings);
        $bindings = \array_map(fn ($value) => $this->prepareValue($event, $pdo, $dateFormat, $value), $bindings);

        return $this->prepareQuery($event->sql, $bindings);
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

    protected function prepareValue(QueryExecuted $event, ?\PDO $pdo, string $dateFormat, $value): string
    {
        if (\method_exists($event->connection, 'escape')) {
            try {
                return $event->connection->escape($value);
            } catch (\Throwable) {
            }
        }
        
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

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format($dateFormat);
        }

        if ($value instanceof \Stringable) {
            $value = \strval($value);
        }

        if (\is_object($value) && \method_exists($value, 'toString')) {
            $value = $value->toString();
        }

        // objects not implementing __toString() or toString() will fail here
        return $this->quote($pdo, \strval($value));
    }

    protected function quote(?\PDO $pdo, string $value): string
    {
        if ($pdo) {
            return $pdo->quote($value);
        }

        $search = ["\\", "\x00", "\n", "\r", "'", '"', "\x1a"];
        $replace = ["\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z"];

        return "'" . \str_replace($search, $replace, $value) . "'";
    }

    protected function guessCallSpot(): array
    {
        $stack = \debug_backtrace(\DEBUG_BACKTRACE_IGNORE_ARGS);
        $vendor = \DIRECTORY_SEPARATOR . 'vendor' . \DIRECTORY_SEPARATOR;

        foreach ($stack as $trace) {
            if (\array_key_exists('file', $trace) && ! \str_contains($trace['file'], $vendor)) {
                return Arr::only($trace, ['file', 'line', 'function']);
            }
        }

        return ['file' => null, 'line' => null, 'function' => null];
    }
}
