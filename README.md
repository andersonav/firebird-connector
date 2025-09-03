# Firebird for Laravel

[![Total Downloads](https://poser.pugx.org/andersonav/firebird-connector/downloads)](https://packagist.org/packages/andersonav/firebird-connector)
[![License](https://poser.pugx.org/andersonav/firebird-connector/license)](https://packagist.org/packages/andersonav/firebird-connector)

This package adds support for the Firebird PDO Database Driver in Laravel applications.

## Version Support

- **PHP:** 8.2, 8.3, 8.4
- **Laravel:** 12.x
- **Firebird:** 3.0, 4.0, 5.0

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
- [Anderson Alves](https://github.com/andersonav)

## License
Licensed under the [MIT](https://choosealicense.com/licenses/mit/) license.
