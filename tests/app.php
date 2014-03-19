<?php

use PHPCRAPI\Silex\APIServiceProvider;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Application\UrlGeneratorTrait;
use Silex\WebTestCase;

class AppTest extends Application
{
	use UrlGeneratorTrait;
}

$app = new AppTest();

$app->register(new UrlGeneratorServiceProvider());

//$app['debug'] = true;
$app['exception_handler']->disable();

$app->register(new APIServiceProvider(),array(
	'phpcr_api.repositories_config'	=>	array(
		'repository_test' => array(
			'factory' => 'jackalope.jackrabbit',
       		'parameters' => array(
            	'jackalope.jackrabbit_uri' 	=> 'http://localhost:8080/server',
            	'credentials.username'		=> 'admin',
            	'credentials.password' 		=> 'admin'
			)
		)
	),
	'phpcr_api.mount_prefix'	=>	'/api'
));
return $app;