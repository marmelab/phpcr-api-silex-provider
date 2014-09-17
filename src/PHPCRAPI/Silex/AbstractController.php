<?php

namespace PHPCRAPI\Silex;

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController
{
    protected $app;

    private $formater;

    public function __construct(Application $app, ResponseFormater $formater)
    {
        $this->app = $app;
        $this->formater = $formater;
    }

    public function buildResponse($data, $maxAge = 60)
    {
        return $this->buildCachedResponse(
            $this->app['serializer']->serialize($this->formater->format($data), 'json'),
            200,
            $maxAge
        );
    }

    public function buildResponseWithContext($data, $context, $maxAge = 60)
    {
        return $this->buildCachedResponse(
            $this->app['serializer']->serialize($this->formater->format($data), 'json', $context),
            200,
            $maxAge
        );
    }

    private function buildCachedResponse($content, $statusCode, $maxAge)
    {
        return $this->responseFactory(
            $content,
            $statusCode,
            [
                'Cache-Control' => sprintf('s-maxage=%s, private, must-revalidate', $maxAge)
            ]
        );
    }

    private function responseFactory($content, $statusCode, array $headers = array())
    {
        $headers['Content-Type'] = 'application/json';

        return new Response($content, $statusCode, $headers);
    }
}
