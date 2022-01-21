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
    protected string $uri;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->user = new User();
        $this->images = new Image();
        $this->galleries = new Gallery();
        $this->uri = Application::$app->request->getPath();
    }

    public function index()
    {
        if(Application::$app->isGuest())
        {
            throw new ForbidenException();
        }

        $imageContent = $this->images->imagesForProfile(Application::$app->session->getSession('user'));
        $galleryContent = $this->galleries->galleriesForProfile(Application::$app->session->getSession('user'));
        $userContent = $this->user->profileDetails(Application::$app->session->getSession('user'));
        $plan = $this->user->getPlan(Application::$app->session->getSession('user'));
        
        return $this->view->render('profile.html', [
            'title' => 'Your Profile',
            'userContent' => $this->user,
            'imageContent' => $imageContent,
            'userContent' => $userContent,
            'galleryContent' => $galleryContent,
            'userId' => Application::$app->session->getSession('user'),
            'plan' => $plan,
            'planExpire' => strtotime($plan[0]['plan_expire']) ? date("Y-m-d",strtotime($plan[0]['plan_expire'])) : $plan[0]['plan_expire'],
            'planStatus' => $plan[0]['status'] === 0 ? 'inactive' : 'active'
        ]); 
    }

    public function cancel()
    {
        $data = Application::$app->request->getData();

        if(key_exists('cancel', $data))
        {
            $this->user->cancelSubscription(Application::$app->session->getSession('user'));

            Application::$app->response->redirectToAnotherPage($this->uri);
        }
    }

    public function create()
    {
        $data = Application::$app->request->getData();
        $imageContent = $this->images->imagesForProfile(Application::$app->session->getSession('user'));
        $galleryContent = $this->galleries->galleriesForProfile(Application::$app->session->getSession('user'));
        $userContent = $this->user->profileDetails(Application::$app->session->getSession('user'));
        $plan = $this->user->getPlan(Application::$app->session->getSession('user'));

        if(isset($data['submitGallery']))
        {
            Application::$app->validation('gallery_create');

            if(Application::$app->hasErrors())
            {
                return $this->view->render('profile.html', [
                    'title' => 'Your Profile',
                    'userContent' => $this->user,
                    'imageContent' => $imageContent,
                    'userContent' => $userContent,
                    'galleryContent' => $galleryContent,
                    'userId' => Application::$app->session->getSession('user'),
                    'plan' => $plan,
                    'planExpire' => strtotime($plan[0]['plan_expire']) ? date("Y-m-d",strtotime($plan[0]['plan_expire'])) : $plan[0]['plan_expire'],
                    'planStatus' => $plan[0]['status'] === 0 ? 'inactive' : 'active',
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData()
                ]); 
            }
            else
            {
                $registeredUser = $this->user->get(Application::$app->session->getSession('user'));
                $this->galleries->createGallery($data['name'], $data['gallery_slug'], $data['description'], $registeredUser[0]['id']);

                Application::$app->response->redirectToAnotherPage($this->uri);
            }
        }

        if(isset($data['submitImage']))
        {
            Application::$app->validation('image_create');

            if(Application::$app->hasErrors())
            {
                return $this->view->render('profile.html', [
                    'title' => 'Your Profile',
                    'userContent' => $this->user,
                    'imageContent' => $imageContent,
                    'userContent' => $userContent,
                    'galleryContent' => $galleryContent,
                    'userId' => Application::$app->session->getSession('user'),
                    'plan' => $plan,
                    'planExpire' => strtotime($plan[0]['plan_expire']) ? date("Y-m-d",strtotime($plan[0]['plan_expire'])) : $plan[0]['plan_expire'],
                    'planStatus' => $plan[0]['status'] === 0 ? 'inactive' : 'active',
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData()
                ]); 
            }
            else
            {
                $registeredUser = $this->user->get(Application::$app->session->getSession('user'));
                $this->images->createImage($_FILES['file'], $data['image_name'], $data['image_slug'], $data['gallery_name'], $registeredUser[0]['id']);

                Application::$app->response->redirectToAnotherPage($this->uri);
            }
        }
    }

    public function otherProfile($id)
    {
        $data = Application::$app->request->getData();
        $imageContent = $this->images->imagesForProfile($id);
        $galleryContent = $this->galleries->galleriesForProfile($id);
        $userContent = $this->user->otherProfileDetails($id);
        $uriParametars = '?'.$_SERVER['QUERY_STRING'];
        
        if(!key_exists('id', $_GET) || !is_numeric($id) || $id <= 0)
        {
            throw new NotFoundException();
        }
        
        if(Application::$app->session->getSession('user'))
        {
            if(key_exists('status', $data))
            {
                $this->user->changeUserStatus($id, $data['status']);
                Application::$app->response->redirectToAnotherPage('/user_profile' . $uriParametars);
            }

            if(key_exists('role', $data))
            {
                $this->user->changeUserRole($id, $data['role']);
                Application::$app->response->redirectToAnotherPage('/user_profile' . $uriParametars);
            }
        }

        return $this->view->render('other_profile.html', [
            'title' => ucwords($userContent[0]['username']) . ' Profile',
            'userContent' => $userContent,
            'imageContent' => $imageContent,
            'galleryContent' => $galleryContent,
            'user' => $this->user,
            'id' => $id,
        ]);
    }
}