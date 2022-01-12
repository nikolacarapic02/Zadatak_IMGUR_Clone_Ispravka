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

    public function index()
    {
        $value = $_SERVER['REQUEST_URI'];
        return $this->view->render($value . '.html', ['title' => 'Register Page']);
    }

    public function register()
    {
        Application::$app->validation('register');
        
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
        Application::$app->validation('login');

        $user = $this->model->login($_POST);

        if(Application::$app->hasErrors())
        {
            return $this->view->render('login.html', [
                'errors' => Application::$app->getErrors(),
                'title' => 'Login',
                'values' => Application::$app->request->getData()
            ]);
        }
        else
        {
            Application::$app->session->setSession('user', $user[0]['id']);
            Application::$app->response->redirectToAnotherPage('/profile');
        }
    }

    public function logout()
    {
        $this->model->logout();
        Application::$app->response->redirectToAnotherPage('/');
    }
}