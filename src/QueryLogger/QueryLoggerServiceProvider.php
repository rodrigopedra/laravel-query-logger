<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Connection;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class QueryLoggerServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, LoggerInterface $logger, Connection $connection): void
    {
        if ($config->get('app.debug') === true) {
            $this->startQueryLogger($logger, $connection);
        } else {
            $connection->disableQueryLog();
        }
    }

    protected function startQueryLogger(LoggerInterface $logger, Connection $connection): void
    {
        $connection->listen(function (QueryExecuted $event) use ($logger): void {
            // Format binding values
            $bindings = \array_map(static function ($value) {
                if (\is_null($value)) {
                    return 'NULL';
                }

                if (\is_bool($value)) {
                    return $value ? '1' : '0';
                }

                if (\is_int($value) || \is_float($value)) {
                    return \strval($value);
                }

                if (\is_scalar($value)) {
                    return "'{$value}'";
                }

                if (\is_object($value) && $value instanceof \DateTime) {
                    return '\'' . $value->format('Y-m-d H:i:s') . '\'';
                }

                if (\is_object($value) && \method_exists($value, '__toString')) {
                    return "'{$value->__toString()}'";
                }

                return $value;
            }, $event->bindings);

            // Replace SQL statement placeholders
            $query = \preg_replace_callback('/\?/', static fn () => \array_shift($bindings), $event->sql);

            $logger->info($query, ['bindings' => $bindings, 'time' => $event->time]);
        });
    }
}
