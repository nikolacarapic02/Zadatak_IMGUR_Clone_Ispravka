<?php

namespace app\controllers;

use app\models\User;
use app\models\Image;
use Twig\Environment;
use app\core\Application;
use app\core\lib\Controller;
use app\exceptions\NotFoundException;

class PhotosController extends Controller
{
    protected Environment $view;
    protected Image $images;
    protected User $user;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->images = new Image();
        $this->user = new User();
    }

    public function index()
    {
        return $this->view->render('photos.html', [
            'title' => 'Photos', 
            'imageContent' => $this->images,
            'numOfPages' => $this->images->numOfPages(),
            'page' => $this->images->page,
            'pageNumPre' => $this->images->page - 1,
            'pageNumNext' => $this->images->page + 1,
            'pageNum' => $this->images->page,
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
            $this->images->createComment($_POST['comment'], $_GET['id']);
        }

        if(Application::$app->session->getSession('user'))
        {
            $user = new User();

            if($user->isYourImage($_GET['id']))
            {
                if(!empty($_POST))
                {
                    if(!empty($_POST['new_name']) && !empty($_POST['slug']))
                    {
                        $this->images->editImage($_GET['id'], $_POST['new_name'], $_POST['slug']);
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
                        
                        $this->images->editImage($_GET['id'], $newName, $slug);
                    }

                    if(!empty($_POST['delete']))
                    {
                        $this->images->deleteImage($_GET['id']);
                    }
                }
            }

            if($user->isModerator(Application::$app->session->getSession('user')) && !$user->isYourImage($_GET['id']))
            {
                if(isset($_POST['submit']))
                {
                    if(key_exists('nsfw', $_POST) || key_exists('hidden', $_POST))
                    {
                        if(!empty($_POST['nsfw']) && !empty($_POST['hidden']))
                        {
                            $this->images->editImageByModerator($_POST['nsfw'], $_POST['hidden'], $_GET['id']);
                        }
                        else
                        {
                            if(!empty($_POST['nsfw']))
                            {
                                $this->images->editImageByModerator($_POST['nsfw'], '', $_GET['id']);
                            }
                        
                            if(!empty($_POST['hidden']))
                            {
                                $this->images->editImageByModerator('', $_POST['hidden'], $_GET['id']);
                            }
                        }
                    }
                    else
                    {
                        $this->images->editImageByModerator('', '', $_GET['id']);
                    }
                }
            }

            if($user->isAdmin(Application::$app->session->getSession('user')) && !$user->isYourImage($_GET['id']))
            {
                if(isset($_POST['submit']))
                {
                    if(key_exists('file_name', $_POST) || key_exists('slug', $_POST) || key_exists('nsfw', $_POST) || key_exists('hidden', $_POST))
                    {
                        if(!empty($_POST['file_name']) && !empty($_POST['slug']) && !empty($_POST['nsfw']) && !empty($_POST['hidden']))
                        {
                            $this->images->editImageByAdmin($_POST['file_name'], $_POST['slug'], $_POST['nsfw'], $_POST['hidden'], $_GET['id']);
                        }
                        else
                        {
                            if(!empty($_POST['file_name']))
                            {
                                $fileName = $_POST['file_name'];
                            }
                            else
                            {
                                $fileName = '';
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
                        
                            $this->images->editImageByAdmin($fileName, $slug, $nsfw, $hidden, $_GET['id']);
                        }
                    }
                    else
                    {
                        $this->images->editImageByAdmin('', '', '', '', $_GET['id']);
                    }
                }
            }
        }

        return $this->view->render('photo_details.html', [
            'title' => 'Photo Details',
            'id' => $id,
            'imageContent' => $this->images,
            'user' => $this->user
        ]);
    }
}