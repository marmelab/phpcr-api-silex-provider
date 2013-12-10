<?php

namespace PHPCRAPI\Silex;

use PHPCRAPI\API\Exception\ExceptionInterface;
use PHPCRAPI\API\Exception\ResourceNotFoundException;
use PHPCRAPI\API\Manager\RepositoryManager;
use PHPCRAPI\API\Manager\SessionManager;
use PHPCRAPI\API\RepositoryLoader;
use PHPCRAPI\PHPCR\Exception\CollectionUnknownKeyException;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class APIServiceProvider implements ServiceProviderInterface, ControllerProviderInterface
{
	public function register(Application $app){
        $app['phpcr_api.mount_prefix'] = isset($app['phpcr_api.mount_prefix']) ? $app['phpcr_api.mount_prefix'] : '/_api';
		$app['phpcr_api.repositories_config'] = isset($app['phpcr_api.repositories_config']) ? $app['phpcr_api.repositories_config'] : array();
		$app['phpcr_api.repository_loader'] = $app->share(function() use ($app){
			return new RepositoryLoader($app['phpcr_api.repositories_config']);
		});

        $app->error(function (ExceptionInterface $e) use ($app) {
            return $app->json(
                ['message' => $e->getMessage()],
                404 /* ignored */,
                array('X-Status-Code' => $e->getCode())
            );
        });
	}

	public function connect(Application $app){
		$sessionManagerConverter = function($repository, Request $request) use($app){
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

    public function getWorkspaceAction(SessionManager $repository, $workspace, Application $app)
    {
        $repositorySupport = $repository->getFactory()->getSupportedOperations();
        $workspaceSupport = array();

        foreach($repositorySupport as $support){
            if(substr($support, 0, strlen('workspace.')) == 'workspace.'){
                $workspaceSupport[] = $support;
            }
        }

        $data = array(
            'workspace' => array(
                'name'  =>  $workspace
            ),
            'support'    => $workspaceSupport
        );

        return $app->json($data);
    }

    public function getNodeAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $repositorySupport = $repository->getFactory()->getSupportedOperations();
        $nodeSupport = array();

        foreach($repositorySupport as $support){
            if(substr($support, 0, strlen('node.')) == 'node.'){
                $nodeSupport[] = $support;
            }
        }

        $data = array(
            'support'   =>  $nodeSupport,
            'node'      =>  array()
        );

        $currentNode = $repository->getNode($path);

        if($request->query->has('reducedTree')){
            $data['node']['reducedTree'] = $currentNode->getReducedTree();
        }

        $data['node']['name'] = $currentNode->getName();
        $data['node']['path'] = $currentNode->getPath();
        $data['node']['repository'] = $repository->getName();
        $data['node']['workspace'] = $workspace;
        $data['node']['children'] = array();
        foreach ($currentNode->getChildren() as $node) {
            $data['node']['children'][] = array(
                'name'          =>  $node->getName(),
                'path'          =>  $node->getPath(),
                'children'      =>  array(),
                'hasChildren'   =>  (count($node->getChildren()) > 0)
            );
        }

        $data['node']['hasChildren'] = (count($data['node']['children']) > 0);

        if ($currentNode->getPath() != $repository->getRootNode()->getPath()) {
            $data['node']['parent'] = $currentNode->getParent()->getName();
        }
        $data['node']['nodeProperties'] = $currentNode->getPropertiesToArray();

        return $app->json($data);
    }

    public function createWorkspaceAction(SessionManager $repository, Application $app, Request $request)
    {
        $name = $request->request->get('name', null);
        $srcWorkspace = $request->request->get('srcWorkspace', null);

        $currentWorkspace = $repository->getWorkspaceManager();
        $currentWorkspace->createWorkspace($name, $srcWorkspace);
       
        return $app->json(sprintf('Workspace %s created', $name));
    }

    public function deleteWorkspaceAction(Session $repository, $workspace, Application $app, Request $request)
    {
        $currentWorkspace = $repository->getWorkspace();
        $currentWorkspace->deleteWorkspace($workspace);
       
        return $app->json(sprintf('Workspace %s deleted', $workspace));
    }

    public function deleteNodePropertyAction(SessionManager $repository, $workspace, $path, $property, Application $app, Request $request)
    {
        $currentNode = $repository->getNode($path);
        $currentNode->removeProperty($property);
        return $app->json(sprintf('Property %s deleted', $property));
    }

    public function addNodePropertyAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        $currentNode = $repository->getNode($path);
       
        $name = $request->request->get('name',null);
        $value = $request->request->get('value',null);
        $type = $request->request->get('type',null);
        
        $currentNode->setProperty($name, $value, $type);
        return $app->json(sprintf('Property %s added', $name));
    }
}
