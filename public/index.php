<?php

use Dotenv\Dotenv;
use Twig\Environment;
use app\core\Application;
use Twig\Loader\FilesystemLoader;
use app\controllers\AuthController;
use app\controllers\HomeController;
use app\controllers\AboutController;
use app\controllers\PhotosController;
use app\controllers\ProfileController;
use app\controllers\GalleriesController;
use app\controllers\ModeratorLoggingController;
use app\controllers\PlanController;

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
$twig->addGlobal('_app', $app);

$app->router->set404(function() use ($twig){
    echo $twig->render('_error.html', [
        'title' => 'Error 404',
        'exception' => ['code' => 404, 'message' => 'Page Not Found']
    ]);
});

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
    echo $controller->logout();
});

$app->router->get('/profile', function() use ($twig){
    $controller = new ProfileController($twig);
    echo $controller->index();
});

$app->router->get('/user_profile', function() use ($twig){
    $controller = new ProfileController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->otherProfile($id);
});

$app->router->get('/photos', function() use ($twig){
    $controller = new PhotosController($twig);
    echo $controller->index();
});

$app->router->get('/photo_details', function() use ($twig){
    $controller = new PhotosController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->details($id);
});

$app->router->get('/user_photos', function() use ($twig){
    $controller = new PhotosController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->userPhotos($id);
});

$app->router->get('/galleries', function() use ($twig){
    $controller = new GalleriesController($twig);
    echo $controller->index();
});

$app->router->get('/gallery_details', function() use ($twig){
    $controller = new GalleriesController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->details($id);
});

$app->router->get('/user_galleries', function() use ($twig){
    $controller = new GalleriesController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->userGalleries($id);
});

$app->router->get('/about', function() use ($twig){
    $controller = new AboutController($twig);
    echo $controller->index();
});

$app->router->get('/moderator_logging', function() use ($twig){
    $controller = new ModeratorLoggingController($twig);
    echo $controller->index();
});

$app->router->get('/plan_pricing', function() use ($twig){
    $controller = new PlanController($twig);
    echo $controller->index();
});

$app->router->get('/subscription', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->index();
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
    $controller = new ProfileController($twig);
    echo $controller->create();
    echo $controller->cancel();
    echo $controller->buy();
});

$app->router->post('/user_profile', function() use ($twig){
    $controller = new ProfileController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->otherProfile($id);
});

$app->router->post('/photo_details', function() use ($twig){
    $controller = new PhotosController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->details($id);
});

$app->router->post('/gallery_details', function() use ($twig){
    $controller = new GalleriesController($twig);
    key_exists('id', $_GET) ? $id = $_GET['id'] : $id = null;
    echo $controller->details($id);
});

$app->router->post('/subscription', function() use ($twig){
    $controller = new AuthController($twig);
    echo $controller->subscription();
});

try
{
    $app->router->run();
}
catch(\Exception $e)
{
    echo $twig->render('_error.html', [
        'title' => 'Error ' . $e->getCode(),
        'exception' => $e
    ]);
}


