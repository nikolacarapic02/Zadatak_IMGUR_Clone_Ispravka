<?php

namespace app\models;

use app\core\Application;
use app\exceptions\NotFoundException;

class Gallery
{
    private array $galleries = [];
    private int $i = 0;
    public string $page = '';

    public function __construct()
    {
        if(key_exists('page',$_GET))
        {
            if(is_numeric($_GET['page']) &&  $_GET['page'] > 0)
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
            $this->galleries = Application::$app->db->getAllGaleriesForPage($this->page);
        }
        else
        {
            $this->galleries = Application::$app->db->getGalleriesForPage($this->page);
        }

        for($this->i = 0; $this->i < count($this->galleries); $this->i++){
            $instance = new User();
            $user = $instance->get($this->galleries[$this->i]['user_id']);
            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                    <figure class="effect-ming tm-video-item">
                        <img src="assets/img/gallery.jpg" alt="Image" class="img-fluid">
                        <figcaption class="d-flex align-items-center justify-content-center">
                            <h2>Details</h2>
                            <a href="/gallery_details?id=%s">View more</a>
                        </figcaption>                    
                    </figure>
                    <div class="d-flex justify-content-between tm-text-gray">
                        <span class="tm-text-gray-light">%s</span>
                        <a href="/user_profile?id=%s">%s</a>
                    </div>
                </div>        
                ',
                $this->galleries[$this->i]['id'],
                $this->galleries[$this->i]['name'],
                $user[0]['id'],
                $user[0]['username']
            );
        }
    }

    public function getUserGalleries($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->page > $this->numOfUserPages($id))
        {
            $this->page = $this->numOfUserPages($id);
        }

        if($this->checkContentToLoad())
        {
            $this->galleries = Application::$app->db->getAllGalleriesForUser($user[0]['id'], $this->page);
        }
        else
        {
            $this->galleries = Application::$app->db->getGalleriesForUser($user[0]['id'], $this->page);
        }

        if(empty($this->galleries))
        {
            throw new NotFoundException();
        }

        echo sprintf('
            <div class="row mb-2">
            <h2 class="col-6 tm-text-primary">
                Galleries by %s
            </h2>
            </div>
            <hr class="underline">
            <div class="row tm-mb-90 tm-gallery">
            ',
            $user[0]['username']
        );

        for($this->i = 0; $this->i < count($this->galleries); $this->i++){
            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-5">
                    <figure class="effect-ming tm-video-item">
                        <img src="/assets/img/gallery.jpg" alt="Image" class="img-fluid">
                        <figcaption class="d-flex align-items-center justify-content-center">
                            <h2>Details</h2>
                            <a href="/gallery_details?id=%s">View more</a>
                        </figcaption>                    
                    </figure>
                    <div class="d-flex justify-content-between tm-text-gray">
                        <span class="tm-text-gray-light">%s</span>
                        <a href="/user_profile?id=%s">%s</a>
                    </div>
                </div>       
                ',
                $this->galleries[$this->i]['id'],
                $this->galleries[$this->i]['name'],
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
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);
            $imagesId = Application::$app->db->getAllImagesFromGallery($id);
        }
        else
        {
            $gallery = Application::$app->db->getSingleGallery($id);
            $imagesId = Application::$app->db->getImagesFromGallery($id);
        }

        if(empty($gallery))
        {
            throw new NotFoundException();
        }
    
        $instance = new User();
        $user = $instance->get($gallery[0]['user_id']);
        
        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-40">
                <div class="row tm-mb-40">  
                    <div class="col-xl-1 col-lg-1 col-md-1">
                    </div>  
                    <div class="col-xl-10 col-lg-10 col-md-10 col-sm-12 mb-5 mt-5">
                        <div class="tm-bg-gray tm-video-details mb-5">                  
                            <div class="mb-4">
                                <div class="mr-4 mb-2 d-flex flex-wrap" id="Details">
                                    <span class="tm-text-gray-dark" >Gallery name: </span><span class="tm-text-primary ms-2">%s</span>
                                </div>
                                <div class="mr-4 mb-2 d-flex flex-wrap" id="Details">
                                    <span class="tm-text-gray-dark" >Created by: </span><a href="/user_profile?id=%s" class="tm-text"><span class="ms-2">%s</span></a>
                                </div>
                                <div class="mr-4 mb-4" id="Details">
                                    <span class="tm-text-gray-dark">Description: </span><span class="tm-text-primary">%s</span>
                                </div>
                            </div>
                            <div class="text-center">
                                <h3 class="tm-text-gray-dark mb-3" style="font-size: 2rem" >License</h3>
                                <p style="font-size: 1.6rem">Free for both personal and commercial use. No need to pay anything. No need to make any attribution.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <h2 class="tm-text-primary text-center">
                        Photos of gallery "%s"
                    </h2>
                </div>
                <hr class="underline">
            </div>
        ',
            $gallery[0]['name'],
            $user[0]['id'],
            $user[0]['username'],
            $gallery[0]['description'],
            $gallery[0]['name']
        );

        if(empty($imagesId))
        {
            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-1 mt-2">
                    <p class="comment-text">There is no galleries</p>
                </div>   
            ');
        }

        for($this->i = 0; $this->i < count($imagesId); $this->i++)
        {
            if($this->checkContentToLoad())
            {
                $image = Application::$app->db->getSingleImageByIdWithoutRule($imagesId[$this->i]['image_id']);
            }
            else
            {
                $image = Application::$app->db->getSingleImageById($imagesId[$this->i]['image_id']);
            }

            if(empty($gallery))
            {
                throw new NotFoundException();
            }

            echo sprintf('
                <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-3 mt-2">
                    <figure class="effect-ming tm-video-item">
                        <img src="%s" alt="Image" class="img-fluid">
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
                $image[0]['file_name'],
                $image[0]['id'],
                $image[0]['slug'],
            );
        }
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

        if(empty($gallery))
        {
            throw new NotFoundException();
        }

        $comments = Application::$app->db->getCommentsForGallery($gallery[0]['id']);

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
            throw new NotFoundException();
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

        if(empty($gallery))
        {
            throw new NotFoundException();
        }

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
        }
    }

    public function editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if(empty($gallery))
        {
            throw new NotFoundException();
        }

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
    }

    public function numOfPages()
    {
        if($this->checkContentToLoad())
        {
            $instance = Application::$app->db->getNumOfAllGalleries();
        }
        else
        {
            $instance = Application::$app->db->getNumOfGalleries();
        }

        $numGall = $instance[0]['num'];

        return ceil($numGall/16);
    }

    public function numOfUserPages($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            $num = Application::$app->db->getNumOfYourAllGalleries($user[0]['id']);
        }
        else
        {
            $num = Application::$app->db->getNumOfYourGalleries($user[0]['id']);
        }

        $numImg = $num[0]['num'];

        return ceil($numImg/8);
    }

    public function galleriesForProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if($this->checkContentToLoad())
        {
            $this->galleries = Application::$app->db->getAllGalleriesForUser($user[0]['id'], $this->page);
        }
        else
        {
            $this->galleries = Application::$app->db->getGalleriesForUser($user[0]['id'], $this->page);
        }

        echo sprintf('
            <div class="container-fluid tm-container-content tm-mt-30">
                <div class="row mb-4">
                    <h2 class="tm-text-primary ">
                        Galleries of %s
                    </h2>
                </div>
                <hr class="underline">
            </div>
            ',
            $user[0]['username']
        );

        if(empty($this->galleries))
        {

            echo sprintf('
                    <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-3 mt-2">
                        <p class="comment-text">There is no galleries</p>
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
            for($this->i = 0; $this->i < count($this->galleries); $this->i++)
            {
                echo sprintf('
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12 mb-3 mt-2">
                            <figure class="effect-ming tm-video-item">
                                <img src="assets/img/gallery.jpg" alt="Gallery" class="img-fluid">
                                <figcaption class="d-flex align-items-center justify-content-center">
                                    <h2>Details</h2>
                                    <a href="/gallery_details?id=%s">View more</a>
                                </figcaption>                    
                            </figure>
                            <div class="d-flex justify-content-between tm-text-gray">
                                <span class="tm-text-gray-light">%s</span>
                            </div>
                        </div>         
                    ',
                    $this->galleries[$this->i]['id'],
                    $this->galleries[$this->i]['name'],
                );
            }

            echo sprintf('
                <div class="row tm-mb-90">
                    <div class="col-12 d-flex justify-content-between align-items-center tm-paging-col">
                        <a href="/user_galleries?id=%s" class="btn btn-primary tm-btn" id="moreButtonProfile"><span class="fas fa-plus"></span>  More</a>
                    </div>            
                </div>  
                ',
                $user[0]['id']
            );
        }
    }

    public function createGallery($name, $slug, $description, $user_id)
    {
        $gallery = Application::$app->db->createGallery($name, $slug, $description, $user_id);
        $newGallery = Application::$app->db->getSingleGalleryWithoutRule($gallery[0]['id']);
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
        }
    }

    public function deleteGallery($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        Application::$app->db->deleteGalleryImageKey($id);
        Application::$app->db->deleteGalleryCommentKey($id);
        Application::$app->db->deleteGallery($id);
    }
}