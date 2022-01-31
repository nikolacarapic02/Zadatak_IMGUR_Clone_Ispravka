<?php

namespace app\models;

use DateTime;
use app\core\lib\Model;
use app\core\Application;
use app\core\lib\classes\AmericanExpress;
use app\core\lib\classes\Crypto;
use app\core\lib\classes\MasterCard;
use app\core\lib\classes\PayPal;
use app\core\lib\classes\VisaCard;
use app\exceptions\NotFoundException;
use app\core\lib\interfaces\SubscriptionInterface;
use app\core\lib\PaymentAdapter;

class User extends Model implements SubscriptionInterface
{
    private array $user = [];
    private static $model;
    private array $plan = [];
    
    public function __construct()
    {
        self::$model = $this;
        parent::__construct(self::$model);
    }

    public function register(array $attributes)
    {
        Application::$app->db->registerUser($attributes);
    }

    public function login(array $attributes)
    {
        return Application::$app->db->loginUser($attributes);
    }

    public function logout()
    {
        Application::$app->session->unsetSession('user');
        $this->redis->clearFromHash('/profile', 'user');
    }

    public function get($id)
    {
        $this->user = Application::$app->db->getUser($id);

        return $this->user;
    }

    public function getAll()
    {
        return Application::$app->db->getAllUsers();
    }


