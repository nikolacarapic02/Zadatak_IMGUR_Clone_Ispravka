<?php

namespace app\controllers;

use app\core\Application;
use app\models\User;
use Twig\Environment;
use app\core\lib\Controller;

class PlanController extends Controller
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
        if(!Application::$app->isGuest())
        {
            $plan = $this->user->getPlan(Application::$app->session->getSession('user'));
            $pendingPlan = $this->user->checkUserHavePendingPlan(Application::$app->session->getSession('user'));
        }
        else
        {
            $plan = null;
            $pendingPlan = null;
        }

        return $this->view->render('plans.html', [
            'title' => 'Plan Pricing',
            'uri' => '/subscription',
            'plan' => $plan,
            'pendingPlan' => $pendingPlan
        ]);
    }
}