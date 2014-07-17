<?php

namespace PHPCRAPI\Silex\Tests\Controller;

use PHPCRAPI\Silex\Tests\AbstractWebTestCase;

class WorkspaceControllerTest extends AbstractWebTestCase
{
    public function testItShouldReturnWorkspacesNameWhenAGetRequestIsSentToARepositoryUrl()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories/repository_test/workspaces');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $workspaces = $json['message'];

        $this->assertContains(['name' => 'default'], $workspaces);
    }

    public function testItShouldReturnWorkspaceInfosWhenAGetRequestIsSentToAWorkspaceUrl()
    {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default');
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $workspace = $json['message'];

        $this->assertEquals('default', $workspace['name']);
    }

    public function testItShouldCreateAWorkspaceWhenAPostRequestIsSentToAWorkspaceCollectionUrl()
    {
        $client = $this->createClient();

        $workspaceName = uniqid('Wk');
        $crawler = $client->request(
            'POST',
            '/api/repositories/repository_test/workspaces',
            [
                'name' =>  $workspaceName
            ]
        );
        $this->assertEquals(201, $client->getResponse()->getStatusCode());
        $this->assertEquals(sprintf('/api/repositories/repository_test/workspaces/%s', $workspaceName), $client->getResponse()->getTargetUrl());

        return $workspaceName;
    }

    /**
     * @depends testItShouldCreateAWorkspaceWhenAPostRequestIsSentToAWorkspaceCollectionUrl
     */
    public function testItShouldDeleteAWorkspaceWhenADeleteRequestIsSentToAWorkspaceUrl($workspaceName)
    {
        $client = $this->createClient();

        $crawler = $client->request(
            'DELETE',
            sprintf('/api/repositories/repository_test/workspaces/%s', $workspaceName)
        );

        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals('Workspace deleted', $json['message']);
    }
}
