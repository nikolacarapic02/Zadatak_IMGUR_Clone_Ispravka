<?php

namespace app\core\lib\classes;

use app\core\Application;
use app\core\lib\interfaces\PaymentInterface;
use app\core\lib\interfaces\PayPalInterface;

class PayPal implements PayPalInterface, PaymentInterface
{
    private bool $accValidity = true;

    public function checkAccount(string $email)
    {
        if(!$email)
        {
            Application::$app->db->changePayPalDataValidity($email);
            $this->accValidity = false;
        }
    }

    public function checkPayment()
    {
        if($this->accValidity)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function makePayment()
    {
        if($this->checkPayment)
        {
            return true;
        }
    }
}