# Query Logger for Laravel 5

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
