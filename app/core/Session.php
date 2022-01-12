<?php

namespace app\core;

class Session
{
    protected $flash = [];

    public function __construct()
    {
        $this->start();
    }

    private function start()
    {
        if(session_status() === PHP_SESSION_NONE)
        {
            session_start();
        }
    }

    public function setSession($key, $value)
    {
        $_SESSION[$key] = $value;

        return $this;
    }

    public function getSession($key)
    {
        if(key_exists($key, $_SESSION))
        {
            return $_SESSION[$key];
        }
    }

    public function getFlashMessage($key)
    {
        if (!isset($this->flash[$key])) {
            $message = $this->getSession($key);
            $this->flash[$key] = $message;
        }

        return $this->flash[$key];
    }

    public function unsetSession($key)
    {
        unset($_SESSION[$key]);
    }
}