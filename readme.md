# Query Logger for Laravel

- *for Laravel versions 5.0 and 5.1 use the 1.0 release*
- *for Laravel versions from 5.2 to 7.x with PHP < 7.4 use 2.x release*

Writes all queries to the log when `APP_DEBUG=true`. 

This package tries to replace the bindings with their values so SQL queries becomes easier to debug.

## Installation

```
composer require rodrigopedra/laravel-query-logger --dev
```

## Configuration

As releases 3.x+ requires at least Laravel version 5.5, the service provider 
should be configured automatically using Laravel's package auto-discovery.

If you are not using pakacge auto-discovery, you will need to add the provider to your `config/app.php`:

```php
// in your config/app.php add the provider to the service providers array

'providers' => [
    /* ... */
    
    'RodrigoPedra\QueryLogger\QueryLoggerServiceProvider',
]
```

### License

This package is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
