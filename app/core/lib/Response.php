<?php 

namespace app\core\lib;

class Response
{
    public function setStatusCode($code)
    {
        http_response_code($code);
    }

    public function redirectToAnotherPage($uri)
    {
        header('Location: ' . $uri);
    }
}