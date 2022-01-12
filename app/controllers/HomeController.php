<?php

namespace app\controllers;

use app\models\User;
use app\models\Image;
use app\models\Gallery;
use app\core\Controller;

class HomeController extends Controller
{
    protected $view;
    protected User $user;
    protected Image $images;
    protected Gallery $galleries;

    public function __construct($view)
    {
        $this->view = $view;
        $this->user = new User();
        $this->images = new Image();
        $this->galleries = new Gallery();
    }

    public function index()
    {
        return $this->view->render('home.html', ['title' => 'Home Page']);
    }
}