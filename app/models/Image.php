<?php

namespace app\models;

use app\core\lib\Model;
use app\core\Application;
use app\exceptions\NotFoundException;

class Image extends Model
{
    private array $images = [];
    private static $model;

    public function __construct()
    {    
        self::$model = $this;
        parent::__construct(self::$model);
    }

    public function getPage()
    {
        return $this->page;
    }

    public function isNsfw($id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);

        if($image[0]['nsfw'] == 1)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isHidden($id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);

        if($image[0]['hidden'] == 1)
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
            if($this->redis->isCached($this->uri, 'all_photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getAllImagesForPage($this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getImagesForPage($this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }

        return $this->images;
    }

    public function getUserImages($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_all_photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getAllImagesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getImagesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }

        return $this->images;
    }

    public function details($id)
    {
        if($this->checkContentToLoad())
        {
            $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        }
        else
        {
            $image = Application::$app->db->getSingleImageById($id);
        }
            
        if(empty($image))
        {
            throw new NotFoundException();
        }

        return $image;
    }

    public function getComments($id)
    {
        if($this->checkContentToLoad())
        {
            $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        }
        else
        {
            $image = Application::$app->db->getSingleImageById($id);
        }

        $comments = Application::$app->db->getCommentsForImage($image[0]['id']);

        return $comments;
    }

    public function createComment($comment ,$id)
    {
        $userId = Application::$app->session->getSession('user');

        if($this->checkContentToLoad())
        {
            $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        }
        else
        {
            $image = Application::$app->db->getSingleImageById($id);
        }

        Application::$app->db->createCommentForImage($userId, $image[0]['id'], $comment);
    }

    public function editImageByModerator($image_id, array $attributes)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($image_id);

        $instance = new User();
        $user = $instance->get(Application::$app->session->getSession('user'));

        $nsfwOld = $image[0]['nsfw'];
        $hiddenOld = $image[0]['hidden'];

        if(!key_exists('nsfw', $attributes))
        {
            $nsfw = 0;
        }
        else
        {
            $nsfw = $attributes['nsfw'];
        }

        if(!key_exists('hidden', $attributes))
        {
            $hidden = 0;
        }
        else
        {
            $hidden = $attributes['hidden'];
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

        Application::$app->db->editImageByModerator($nsfw, $hidden, $image_id);

        $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image_id);

        $nsfwNew = $newImage[0]['nsfw'];
        $hiddenNew = $newImage[0]['hidden'];

        if($nsfwOld != $nsfwNew || $hiddenOld != $hiddenNew)
        {
            Application::$app->db->moderatorImageLogging($user[0]['id'], $user[0]['username'], $image[0]['id'], $image[0]['slug'], $action);
            $this->redis->editImageFromCache($image, $newImage);
        }
    }

    public function editImageByAdmin($image_id, array $attributes)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($image_id);
        $oldName = $image[0]['file_name'];
        $format = substr($image[0]['file_name'], strpos($image[0]['file_name'], ".") + 1);

        if($attributes['file_name'] == '')
        {
            $file_name = $image[0]['file_name'];
        }
        else
        {
            $file_name = $attributes['file_name'] . '.' . $format;
        }

        if($attributes['slug'] == '')
        {
            $slug = $image[0]['slug'];
        }
        else
        {
            $slug = $attributes['slug'];
        }

        if(!key_exists('nsfw', $attributes))
        {
            $nsfw = 0;
        }
        else
        {
            $nsfw = $attributes['nsfw'];
        }
   
        if(!key_exists('hidden', $attributes))
        {
            $hidden = 0;
        }
        else
        {
            $hidden = $attributes['hidden'];
        }
        

        Application::$app->db->editImageByAdmin($file_name, $slug, $nsfw, $hidden, $image_id);
        $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image_id);
        $this->redis->editImageFromCache($image, $newImage);

        if(file_exists("uploads/$oldName"))
        {
            rename("uploads/$oldName", "uploads/$file_name");
        }
    }

    public function imagesForProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_all_photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getAllImagesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }
        else
        {
            if($this->redis->isCached($this->uri, 'user_' . $user[0]['id'] . '_photos_page_' . $this->page))
            {   
                $this->images = $this->redis->getCachedImages($this->uri, $this->page);
            }
            else
            {
                $this->images = Application::$app->db->getImagesForUser($user[0]['id'], $this->page);
                
                if(!empty($this->images))
                {
                    $this->redis->cacheImages($this->images, $this->uri, $this->page);
                }
            }
        }

        return $this->images;
    }

    public function createImage($file, $name, $slug, $gallery_name, $user_id)
    {
        $gallery = Application::$app->db->getYourGalleryByName($gallery_name, $user_id);
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($file['name']);
        $format = substr($file['name'], strpos($file['name'], ".") + 1);
        $name = $name . '.' . $format;

        move_uploaded_file($file["tmp_name"], $target_file);

        rename("uploads/" . $file['name'], "uploads/$name");

        $file['name'] = $name;

        $image = Application::$app->db->createImage($file['name'], $slug, $user_id);

        $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image[0]['id']);
        
        $this->redis->createImageInCache($newImage);

        Application::$app->db->AddToTableImageGallery($image[0]['id'], $gallery[0]['id']);
    }

    public function editImage($image_id, array $attributes)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($image_id);
        $oldName = $image[0]['file_name'];
        $format = substr($image[0]['file_name'], strpos($image[0]['file_name'], ".") + 1);
        
        if(!empty($image))
        {   
            if($attributes['new_name'] != '')
            {
                $name = $attributes['new_name'] . '.' . $format;
            }
            else
            {
                $name = $image[0]['file_name'];
            }

            if($attributes['slug'] != '')
            {
                $slug = $attributes['slug'];
            }
            else
            {
                $slug = $image[0]['slug'];
            }

            Application::$app->db->editImage($name, $slug, $image[0]['id'], Application::$app->session->getSession('user'));

            $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image[0]['id']);
            $this->redis->editImageFromCache($image, $newImage);

            if(file_exists("uploads/$oldName"))
            {
                rename("uploads/$oldName", "uploads/$name");
            }
        }
    }

    public function deleteImage($id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        $filename = $image[0]['file_name'];

        Application::$app->db->deleteImageGalleryKey($id);
        Application::$app->db->deleteImageCommentKey($id);
        Application::$app->db->deleteImage($id);
        $this->redis->deleteImageFromCache($image);

        if(file_exists("uploads/$filename"))
        {
            unlink("uploads/$filename");
        }
    }
}