# Firebird for Laravel

[![Latest Stable Version](https://poser.pugx.org/andersonav/firebird-connector/v/stable)](https://packagist.org/packages/andersonav/firebird-connector)
[![Total Downloads](https://poser.pugx.org/andersonav/firebird-connector/downloads)](https://packagist.org/packages/andersonav/firebird-connector)
[![Tests](https://github.com/andersonav/firebird-connector/actions/workflows/tests.yml/badge.svg)](https://github.com/andersonav/firebird-connector/actions/workflows/tests.yml)
[![License](https://poser.pugx.org/andersonav/firebird-connector/license)](https://packagist.org/packages/andersonav/firebird-connector)

This package adds support for the Firebird PDO Database Driver in Laravel applications.

## Version Support

- **PHP:** 7.4, 8.0, 8.1, 8.2
- **Laravel:** 8.x, 9.x, 10.x
- **Firebird:** 2.5, 3.0, 4.0

## Installation

You can install the package via composer:

```bash
composer require andersonav/firebird-connector
```

_The package will automatically register itself._

Declare the connection within your `config/database.php` file by using `firebird` as the
driver:
```php
'connections' => [

    'firebird' => [
        'driver'   => 'firebird',
        'host'     => env('DB_HOST', 'localhost'),
        'port'     => env('DB_PORT', '3050'),
        'database' => env('DB_DATABASE', '/path_to/database.fdb'),
        'username' => env('DB_USERNAME', 'sysdba'),
        'password' => env('DB_PASSWORD', 'masterkey'),
        'charset'  => env('DB_CHARSET', 'UTF8'),
        'role'     => null,
    ],

],
```

To register this package in Lumen, you'll also need to add the following line to the service providers in your `config/app.php` file:
`$app->register(\AndersonAv\Firebird\FirebirdServiceProvider::class);`

## Limitations
This package does not intend to support database migrations and it should not be used for this use case.

## Credits
- [Harry Gulliford](https://github.com/andersonav)
- [Jacques van Zuydam](https://github.com/jacquestvanzuydam/firebird-connector)
- [Simonov Denis](https://github.com/sim1984/firebird-connector)
- [All Contributors](https://github.com/andersonav/firebird-connector/graphs/contributors)

## License
Licensed under the [MIT](https://choosealicense.com/licenses/mit/) license.
