<?php

namespace PHPCRAPI\Provider;

use PHPCRAPI\API\Exception\ExceptionInterface;
use PHPCRAPI\API\Exception\ResourceNotFoundException;
use PHPCRAPI\PHPCR\Exception\CollectionUnknownKeyException;
use PHPCRAPI\API\RepositoryLoader;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class PHPCRAPISilexServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
	public function register(Application $app){
		$app['phpcr_api.repositories_config'] = isset($app['phpcr_api.repositories_config']) ? $app['phpcr_api.repositories_config'] : array();
		$app['phpcr_api.repository_loader'] = $app->share(function() use ($app){
			return new RepositoryLoader($app['phpcr_api.repositories_config']);
		});
	}

	public function connect(Application $app){
		$sessionManagerConverter = function($repository, Request $request){
			if (is_null($repository)) {
            	return null;
        	}
       		$workspace = $request->attributes->has('workspace') ? $request->attributes->get('workspace') : null;
       
	        try {
	        	$repositoryManager = new RepositoryManager(
	        		$app['phpcr_api.repository_loader']->getRepositories()->get($repository)
	        	);

	            $sessionManager = $repositoryManager->getSessionManager($workspace);

	            if (!is_null($workspace)) {
	                $path = $request->attributes->get('path');
	                if (substr($path, 0, 1) != '/') {
	                    $path = '/'.$path;
	                }
	            }

	            return $sessionManager;
	        } catch (CollectionUnknownKeyException $e) {
	            throw new ResourceNotFoundException('The repository is unknown');
	        }
	    };

	    $pathConverter = function ($path) {
            if(mb_substr($path,0,1) != '/'){
                return '/'.$path;
            }else{
                return $path;
            }
        };

        $controllers = $app['controllers_factory'];

		 // Get all repositories
        $controllers->get('/repositories', array($this, 'getRepositoriesAction'))
            ->bind('phpcr_api.repositories');

        // Get a repository
        $controllers->get('/repositories/{repository}', array($this, 'getRepositoryAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.repository');

        // Get all workspace in a repository
        $controllers->get('/repositories/{repository}/workspaces', array($this, 'getWorkspacesAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.workspaces');

        // Get a workspace
        $controllers->get('/repositories/{repository}/workspaces/{workspace}', array($this, 'getWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.workspace');

        // Get a node in a workspace
        $controllers->get('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'getNodeAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.node');

        // Add a workspace in a repository
        $controllers->post('/repositories/{repository}/workspaces', array($this, 'createWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter);

        // Delete a workspace from a repository
        $controllers->delete('/repositories/{repository}/workspaces/{workspace}', array($this, 'deleteWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter);

         // Add a property in a node
        $controllers->post('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'addNodePropertyAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter);

        // Delete a property from a node
        $controllers->delete('/repositories/{repository}/workspaces/{workspace}/nodes{path}@{property}', array($this, 'deleteNodePropertyAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter);

        return $controllers;
	}

	public function boot(Application $app){
		$app->mount($app['phpcr_api.mount_prefix'], $this->connect($app));

		$app->before(function (Request $request) {
            if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                $data = json_decode($request->getContent(), true);
                $request->request->replace(is_array($data) ? $data : array());
            }
        });

        $app->error(function (ExceptionInterface $e) use ($app) {
            return $app->json(
                ['message' => $e->getMessage()],
                404 /* ignored */,
                array('X-Status-Code' => $e->getCode())
            );
        });
	}

	public function getRepositoriesAction(Application $app)
    {
        $repositories = $app['phpcr_api.repository_loader']->getRepositories()->getAll();
        $data = array(
            'repositories'  =>  array()
        );

        foreach ($repositories as $repository) {
            $data['repositories'][] = array(
                'name'          =>  $repository->getName(),
                'factoryName'  =>  $repository->getFactory()->getName()
            );
        }

        return $app->json($data);
    }

    public function getRepositoryAction(SessionManager $repository, Application $app)
    {
        $data = array(
            'repository'    =>  array(
                'name'          =>  $repository->getName(),
                'factoryName'  =>  $repository->getFactory()->getName()
            )
        );
        return $app->json($data);
    }

    public function getWorkspacesAction(SessionManager $repository, Application $app)
    {
        $repositorySupport = $repository->getFactory()->getSupportedOperations();
        $workspaceSupport = array();

        foreach($repositorySupport as $support){
            if(substr($support, 0, strlen('workspace.')) == 'workspace.'){
                $workspaceSupport[] = $support;
            }
        }

        $data = array(
            'workspaces' => array(),
            'support'    => $workspaceSupport
        );

        foreach ($repository->getWorkspaceManager()->getAccessibleWorkspaceNames() as $workspaceName) {
            $data['workspaces'][$workspaceName] = array(
                'name'      =>  $workspaceName
            );
        }
        ksort($data['workspaces']);
        $data['workspaces'] = array_values($data['workspaces']);
        return $app->json($data);
    }
}
