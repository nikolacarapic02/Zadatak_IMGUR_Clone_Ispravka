<?php 

namespace app\controllers;

use app\core\lib\Controller;
use Twig\Environment;

class AboutController extends Controller
{
    protected Environment $view;
    
    public function __construct(Environment $view)
    {
        $this->view = $view;
    }

    public function index()
    {
        return $this->view->render('about.html', ['title' => 'About']);
    }
}