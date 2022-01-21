<?php

namespace app\controllers;

use app\models\User;
use app\models\Image;
use Twig\Environment;
use app\models\Gallery;
use app\core\lib\Controller;

class HomeController extends Controller
{
    protected Environment $view;
    protected Image $images;
    protected Gallery $galleries;
    protected User $user;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->images = new Image();
        $this->galleries = new Gallery();
        $this->user = new User();
    }

    public function index()
    {
        $imageContent = $this->images->get();
        $galleryContent = $this->galleries->get();

        return $this->view->render('home.html', [
            'title' => 'Home Page', 
            'imageContent' => $imageContent,
            'galleryContent' => $galleryContent,
            'userContent' => $this->user
        ]);
    }
}