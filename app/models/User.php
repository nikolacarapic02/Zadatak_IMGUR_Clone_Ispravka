<?php

namespace app\models;

use app\core\Application;
use app\core\lib\interfaces\Subscription;
use app\core\lib\Model;
use app\exceptions\NotFoundException;

class User extends Model implements Subscription
{
    private array $user = [];
    private static $model;
    private array $plan = [array( 
        'plan' => 'free',
        'plan_expire' => 'none',
        'status' => '1'
    )];
    
    public function __construct()
    {
        self::$model = $this;
        parent::__construct(self::$model);
    }

    public function register(array $attributes)
    {
        Application::$app->db->registerUser($attributes);
    }

    public function login(array $attributes)
    {
        return Application::$app->db->loginUser($attributes);
    }

    public function logout()
    {
        Application::$app->session->unsetSession('user');
        $this->redis->clearFromHash('/profile', 'user');
    }

    public function subscribe(array $attributes)
    {
        Application::$app->db->subscribeToPlan(Application::$app->session->getSession('user'), $attributes);
    }

    public function cancelSubscription($user_id)
    {
        Application::$app->db->cancelSubscriptionForUser($user_id);
    }

    public function getPlan($user_id)
    {
        $value = Application::$app->db->getPlanInfo($user_id);
        
        if(!empty($value))
        {
            return $value;
        }
        else
        {
            return $this->plan;
        }
    }

    public function get($id)
    {
        $this->user = Application::$app->db->getUser($id);

        return $this->user;
    }


    public function isModerator($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['role'] == 'moderator')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isAdmin($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['role'] == 'admin')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isYourImage($id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);

        if(!empty($image))
        {
            if($image[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function isYourGallery($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if(!empty($gallery))
        {
            if($gallery[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function isYourGalleryName($name)
    {
        $galleryId = Application::$app->db->getYourGalleryByName($name, Application::$app->session->getSession('user'));

        if(!empty($galleryId))
        {
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($galleryId[0]['id']); 

            if($gallery[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    public function isYourProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if(empty($user))
        {
            throw new NotFoundException();
        }

        if($user[0]['id'] == Application::$app->session->getSession('user'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isBanned($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['status'] == 'inactive')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isActive($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['status'] == 'active')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function profileDetails($id)
    {
        if($this->redis->isCached('/profile', 'user'))
        {
            $this->user = $this->redis->getCachedUser();
        }
        else
        {
            $this->user = Application::$app->db->getUser($id);
            $this->redis->cacheUser($this->user, 120);
        }

        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        return $this->user;
    }

    public function otherProfileDetails($id)
    {
        $this->user = Application::$app->db->getUser($id);
        
        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        return $this->user;
    }

    public function changeUserStatus($id, $status)
    {
        if($status == 1)
        {
            $status = 'active';
        }
        else
        {
            $status = 'inactive';
        }
        
        Application::$app->db->changeUserStatus($id, $status);
    }

    public function changeUserRole($id, $role)
    {
        if($role == 1)
        {
            $role = 'user';
        }
        
        if($role == 2)
        {
            $role = 'moderator';
        }

        if($role == 3)
        {
            $role = 'admin';
        }

        Application::$app->db->changeUserRole($id, $role);
    }

    public function getModeratorLogging()
    {
        $content = Application::$app->db->getModeratorLogging();

        return $content;
    }
}
