<?php

namespace app\core;

class Controller
{
    protected $middlewares = [];

    public function getMiddleware()
    {
        return $this->middlewares;
    }

    public function addMiddleware($middlewares)
    {
        $this->middlewares = $middlewares;
    }
}