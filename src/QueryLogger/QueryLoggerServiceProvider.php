<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;

class QueryLoggerServiceProvider extends ServiceProvider
{
    public array $singletons = [
        QueryLogger::class,
    ];

    public function boot(Dispatcher $events): void
    {
        if ($this->app->hasDebugModeEnabled()) {
            $events->listen(QueryExecuted::class, QueryLogger::class);
        }
    }
}