    public function isModerator($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['role'] == 'moderator')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isAdmin($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['role'] == 'admin')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isYourImage($id)
    {
        $image = Application::$app->db->getSingleImageByIdWithoutRule($id);

        if(!empty($image))
        {
            if($image[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function isYourGallery($id)
    {
        $gallery = Application::$app->db->getSingleGalleryWithoutRule($id);

        if(!empty($gallery))
        {
            if($gallery[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
            else
            {
                return false;
            }
        }
    }

    public function isYourGalleryName($name)
    {
        $galleryId = Application::$app->db->getYourGalleryByName($name, Application::$app->session->getSession('user'));

        if(!empty($galleryId))
        {
            $gallery = Application::$app->db->getSingleGalleryWithoutRule($galleryId[0]['id']); 

            if($gallery[0]['user_id'] == Application::$app->session->getSession('user'))
            {
                return true;
            }
        }
        else
        {
            return false;
        }
    }

    public function isYourProfile($id)
    {
        $instance = new User();
        $user = $instance->get($id);

        if(empty($user))
        {
            throw new NotFoundException();
        }

        if($user[0]['id'] == Application::$app->session->getSession('user'))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isBanned($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['status'] == 'inactive')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isActive($id)
    {
        $this->user = Application::$app->db->getUser($id);

        if($this->user[0]['status'] == 'active')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function profileDetails($id)
    {
        if($this->redis->isCached('/profile', 'user'))
        {
            $this->user = $this->redis->getCachedUser();
        }
        else
        {
            $this->user = Application::$app->db->getUser($id);
            $this->redis->cacheUser($this->user, 120);
        }

        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        return $this->user;
    }

    public function otherProfileDetails($id)
    {
        $this->user = Application::$app->db->getUser($id);
        
        if(empty($this->user))
        {
            throw new NotFoundException();
        }

        return $this->user;
    }

    public function changeUserStatus($id, $status)
    {
        if($status == 1)
        {
            $status = 'active';
        }
        else
        {
            $status = 'inactive';
        }
        
        Application::$app->db->changeUserStatus($id, $status);
    }

    public function changeUserRole($id, $role)
    {
        if($role == 1)
        {
            $role = 'user';
        }
        
        if($role == 2)
        {
            $role = 'moderator';
        }

        if($role == 3)
        {
            $role = 'admin';
        }

        Application::$app->db->changeUserRole($id, $role);
    }

    public function getModeratorLogging()
    {
        $content = Application::$app->db->getModeratorLogging();

        return $content;
    }


    // Subscription

    public function subscribe(array $attributes)
    {
        $this->pay($attributes);

        Application::$app->db->subscribeToPlan(Application::$app->session->getSession('user'), $attributes);
    }

    public function upgrade(array $attributes)
    {
        $this->pay($attributes);

        Application::$app->db->upgradeExistingPlan(Application::$app->session->getSession('user'), $attributes);
    }

    public function pay(array $attributes)
    {
        if($attributes['method'] === 'paypal')
        {
            $paypal = new PayPal();
            $paypal->checkAccount($attributes['paypal_mail']);
            $paypal->makePayment();
        }
        else if($attributes['method'] === 'crypto')
        {
            $crypto = new Crypto();
            $crypto->checkAccount($attributes['crypto_mail']);
            $crypto->makePayment();
        }
        else
        {
            if($attributes['card_type'] === 'visa')
            {
                $card = new VisaCard();
            }
            else if($attributes['card_type'] === 'mastercard')
            {
                $card = new MasterCard();
            }
            else
            {
                $card = new AmericanExpress();
            }

            $payment = new PaymentAdapter($card, $attributes);
            $payment->checkPayment($attributes);
            $payment->makePayment();
        }
    }

    public function cancelSubscription($user_id)
    {
        Application::$app->db->cancelSubscriptionForUser($user_id);
    }
    
    public function getPlan($user_id)
    {
        $value = Application::$app->db->getPlanInfo($user_id);
 
        if(!empty($value))
        {
            if($value[0]['status'] === 'active')
            {
                $this->plan = $value;

                return $this->plan;
            }
        }
        else
        {
            $this->plan = [array(
                'plan' => 'free'
            )];
            
            return $this->plan;
        }
    }

    public function isPlanFree($plan)
    {
        if($plan[0]['plan'] === 'free')
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getAllPlans($user_id)
    {
        return Application::$app->db->getAllPlansInfo($user_id);
    }

    public function changePlanStatus($plan_id, $status)
    {
        Application::$app->db->setPlanStatus($plan_id, $status);
    }

    public function userLastExpiredPlan($user_id)
    {
        return Application::$app->db->checkLastExpiredPlan($user_id);
    }

    public function checkPlanStatus()
    {
        if(!$this->isPlanFree($this->plan))
        {
            if(date("Y-m-d H:i:s") > date("Y-m-d H:i:s", strtotime($this->plan[0]['expire_time'])))
            {
                return false;
            }
            else
            {
                return true;
            }
        }
        else
        {
            return true;
        }
    }

    public function checkSubscriptionRights($user_id)
    {
        if($this->isPlanFree($this->plan))
        {
            $value = $this->userLastExpiredPlan($user_id);

            if(!is_null($value[0]['expire_time']))
            {
                $first_date = $value[0]['expire_time'];
                $last_date = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($first_date)));
                $countOfImages = Application::$app->db->getCountOfCreatedImages($user_id, $first_date, $last_date);

                if($countOfImages[0]['num'] >= 5)
                {
                    return true;
                }
            }
            else
            {
                $first_date = date('Y-m-01 H:i:s');
                $date = new DateTime('now');
                $date->modify('last day of this month');
                $last_date = $date->format('Y-m-d H:i:s');
                $countOfImages = Application::$app->db->getCountOfCreatedImages($user_id, $first_date, $last_date);

                if($countOfImages[0]['num'] >= 5)
                {
                    return true;
                }
            }
        }
        else
        {
            $first_date = $this->plan[0]['start_time'];
            $last_date = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($first_date)));
            $countOfImages = Application::$app->db->getCountOfCreatedImages($user_id, $first_date, $last_date);

            if($this->plan[0]['plan'] == '1 month')
            {
                if($countOfImages[0]['num'] >= 20)
                {
                    return true;
                }
            }
            else if($this->plan[0]['plan'] == '6 months')
            {
                if($countOfImages[0]['num'] >= 30)
                {
                    return true;
                }
            }
            else if($this->plan[0]['plan'] == '12 months')
            {
                if($countOfImages[0]['num'] >= 50)
                {
                    return true;
                }
            }
        }
    }

    public function checkUserHavePendingPlan($user_id)
    {
        $value = Application::$app->db->getPendingPlanInfo($user_id);
        
        if(!empty($value))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function checkDataValidity($plan)
    {
        $value = Application::$app->db->checkDataValidityForPayment($plan[0]['id']);

        if(empty($value))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function isPlanCanceled()
    {
        if(!$this->isPlanFree($this->plan))
        {
            if($this->plan[0]['cancel'] === 1)
            {
                return true;
            }
        }
    }

    public function renewalSubscription($user_id, $planInfo)
    {
        if(!empty($planInfo))
        {
            $paymentInfo = Application::$app->db->getPaymentInfo($planInfo['id']);

            $this->pay($paymentInfo[0]);

            Application::$app->db->renewalPlan($user_id, $planInfo, $paymentInfo[0]);
        }
    }
}
