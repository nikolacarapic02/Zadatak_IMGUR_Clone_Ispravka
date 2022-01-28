<?php

namespace app\core\lib;

use app\cache\Cache;
use app\models\User;
use app\core\Application;
use app\models\Gallery;
use app\models\Image;

class Model
{
    protected Cache $redis;
    protected string $page = '';
    protected string $uri;

    public function __construct($model)
    {
        $this->redis = new Cache();

        if($model instanceof Image || $model instanceof Gallery)
        {
            $this->uri = Application::$app->request->getPath();
            $this->page = $this->checkPage($model);
        }
    }

    protected function checkPage()
    {
        if(key_exists('page',$_GET))
        {
            if(is_numeric($_GET['page']) && $_GET['page'] > 0)
            {
                return $_GET['page']; 
            }
            else
            {
                return 1;
            }
        } 
        else
        {
            return 1;
        }
    }

    public function restrictPage($model)
    {
        $value = $this->checkPage();
        $numOfPages = $this->numOfPages($model);

        if($value > $numOfPages)
        {
            $this->page = $numOfPages;
        }
    }

    public function restrictUserPage($model, $id)
    {
        $value = $this->checkPage();
        $numOfUserPages = $this->numOfUserPages($model, $id);

        if($value > $numOfUserPages)
        {
            $this->page = $numOfUserPages;
        }
    }

    public function numOfPages($model)
    {
        if($model instanceof Image)
        {
            if($this->checkContentToLoad())
            {
                $num = Application::$app->db->getNumOfAllImages();
            }
            else
            {
                $num = Application::$app->db->getNumOfImages();
            }
        }
        else
        {
            if($this->checkContentToLoad())
            {
                $num = Application::$app->db->getNumOfAllGalleries();
            }
            else
            {
                $num = Application::$app->db->getNumOfGalleries();
            }
        }

        $numImg = $num[0]['num'];

        return ceil($numImg/16);
    }

    public function numOfUserPages($model, $id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($model instanceof Image)
        {
            if($this->checkContentToLoad())
            {
                $num = Application::$app->db->getNumOfYourAllImages($user[0]['id']);
            }
            else
            {
                $num = Application::$app->db->getNumOfYourImages($user[0]['id']);
            }
        }
        else
        {
            if($this->checkContentToLoad())
            {
                $num = Application::$app->db->getNumOfYourAllGalleries($user[0]['id']);
            }
            else
            {
                $num = Application::$app->db->getNumOfYourGalleries($user[0]['id']);
            }
        }

        $numImg = $num[0]['num'];

        return ceil($numImg/8);
    }

    public function checkContentToLoad()
    {
        if(Application::$app->session->getSession('user'))
        {
            $user = new User();
            
            if($user->isModerator(Application::$app->session->getSession('user')) || $user->isAdmin(Application::$app->session->getSession('user')))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }
}