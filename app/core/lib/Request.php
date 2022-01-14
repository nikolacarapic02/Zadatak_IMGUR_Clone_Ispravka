<?php

namespace app\core\lib;

class Request
{
    public function getMethod()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function isGet()
    {
        return $this->getMethod() === 'GET' ? true : false;
    }

    public function isPost()
    {
        return $this->getMethod() === 'POST' ? true : false;
    }

    public function getPath()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $separator = strpos($uri, '?');

        if($uri === '')
        {
            $uri = '/';
        }

        if($separator === false)
        {
            return $uri;
        }
        else
        {
            return substr($uri, 0, $separator);
        }
    }

    public function getFullPath()
    {
        return $_SERVER['REQUEST_URI'];
    }

    public function getData()
    {
        $data = [];

        if($this->isGet())
        {
            $get = $_GET;

            foreach($get as $key => $value)
            {
                $data[$key] = filter_input(INPUT_GET, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        if($this->isPost())
        {
            $post = $_POST;

            foreach($post as $key => $value)
            {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $data;
    }
}