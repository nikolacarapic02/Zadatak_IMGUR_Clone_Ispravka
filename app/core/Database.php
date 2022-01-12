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
}