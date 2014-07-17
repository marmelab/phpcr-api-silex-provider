<?php

namespace PHPCRAPI\Silex\SerializationContext;

use JMS\Serializer\SerializationContext;
use PHPCRAPI\API\Manager\SessionManager;

class NodeContext extends SerializationContext
{
    private $repository;

    private $workspaceName;

    private $reducedTree = false;

    public function __construct(SessionManager $repository, $workspaceName)
    {
        $this->repository = $repository;
        $this->workspaceName = $workspaceName;
    }

    public function enableReducedTree()
    {
        $this->reducedTree = true;
    }

    public function isReducedTreeEnabled()
    {
        return $this->reducedTree;
    }

    public function getRepository()
    {
        return $this->repository;
    }

    public function getWorkspaceName()
    {
        return $this->workspaceName;
    }
}
