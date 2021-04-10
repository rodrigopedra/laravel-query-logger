<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\ServiceProvider;

class QueryLoggerServiceProvider extends ServiceProvider
{
    public function boot(Repository $config, Dispatcher $events)
    {
        if ($config->get('app.debug') === true) {
            $events->listen(QueryExecuted::class, QueryLogger::class);
        }
    }
}
