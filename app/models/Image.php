<?php

namespace app\models;

use app\core\Application;
use app\exceptions\NotFoundException;

class Image
{
    private array $images = [];
    private int $i;
    public string $page = '';

    public function __construct()
    {
        if(key_exists('page',$_GET))
        {
            if(is_numeric($_GET['page']) && $_GET['page'] > 0)
            {
                $this->page = $_GET['page']; 

                if($this->page > $this->numOfPages())
                {
                    $this->page = $this->numOfPages();
                }
            }
            else
            {
                $this->page = 1;
            }
        } 
        else
        {
            $this->page = 1;
        }

        $this->i = 0;
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
    
    public function get()
    {
        if($this->checkContentToLoad())
        {
            $this->images = Application::$app->db->getAllImagesForPage($this->page);
        }
        else
        {
            $this->images = Application::$app->db->getImagesForPage($this->page);
        }

        for($this->i = 0; $this->i < count($this->images); $this->i++)
        {
            $instance = new User();
            $user = $instance->get($this->images[$this->i]['user_id']);
            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                    <figure class="effect-ming tm-video-item">
                        <img src="uploads/%s" alt="Image" class="img-fluid">
                        <figcaption class="d-flex align-items-center justify-content-center">
                            <h2>Details</h2>
                            <a href="/photo_details?id=%s">View more</a>
                        </figcaption>                    
                    </figure>
                    <div class="d-flex justify-content-between tm-text-gray">
                        <span class="tm-text-gray-light">%s</span>
                        <a href="/user_profile?id=%s">%s</a>
                    </div>
                </div>        
                ',
                $this->images[$this->i]['file_name'],
                $this->images[$this->i]['id'],
                preg_replace('/\\.[^.\\s]{3,4}$/', '', $this->images[$this->i]['file_name']),
                $user[0]['id'],
                $user[0]['username']
            );
        }
    }

    public function getUserImages($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->page > $this->numOfUserPages($id))
        {
            $this->page = $this->numOfUserPages($id);
        }

        if($this->checkContentToLoad())
        {
            $this->images = Application::$app->db->getAllImagesForUser($user[0]['id'], $this->page);
        }
        else
        {
            $this->images = Application::$app->db->getImagesForUser($user[0]['id'], $this->page);
        }

        if(empty($this->images))
        {
            throw new NotFoundException();
        }

        echo sprintf('
            <div class="row mb-2">
                <h2 class="col-6 tm-text-primary">
                    Photos by %s
                </h2>
            </div>
            <hr class="underline">
            <div class="row tm-mb-90 tm-gallery">
            ',
            $user[0]['username']
        );

