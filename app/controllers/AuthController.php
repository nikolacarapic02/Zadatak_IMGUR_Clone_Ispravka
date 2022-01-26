<?php

namespace app\controllers;

use app\core\Application;
use app\models\User;
use app\core\lib\Controller;
use Twig\Environment;

class AuthController extends Controller
{
    protected Environment $view;
    protected User $user;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->user = new User();
    }

    public function index()
    {
        $value = Application::$app->request->getPath();

        if($value === '/register')
        {
            return $this->view->render($value . '.html', ['title' => 'Register Page']);
        }
        else if($value === '/login')
        {
            return $this->view->render($value . '.html', ['title' => 'Login Page']);
        }
        else
        {
            $plan = $_GET['plan'];

            if($plan == 1)
            {
                $plan = '1 month';
                $amount = '10$';
            }
            else if($plan == 2)
            {
                $plan = '6 months';
                $amount = '30$';
            }
            else
            {
                $plan = '12 months';
                $amount = '50$';
            }

            return $this->view->render($value . '.html', [
                'title' => 'Subscription',
                'user' => $this->user->get(Application::$app->session->getSession('user')),
                'plan' => $plan,
                'amount' => $amount
            ]);
        }

    }

    public function register()
    {
        $data = Application::$app->request->getData();

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
            $this->user->register($data);
            Application::$app->session->setSession('register', 'Thank you for registration');
            Application::$app->response->redirectToAnotherPage('/');
        }
    }

    public function login()
    {
        $data = Application::$app->request->getData();

        Application::$app->validation('login');

        $user = $this->user->login($data);

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
        $this->user->logout();
        Application::$app->response->redirectToAnotherPage('/');
    }

    public function subscription()
    {
        $data = Application::$app->request->getData();

        Application::$app->validation('subscription');

        $plan = $_GET['plan'];

        if($plan == 1)
        {
            $plan = '1 month';
            $amount = '10$';
        }
        else if($plan == 2)
        {
            $plan = '6 months';
            $amount = '30$';
        }
        else
        {
            $plan = '12 months';
            $amount = '50$';
        }

        if(Application::$app->hasErrors())
        {
            return $this->view->render('subscription.html', [
                'errors' => Application::$app->getErrors(),
                'title' => 'Subscription',
                'values' => Application::$app->request->getData(),
                'user' => $this->user->get(Application::$app->session->getSession('user')),
                'plan' => $plan,
                'amount' => $amount,
                'selectedMethod' => $data['payment_methods']
            ]);
        }
        else
        {
            $this->user->subscribe($data);
            Application::$app->response->redirectToAnotherPage('/profile');
        }
    }
}