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
        $this->images->restrictPage($this->images);
        $imageContent = $this->images->get();

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
        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }

        $data = Application::$app->request->getData();

        if(!empty($data))
        {
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
                        if(isset($data['submit']))
                        {  
                            $this->images->editImage($id, $data);
                        }

                        if(isset($data['delete']))
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
                        $this->images->editImageByModerator($id, $data);
                    }
                }

                if($this->user->isAdmin(Application::$app->session->getSession('user')) && !$this->user->isYourImage($id))
                {
                    if(isset($data['submit']))
                    {
                        $this->images->editImageByAdmin($id, $data);
                    }
                }
            }
        }

        $imageContent = $this->images->details($id);
        $commentContent = $this->images->getComments($id);
        
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
        if(!key_exists('id', $_GET) || !is_numeric($id) || empty($_GET['id']))
        {
            throw new NotFoundException();
        }

        $this->images->restrictUserPage($this->images, $id);

        $imageContent = $this->images->getUserImages($id);
        $userContent = $this->user->get($id);
        $userData = $this->user->get($id);

        return $this->view->render('user_photos.html', [
            'title' => 'Photos of ' . ucwords($userData[0]['username']),
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