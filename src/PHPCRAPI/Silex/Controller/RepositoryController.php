<?php

namespace PHPCRAPI\Silex\Controller;

use PHPCRAPI\Silex\AbstractController;
use PHPCRAPI\API\Manager\SessionManager;

class RepositoryController extends AbstractController
{
    public function getRepositoriesAction()
    {
        $repositories = $this->app['phpcr_api.repository_loader']->getRepositories()->getAll();
        ksort($repositories);
        return $this->buildResponse(array_values($repositories));
    }

    public function getRepositoryAction(SessionManager $repository)
    {
        return $this->buildResponse($repository);
    }
}
