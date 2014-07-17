<?php

namespace PHPCRAPI\Silex\Tests;

use PHPCRAPI\Silex\APIServiceProvider;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\WebTestCase;

class AppTest extends Application
{
    use UrlGeneratorTrait;
}

abstract class AbstractWebTestCase extends WebTestCase
{
    public static function setUpBeforeClass() {
        copy(__DIR__.'/jackalope.db-dist', __DIR__.'/jackalope.db');
    }

    public function createApplication()
    {
        $app = new AppTest();

        $app->register(new UrlGeneratorServiceProvider());

        $app['exception_handler']->disable();

        $app->register(new APIServiceProvider(),array(
            'phpcr_api.repositories_config' =>  array(
                'repository_test' => array(
                    'factory' => 'jackalope.doctrine-dbal',
                    'parameters' => array(
                        'doctrine_dbal.config' => array(
                            'driver' => 'pdo_sqlite',
                            'path'   => __DIR__.'/jackalope.db',
                        ),
                        'credentials.username' => 'admin',
                        'credentials.password' => 'admin'
                    )
                )
            ),
            'phpcr_api.mount_prefix'    =>  '/api'
        ));

        return $app;
    }
}
