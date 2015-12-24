# Query Logger for Laravel 5.2

*for Laravel 5.0 and 5.1 use the 1.0 release*

Logs all queries when `APP_DEBUG=true`

## Installation

```
composer require rodrigopedra/laravel-query-logger
```

## Configuration

Add the provider to your config/app.php:

```php
// in your config/app.php add the provider to the service providers key

'providers' => [
    /* ... */
    
    'RodrigoPedra\QueryLogger\QueryLoggerServiceProvider',
]
```

### License

This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
