<?php

namespace app\models;

use app\core\Application;
use app\core\lib\Model;
use app\exceptions\ForbidenException;

class Gallery extends Model
{
    private array $galleries = [];
    private int $i = 0;
    private static $model;

    public function __construct()
    {       
        self::$model = $this;
        parent::__construct(self::$model);
    }

    public function isNsfw($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if($gallery[0]['nsfw'] == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getPage()
    {
        return $this->page;
    }

    public function isHidden($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if($gallery[0]['hidden'] == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function get()
    {
        if($this->checkContentToLoad())
        {
            if($this->redis->isCached($this->uri, 'all_galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getAllGaleriesForPage($this->page);

                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getGalleriesForPage($this->page);
                
                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }

        return $this->galleries;
    }

    public function getUserGalleries($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_all_galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getAllGalleriesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getGalleriesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }

        return $this->galleries;
    }

    public function details($id)
    {
        if($this->checkContentToLoad())
        {
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        }
        else
        {
            $gallery = Application::$app->db->getSingleGallery($id);
        }

        if(empty($gallery))
        {
            throw new ForbidenException();
        }
    
        return $gallery;
    }

    public function getImagesForGallery($id)
    {
        if($this->checkContentToLoad())
        {
            $imagesId = Application::$app->db->getAllImagesKeysFromGallery($id);
        }
        else
        {
            $imagesId = Application::$app->db->getImagesKeysFromGallery($id);
        }

        $images = []; 

        for($this->i = 0; $this->i < count($imagesId); $this->i++)
        {
            array_push($images, Application::$app->db->getSingleImageByIdWithoutRule($imagesId[$this->i]['image_id']));
        }

        return $images;
    }

    public function getComments($id)
    {
        if($this->checkContentToLoad())
        {
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        }
        else
        {
            $gallery = Application::$app->db->getSingleGallery($id);
        }

        $comments = Application::$app->db->getCommentsForGallery($gallery[0]['id']);

        return $comments;
    }

    public function createComment($comment ,$id)
    {
        if($this->checkContentToLoad())
        {
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        }
        else
        {
            $gallery = Application::$app->db->getSingleGallery($id);
        }

        if(!empty($comment))
        {
            $comment = $_POST['comment'];
            $userId = Application::$app->session->getSession('user');
            $registeredUser = new User();

            if($registeredUser->isModerator($userId) || $registeredUser->isAdmin($userId))
            {
                $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
            }
            else
            {
                $gallery = Application::$app->db->getSingleGallery($id);
            }   

            Application::$app->db->createCommentForGallery($userId, $gallery[0]['id'], $comment);
        }
    }

    public function editGalleryByModerator($nsfw, $hidden , $id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        $instance = new User();
        $user = $instance->get(Application::$app->session->getSession('user'));

        $nsfwOld = $gallery[0]['nsfw'];
        $hiddenOld = $gallery[0]['hidden'];

        if($nsfw == '')
        {
            $nsfw = 0;
        }

        if($hidden == '')
        {
            $hidden = 0;
        }

        if($nsfw == 1 && $hidden == 1)
        {
            $action = 'je hidden i nsfw';
        }
        else
        {
            if($nsfw == 1 && $nsfw != $nsfwOld)
            {
                $action = 'je nsfw';
            }

            if($hidden == 1 && $hidden != $hiddenOld)
            {
                $action = 'je hidden';
            }

            if($nsfw == 0 && $nsfw != $nsfwOld)
            {
                $action = 'vise nije nsfw';
            }

            if($hidden == 0 && $hidden != $hiddenOld)
            {
                $action = 'vise nije hidden';
            }
            
            if($hidden == 0 && $hidden != $hiddenOld && $nsfw == 0 && $nsfw != $nsfwOld)
            {
                $action = 'vise nije ni hidden, a ni nsfw';
            }
        }

        Application::$app->db->editGalleryByModerator($nsfw, $hidden, $id);

        $newGallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        $nsfwNew = $newGallery[0]['nsfw'];
        $hiddenNew = $newGallery[0]['hidden'];

        if($nsfwOld != $nsfwNew || $hiddenOld != $hiddenNew)
        {
            Application::$app->db->moderatorGalleryLogging($user[0]['id'], $user[0]['username'], $gallery[0]['id'], $gallery[0]['slug'], $action);
            $this->redis->editGalleryFromCache($gallery, $newGallery);
        }
    }

    public function editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if($name == '')
        {
            $name = $gallery[0]['name'];
        }

        if($description == '')
        {
            $description = $gallery[0]['description'];
        }

        if($slug == '')
        {
            $slug = $gallery[0]['slug'];
        }

        if($nsfw == '')
        {
            $nsfw = 0;
        }
   
        if($hidden == '')
        {
            $hidden = 0;
        }

        Application::$app->db->editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $id);
        $newGallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        $this->redis->editGalleryFromCache($gallery, $newGallery);
    }

    public function galleriesForProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_all_galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getAllGalleriesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'].'_galleries_page_' . $this->page))
            {   
                $this->galleries = $this->redis->getCachedGalleries($this->uri, $this->page);
            }
            else
            {
                $this->galleries = Application::$app->db->getGalleriesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->galleries))
                {
                    $this->redis->cacheGalleries($this->galleries, $this->uri, $this->page);
                }
            }
        }

        return $this->galleries;
    }

    public function createGallery($name, $slug, $description, $user_id)
    {
        $galleryId = Application::$app->db->createGallery($name, $slug, $description, $user_id);
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($galleryId[0]['id']);
        $this->redis->createGalleryInCache($gallery);
    }

    public function editGallery($id, $name, $slug, $description)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        
        if(!empty($gallery))
        {   
            if($name == '')
            {
                $name = $gallery[0]['name'];
            }

            if($slug == '')
            {
                $slug = $gallery[0]['slug'];
            }

            if($description == '')
            {
                $description = $gallery[0]['description'];
            }

            Application::$app->db->editGallery($name, $slug, $description, $gallery[0]['id'], Application::$app->session->getSession('user'));

            $newGallery = Application::$app->db->getSingleGalleryWithoutRule($gallery[0]['id']);
            $this->redis->editGalleryFromCache($gallery, $newGallery);
        }
    }

    public function deleteGallery($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
        Application::$app->db->deleteGalleryImageKey($id);
        Application::$app->db->deleteGalleryCommentKey($id);
        Application::$app->db->deleteGallery($id);
        $this->redis->deleteGalleryFromCache($gallery);
    }
}