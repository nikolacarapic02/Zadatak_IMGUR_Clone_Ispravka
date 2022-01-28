<?php

namespace app\core\lib\classes;

use app\core\Application;
use app\core\lib\interfaces\CreditCardInterface;

class MasterCard implements CreditCardInterface
{
    private bool $cardValidity = true;

    public function checkCard(array $attributes)
    {
        if(!$attributes)
        {
            Application::$app->db->changeCreditCardDataValidity($attributes['card_num']);
            $this->cardValidity = false;
        }
    }
    
    public function makePayment()
    {
        if($this->cardValidity)
        {
            return true;
        }
    }
}