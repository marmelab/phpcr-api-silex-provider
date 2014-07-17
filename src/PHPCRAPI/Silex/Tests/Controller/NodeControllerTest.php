<?php

namespace PHPCRAPI\Silex\Tests\Controller;

use PHPCRAPI\Silex\Tests\AbstractWebTestCase;
use PHPCR\PropertyType;

class NodeControllerTest extends AbstractWebTestCase
{
    public function testItShouldReturnRootNodeInfosWhenAGetRequestIsSentToANodeCollectionUrl()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default/nodes');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $node = $json['message'];

        $this->assertArrayHasKey('name', $node);
        $this->assertArrayHasKey('path', $node);
        $this->assertArrayHasKey('workspace', $node);
        $this->assertArrayHasKey('children', $node);
        $this->assertArrayHasKey('hasChildren', $node);
        $this->assertArrayHasKey('properties', $node);
        $this->assertArrayNotHasKey('reducedTree', $node);
    }

    public function testItShouldReturnNodeInfosWithAReducedWhenAGetRequestIsSentToANodeUrlWithReducedTreeInTheQueryString()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default/nodes?reducedTree');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $node = $json['message'];

        $this->assertArrayHasKey('name', $node);
        $this->assertArrayHasKey('path', $node);
        $this->assertArrayHasKey('workspace', $node);
        $this->assertArrayHasKey('children', $node);
        $this->assertArrayHasKey('hasChildren', $node);
        $this->assertArrayHasKey('properties', $node);
        $this->assertArrayHasKey('reducedTree', $node);
    }

    public function testItShouldCreateANodeWhenAPostRequestIsSentToANodeUrl()
    {
        $client = $this->createClient();

        $relPath = uniqid('Nd');
        $crawler = $client->request(
            'POST',
            '/api/repositories/repository_test/workspaces/default/nodes/',
            [
                'relPath' =>  $relPath
            ]
        );
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s', $relPath), $client->getResponse()->getTargetUrl());

        $relPath2 = uniqid('Nd');
        $crawler = $client->request(
            'POST',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s', $relPath),
            [
                'relPath' =>  $relPath2
            ]
        );
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s/%s', $relPath, $relPath2), $client->getResponse()->getTargetUrl());

        return [
            '/' . $relPath,
            '/' . implode('/', [$relPath, $relPath2]),

        ];
    }

    /**
     * @depends testItShouldCreateANodeWhenAPostRequestIsSentToANodeUrl
     */
    public function testItShouldDeleteANodeWhenADeleteRequestIsSentToANodeUrl($nodePaths)
    {
        $client = $this->createClient();

        $crawler = $client->request(
            'DELETE',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes%s', $nodePaths[1])
        );

        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Node deleted', $json['message']);

        return $nodePaths[0];
    }

    /**
     * @depends testItShouldDeleteANodeWhenADeleteRequestIsSentToANodeUrl
     */
    public function testItShouldCreateAPropertyWhenAPostRequestIsSentToANodePropertyCollectionUrl($nodePath)
    {
        $client = $this->createClient();

        $crawler = $client->request(
            'GET',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes%s', $nodePath)
        );

        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $node = $json['message'];

        $propertyName = uniqid('P');
        $this->assertArrayNotHasKey($propertyName, $node['properties']);

        $crawler = $client->request(
            'POST',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes%s@properties', $nodePath),
            [
                'name' =>  $propertyName,
                'value'=>  'I am a new property',
                'type' =>   PropertyType::STRING
            ]
        );
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(sprintf('/api/repositories/repository_test/workspaces/default/nodes%s', $nodePath), $client->getResponse()->getTargetUrl());

        $crawler = $client->request(
            'GET',
            $client->getResponse()->getTargetUrl()
        );

        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $node = $json['message'];

        $this->assertArrayHasKey($propertyName, $node['properties']);
        $this->assertEquals('I am a new property', $node['properties'][$propertyName]['value']);
        $this->assertEquals(PropertyType::STRING, $node['properties'][$propertyName]['type']);

        return [
            'nodePath'     => $nodePath,
            'propertyName' => $propertyName
        ];
    }

    /**
     * @depends testItShouldCreateAPropertyWhenAPostRequestIsSentToANodePropertyCollectionUrl
     */
    public function testItShouldDeleteAPropertyWhenADeleteRequestIsSentToANodePropertyUrl($data)
    {
        $client = $this->createClient();

        $crawler = $client->request(
            'DELETE',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes%s@properties/%s', $data['nodePath'], $data['propertyName'])
        );

        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Property deleted', $json['message']);
    }
}
