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
        $data = Application::$app->request->getData();

        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }

        if(!empty($data['comment']))
        {
            $this->galleries->createComment($data['comment'], $id);
        }

        if(!Application::$app->isGuest())
        {
            if($this->user->isYourGallery($id))
            {
                if(!empty($data))
                {
                    if(!empty($data['new_name']) && !empty($data['slug']) && !empty($data['description']))
                    {
                        $this->galleries->editGallery($id, $data['new_name'], $data['slug'], $data['description']);
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

                        if(!empty($data['description']))
                        {
                            $description = $data['description'];
                        }
                        else
                        {
                            $description = '';
                        }

                        $this->galleries->editGallery($id, $newName, $slug, $description);
                    }

                    if(!empty($data['delete']))
                    {
                        $this->galleries->deleteGallery($id);
                        Application::$app->response->redirectToAnotherPage('/');
                    }
                }
            }

            if($this->user->isModerator(Application::$app->session->getSession('user')) && !$this->user->isYourGallery($id))
            {
                if(isset($data['submit']))
                {
                    if(key_exists('nsfw',$data) || key_exists('hidden', $data))
                    {
                        if(!empty($data['nsfw']) && !empty($data['hidden']))
                        {
                            $this->galleries->editGalleryByModerator($data['nsfw'], $data['hidden'], $id);
                        }
                        else
                        {
                            if(!empty($data['nsfw']))
                            {
                                $this->galleries->editGalleryByModerator($data['nsfw'], '', $id);
                            }
                        
                            if(!empty($data['hidden']))
                            {
                                $this->galleries->editGalleryByModerator('', $data['hidden'], $id);
                            }
                        }
                    }
                    else
                    {
                        $this->galleries->editGalleryByModerator('', '', $id);
                    }
                }
            }

            if($this->user->isAdmin(Application::$app->session->getSession('user')) && !$this->user->isYourGallery($id))
            {
                if(isset($data['submit']))
                {
                    if(key_exists('name', $data) || key_exists('slug', $data) || key_exists('nsfw', $data) || key_exists('hidden', $data) || key_exists('description', $data))
                    {
                        if(!empty($data['name']) && !empty($data['slug']) && !empty($data['nsfw']) && !empty($data['hidden']) && !empty($data['description']))
                        {
                            $this->galleries->editGalleryByAdmin($data['name'], $data['slug'], $data['nsfw'], $data['hidden'], $data['description'], $id);
                        }
                        else
                        {
                            if(!empty($data['name']))
                            {
                                $name = $data['name'];
                            }
                            else
                            {
                                $name = '';
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
                        
                            if(!empty($data['description']))
                            {
                                $description = $data['description'];
                            }
                            else
                            {
                                $description = '';
                            }
                        
                            $this->galleries->editGalleryByAdmin($name, $slug, $nsfw, $hidden, $description, $id);
                        }
                    }
                    else
                    {
                        $this->galleries->editGalleryByAdmin('', '', '', '', '', $id);
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

    public function userGalleries($id)
    {
        if(!key_exists('id', $_GET) || !is_numeric($id))
        {
            throw new NotFoundException();
        }
        
        return $this->view->render('user_galleries.html', [
            'title' => 'User Galleries',
            'galleryContent' => $this->galleries,
            'numOfPages' => $this->galleries->numOfUserPages($id),
            'page' => $this->galleries->page,
            'pageNumPre' => $this->galleries->page - 1,
            'pageNumNext' => $this->galleries->page + 1,
            'pageNum' => $this->galleries->page,
            'id' => $id
        ]);
    }
}