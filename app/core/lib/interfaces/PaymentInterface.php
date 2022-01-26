<?php

namespace app\core\lib\interfaces;

interface PaymentInterface
{
    public function checkPayment();

    public function makePayment();
}