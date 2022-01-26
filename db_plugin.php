<?php
use Dotenv\Dotenv;
use app\core\Application;

require_once __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD']
    ]
];

$app = new Application($config);

$db = $app->db;

$db->addNsfwToUser();
$db->addStatusToUser();
$db->dropTableDoctrine();
$db->createTableModeratorLogging();
$db->createTableSubscription();
$db->createTablePayment();
$db->addCreateTimeToImage();
$db->optimizeDatabase();