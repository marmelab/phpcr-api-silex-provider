<table>
        <tr>
            <td><img width="20" src="https://cdnjs.cloudflare.com/ajax/libs/octicons/8.5.0/svg/archive.svg" alt="archived" /></td>
            <td><strong>Archived Repository</strong><br />
            This code is no longer maintained. Feel free to fork it, but use it at your own risks.
        </td>
        </tr>
</table>

# PHPCR API Silex Provider [![Build Status](https://travis-ci.org/marmelab/phpcr-api-silex-provider.svg?branch=master)](https://travis-ci.org/marmelab/phpcr-api-silex-provider)

PHPCR API Silex Provider provides a REST access to [marmelab/phpcr-api](https://github.com/marmelab/phpcr-api).

Installation
------------
The recommended way to install phpcr-api is through `Composer`. Just create a
``composer.json`` file and run the ``composer install`` command to
install it:

```json
{
    "require": {
        "marmelab/phpcr-api-silex-provider": "dev-master"
    }
}
```
Utilisation
-------------
```php
$repositoriesConfig = array(
    'Repository Test' => array(
        'factory' => jackalope.jackrabbit,
        'parameters' => array(
            'jackalope.jackrabbit_uri' => 'http://localhost:8080/server',
            'credentials.username' => 'admin',
            'credentials.password' => 'admin'
        )
    ),
    'Repository Test2' => array(
        'factory' => 'jackalope.doctrine-dbal',
        'parameters' => array(
            'doctrine_dbal.config' => array(
                'driver' => 'pdo_sqlite',
                'path' => '../src/app.db',
            ),
            'credentials.username' => 'admin',
            'credentials.password' => 'admin'
        )
    )
);

$app->register(new \PHPCRAPI\Silex\ApiServiceProvider(),array(
    'phpcr_api.repositories_config' =>  $repositoriesConfig,
    'phpcr_api.mount_prefix'    =>  '/api'
));
```

License
-------

This application is available under the MIT License, courtesy of [marmelab](http://marmelab.com).
