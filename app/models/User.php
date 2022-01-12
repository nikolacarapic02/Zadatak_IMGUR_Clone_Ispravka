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
        $username = $attributes['username'];
        $email = $attributes['email'];
        $password = $attributes['password'];
        $api_key = implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));

        $statement1 = $this->pdo->prepare("INSERT INTO user(username, email, password, api_key, role, nsfw, status) 
        VALUES ('$username', '$email', '$password', '$api_key', 'user', 0, 'active')");

        $statement1->execute();
    }

    public function login()
    {

    }
}