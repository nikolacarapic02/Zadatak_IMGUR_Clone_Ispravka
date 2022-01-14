<?php

namespace app\controllers;

use app\core\Application;
use app\core\lib\Controller;
use app\exceptions\ForbidenException;
use app\models\User;
use Twig\Environment;

class ModeratorLoggingController extends Controller
{
    protected Environment $view;
    protected User $user;

    public function __construct(Environment $view)
    {
        $this->view = $view;
        $this->user = new User();
    }

    public function index()
    {
        if(Application::$app->isGuest())
        {
            throw new ForbidenException();
        }
        else
        {
            if(!$this->user->isAdmin(Application::$app->session->getSession('user')))
            {
                throw new ForbidenException();
            }
        }
        
        return $this->view->render('moderator_logging.html', [
            'title' => 'Moderator Logging',
            'userContent' => $this->user
        ]);
    }
}