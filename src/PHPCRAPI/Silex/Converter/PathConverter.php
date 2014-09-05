<?php

namespace PHPCRAPI\Silex\Converter;

use PHPCRAPI\Silex\AbstractConverter;

class PathConverter extends AbstractConverter
{
    public function convert($path)
    {
        if (mb_substr($path,0,1) != '/') {
            return '/'.$path;
        } else {
            return $path;
        }
    }
}
