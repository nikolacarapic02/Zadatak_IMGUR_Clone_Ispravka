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
        $data = Application::$app->request->getData();

        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }

        if(!empty($data['comment']))
        {
            $this->images->createComment($data['comment'], $id);
        }

        if(!Application::$app->isGuest())
        {
            if($this->user->isYourImage($id))
            {
                if(!empty($data))
                {
                    if(!empty($data['new_name']) && !empty($data['slug']))
                    {
                        $this->images->editImage($id, $data['new_name'], $data['slug']);
                    }
                    else
                    {
                        if(!empty($data['new_name']))
                        {
                            $newName = $data['new_name'];
                        }
                        else
                        {
                            $newName = '';
                        }

                        if(!empty($data['slug']))
                        {
                            $slug = $data['slug'];
                        }
                        else
                        {
                            $slug = '';
                        }
                        
                        $this->images->editImage($id, $newName, $slug);
                    }

                    if(!empty($data['delete']))
                    {
                        $this->images->deleteImage($id);
                        Application::$app->response->redirectToAnotherPage('/');
                    }
                }
            }

            if($this->user->isModerator(Application::$app->session->getSession('user')) && !$this->user->isYourImage($id))
            {
                if(isset($data['submit']))
                {
                    if(key_exists('nsfw', $data) || key_exists('hidden', $data))
                    {
                        if(!empty($data['nsfw']) && !empty($data['hidden']))
                        {
                            $this->images->editImageByModerator($data['nsfw'], $data['hidden'], $id);
                        }
                        else
                        {
                            if(!empty($data['nsfw']))
                            {
                                $this->images->editImageByModerator($data['nsfw'], '', $id);
                            }
                        
                            if(!empty($data['hidden']))
                            {
                                $this->images->editImageByModerator('', $data['hidden'], $id);
                            }
                        }
                    }
                    else
                    {
                        $this->images->editImageByModerator('', '', $id);
                    }
                }
            }

            if($this->user->isAdmin(Application::$app->session->getSession('user')) && !$this->user->isYourImage($id))
            {
                if(isset($data['submit']))
                {
                    if(key_exists('file_name', $data) || key_exists('slug', $data) || key_exists('nsfw', $data) || key_exists('hidden', $data))
                    {
                        if(!empty($data['file_name']) && !empty($data['slug']) && !empty($data['nsfw']) && !empty($data['hidden']))
                        {
                            $this->images->editImageByAdmin($data['file_name'], $data['slug'], $data['nsfw'], $data['hidden'], $id);
                        }
                        else
                        {
                            if(!empty($data['file_name']))
                            {
                                $fileName = $data['file_name'];
                            }
                            else
                            {
                                $fileName = '';
                            }
                        
                            if(!empty($data['slug']))
                            {
                                $slug = $data['slug'];
                            }
                            else
                            {
                                $slug = '';
                            }
                        
                            if(!empty($data['nsfw']))
                            {
                                $nsfw = $data['nsfw'];
                            }
                            else
                            {
                                $nsfw = '';
                            }
                        
                            if(!empty($data['hidden']))
                            {
                                $hidden = $data['hidden'];
                            }
                            else
                            {
                                $hidden = '';
                            }
                        
                            $this->images->editImageByAdmin($fileName, $slug, $nsfw, $hidden, $id);
                        }
                    }
                    else
                    {
                        $this->images->editImageByAdmin('', '', '', '', $id);
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

    public function userPhotos($id)
    {
        if(!key_exists('id', $_GET) || !is_numeric($id))
        {
            throw new NotFoundException();
        }

        return $this->view->render('user_photos.html', [
            'title' => 'User Photos',
            'imageContent' => $this->images,
            'numOfPages' => $this->images->numOfUserPages($id),
            'page' => $this->images->page,
            'pageNumPre' => $this->images->page - 1,
            'pageNumNext' => $this->images->page + 1,
            'pageNum' => $this->images->page,
            'id' => $id
        ]);
    }
}