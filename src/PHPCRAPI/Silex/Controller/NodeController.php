<?php

namespace PHPCRAPI\Silex\Controller;

use PHPCRAPI\Silex\AbstractController;
use PHPCRAPI\Silex\SerializationContext\NodeContext;
use PHPCRAPI\API\Manager\SessionManager;
use PHPCRAPI\API\Exception\ResourceNotFoundException;
use PHPCRAPI\API\Exception\NotSupportedOperationException;
use Symfony\Component\HttpFoundation\Request;

class NodeController extends AbstractController
{
    public function getNodeAction(SessionManager $repository, $workspace, $path, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $context = new NodeContext($repository, $workspace);

        if ($request->query->has('reducedTree')) {
            $context->enableReducedTree();
        }

        return $this->buildResponseWithContext(
            $repository->getNode($path),
            $context
        );
    }

    public function deleteNodePropertyAction(SessionManager $repository, $workspace, $path, $property, Request $request)
    {
        $currentNode = $repository->getNode($path);
        $currentNode->removeProperty($property);

        return $this->buildResponse('Property deleted', 0);
    }

    public function addNodePropertyAction(SessionManager $repository, $workspace, $path, Request $request)
    {
        $currentNode = $repository->getNode($path);
        $name  = $request->request->get('name');
        $value = $request->request->get('value');

        if ($name === null || $value === null) {
            $this->app->abort(400, 'Missing parameters');
        }

        $type  = $request->request->get('type', null);
        $properties = $currentNode->getPropertiesAsArray();

        if (array_key_exists($name, $properties) && is_array($properties[$name]['value'])) {
            $json = json_decode($value, true);
            if (!is_null($json) && $json !== false) {
                $value = $json;
            }
        }

        $currentNode->setProperty($name, $value, $type);

        return $this->app->redirect(
            $this->app->path('node', [
                'repository' => $repository->getName(),
                'workspace'  => $workspace,
                'path'       => $currentNode->getPath()
            ]),
            201
        );
    }

    public function updateNodeAction(SessionManager $repository, $workspace, $path, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);

        if (($method = $request->request->get('method')) === null) {
            $this->app->abort(400, 'Missing parameters');
        }

        switch($method){
            default:
                throw new NotSupportedOperationException('Unknown method');
                break;

            case 'rename':
                if (!$newName = $request->request->get('newName')) {
                    $this->app->abort(400, 'Missing parameters');
                }
                $currentNode->rename($newName);
                break;

            case 'move':
                if (!$destAbsPath = $request->request->get('destAbsPath')) {
                    $this->app->abort(400, 'Missing parameters');
                }
                $repository->move($path, $destAbsPath);
                break;
        }

        return $this->app->redirect(
            $this->app->path('node', [
                'repository' => $repository->getName(),
                'workspace'  => $workspace,
                'path'       => $currentNode->getPath()
            ]),
            201
        );
    }

    public function deleteNodeAction(SessionManager $repository, $workspace, $path, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);
        $currentNode->remove();

        return $this->buildResponse('Node deleted', 0);
    }

    public function addNodeAction(SessionManager $repository, $workspace, $path, Request $request)
    {
        if (!$repository->nodeExists($path)) {
            throw new ResourceNotFoundException('Unknown node');
        }

        $currentNode = $repository->getNode($path);

        if (($relPath = $request->request->get('relPath')) === null) {
            $this->app->abort(400, 'Missing parameters');
        }

        $primaryNodeTypeName = $request->request->get('primaryNodeTypeName', null);

        $currentNode->addNode($relPath, $primaryNodeTypeName);

        return $this->app->redirect(
            $this->app->path('node', [
                'repository' => $repository->getName(),
                'workspace'  => $workspace,
                'path'       => $currentNode->getPath() === '/' ? $currentNode->getPath() . $relPath : $currentNode->getPath() . '/' . $relPath
            ]),
            201
        );
    }
}
