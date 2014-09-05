<?php

namespace PHPCRAPI\Silex;

use Silex\Application;

abstract class AbstractConverter
{
    protected $app;

    public function __construct(Application $app) {
        $this->app = $app;
    }

    /* All child classes need to implement `public function convert()` which is not defined here because the arguments are not always the same */
}
