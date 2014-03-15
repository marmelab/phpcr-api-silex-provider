<?php

namespace PHPCRAPI\Silex;

use PHPCRAPI\API\Exception\ExceptionInterface;
use PHPCRAPI\API\Exception\NotSupportedOperationException;
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
    public function register(Application $app)
    {
         $app['phpcr_api.mount_prefix'] = isset($app['phpcr_api.mount_prefix']) ? $app['phpcr_api.mount_prefix'] : '/_api';

        $app['phpcr_api.repositories_config'] = isset($app['phpcr_api.repositories_config']) ? $app['phpcr_api.repositories_config'] : array();

        $app->error(function (ExceptionInterface $e) use ($app) {
            return $app->json(
                ['message' => $e->getMessage()],
                404 /* ignored */,
                array('X-Status-Code' => $e->getCode())
            );
        });
    }

    public function connect(Application $app)
    {
        $sessionManagerConverter = function ($repository, Request $request) use ($app) {
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
            if (mb_substr($path,0,1) != '/') {
                return '/'.$path;
            } else {
                return $path;
            }
        };

        $controllers = $app['controllers_factory'];

         // Get all repositories
        $controllers->get('/repositories', array($this, 'getRepositoriesAction'))
            ->bind('phpcr_api.get_repositories');

        // Get a repository
        $controllers->get('/repositories/{repository}', array($this, 'getRepositoryAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.get_repository');

        // Get all workspace in a repository
        $controllers->get('/repositories/{repository}/workspaces', array($this, 'getWorkspacesAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.get_workspaces');

        // Add a workspace in a repository
        $controllers->post('/repositories/{repository}/workspaces', array($this, 'createWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter);

        // Get a workspace
        $controllers->get('/repositories/{repository}/workspaces/{workspace}', array($this, 'getWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.get_workspace');

        // Delete a workspace from a repository
        $controllers->delete('/repositories/{repository}/workspaces/{workspace}', array($this, 'deleteWorkspaceAction'))
            ->convert('repository', $sessionManagerConverter)
            ->bind('phpcr_api.delete_workspace');

        // Add a property in a node
        $controllers->post('/repositories/{repository}/workspaces/{workspace}/nodes{path}@properties', array($this, 'addNodePropertyAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.add_property');

        // Delete a property from a node
        $controllers->delete('/repositories/{repository}/workspaces/{workspace}/nodes{path}@properties/{property}', array($this, 'deleteNodePropertyAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.delete_property');

         // Get a node in a workspace
        $controllers->get('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'getNodeAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.get_node');

        // delete a node in a workspace
        $controllers->delete('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'deleteNodeAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.delete_node');

        // Add a node to a node
        $controllers->post('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'addNodeAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.add_node');

        // Update a node in a workspace
        $controllers->put('/repositories/{repository}/workspaces/{workspace}/nodes{path}', array($this, 'updateNodeAction'))
            ->assert('path', '.*')
            ->convert('repository', $sessionManagerConverter)
            ->convert('path', $pathConverter)
            ->bind('phpcr_api.update_node');

        return $controllers;
    }

    public function boot(Application $app)
    {
        $app->mount($app['phpcr_api.mount_prefix'], $this->connect($app));

        $app['phpcr_api.repository_loader'] = $app->share(function () use ($app) {
            return new RepositoryLoader($app['phpcr_api.repositories_config']);
        });

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
        $data = array();

        foreach ($repositories as $repository) {
            $data[] = array(
                'name'          =>  $repository->getName(),
                'factoryName'   =>  $repository->getFactory()->getName(),
                'support'       =>  $repository->getFactory()->getSupportedOperations()

            );
        }

        return $this->jsonCache($app, $data, 60);
    }

    public function getRepositoryAction(SessionManager $repository, Application $app)
    {
        $data = array(
            'name'          =>  $repository->getName(),
            'factoryName'  =>  $repository->getFactory()->getName(),
            'support'       =>  $repository->getFactory()->getSupportedOperations()
        );

        return $this->jsonCache($app, $data, 60);
    }

    public function getWorkspacesAction(SessionManager $repository, Application $app, Request $request)
    {
        $repositorySupport = $repository->getFactory()->getSupportedOperations();

        $data = array();

        foreach ($repository->getWorkspaceManager()->getAccessibleWorkspaceNames() as $workspaceName) {
            $data[$workspaceName] = array(
                'name'      =>  $workspaceName
            );
        }
        ksort($data);
        $data = array_values($data);

        return $this->jsonCache($app, $data, 60);
    }

    public function getWorkspaceAction(SessionManager $repository, $workspace, Application $app)
    {
        $data = array(
            'name'  =>  $workspace
        );

        return $this->jsonCache($app, $data, 60);
    }

    public function getNodeAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $data = array();

        $currentNode = $repository->getNode($path);

        if ($request->query->has('reducedTree')) {
            $data['reducedTree'] = $currentNode->getReducedTree();
        }

        $data['name'] = $currentNode->getName();
        $data['path'] = $currentNode->getPath();
        $data['repository'] = $repository->getName();
        $data['workspace'] = $workspace;
        $data['children'] = array();
        foreach ($currentNode->getChildren() as $node) {
            $data['children'][] = array(
                'name'          =>  $node->getName(),
                'path'          =>  $node->getPath(),
                'children'      =>  array(),
                'hasChildren'   =>  (count($node->getChildren()) > 0)
            );
        }

        $data['hasChildren'] = (count($data['children']) > 0);

        if ($currentNode->getPath() != $repository->getRootNode()->getPath()) {
            $data['parent'] = $currentNode->getParent()->getName();
        }
        $data['properties'] = $currentNode->getPropertiesToArray();

        return $this->jsonCache($app, $data, 60);
    }

    public function createWorkspaceAction(SessionManager $repository, Application $app, Request $request)
    {
        $name = $request->request->get('name', null);
        $srcWorkspace = $request->request->get('srcWorkspace', null);

        $currentWorkspace = $repository->getWorkspaceManager();
        $currentWorkspace->createWorkspace($name, $srcWorkspace);

        return $app->json(sprintf('Workspace %s created', $name));
    }

    public function deleteWorkspaceAction(SessionManager $repository, $workspace, Application $app, Request $request)
    {
        $currentWorkspace = $repository->getWorkspaceManager();
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

    public function updateNodeAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);

        $method = $request->request->get('method',null);
        $output = sprintf('Node %s updated', $path);

        switch($method){
            default:
                throw new NotSupportedOperationException('Unknown edit method');
                break;

            case 'rename':
                $name = $request->request->get('newName', null);
                $currentNode->rename($name);
                $output = sprintf('Node %s renamed', $path);
                break;

            case 'move':
                $destAbsPath = $request->request->get('destAbsPath', null);
                $repository->move($path, $destAbsPath);
                $output = sprintf('Node %s moved', $path);
                break;
        }

        return $app->json($output);
    }

    public function deleteNodeAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);
        $currentNode->remove();

        return $app->json(sprintf('Node %s removed', $path));
    }

    public function addNodeAction(SessionManager $repository, $workspace, $path, Application $app, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);

        $relPath = $request->request->get('relPath',null);
        $primaryNodeTypeName = $request->request->get('primaryNodeTypeName', null);

        $currentNode->addNode($relPath, $primaryNodeTypeName);

        return $app->json(sprintf('Node %s added to %s', $relPath, $path));
    }

    private function jsonCache(Application $app, $json, $max = 60)
    {
        return $app->json($json, 200, array('Cache-Control' => sprintf('s-maxage=%s, public, must-revalidate', $max)));
    }
}