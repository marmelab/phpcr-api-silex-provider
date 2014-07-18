<?php

namespace PHPCRAPI\Silex\Tests\Converter;

use PHPCRAPI\Silex\Converter\PathConverter;

class PathConverterTest extends \PHPUnit_Framework_TestCase
{
    use \Xpmock\TestCaseTrait;

    public function testItShouldAlwaysReturnAPathWithASlashAsFirstCharacter()
    {
        $converter = new PathConverter($this->mock('\Silex\Application', null));

        $this->assertEquals('/test', $converter->convert('test'));
        $this->assertEquals('/test', $converter->convert('/test'));
    }
}
