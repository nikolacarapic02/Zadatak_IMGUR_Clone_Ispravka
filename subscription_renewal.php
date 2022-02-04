<?php
use Dotenv\Dotenv;
use app\models\User;
use app\core\Application;

require_once __DIR__.'/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$config = [
    'db' => [
        'dsn' => $_ENV['DB_DSN'],
        'user' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASSWORD']
    ]
];

$app = new Application($config);
$user = new User();

$allUsers = $user->getAll();

for($i = 0; $i < count($allUsers); $i++)
{
    $plan = $user->getPlan($allUsers[$i]['id']);

    if(!$user->checkPlanStatus())
    {
        if($user->checkUserHavePendingPlan($allUsers[$i]['id']))
        {
            $user->changePlanStatus($plan[0]['id'], 'inactive');

            $newPlan = $app->db->getPendingPlanInfo($allUsers[$i]['id']);
            $user->changePlanStatus($newPlan[0]['id'], 'active');
        }
        else if($user->isPlanCanceled())
        {
            $user->changePlanStatus($plan[0]['id'], 'inactive');
        }
        else if(!$user->checkDataValidity($plan[0]['id']))
        {   
            $user->changePlanStatus($plan[0]['id'], 'inactive');
        }
        else
        {
            $user->changePlanStatus($plan[0]['id'], 'inactive');
            $user->renewalSubscription($allUsers[$i]['id'], $plan[0]);
        }
    }
}

