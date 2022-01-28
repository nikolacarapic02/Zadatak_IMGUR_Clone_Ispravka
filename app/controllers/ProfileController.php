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

        $id = Application::$app->session->getSession('user');
        $imageContent = $this->images->imagesForProfile($id);
        $galleryContent = $this->galleries->galleriesForProfile($id);
        $userContent = $this->user->profileDetails($id);
        $plan = $this->user->getPlan($id);
        $allPlans = $this->user->getAllPlans($id);
        $restriction = $this->user->checkSubscriptionRights($id);
        $pendingPlan = $this->user->checkUserHavePendingPlan($id);
        
        return $this->view->render('profile.html', [
            'title' => 'Your Profile',
            'userContent' => $this->user,
            'imageContent' => $imageContent,
            'userContent' => $userContent,
            'galleryContent' => $galleryContent,
            'userId' => $id,
            'plan' => $plan,
            'planExpire' => key_exists('expire_time', $plan[0]) ? (strtotime($plan[0]['expire_time']) ? date("Y-m-d",strtotime($plan[0]['expire_time'])) : $plan[0]['expire_time']) : '',
            'allPlans' => $allPlans,
            'restriction' => $restriction,
            'pendingPlan' => $pendingPlan
        ]); 
    }

    public function create()
    {
        $data = Application::$app->request->getData();
        $id = Application::$app->session->getSession('user');
        $imageContent = $this->images->imagesForProfile($id);
        $galleryContent = $this->galleries->galleriesForProfile($id);
        $userContent = $this->user->profileDetails($id);
        $plan = $this->user->getPlan($id);
        $allPlans = $this->user->getAllPlans($id);
        $pendingPlan = $this->user->checkUserHavePendingPlan($id);

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
                    'planExpire' => key_exists('expire_time', $plan[0]) ? (strtotime($plan[0]['expire_time']) ? date("Y-m-d",strtotime($plan[0]['expire_time'])) : $plan[0]['expire_time']) : '',
                    'allPlans' => $allPlans,
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData(),
                    'pendingPlan' => $pendingPlan
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
                    'planExpire' => key_exists('expire_time', $plan[0]) ? (strtotime($plan[0]['expire_time']) ? date("Y-m-d",strtotime($plan[0]['expire_time'])) : $plan[0]['expire_time']) : '',
                    'allPlans' => $allPlans,
                    'errors' => Application::$app->getErrors(),
                    'values' => Application::$app->request->getData(),
                    'pendingPlan' => $pendingPlan
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

    public function cancel()
    {
        $data = Application::$app->request->getData();

        if(key_exists('cancel', $data))
        {
            $this->user->cancelSubscription(Application::$app->session->getSession('user'));

            Application::$app->response->redirectToAnotherPage($this->uri);
        }
    }

    public function buyOrUpgrade()
    {
        $data = Application::$app->request->getData();

        if(key_exists('buy', $data) || key_exists('upgrade', $data) )
        {
            Application::$app->response->redirectToAnotherPage('/plan_pricing');
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

        $imageContent = $this->images->imagesForProfile($id);
        $galleryContent = $this->galleries->galleriesForProfile($id);
        $userContent = $this->user->otherProfileDetails($id);
        $plan = $this->user->getPlan($id);
        $allPlans = $this->user->getAllPlans($id);

        return $this->view->render('other_profile.html', [
            'title' =>  'Profile of ' . ucwords($userContent[0]['username']),
            'userContent' => $userContent,
            'imageContent' => $imageContent,
            'galleryContent' => $galleryContent,
            'user' => $this->user,
            'plan' => $plan,
            'planExpire' => key_exists('expire_time', $plan[0]) ? (strtotime($plan[0]['expire_time']) ? date("Y-m-d",strtotime($plan[0]['expire_time'])) : $plan[0]['expire_time']) : '',
            'allPlans' => $allPlans,
            'id' => $id,
        ]);
    }
}