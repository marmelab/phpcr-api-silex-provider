<?php

namespace PHPCRAPI\Silex\Tests\SerializationHandler;

use PHPCRAPI\Silex\SerializationHandler\NodeHandler;

class NodeHandlerTest extends \PHPUnit_Framework_TestCase
{
    use \Xpmock\TestCaseTrait;

    public function testItShouldCorrectlySerializeANode()
    {
        $jsonSerializationVisitor = $this->mock('\JMS\Serializer\JsonSerializationVisitor', null);

        $handler = new NodeHandler();

        $node = $this->mock('\PHPCRAPI\API\Manager\NodeManager')
            ->getName('root')
            ->getPath('/')
            ->getChildren([])
            ->hasChildren(false)
            ->getPropertiesAsArray([])
            ->new()
        ;

        $repository = $this->mock('\PHPCRAPI\API\Manager\SessionManager')
            ->getName('repository_test')
            ->getRootNode($node, $this->exactly(2))
            ->new()
        ;

        $context = $this->mock('\PHPCRAPI\Silex\SerializationContext\NodeContext')
            ->getRepository($repository)
            ->getWorkspaceName('default')
            ->new()
        ;

        $serialized = $handler->serializeNodeToJson($jsonSerializationVisitor, $node, [], $context);

        $this->assertEquals('root', $serialized['name']);
        $this->assertEquals('/', $serialized['path']);
        $this->assertEquals('default', $serialized['workspace']);
        $this->assertFalse($serialized['hasChildren']);
        $this->assertCount(0, $serialized['properties']);
        $this->assertCount(0, $serialized['children']);
        $this->assertArrayNotHasKey('parent', $serialized);
        $this->assertArrayNotHasKey('reducedTree', $serialized);

        $node = $this->mock('\PHPCRAPI\API\Manager\NodeManager')
            ->getName('root')
            ->getPath('/')
            ->getChildren([])
            ->hasChildren(false)
            ->getPropertiesAsArray([])
            ->getReducedTree(null, $this->once())
            ->new()
        ;

        $context->enableReducedTree();

        // Now it should call getReducedTree on the node
        $serialized = $handler->serializeNodeToJson($jsonSerializationVisitor, $node, [], $context);
        $this->assertArrayHasKey('reducedTree', $serialized);
    }
}
