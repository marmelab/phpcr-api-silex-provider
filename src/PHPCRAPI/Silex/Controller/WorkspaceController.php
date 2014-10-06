<?php

namespace PHPCRAPI\Silex\Controller;

use PHPCRAPI\Silex\AbstractController;
use PHPCRAPI\API\Manager\SessionManager;
use Symfony\Component\HttpFoundation\Request;

class WorkspaceController extends AbstractController
{
    public function getWorkspacesAction(SessionManager $repository, Request $request)
    {
        $data = [];

        foreach ($repository->getWorkspaceManager()->getAccessibleWorkspaceNames() as $workspaceName) {
            $data[$workspaceName] = [
                'name'      =>  $workspaceName
            ];
        }
        ksort($data);
        $data = array_values($data);

        return $this->buildResponse($data);
    }

    public function getWorkspaceAction(SessionManager $repository, $workspace)
    {
        $data = [
            'name'  =>  $workspace
        ];

        return $this->buildResponse($data);
    }

    public function createWorkspaceAction(SessionManager $repository, Request $request)
    {
        if (($name = $request->request->get('name')) === null) {
            $this->app->abort(400, 'Missing parameters');
        }

        $srcWorkspace = $request->request->get('srcWorkspace', null);

        $currentWorkspace = $repository->getWorkspaceManager();
        $currentWorkspace->createWorkspace($name, $srcWorkspace);

        return $this->app->redirect(
            $this->app->path('workspace', [
                'repository' => $repository->getName(),
                'workspace'  => $name
            ]),
            201
        );
    }

    public function deleteWorkspaceAction(SessionManager $repository, $workspace, Request $request)
    {
        $currentWorkspace = $repository->getWorkspaceManager();
        $currentWorkspace->deleteWorkspace($workspace);

        return $this->buildResponse('Workspace deleted', 0);
    }
}
