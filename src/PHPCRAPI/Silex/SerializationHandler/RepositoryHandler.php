<?php

namespace PHPCRAPI\Silex\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Context;

class RepositoryHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'PHPCRAPI\PHPCR\Repository',
                'method'    => 'serializeRepositoryToJson',
            ),
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'PHPCRAPI\API\Manager\SessionManager',
                'method'    => 'serializeRepositoryToJson',
            )
        );
    }

    public function serializeRepositoryToJson(JsonSerializationVisitor $visitor, $repository, array $type, Context $context)
    {
        return [
            'name'                  =>  $repository->getName(),
            'factoryName'           =>  $repository->getFactory()->getName(),
            'supportedOperations'   =>  $repository->getFactory()->getSupportedOperations()
        ];
    }
}