        for($this->i = 0; $this->i < count($this->images); $this->i++){
            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                    <figure class="effect-ming tm-video-item">
                        <img src="uploads/%s" alt="Image" class="img-fluid">
                        <figcaption class="d-flex align-items-center justify-content-center">
                            <h2>Details</h2>
                            <a href="/photo_details?id=%s">View more</a>
                        </figcaption>                    
                    </figure>
                    <div class="d-flex justify-content-between tm-text-gray">
                        <span class="tm-text-gray-light">%s</span>
                        <a href="/user_profile?id=%s">%s</a>
                    </div>
                </div>       
                ',
                $this->images[$this->i]['file_name'],
                $this->images[$this->i]['id'],
                preg_replace('/\\.[^.\\s]{3,4}$/', '', $this->images[$this->i]['file_name']),
                $user[0]['id'],
                $user[0]['username']
            );
        }

        echo sprintf('</div>');
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

        $instance = new User();
        $user = $instance->get($image[0]['user_id']);

        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-60">
                <div class="row tm-mb-50">            
                    <div class="col-xl-8 col-lg-7 col-md-6 col-sm-12">
                        <img src="uploads/%s" alt="%s" class="img-fluid" id="photoDetail">
                    </div>
                    <div class="col-xl-4 col-lg-5 col-md-6 col-sm-12">
                        <div class="tm-bg-gray tm-video-details">
                            <div class="text-center mb-5">
                                <a href="uploads/%s" class="btn btn-primary tm-btn-big" download="%s">
                                    <span class="fas fa-download"></span>  Download
                                </a>
                            </div>                    
                            <div class="mb-4">
                                <div class="mr-4 mb-2" id="Details">
                                    <span class="tm-text-gray-dark">File name: </span><span class="tm-text-primary">%s</span>
                                </div>
                                <div class="mr-4 mb-2" id="Details">
                                    <span class="tm-text-gray-dark">Format: </span><span class="tm-text-primary">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap" id="Details">
                                    <span class="tm-text-gray-dark">Posted by: </span><a href="/user_profile?id=%s" class="tm-text"><span class="ms-2">%s</span></a>
                                </div>
                            </div>
                            <div class="mb-4 text-center">
                                <h3 class="tm-text-gray-dark mb-3">License</h3>
                                <p>Free for both personal and commercial use. No need to pay anything. No need to make any attribution.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> 
        ',
        $image[0]['file_name'],
        preg_replace('/\\.[^.\\s]{3,4}$/', '', $image[0]['file_name']),
        $image[0]['file_name'],
        $image[0]['file_name'],
        preg_replace('/\\.[^.\\s]{3,4}$/', '', $image[0]['file_name']),
        strtoupper(substr($image[0]['file_name'], strpos($image[0]['file_name'], ".") + 1)),
        $user[0]['id'],
        $user[0]['username']
        );
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

        if(empty($image))
        {
            throw new NotFoundException();
        }

        $comments = Application::$app->db->getCommentsForImage($image[0]['id']);

        if(count($comments) == 0)
        {
            echo '
                <div class="d-flex flex-column comment-section">
                    <div class="bg-white p-2">
                        <div class="mt-2">
                            <p class="comment-text">There is no comments</p>
                        </div>
                </div>
            ';
        }
        else
        {
            for($this->i = 0; $this->i < count($comments); $this->i++)
            {
                $instance = new User();
                $commentedUser = $instance->get($comments[$this->i]['user_id']);

                echo sprintf('
                    <div class="d-flex flex-column comment-section">
                        <div class="bg-white p-2">
                            <div class="d-flex flex-row user-info"><img class="rounded-circle" src="assets/img/user.png" width="40" height="40">
                                <div class="d-flex flex-column justify-content-start ml-2"><span class="d-block font-weight-bold name">%s</span><span class="date text-black-50">Shared publicly</span></div>
                            </div>
                            <div class="mt-2">
                                <p class="comment-text">%s</p>
                            </div>
                        </div>
                    </div>
                    <hr class="underline" id="underlineForm">
                    ',
                    $commentedUser[0]['username'],
                    $comments[$this->i]['comment']
                );
            }
        }
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

        if(empty($image))
        {
            throw new NotFoundException();
        }

        Application::$app->db->createCommentForImage($userId, $image[0]['id'], $comment);
    }

    public function editImageByModerator($nsfw, $hidden , $id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);

        if(empty($image))
        {
            throw new NotFoundException();
        }

        $instance = new User();
        $user = $instance->get(Application::$app->session->getSession('user'));

        $nsfwOld = $image[0]['nsfw'];
        $hiddenOld = $image[0]['hidden'];

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

        Application::$app->db->editImageByModerator($nsfw, $hidden, $id);

        $newImage = Application::$app->db->getSingleImageByIdWithoutRule($id);

        $nsfwNew = $newImage[0]['nsfw'];
        $hiddenNew = $newImage[0]['hidden'];

        if($nsfwOld != $nsfwNew || $hiddenOld != $hiddenNew)
        {
            Application::$app->db->moderatorImageLogging($user[0]['id'], $user[0]['username'], $image[0]['id'], $image[0]['slug'], $action);
        }
    }

    public function editImageByAdmin($file_name, $slug, $nsfw, $hidden, $id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        $oldName = $image[0]['file_name'];
        $format = substr($image[0]['file_name'], strpos($image[0]['file_name'], ".") + 1);

        if(empty($image))
        {
            throw new NotFoundException();
        }

        if($file_name != '')
        {
            $file_name = $file_name . '.' . $format;
        }
        else
        {
            $file_name = $image[0]['file_name'];
        }

        if($slug == '')
        {
            $slug = $image[0]['slug'];
        }

        if($nsfw == '')
        {
            $nsfw = 0;
        }
   
        if($hidden == '')
        {
            $hidden = 0;
        }
        

        Application::$app->db->editImageByAdmin($file_name, $slug, $nsfw, $hidden, $id);

        if(file_exists("uploads/$oldName"))
        {
            rename("uploads/$oldName", "uploads/$file_name");
        }
    }

    public function numOfPages()
    {
        if($this->checkContentToLoad())
        {
            $num = Application::$app->db->getNumOfAllImages();
        }
        else
        {
            $num = Application::$app->db->getNumOfImages();
        }

        $numImg = $num[0]['num'];

        return ceil($numImg/16);
    }

    public function numOfUserPages($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            $num = Application::$app->db->getNumOfYourAllImages($user[0]['id']);
        }
        else
        {
            $num = Application::$app->db->getNumOfYourImages($user[0]['id']);
        }

        $numImg = $num[0]['num'];

        return ceil($numImg/8);
    }

    public function imagesForProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            $this->images = Application::$app->db->getAllImagesForUser($user[0]['id'], $this->page);
        }
        else
        {
            $this->images = Application::$app->db->getImagesForUser($user[0]['id'], $this->page);
        }

        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-30">
                <div class="row mb-4">
                    <h2 class="tm-text-primary ">
                        Photos of %s
                    </h2>
                </div>
                <hr class="underline">
            </div>
            ',
            $user[0]['username']
        );

        if(empty($this->images))
        {
            echo sprintf('
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-3 mt-2">
                        <p class="comment-text">There is no images</p>
                    </div>     
                    <div class="row tm-mb-90">
                        <div class="col-12 d-flex justify-content-between align-items-center tm-paging-col">
                            <a href="/" class="btn btn-primary tm-btn disabled" id="moreButtonProfile"><span class="fas fa-plus"></span>  More</a>
                        </div>            
                    </div>     
                '
            );
        }
        else
        {
            for($this->i = 0; $this->i < count($this->images); $this->i++)
            {
                echo sprintf('
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-3 mt-2">
                        <figure class="effect-ming tm-video-item">
                            <img src="uploads/%s" alt="Image" class="img-fluid">
                            <figcaption class="d-flex align-items-center justify-content-center">
                                <h2>Details</h2>
                                <a href="/photo_details?id=%s">View more</a>
                            </figcaption>                    
                        </figure>
                        <div class="d-flex justify-content-between tm-text-gray">
                            <span class="tm-text-gray-light">%s</span>
                        </div>
                    </div>         
                    ',
                    $this->images[$this->i]['file_name'],
                    $this->images[$this->i]['id'],
                    preg_replace('/\\.[^.\\s]{3,4}$/', '', $this->images[$this->i]['file_name']),
                );
            }

            echo sprintf('
                <div class="row tm-mb-90">
                    <div class="col-12 d-flex justify-content-between align-items-center tm-paging-col">
                        <a href="/user_photos?id=%s" class="btn btn-primary tm-btn" id="moreButtonProfile"><span class="fas fa-plus"></span>  More</a>
                    </div>            
                </div>  
            ',
            $user[0]['id']
            );

        }
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

        $image = Application::$app->db->createImage($file['name'], $slug, $user_id, $gallery_name);
        
        $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image[0]['id']);

        Application::$app->db->AddToTableImageGallery($image[0]['id'], $gallery[0]['id']);
    }

    public function editImage($id, $name, $slug)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);
        $oldName = $image[0]['file_name'];
        $format = substr($image[0]['file_name'], strpos($image[0]['file_name'], ".") + 1);
        
        if(!empty($image))
        {   
            if($name != '')
            {
                $name = $name . '.' . $format;
            }
            else
            {
                $name = $image[0]['file_name'];
            }

            if($slug == '')
            {
                $slug = $image[0]['slug'];
            }

            Application::$app->db->editImage($name, $slug, $image[0]['id'], Application::$app->session->getSession('user'));

            $newImage = Application::$app->db->getSingleImageByIdWithoutRule($image[0]['id']);

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

        if(file_exists("uploads/$filename"))
        {
            unlink("uploads/$filename");
        }
    }
}