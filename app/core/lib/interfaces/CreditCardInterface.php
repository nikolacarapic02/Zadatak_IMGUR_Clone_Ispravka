<?php

namespace app\core\lib\interfaces;

interface CreditCardInterface
{
    public function checkCard($card_num);

    public function makePayment();
}