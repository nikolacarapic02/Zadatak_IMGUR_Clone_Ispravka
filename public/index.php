<?php

use app\controllers\AuthController;
use app\controllers\HomeController;
use app\core\Application;
use Dotenv\Dotenv;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$config = [
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD']
    ],
]; 

$loader = new FilesystemLoader(__DIR__ . '/../app/views');
$twig = new Environment($loader);

$app = new Application($config);

$twig->addGlobal('_session', $app->session);

//GET
$app->router->get('/', function() use ($twig){
    $controller = new HomeController($twig);
    echo $controller->index();
});

$app->router->get('/login', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->index();
});

$app->router->get('/register', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->index();
});

$app->router->get('/logout', function() use ($twig){
    $controller = new AuthController($twig);
});

$app->router->get('/profile', function() use ($twig){
    echo $twig->render('profile.html');
});

$app->router->get('/user_profile', function() use ($twig){
    echo $twig->render('other_profile.html');
});

$app->router->get('/photos', function() use ($twig){
    echo $twig->render('photos.html');
});

$app->router->get('/photo_details', function() use ($twig){
    echo $twig->render('photo_details.html');
});

$app->router->get('/user_photos', function() use ($twig){
    echo $twig->render('user_photos.html');
});

$app->router->get('/galleries', function() use ($twig){
    echo $twig->render('galleries.html');
});

$app->router->get('/gallery_details', function() use ($twig){
    echo $twig->render('gallery_details.html');
});

$app->router->get('/user_galleries', function() use ($twig){
    echo $twig->render('user_galleries.html');
});

$app->router->get('/about', function() use ($twig){
    echo $twig->render('about.html');
});

$app->router->get('/moderator_logging', function() use ($twig){
    echo $twig->render('moderator_logging.html');
});

//POST

$app->router->post('/login', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->login();
});

$app->router->post('/register', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->register();
});

$app->router->post('/profile', function() use ($twig){
    $twig->render('profile.html');
});

$app->router->post('/user_profile', function() use ($twig){
    $twig->render('other_profile.html');
});

$app->router->post('/photo_details', function() use ($twig){
    $twig->render('photo_details.html');
});

$app->router->post('/gallery_details', function() use ($twig){
    $twig->render('gallery_details.html');
});

try
{
    $app->router->run();
}
catch(Exception $e)
{
    $app->response->setStatusCode($e->getCode());
    $twig->render('_error.html', ['exception' => ['message' => $e->getMessage()], ['code' => $e->getCode()]]);
}
