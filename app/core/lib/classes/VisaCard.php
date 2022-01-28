<?php

namespace app\core\lib\classes;

use app\core\Application;
use app\core\lib\interfaces\CreditCardInterface;

class VisaCard implements CreditCardInterface
{
    private bool $cardValidity = true;

    public function checkCard(array $attrbutes)
    {
        if(!$attrbutes)
        {
            Application::$app->db->changeCreditCardDataValidity($attrbutes['card_num']);
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