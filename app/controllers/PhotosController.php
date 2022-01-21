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
    protected string $uri;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->images = new Image();
        $this->user = new User();
        $this->uri = Application::$app->request->getPath();
    }

    public function index()
    {
        $imageContent = $this->images->get();
        $this->images->restrictPage($this->images);

        return $this->view->render('photos.html', [
            'title' => 'Photos', 
            'imageContent' => $imageContent,
            'user' => $this->user,
            'numOfPages' => $this->images->numOfPages($this->images),
            'page' => $this->images->getPage(),
            'pageNumPre' => $this->images->getPage() - 1,
            'pageNumNext' => $this->images->getPage() + 1,
            'pageNum' => $this->images->getPage(),
        ]);
    }

    public function details($id)
    {
        $data = Application::$app->request->getData();
        $imageContent = $this->images->details($id);
        $commentContent = $this->images->getComments($id);
        $uriParametars = '?'.$_SERVER['QUERY_STRING'];
    
        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }

        if(!empty($data['comment']))
        {
            $this->images->createComment($data['comment'], $id);
            Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
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
                        Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
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
                        Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
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
                            Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
                        }
                        else
                        {
                            if(!empty($data['nsfw']))
                            {
                                $this->images->editImageByModerator($data['nsfw'], '', $id);
                                Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
                            }
                        
                            if(!empty($data['hidden']))
                            {
                                $this->images->editImageByModerator('', $data['hidden'], $id);
                                Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
                            }
                        }
                    }
                    else
                    {
                        $this->images->editImageByModerator('', '', $id);
                        Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
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
                            Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
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
                            Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
                        }
                    }
                    else
                    {
                        $this->images->editImageByAdmin('', '', '', '', $id);
                        Application::$app->response->redirectToAnotherPage($this->uri . $uriParametars);
                    }
                }
            }
        }

        return $this->view->render('photo_details.html', [
            'title' => 'Photo Details',
            'id' => $id,
            'image' => $this->images,
            'imageContent' => $imageContent,
            'user' => $this->user,
            'filename' => preg_replace('/\\.[^.\\s]{3,4}$/', '', $imageContent[0]['file_name']),
            'format' => strtoupper(substr($imageContent[0]['file_name'], strpos($imageContent[0]['file_name'], ".") + 1)),
            'commentContent' => $commentContent
        ]);
    }

    public function userPhotos($id)
    {
        $imageContent = $this->images->getUserImages($id);
        $userContent = $this->user->get($id);

        if(!key_exists('id', $_GET) || !is_numeric($id))
        {
            throw new NotFoundException();
        }

        $this->images->restrictUserPage($this->images, $id);

        $userData = $this->user->get($id);

        return $this->view->render('user_photos.html', [
            'title' => ucwords($userData[0]['username']) . ' Photos',
            'imageContent' => $imageContent,
            'userContent' => $userContent,
            'numOfPages' => $this->images->numOfUserPages($this->images, $id),
            'page' => $this->images->getPage(),
            'pageNumPre' => $this->images->getPage() - 1,
            'pageNumNext' => $this->images->getPage() + 1,
            'pageNum' => $this->images->getPage(),
            'id' => $id
        ]);
    }
}