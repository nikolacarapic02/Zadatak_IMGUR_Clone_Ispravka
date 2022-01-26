<?php

namespace app\core\lib\interfaces;

interface CryptoInterface
{
    public function checkAccount(string $email);

    public function makePayment();
}