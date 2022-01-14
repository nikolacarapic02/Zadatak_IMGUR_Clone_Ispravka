<?php

namespace app\controllers;

use app\core\Application;
use app\core\lib\Controller;
use app\models\Gallery;
use app\models\Image;
use app\models\User;
use Twig\Environment;

class ProfileController extends Controller
{
    protected Environment $view;
    protected User $user;
    protected Image $images;
    protected Gallery $galleries;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->user = new User();
        $this->images = new Image();
        $this->galleries = new Gallery();
    }

    public function index()
    {
        return $this->view->render('profile.html', [
            'title' => 'Profile',
            'userContent' => $this->user,
            'imageContent' => $this->images,
            'galleryContent' => $this->galleries,
            'userId' => Application::$app->session->getSession('user'),
            'values' =>  ''
        ]); 
    }

    public function create()
    {
        if(isset($_POST['submitGallery']))
        {
            Application::$app->validation('gallery_create');

            if(Application::$app->hasErrors())
            {
                return $this->view->render('profile.html', [
                    'title' => 'Profile',
                    'userContent' => $this->user,
                    'imageContent' => $this->images,
                    'galleryContent' => $this->galleries,
                    'userId' => Application::$app->session->getSession('user'),
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData()
                ]);
            }
            else
            {
                $registeredUser = $this->user->get(Application::$app->session->getSession('user'));
                $this->galleries->createGallery($_POST['name'], $_POST['slug'], $_POST['description'], $registeredUser[0]['id']);

                return $this->view->render('profile.html', [
                    'title' => 'Profile',
                    'userContent' => $this->user,
                    'imageContent' => $this->images,
                    'galleryContent' => $this->galleries,
                    'userId' => Application::$app->session->getSession('user')
                ]);
            }
        }

        if(isset($_POST['submitImage']))
        {
            Application::$app->validation('image_create');

            if(Application::$app->hasErrors())
            {
                return $this->view->render('profile.html', [
                    'title' => 'Profile',
                    'userContent' => $this->user,
                    'imageContent' => $this->images,
                    'galleryContent' => $this->galleries,
                    'userId' => Application::$app->session->getSession('user'),
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData()
                ]);
            }
            else
            {
                $registeredUser = $this->user->get(Application::$app->session->getSession('user'));
                $this->images->createImage($_FILES['file'], $_POST['slug'], $_POST['gallery_name'], $registeredUser[0]['id']);

                return $this->view->render('profile.html', [
                    'title' => 'Profile',
                    'userContent' => $this->user,
                    'imageContent' => $this->images,
                    'galleryContent' => $this->galleries,
                    'userId' => Application::$app->session->getSession('user')
                ]);
            }
        }
    }
}