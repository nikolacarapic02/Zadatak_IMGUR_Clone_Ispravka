<?php

namespace app\core\lib\classes;

use app\core\Application;
use app\core\lib\interfaces\CreditCardInterface;

class AmericanExpress implements CreditCardInterface
{
    private bool $cardValidity = true;

    public function checkCard($card_num)
    {
        if(!$card_num)
        {
            Application::$app->db->changeCreditCardDataValidity($card_num);
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