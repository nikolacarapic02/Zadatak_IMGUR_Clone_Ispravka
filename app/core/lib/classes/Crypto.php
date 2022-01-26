<?php

namespace app\core\lib\classes;

use app\core\Application;
use app\core\lib\interfaces\CryptoInterface;
use app\core\lib\interfaces\PaymentInterface;

class Crypto implements CryptoInterface, PaymentInterface
{
    private bool $accValidity = true;

    public function checkAccount(string $email)
    {
        if(!$email)
        {
            Application::$app->db->changeCryptoDataValidity($email);
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