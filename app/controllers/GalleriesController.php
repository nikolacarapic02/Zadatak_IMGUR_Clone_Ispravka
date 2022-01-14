<?php

namespace app\controllers;

use app\models\User;
use Twig\Environment;
use app\models\Gallery;
use app\core\Application;
use app\core\lib\Controller;
use app\exceptions\NotFoundException;

class GalleriesController extends Controller
{
    protected Environment $view;
    protected Gallery $galleries;
    protected User $user;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->galleries = new Gallery();
        $this->user = new User();
    }

    public function index()
    {
        return $this->view->render('galleries.html', [
            'title' => 'Galleries', 
            'galleryContent' => $this->galleries,
            'numOfPages' => $this->galleries->numOfPages(),
            'page' => $this->galleries->page,
            'pageNumPre' => $this->galleries->page - 1,
            'pageNumNext' => $this->galleries->page + 1,
            'pageNum' => $this->galleries->page,
        ]);
    }

    public function details($id)
    {
        if(!key_exists('id', $_GET) || !is_numeric($_GET['id']))
        {
            throw new NotFoundException();
        }

        if(!empty($_POST['comment']))
        {
            $this->galleries->createComment($_POST['comment'], $_GET['id']);
        }

        if(Application::$app->session->getSession('user'))
        {
            $user = new User();

            if($user->isYourGallery($_GET['id']))
            {
                if(!empty($_POST))
                {
                    if(!empty($_POST['new_name']) && !empty($_POST['slug']) && !empty($_POST['description']))
                    {
                        $this->galleries->editGallery($_GET['id'], $_POST['new_name'], $_POST['slug'], $_POST['description']);
                    }
                    else
                    {
                        if(!empty($_POST['new_name']))
                        {
                            $newName = $_POST['new_name'];
                        }
                        else
                        {
                            $newName = '';
                        }

                        if(!empty($_POST['slug']))
                        {
                            $slug = $_POST['slug'];
                        }
                        else
                        {
                            $slug = '';
                        }

                        if(!empty($_POST['description']))
                        {
                            $description = $_POST['description'];
                        }
                        else
                        {
                            $description = '';
                        }

                        $this->galleries->editGallery($_GET['id'], $newName, $slug, $description);
                    }

                    if(!empty($_POST['delete']))
                    {
                        $this->galleries->deleteGallery($_GET['id']);
                    }
                }
            }

            if($user->isModerator(Application::$app->session->getSession('user')) && !$user->isYourGallery($_GET['id']))
            {
                if(isset($_POST['submit']))
                {
                    if(key_exists('nsfw',$_POST) || key_exists('hidden', $_POST))
                    {
                        if(!empty($_POST['nsfw']) && !empty($_POST['hidden']))
                        {
                            $this->galleries->editGalleryByModerator($_POST['nsfw'], $_POST['hidden'], $_GET['id']);
                        }
                        else
                        {
                            if(!empty($_POST['nsfw']))
                            {
                                $this->galleries->editGalleryByModerator($_POST['nsfw'], '', $_GET['id']);
                            }
                        
                            if(!empty($_POST['hidden']))
                            {
                                $this->galleries->editGalleryByModerator('', $_POST['hidden'], $_GET['id']);
                            }
                        }
                    }
                    else
                    {
                        $this->galleries->editGalleryByModerator('', '', $_GET['id']);
                    }
                }
            }

            if($user->isAdmin(Application::$app->session->getSession('user')) && !$user->isYourGallery($_GET['id']))
            {
                if(isset($_POST['submit']))
                {
                    if(key_exists('name', $_POST) || key_exists('slug', $_POST) || key_exists('nsfw', $_POST) || key_exists('hidden', $_POST) || key_exists('description', $_POST))
                    {
                        if(!empty($_POST['name']) && !empty($_POST['slug']) && !empty($_POST['nsfw']) && !empty($_POST['hidden']) && !empty($_POST['description']))
                        {
                            $this->galleries->editGalleryByAdmin($_POST['name'], $_POST['slug'], $_POST['nsfw'], $_POST['hidden'], $_POST['description'], $_GET['id']);
                        }
                        else
                        {
                            if(!empty($_POST['name']))
                            {
                                $name = $_POST['name'];
                            }
                            else
                            {
                                $name = '';
                            }
                        
                            if(!empty($_POST['slug']))
                            {
                                $slug = $_POST['slug'];
                            }
                            else
                            {
                                $slug = '';
                            }
                        
                            if(!empty($_POST['nsfw']))
                            {
                                $nsfw = $_POST['nsfw'];
                            }
                            else
                            {
                                $nsfw = '';
                            }
                        
                            if(!empty($_POST['hidden']))
                            {
                                $hidden = $_POST['hidden'];
                            }
                            else
                            {
                                $hidden = '';
                            }
                        
                            if(!empty($_POST['description']))
                            {
                                $description = $_POST['description'];
                            }
                            else
                            {
                                $description = '';
                            }
                        
                            $this->galleries->editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $_GET['id']);
                        }
                    }
                    else
                    {
                        $this->galleries->editGalleryByAdmin('', '', '', '', '', $_GET['id']);
                    }
                }
            }
        }


        return $this->view->render('gallery_details.html', [
            'title' => 'Gallery Details', 
            'galleryContent' => $this->galleries,
            'id' => $id,
            'user' => $this->user
        ]);
    }
}