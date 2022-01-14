<?php

namespace app\controllers;

use app\models\User;
use app\models\Image;
use Twig\Environment;
use app\models\Gallery;
use app\core\Application;
use app\core\lib\Controller;
use app\exceptions\ForbidenException;
use app\exceptions\NotFoundException;

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
        if(Application::$app->isGuest())
        {
            throw new ForbidenException();
        }
        
        return $this->view->render('profile.html', [
            'title' => 'Profile',
            'userContent' => $this->user,
            'imageContent' => $this->images,
            'galleryContent' => $this->galleries,
            'userId' => Application::$app->session->getSession('user'),
        ]); 
    }

    public function create()
    {
        $data = Application::$app->request->getData();

        if(isset($data['submitGallery']))
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
                $this->galleries->createGallery($data['name'], $data['gallery_slug'], $data['description'], $registeredUser[0]['id']);

                return $this->view->render('profile.html', [
                    'title' => 'Profile',
                    'userContent' => $this->user,
                    'imageContent' => $this->images,
                    'galleryContent' => $this->galleries,
                    'userId' => Application::$app->session->getSession('user')
                ]);
            }
        }

        if(isset($data['submitImage']))
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
                $this->images->createImage($_FILES['file'], $data['image_name'], $data['image_slug'], $data['gallery_name'], $registeredUser[0]['id']);

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

    public function otherProfile($id)
    {
        $data = Application::$app->request->getData();
        
        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }
        
        if(Application::$app->session->getSession('user'))
        {
            if(key_exists('status', $data))
            {
                $this->user->changeUserStatus($id, $data['status']);
            }

            if(key_exists('role', $data))
            {
                $this->user->changeUserRole($id, $data['role']);
            }
        }
        
        return $this->view->render('other_profile.html', [
            'title' => 'User Profile',
            'userContent' => $this->user,
            'imageContent' => $this->images,
            'galleryContent' => $this->galleries,
            'id' => $id
        ]);
    }
}