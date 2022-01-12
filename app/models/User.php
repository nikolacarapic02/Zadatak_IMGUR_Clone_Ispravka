<?php

namespace app\models;

use app\core\Application;

class User
{
    public $page;
    public $pdo;

    public function __construct()
    {
        $this->pdo = Application::$app->db;
    }

    public function register(array $attributes)
    {
        $this->pdo->register($attributes);
    }

    public function login(array $attributes)
    {
        return $this->pdo->login($attributes);
    }

    public function logout()
    {
        Application::$app->session->unsetSession('user');
    }
}