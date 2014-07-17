<?php

namespace PHPCRAPI\Silex\SerializationHandler;

use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\JsonSerializationVisitor;
use PHPCRAPI\Silex\SerializationContext\NodeContext;
use PHPCRAPI\API\Manager\NodeManager;
use PHPCR\PropertyType;

class NodeHandler implements SubscribingHandlerInterface
{
    public static function getSubscribingMethods()
    {
        return array(
            array(
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'format'    => 'json',
                'type'      => 'PHPCRAPI\API\Manager\NodeManager',
                'method'    => 'serializeNodeToJson',
            )
        );
    }

    public function serializeNodeToJson(JsonSerializationVisitor $visitor, NodeManager $node, array $type, NodeContext $context)
    {
        $serialized = [];
        $repository = $context->getRepository();
        $workspaceName  = $context->getWorkspaceName();

        $serialized['name']         = $node->getName();
        $serialized['path']         = $node->getPath();
        $serialized['workspace']    = $workspaceName;
        $serialized['children']     = [];

        foreach ($node->getChildren() as $childNode) {
            $serialized['children'][] = [
                'name'          =>  $childNode->getName(),
                'path'          =>  $childNode->getPath(),
                'children'      =>  [],
                'hasChildren'   =>  $childNode->hasChildren()
            ];
        }

        $serialized['hasChildren'] = $node->hasChildren();

        if ($node->getPath() != $repository->getRootNode()->getPath()) {
            $serialized['parent'] = $node->getParent()->getName();
        }

        if ($context->isReducedTreeEnabled()) {
            $serialized['reducedTree'] = $node->getReducedTree();
        }

        $serialized['properties'] = $node->getPropertiesAsArray();

        foreach ($serialized['properties'] as $name=>$property) {
            if ($property['type'] === PropertyType::WEAKREFERENCE) {
                if (is_array($property['value'])) {
                    foreach ($property['value'] as $subkey=>$subvalue) {
                        $serialized['properties'][$name]['value'][$subkey] = $repository->getNodeByIdentifier($subkey)->getPath();
                    }
                } else {
                    $serialized['properties'][$name]['value'] = $repository->getNodeByIdentifier($property['value'])->getPath();
                }
            }
        }

        return $serialized;
    }
}
