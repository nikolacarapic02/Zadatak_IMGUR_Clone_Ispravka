<?php

namespace app\core\lib\interfaces;

interface CreditCardInterface
{
    public function checkCard(array $attributes);

    public function makePayment();
}