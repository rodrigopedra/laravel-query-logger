<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Arr;
use Psr\Log\LoggerInterface;

final readonly class QueryLogger
{
    public function __construct(
        private LoggerInterface $logger,
        private ConnectionResolverInterface $db,
    ) {}

    public function handle(QueryExecuted $event): void
    {
        $this->logger->debug($event->toRawSql(), [
            'time' => $event->time,
            'connection' => $event->connectionName,
            'database' => $this->db->connection($event->connectionName)->getDatabaseName(),
            'bindings' => $event->bindings,
            'callSpot' => $this->guessCallSpot(),
        ]);
    }

    private function guessCallSpot(): array
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
