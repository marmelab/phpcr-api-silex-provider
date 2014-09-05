<?php

namespace PHPCRAPI\Silex\Tests\SerializationHandler;

use PHPCRAPI\Silex\SerializationHandler\RepositoryHandler;

class RepositoryHandlerTest extends \PHPUnit_Framework_TestCase
{
    use \Xpmock\TestCaseTrait;

    public function testItShouldCorrectlySerializeARepository()
    {
        $jsonSerializationVisitor = $this->mock('\JMS\Serializer\JsonSerializationVisitor', null);
        $context = $this->mock('\JMS\Serializer\SerializationContext', null);

        $handler = new RepositoryHandler();

        $factory = $this->mock('\PHPCRAPI\PHPCR\Factory')
            ->getName('test')
            ->getSupportedOperations(['read', 'write'])
            ->new()
        ;

        $repository = $this->mock('\PHPCRAPI\API\Manager\SessionManager')
            ->getName('repository_test')
            ->getFactory($factory)
            ->new()
        ;

        $serialized = $handler->serializeRepositoryToJson($jsonSerializationVisitor, $repository, [], $context);

        $this->assertEquals('repository_test', $serialized['name']);
        $this->assertEquals('test', $serialized['factoryName']);
        $this->assertEquals(['read', 'write'], $serialized['supportedOperations']);
    }
}
