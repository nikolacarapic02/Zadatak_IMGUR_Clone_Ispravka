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
    protected string $uri;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->galleries = new Gallery();
        $this->user = new User();
        $this->uri = Application::$app->request->getPath();
    }

    public function index()
    {
        $this->galleries->restrictPage($this->galleries);
        $galleryContent = $this->galleries->get();

        return $this->view->render('galleries.html', [
            'title' => 'Galleries', 
            'galleryContent' => $galleryContent,
            'user' => $this->user,
            'numOfPages' => $this->galleries->numOfPages($this->galleries),
            'page' => $this->galleries->getPage(),
            'pageNumPre' => $this->galleries->getPage() - 1,
            'pageNumNext' => $this->galleries->getPage() + 1,
            'pageNum' => $this->galleries->getPage(),
        ]);
    }

    public function details($id)
    {
        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }

        $data = Application::$app->request->getData();

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
                    if(isset($data['submit']))
                    {
                        $this->galleries->editGallery($id, $data);
                    }

                    if(isset($data['delete']))
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
                    $this->galleries->editGalleryByModerator($id, $data);
                }
            }

            if($this->user->isAdmin(Application::$app->session->getSession('user')) && !$this->user->isYourGallery($id))
            {
                if(isset($data['submit']))
                {
                    $this->galleries->editGalleryByAdmin($id, $data);
                }
            }
        }

        $galleryContent = $this->galleries->details($id);
        $imageContent = $this->galleries->getImagesForGallery($id);
        $commentContent = $this->galleries->getComments($id);

        return $this->view->render('gallery_details.html', [
            'title' => 'Gallery Details', 
            'galleryContent' => $galleryContent,
            'gallery' => $this->galleries,
            'imageContent' => $imageContent,
            'commentContent' => $commentContent,
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

        $this->galleries->restrictUserPage($this->galleries, $id);

        $galleryContent = $this->galleries->getUserGalleries($id);
        $userContent = $this->user->get($id);
        $userData = $this->user->get($id);

        return $this->view->render('user_galleries.html', [
            'title' => 'Galleries of ' . ucwords($userData[0]['username']),
            'galleryContent' => $galleryContent,
            'numOfPages' => $this->galleries->numOfUserPages($this->galleries, $id),
            'page' => $this->galleries->getPage(),
            'pageNumPre' => $this->galleries->getPage() - 1,
            'pageNumNext' => $this->galleries->getPage() + 1,
            'pageNum' => $this->galleries->getPage(),
            'id' => $id,
            'userContent' => $userContent
        ]);
    }
}