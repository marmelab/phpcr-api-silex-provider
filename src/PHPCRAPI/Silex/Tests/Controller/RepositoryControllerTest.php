<?php

namespace PHPCRAPI\Silex\Tests\Controller;

use PHPCRAPI\Silex\Tests\AbstractWebTestCase;

class RepositoryControllerTest extends AbstractWebTestCase
{
    public function testItShouldReturnRepositoriesNameWhenAGetRequestIsSentToRootUrl()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $repositories = $json['message'];

        $this->assertArrayHasKey('supportedOperations', $repositories[0]);
        $this->assertArrayHasKey('name', $repositories[0]);
        $this->assertArrayHasKey('factoryName', $repositories[0]);
    }

    public function testItShouldReturnRepositoryInfosWhenAGetRequestIsSentToARepositoryUrl()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories/repository_test');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $repository = $json['message'];

        $this->assertArrayHasKey('supportedOperations', $repository);
        $this->assertArrayHasKey('name', $repository);
        $this->assertArrayHasKey('factoryName', $repository);
    }
}
