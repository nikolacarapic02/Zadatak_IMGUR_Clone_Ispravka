<?php

namespace app\models;

use app\core\Application;
use app\exceptions\NotFoundException;

class User
{
    private $pdo;
    private array $user = [];

    public function __construct()
    {
        $this->pdo = Application::$app->db;
    }

    public function register(array $attributes)
    {
        $this->pdo->registerUser($attributes);
    }

    public function login(array $attributes)
    {
        return $this->pdo->loginUser($attributes);
    }

    public function logout()
    {
        Application::$app->session->unsetSession('user');
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
        $this->user = Application::$app->db->getUser($id);

        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-60">
                <div class="row tm-mb-50">            
                    <div class="col-xl-5 col-lg-5 col-md-6 col-sm-12">
                        <div class="tm-bg-gray tm-video-details">
                            <div class="text-center mb-5">
                                <h2 class="tm-text-primary">Hello <i>%s</i></h2>
                            </div>                    
                            <div class="mb-4" id="Details">
                                <div class="mr-4 mb-2">
                                    <span class="tm-text-gray-dark">Username: </span><span class="tm-text-primary">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap">
                                    <span class="tm-text-gray-dark">Email: </span><span class="tm-text-primary ms-2">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap">
                                    <span class="tm-text-gray-dark">Status: </span><span class="tm-text-primary ms-2">%s</span>
                                </div>
                            </div>
                        </div>
                    </div> 
        ',
        $this->user[0]['username'],
        $this->user[0]['username'],
        $this->user[0]['email'],
        $this->user[0]['status'],
        );
    }

    public function userProfileDetails($id)
    {
        $this->user = Application::$app->db->getUser($id);
        
        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-60">
                <div class="row tm-mb-50">  
                    <div class="col-xl-2"></div>          
                    <div class="col-xl-8 col-lg-12 col-md-12 col-sm-12">
                        <div class="tm-bg-gray tm-video-details">
                            <div class="text-center mb-5">
                                <h2 class="tm-text-primary">User: <i>%s</i></h2>
                            </div>                    
                            <div class="mb-4" id="Details">
                                <div class="mr-4 mb-2">
                                    <span class="tm-text-gray-dark">Username: </span><span class="tm-text-primary">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap">
                                    <span class="tm-text-gray-dark">Email: </span><span class="tm-text-primary ms-2">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap">
                                    <span class="tm-text-gray-dark">Status: </span><span class="tm-text-primary ms-2">%s</span>
                                </div>
                            </div>
                        </div>
                    </div> 
                </div>
        ',
        $this->user[0]['username'],
        $this->user[0]['username'],
        $this->user[0]['email'],
        $this->user[0]['status']
        );

    }

    public function changeUserStatus($id, $status)
    {
        $oldUser = Application::$app->db->getUser($id);

        if($status == 1)
        {
            $status = 'active';
        }
        else
        {
            $status = 'inactive';
        }
        
        Application::$app->db->changeUserStatus($id, $status);

        $newUser = Application::$app->db->getUser($id);
    }

    public function changeUserRole($id, $role)
    {
        $oldUser = Application::$app->db->getUser($id);

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

        $newUser = Application::$app->db->getUser($id);
    }

    public function getModeratorLogging()
    {
        $content = Application::$app->db->getModeratorLogging();

        echo sprintf('
            <div class="container-fluid">
                <div class="container-table100 table-responsive"> 
                    <div class="wrap-table100">
                        <div class="table100 m-b-110">
                            <div class="table100-head">
                                <table>
                                    <thead>
                                        <tr class="row100 head">
                                            <th class="cell100 column1">Moderator ID</th>
                                            <th class="cell100 column2">Image ID</th>
                                            <th class="cell100 column3">Gallery ID</th>
                                            <th class="cell100 column4">Action</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
        
                            <div class="table100-body js-pscroll">
                                <table>
                                    <tbody>
            '
        );

        if(!empty($content))
        {
            for($i = 0; $i < count($content); $i++)
            {
                echo sprintf('
                    <tr class="row100 body">
                        <td class="cell100 column1">%s</td>
                        <td class="cell100 column2">%s</td>
                        <td class="cell100 column3">%s</td>
                        <td class="cell100 column4">%s</td>
                    </tr>
                    ',
                    $content[$i]['moderator_id'],
                    empty($content[$i]['image_id']) ? 'empty' : $content[$i]['image_id'],
                    empty($content[$i]['gallery_id']) ? 'empty' : $content[$i]['gallery_id'],
                    $content[$i]['action']
                );
            }
        }
        else
        {
            echo sprintf('
                <tr class="row100 body">
                    <td class="cell100 column1">empty</td>
                    <td class="cell100 column2">empty</td>
                    <td class="cell100 column3">empty</td>
                    <td class="cell100 column4">empty</td>
                </tr>
                ',
            );

        }

            echo sprintf('
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            ');
    }
}
