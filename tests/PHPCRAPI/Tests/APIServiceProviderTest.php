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

    	$repository = array(
    		'name'			=>	'repository_test',
    		'factoryName'	=>	'jackalope.jackrabbit'
    	);

    	$this->assertCount(1, $json['repositories']);
    	$this->assertContains($repository, $json['repositories']);
	}

	public function testGetRequestOnRepositoryUrlShoudlReturnRepositoryInfos()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);

    	$repository = array(
    		'name'			=>	'repository_test',
    		'factoryName'	=>	'jackalope.jackrabbit'
    	);

    	$this->assertCount(1, $json);
    	$this->assertContains($repository, $json);
	}

	public function testGetRequestOnRepositoryWorkspacesUrlShoudlReturnWorkspaceNames()
	{
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);
    	
    	$this->assertContains(array('name' => 'default'), $json['workspaces']);
    	$this->assertArrayHasKey('support', $json);
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
		$client = $this->createClient();
    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default/nodes');
    	$this->assertTrue($client->getResponse()->isOk());

    	$json = json_decode($client->getResponse()->getContent(), true);
    	$this->assertArrayHasKey('support', $json);
    	$this->assertArrayHasKey('node', $json);
    	$this->assertArrayHasKey('name', $json['node']);
    	$this->assertArrayHasKey('path', $json['node']);
    	$this->assertArrayHasKey('repository', $json['node']);
    	$this->assertArrayHasKey('workspace', $json['node']);
    	$this->assertArrayHasKey('children', $json['node']);
    	$this->assertArrayHasKey('hasChildren', $json['node']);
    	$this->assertArrayHasKey('nodeProperties', $json['node']);
	}
}