<?php

namespace PHPCRAPI\Silex\Converter;

use PHPCRAPI\Silex\AbstractConverter;
use PHPCRAPI\API\Exception\ResourceNotFoundException;
use PHPCRAPI\API\Manager\RepositoryManager;
use PHPCRAPI\PHPCR\Exception\CollectionUnknownKeyException;
use Symfony\Component\HttpFoundation\Request;

class SessionManagerConverter extends AbstractConverter
{
    public function convert($repository, Request $request)
    {
        if (is_null($repository)) {
            return null;
        }

        $workspace = $request->attributes->has('workspace') ? $request->attributes->get('workspace') : null;

        try {
            $repositoryManager = new RepositoryManager(
                $this->app['phpcr_api.repository_loader']->getRepositories()->get($repository)
            );

            $sessionManager = $repositoryManager->getSessionManager($workspace);

            return $sessionManager;
        } catch (CollectionUnknownKeyException $e) {
            throw new ResourceNotFoundException('The repository is unknown');
        }
    }
}
