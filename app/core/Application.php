<?php

namespace app\core;

use app\models\User;
use app\core\lib\Request;
use app\core\lib\Session;
use Bramus\Router\Router;
use app\core\lib\Database;
use app\core\lib\Response;

class Application
{
    protected static $config;
    protected array $errors = [];

    public static $app;
    public Database $db;
    public Router $router;
    public Response $response;
    public Request $request;
    public Session $session;

    public function __construct($config)
    {
        self::$app = $this;
        $this->db = new Database($config['db']);
        $this->router = new Router();
        $this->response = new Response();
        $this->request = new Request();
        $this->session = new Session();
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
            $loginUser = $this->db->loginUser($data);

            if(empty($data['email']))
            {
                $this->errors['email'] = 'This field is required!';
            }
            else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL))
            {
                $this->errors['email'] =  'Email format must be correct!';
            }
            else if(empty($loginUser))
            {
                $this->errors['email'] =  "Don't exist any account with this email!";
            }

            if(empty($data['password']))
            {
                $this->errors['password'] = 'This field is required!';
            }
            else if(!empty($loginUser))
            {
                if(!password_verify($data['password'], $loginUser[0]['password']))
                {
                    $this->errors['password'] = 'Password is incorrect!';
                }
            }
        }
        else if($action === 'image_create')
        {
            $extensions = ['png', 'jpg', 'jpeg', 'gif'];

            if(empty($data['image_name']))
            {
                $this->errors['image_name'] = 'This field is required!';
            }

            if(empty($data['image_slug']))
            {
                $this->errors['image_slug'] = 'This field is required!';
            }

            if(empty($data['gallery_name']))
            {
                $this->errors['gallery_name'] = 'This field is required!';
            }
            else if(!$this->user->isYourGalleryName($data['gallery_name']))
            {
                $this->errors['gallery_name'] = "This gallery doesn't exist";
            }

            if(empty($_FILES['file']['name']))
            {
                $this->errors['file'] = 'You must upload an image!';
            }
            else if(!in_array(substr($_FILES['file']['name'], strpos($_FILES['file']['name'], ".") + 1), $extensions))
            {
                $this->errors['file'] = 'File has invalid extension!';
            }
            else if($_FILES["file"]["size"] > 1000000)
            {
                $this->errors['file'] = 'File is to large!';
            }
        }
        else if($action === 'gallery_create')
        {
            if(empty($data['gallery_slug']))
            {
                $this->errors['gallery_slug'] = 'This field is required!';
            }

            if(empty($data['name']))
            {
                $this->errors['name'] = 'This field is required!';
            }

            if(empty($data['description']))
            {
                $this->errors['description'] = 'This field is required!';
            }
        }
        else if($action === 'subscription')
        {
            if($data['method'] === 'Select a payment method')
            {
                $this->errors['method'] = 'You must select a payment method!';
            }

            if($data['method'] === 'credit')
            {
                if(empty($data['first_name']))
                {
                    $this->errors['first_name'] = 'This field is required!';
                }

                if(empty($data['last_name']))
                {
                    $this->errors['last_name'] = 'This field is required!';
                }

                if(empty($data['card_num']))
                {
                    $this->errors['card_num'] = 'This field is required!';
                }
            }

            if($data['method'] === 'paypal')
            {
                if(empty($data['paypal_mail']))
                {
                    $this->errors['paypal_mail'] = 'This field is required!';
                }
                else if(!filter_var($data['paypal_mail'], FILTER_VALIDATE_EMAIL))
                {
                    $this->errors['paypal_mail'] =  'Email format must be correct!';
                }
            }

            if($data['method'] === 'crypto')
            {
                if(empty($data['crypto_mail']))
                {
                    $this->errors['crypto_mail'] = 'This field is required!';
                }
                else if(!filter_var($data['crypto_mail'], FILTER_VALIDATE_EMAIL))
                {
                    $this->errors['crypto_mail'] =  'Email format must be correct!';
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
        $user = new User();
        $registeredUser = $user->get($this->session->getSession('user'));

        if(!$this->isGuest())
        {
            return $registeredUser[0]['username'];
        }
    }

}