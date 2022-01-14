<?php

namespace app\controllers;

use app\models\Image;
use app\models\Gallery;
use app\core\lib\Controller;
use Twig\Environment;

class HomeController extends Controller
{
    protected Environment $view;
    protected Image $images;
    protected Gallery $galleries;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->images = new Image();
        $this->galleries = new Gallery();
    }

    public function index()
    {
        return $this->view->render('home.html', [
            'title' => 'Home Page', 
            'imageContent' => $this->images, 
            'galleryContent' => $this->galleries
        ]);
    }
}