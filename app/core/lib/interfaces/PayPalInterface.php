<?php

namespace app\core\lib\interfaces;

interface PayPalInterface
{
    public function checkAccount(string $email);

    public function makePayment();
}