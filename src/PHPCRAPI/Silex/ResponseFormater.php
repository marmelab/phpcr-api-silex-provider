<?php

namespace PHPCRAPI\Silex;

class ResponseFormater
{
    public function format($data)
    {
        return [
            'message' => $data
        ];
    }
}
