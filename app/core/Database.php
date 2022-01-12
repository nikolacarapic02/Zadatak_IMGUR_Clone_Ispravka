<?php

namespace app\core;

class Database
{
    protected $pdo;
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->pdo = new \PDO($config['dsn'], $config['user'], $config['password']);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    public function prepare($sql)
    {
        return $this->pdo->prepare($sql);
    }

    //User

    public function register($attributes)
    {
        $username = $attributes['username'];
        $email = $attributes['email'];
        $password = password_hash($attributes['password'], PASSWORD_DEFAULT);
        $api_key = implode('-', str_split(substr(strtolower(md5(microtime().rand(1000, 9999))), 0, 30), 6));

        $statement = $this->pdo->prepare("INSERT INTO user(username, email, password, api_key, role, nsfw, status) 
        VALUES ('$username', '$email', '$password', '$api_key', 'user', 0, 'active')");

        $statement->execute();
    }

    public function login($attributes)
    {
        $email = $attributes['email'];

        $statement = $this->pdo->prepare("SELECT * FROM user WHERE email = '$email';");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function get($id)
    {
        $statement = $this->pdo->prepare("SELECT * FROM user WHERE id = '$id';");
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    //Image

    //Gallery
}