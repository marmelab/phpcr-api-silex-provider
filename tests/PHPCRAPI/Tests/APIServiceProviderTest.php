<?php

use Silex\Application;
use Silex\Application\UrlGeneratorTrait;
use Silex\WebTestCase;

class APIServiceProviderTest extends WebTestCase
{
    public function createApplication()
	{
		return require_once __DIR__.'/../../app.php';
	}

	public function testGetRequestOnRootUrlShoudlReturnRepositoryNames()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('support', $json[0]);
        $this->assertArrayHasKey('name', $json[0]);
        $this->assertArrayHasKey('factoryName', $json[0]);
	}

	public function testGetRequestOnRepositoryUrlShoudlReturnRepositoryInfos()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);

    	$this->assertArrayHasKey('support', $json);
        $this->assertArrayHasKey('name', $json);
        $this->assertArrayHasKey('factoryName', $json);
	}

	public function testGetRequestOnRepositoryWorkspacesUrlShoudlReturnWorkspaceNames()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);

    	$this->assertContains(array('name' => 'default'), $json);
	}

	public function testGetRequestOnRepositoryWorkspaceUrlShoudlReturnWorkspaceInfos()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);

    	$this->assertEquals('default', $json['workspace']['name']);
    	$this->assertArrayHasKey('support', $json);
	}

	public function testGetRequestOnRepositoryWorkspaceNodeUrlShoudlReturnNodeInfos()
	{
		$this->markTestSkipped();
	}

    public function testGetRequestOnRepositoryWorkspaceUnknownNodeUrlShoudReturn404()
    {
        $client = $this->createClient();

        $name = uniqid('n');var_dump($name);
        $crawler = $client->request('GET', sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s',$name));
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testPostRequestOnRepositoryWorkspaceNodeUrlShoudlCreateAChildNode()
    {
        $this->markTestSkipped();
    }

    public function testPostRequestOnRepositoryWorkspacesShoudlCreateAWorkspace()
    {
        $client = $this->createClient();

        $name = uniqid('Wk');
        $crawler = $client->request(
            'POST',
            '/api/repositories/repository_test/workspaces',
            array('name' =>  $name)
        );
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($json, sprintf('Workspace %s created', $name));
    }
}