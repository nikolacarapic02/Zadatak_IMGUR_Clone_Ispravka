<?php

namespace app\core\lib;

use app\cache\Cache;
use app\models\User;
use app\core\Application;
use app\models\Banner;
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

        if($model instanceof Image || $model instanceof Gallery || $model instanceof Banner)
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

            return ceil($num[0]['num']/18);
        }
        else if($model instanceof Banner)
        {
            $num = Application::$app->db->getNumOfTestingBanners();

            return ceil($num[0]['num']/10);
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

            return ceil($num[0]['num']/18);
        }
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

        return ceil($num[0]['num']/12);
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