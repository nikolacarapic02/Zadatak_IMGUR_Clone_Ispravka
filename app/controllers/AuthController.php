<?php

namespace app\controllers;

use app\core\Application;
use app\models\User;
use app\core\Controller;

class AuthController extends Controller
{
    protected $view;
    protected User $model;

    public function __construct($view)
    {
        $this->view = $view;
        $this->model = new User();
    }

    public function register()
    {
        Application::$app->validation();
        
        if(Application::$app->hasErrors())
        {
            return $this->view->render('register.html', [
                'errors' => Application::$app->getErrors(),
                'title' => 'Register',
                'values' => Application::$app->request->getData()
            ]);
        }
        else
        {
            $this->model->register($_POST);
            Application::$app->session->setSession('register', 'Thank you for registration');
            Application::$app->response->redirectToAnotherPage('/');
        }
    }

    public function login()
    {
        $this->model->login();

        Application::$app->response->redirectToAnotherPage('/profile');
        return $this->view->render('profile.html', ['title' => 'Profile']);
    }

    public function index()
    {
        $value = $_SERVER['REQUEST_URI'];
        return $this->view->render($value . '.html', ['title' => 'Register Page']);
    }
}