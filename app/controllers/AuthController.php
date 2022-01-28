<?php

namespace app\controllers;

use app\core\Application;
use app\models\User;
use app\core\lib\Controller;
use app\exceptions\ForbidenException;
use app\exceptions\NotFoundException;
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
        $uri = Application::$app->request->getPath();

        if($uri === '/register')
        {
            if(!Application::$app->isGuest())
            {
                throw new ForbidenException();
            }
            else
            {
                return $this->view->render($uri . '.html', ['title' => 'Register Page']);
            }
        }
        else if($uri === '/login')
        {
            if(!Application::$app->isGuest())
            {
                throw new ForbidenException();
            }
            else
            {
                return $this->view->render($uri . '.html', ['title' => 'Login Page']);
            }
        }
        else if($uri === '/subscription')
        {
            $validPlanValues = ['1', '1u', '2', '2u', '3', '3u'];

            if(Application::$app->isGuest() || $this->user->checkUserHavePendingPlan(Application::$app->session->getSession('user')))
            {
                throw new ForbidenException();
            }

            if(!key_exists('plan', $_GET))
            {
                throw new NotFoundException();
            }

            $plan = $_GET['plan'];

            if(!in_array($plan, $validPlanValues))
            {
                throw new NotFoundException();
            }

            if(strpos($plan, '1') === 0)
            {
                $plan = '1 month';
                $amount = '10$';
            }
            else if(strpos($plan, '2') === 0)
            {
                $plan = '6 months';
                $amount = '30$';
            }
            else
            {
                $plan = '12 months';
                $amount = '50$';
            }

            return $this->view->render($uri . '.html', [
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

        $planUriValue = $_GET['plan'];

        if(strpos($planUriValue, '1') === 0)
        {
            $plan = '1 month';
            $amount = '10$';
        }
        else if(strpos($planUriValue, '2') === 0)
        {
            $plan = '6 months';
            $amount = '30$';
        }
        else if(strpos($planUriValue, '3') === 0)
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
                'selectedMethod' => $data['method']
            ]);
        }
        else if(strpos($planUriValue, 'u'))
        {
            $this->user->upgrade($data);
            Application::$app->response->redirectToAnotherPage('/profile');
        }
        else
        {
            $this->user->subscribe($data);
            Application::$app->response->redirectToAnotherPage('/profile');
        }
    }
}