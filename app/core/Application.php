<?php

namespace app\core;

use app\models\User;
use Bramus\Router\Router;

class Application
{
    protected static $config;
    public static $app;

    public Database $db;
    public Router $router;
    public Controller $controller;
    public Response $response;
    public Request $request;
    public User $user;
    public Session $session;

    protected array $errors = [];
    protected ?array $registeredUser = [];

    public function __construct($config)
    {
        self::$app = $this;
        $this->db = new Database($config['db']);
        $this->router = new Router();
        $this->controller = new Controller();
        $this->response = new Response();
        $this->request = new Request();
        $this->user = new User();
        $this->session = new Session();

        if(!$this->isGuest())
        {
            $this->registeredUser = $this->user->pdo->get($this->session->getSession('user'));
        }
        else
        {
            $this->registeredUser = null;
        }
    }

    public function validation($action)
    {
        $data = $this->request->getData();

        if($action === 'register')
        {
            if(empty($data['username']))
            {
                $this->errors['username'] = 'This field is required!';
            }
            
            if(empty($data['email']))
            {
                $this->errors['email'] = 'This field is required!';
            }
            else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            {
                $this->errors['email'] =  'Email foramt must be correct!';
            }

            if(empty($data['password']))
            {
                $this->errors['password'] = 'This field is required!';
            }
            else if(strlen($data['password']) < 8)
            {
                $this->errors['password'] = 'The password must have at least 8 characters!';
            }

            if(empty($data['confirm_password']))
            {
                $this->errors['confirm_password'] ='This field is required!';
            }
            else if($data['confirm_password'] != $data['password'])
            {
                $this->errors['confirm_password'] ='This field must be same as field password!';
            }
        }
        else if($action === 'login')
        {
            $user = $this->user->pdo->login($data);

            if(empty($data['email']))
            {
                $this->errors['email'] = 'This field is required!';
            }
            else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            {
                $this->errors['email'] =  'Email foramt must be correct!';
            }
            else if(empty($user))
            {
                $this->errors['email'] =  "Don't exist any account with this email!";
            }

            if(empty($data['password']))
            {
                $this->errors['password'] = 'This field is required!';
            }
            else if(!empty($user))
            {
                if(!password_verify($data['password'], $user[0]['password']))
                {
                    $this->errors['password'] = 'Password is incorrect!';
                }
            }
        }
    }

    public function hasErrors()
    {
        if(!empty($this->errors))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorForAttribute($attribute)
    {
        return $this->errors[$attribute];
    }

    public function isGuest()
    {
        if(!key_exists('user', $_SESSION))
        {
            return true;
        }
    }

    public function displayUserName()
    {
        if(!$this->isGuest())
        {
            return $this->registeredUser[0]['username'];
        }
    }

}