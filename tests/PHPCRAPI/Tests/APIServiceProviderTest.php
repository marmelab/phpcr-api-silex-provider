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

	// public function testGetRequestOnRootUrlShoudlReturnRepositoryNames()
	// {
	// 	$client = $this->createClient();
 //    	$crawler = $client->request('GET', '/api/repositories');
 //    	$this->assertTrue($client->getResponse()->isOk());

 //    	$json = json_decode($client->getResponse()->getContent(), true);

 //    	$repository = array(
 //    		'name'			=>	'repository_test',
 //    		'factoryName'	=>	'jackalope.jackrabbit'
 //    	);

 //    	$this->assertCount(1, $json['repositories']);
 //    	$this->assertContains($repository, $json['repositories']);
	// }

	// public function testGetRequestOnRepositoryUrlShoudlReturnRepositoryInfos()
	// {
	// 	$client = $this->createClient();
 //    	$crawler = $client->request('GET', '/api/repositories/repository_test');
 //    	$this->assertTrue($client->getResponse()->isOk());

 //    	$json = json_decode($client->getResponse()->getContent(), true);

 //    	$repository = array(
 //    		'name'			=>	'repository_test',
 //    		'factoryName'	=>	'jackalope.jackrabbit'
 //    	);

 //    	$this->assertCount(1, $json);
 //    	$this->assertContains($repository, $json);
	// }

	// public function testGetRequestOnRepositoryWorkspacesUrlShoudlReturnWorkspaceNames()
	// {
	// 	$client = $this->createClient();
 //    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces');
 //    	$this->assertTrue($client->getResponse()->isOk());

 //    	$json = json_decode($client->getResponse()->getContent(), true);

 //    	$this->assertContains(array('name' => 'default'), $json['workspaces']);
 //    	$this->assertArrayHasKey('support', $json);
	// }

	// public function testGetRequestOnRepositoryWorkspaceUrlShoudlReturnWorkspaceInfos()
	// {
	// 	$client = $this->createClient();
 //    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default');
 //    	$this->assertTrue($client->getResponse()->isOk());

 //    	$json = json_decode($client->getResponse()->getContent(), true);

 //    	$this->assertEquals('default', $json['workspace']['name']);
 //    	$this->assertArrayHasKey('support', $json);
	// }

	// public function testGetRequestOnRepositoryWorkspaceNodeUrlShoudlReturnNodeInfos()
	// {
	// 	$client = $this->createClient();

 //    	$crawler = $client->request('GET', '/api/repositories/repository_test/workspaces/default/nodes');
 //    	$this->assertTrue($client->getResponse()->isOk());

 //    	$json = json_decode($client->getResponse()->getContent(), true);

 //    	$this->assertArrayHasKey('support', $json);
 //    	$this->assertArrayHasKey('node', $json);
 //    	$this->assertArrayHasKey('name', $json['node']);
 //    	$this->assertArrayHasKey('path', $json['node']);
 //    	$this->assertArrayHasKey('repository', $json['node']);
 //    	$this->assertArrayHasKey('workspace', $json['node']);
 //    	$this->assertArrayHasKey('children', $json['node']);
 //    	$this->assertArrayHasKey('hasChildren', $json['node']);
 //    	$this->assertArrayHasKey('nodeProperties', $json['node']);
	// }

 //    public function testGetRequestOnRepositoryWorkspaceUnknownNodeUrlShoudReturn404()
 //    {
 //        $client = $this->createClient();

 //        $name = uniqid('n');var_dump($name);
 //        $crawler = $client->request('GET', sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s',$name));
 //        $this->assertEquals(404, $client->getResponse()->getStatusCode());
 //    }

    // public function testPostRequestOnRepositoryWorkspaceNodeUrlShoudlCreateAChildNode()
    // {
    //     $client = $this->createClient();

    //     $relPath = 'myTestNode';
    //     $crawler = $client->request(
    //         'POST',
    //         '/api/repositories/repository_test/workspaces/default/nodes',
    //         array('relPath' =>  $relPath)
    //     );
    //     $this->assertTrue($client->getResponse()->isOk());

    //     $json = json_decode($client->getResponse()->getContent(), true);
    //     $this->assertEquals($json, sprintf('Node %s added to %s', $relPath, '/'));
    // }

    public function testPutRequestOnRepositoryWorkspaceNodeUrlShoudlUpdateAChildNode()
    {
        $client = $this->createClient();
        $relPath = 'myTestNode';
        $newName = uniqid('n');
        $crawler = $client->request(
            'PUT',
            sprintf('/api/repositories/repository_test/workspaces/default/nodes/%s',$relPath),
            array(
                'newName' =>  $newName,
                'method'  =>  'rename'
            )
        );
        $json = json_decode($client->getResponse()->getContent(), true);
        var_dump($json);
        $this->assertTrue($client->getResponse()->isOk());

        $json = json_decode($client->getResponse()->getContent(), true);
        $this->assertEquals($json, sprintf('Node %s renamed', $newName));
    }

    // public function testPostRequestOnRepositoryWorkspacesShoudlCreateAWorkspace()
    // {
    //     $client = $this->createClient();

    //     $name = uniqid('Wk');
    //     $crawler = $client->request(
    //         'POST',
    //         '/api/repositories/repository_test/workspaces',
    //         array('name' =>  $name)
    //     );
    //     $this->assertTrue($client->getResponse()->isOk());

    //     $json = json_decode($client->getResponse()->getContent(), true);
    //     $this->assertEquals($json, sprintf('Workspace %s created', $name));
    // }
}