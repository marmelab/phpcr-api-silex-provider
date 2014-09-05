<?php

namespace PHPCRAPI\Silex;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\Handler\HandlerRegistry;
use PHPCRAPI\Silex\Controller\RepositoryController;
use PHPCRAPI\Silex\Controller\WorkspaceController;
use PHPCRAPI\Silex\Controller\NodeController;
use PHPCRAPI\Silex\Converter\SessionManagerConverter;
use PHPCRAPI\Silex\Converter\PathConverter;
use PHPCRAPI\Silex\SerializationHandler\RepositoryHandler;
use PHPCRAPI\Silex\SerializationHandler\NodeHandler;
use PHPCRAPI\API\Exception\ExceptionInterface;
use PHPCRAPI\API\RepositoryLoader;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Silex\Provider\ServiceControllerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class APIServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
    public function register(Application $app)
    {
        $app->register(new ServiceControllerServiceProvider());

        $app['phpcr_api.mount_prefix'] = isset($app['phpcr_api.mount_prefix']) ? $app['phpcr_api.mount_prefix'] : '/api';
        $app['phpcr_api.repositories_config'] = isset($app['phpcr_api.repositories_config']) ? $app['phpcr_api.repositories_config'] : array();
        $app['serializer_builder'] = $app->share(function() {
            return
                SerializerBuilder::create()
                    ->configureHandlers(function(HandlerRegistry $registry) {
                        $registry->registerSubscribingHandler(new RepositoryHandler());
                    })
                    ->configureHandlers(function(HandlerRegistry $registry) {
                        $registry->registerSubscribingHandler(new NodeHandler());
                    })
                ;
        });

        $app['serializer'] = $app->share(function() use ($app) {
            return $app['serializer_builder']->build();
        });

        $app['response.formater'] = $app->share(function() {
            return new ResponseFormater();
        });

        $app['repository.controller'] = $app->share(function() use ($app) {
            return new RepositoryController($app, $app['response.formater']);
        });

        $app['workspace.controller'] = $app->share(function() use ($app) {
            return new WorkspaceController($app, $app['response.formater']);
        });

        $app['node.controller'] = $app->share(function() use ($app) {
            return new NodeController($app, $app['response.formater']);
        });

        $app['session_manager.converter'] = $app->share(function() use ($app) {
            return new SessionManagerConverter($app);
        });

        $app['path.converter'] = $app->share(function() use ($app) {
            return new PathConverter($app);
        });

        $app['phpcr_api.repository_loader'] = $app->share(function () use ($app) {
            $repositoriesConfig = $app['phpcr_api.repositories_config'];
            $dbOptions = array();

            foreach ($repositoriesConfig as $name => $config) {
                if ('jackalope.doctrine-dbal' !== $config['factory']
                    || empty($config['parameters']['doctrine_dbal.config'])
                ) {
                    continue;
                }

                $dbOptions[$name] = $config['parameters']['doctrine_dbal.config'];
                unset($repositoriesConfig[$name]['parameters']['doctrine_dbal.config']);
            }

            if (!empty($dbOptions)) {
                $app->register(new DoctrineServiceProvider(), array(
                    'dbs.options' => $dbOptions,
                ));

                foreach ($dbOptions as $name => $config) {
                    $repositoriesConfig[$name]['parameters']['jackalope.doctrine_dbal_connection'] = $app['dbs'][$name];
                }
            }

            return new RepositoryLoader($repositoriesConfig);
        });

        $this->registerErrorHandler($app);
        $this->registerBeforeMiddleware($app);
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        /**
         * Repositories
         */
        $controllers->get('/repositories', 'repository.controller:getRepositoriesAction');

        $controllers->get('/repositories/{repository}', 'repository.controller:getRepositoryAction')
            ->convert('repository', 'session_manager.converter:convert')
        ;

        /**
         * Workspaces
         */
        $controllers->get('/repositories/{repository}/workspaces', 'workspace.controller:getWorkspacesAction')
            ->convert('repository', 'session_manager.converter:convert')
        ;

        $controllers->post('/repositories/{repository}/workspaces', 'workspace.controller:createWorkspaceAction')
            ->convert('repository', 'session_manager.converter:convert')
        ;

        $controllers->get('/repositories/{repository}/workspaces/{workspace}', 'workspace.controller:getWorkspaceAction')
            ->convert('repository', 'session_manager.converter:convert')
            ->bind('workspace')
        ;

        $controllers->delete('/repositories/{repository}/workspaces/{workspace}', 'workspace.controller:deleteWorkspaceAction')
            ->convert('repository', 'session_manager.converter:convert')
        ;

        /**
         * Nodes
         */
        $controllers->post('/repositories/{repository}/workspaces/{workspace}/nodes{path}@properties', 'node.controller:addNodePropertyAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
        ;

        $controllers->delete('/repositories/{repository}/workspaces/{workspace}/nodes{path}@properties/{property}', 'node.controller:deleteNodePropertyAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
        ;

        $controllers->get('/repositories/{repository}/workspaces/{workspace}/nodes{path}', 'node.controller:getNodeAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
            ->bind('node')
        ;

        $controllers->delete('/repositories/{repository}/workspaces/{workspace}/nodes{path}', 'node.controller:deleteNodeAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
        ;

        $controllers->post('/repositories/{repository}/workspaces/{workspace}/nodes{path}', 'node.controller:addNodeAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
        ;

        $controllers->put('/repositories/{repository}/workspaces/{workspace}/nodes{path}', 'node.controller:updateNodeAction')
            ->assert('path', '.*')
            ->convert('repository', 'session_manager.converter:convert')
            ->convert('path', 'path.converter:convert')
        ;

        return $controllers;
    }

    public function boot(Application $app)
    {
        $app->mount($app['phpcr_api.mount_prefix'], $this->connect($app));
    }

    private function registerBeforeMiddleware(Application $app) {
        $app->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });
    }

    private function registerErrorHandler(Application $app)
    {
        $app->error(function (ExceptionInterface $e) use ($app) {
            return $app->json(
                $app['response.formater']->format($e->getMessage()),
                404 /* ignored */,
                array('X-Status-Code' => $e->getCode())
            );
        });
    }
}
