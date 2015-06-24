<?php

namespace RodrigoPedra\QueryLogger;

use Illuminate\Support\ServiceProvider;

class QueryLoggerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (env( 'APP_DEBUG', false )) {
            $this->startQueryLogger();
        } else {
            \DB::connection()->disableQueryLog();
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    private function startQueryLogger()
    {
        \Event::listen(
            'illuminate.query',
            function ( $query, $bindings, $time, $name ) {
                $data = compact( 'bindings', 'time', 'name' );

                // Format binding data for sql insertion
                foreach ($bindings as $i => $binding) {
                    if (is_object( $binding ) && $binding instanceof \DateTime) {
                        $bindings[ $i ] = '\'' . $binding->format( 'Y-m-d H:i:s' ) . '\'';
                    } elseif (is_null( $binding )) {
                        $bindings[ $i ] = 'NULL';
                    } elseif (is_bool( $binding )) {
                        $bindings[ $i ] = $binding ? '1' : '0';
                    } elseif (is_string( $binding )) {
                        $bindings[ $i ] = "'$binding'";
                    }
                }

                $query = preg_replace_callback(
                    '/\?/',
                    function () use ( &$bindings ) {
                        return array_shift( $bindings );
                    }, $query
                );

                \Log::info( $query, $data );
            }
        );
    }
}
