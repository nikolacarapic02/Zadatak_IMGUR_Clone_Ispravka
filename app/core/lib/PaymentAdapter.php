<?php

namespace app\core\lib;

use app\core\lib\interfaces\CreditCardInterface;
use app\core\lib\interfaces\PaymentInterface;

class PaymentAdapter implements PaymentInterface
{
    private CreditCardInterface $card;
    private array $attributes;

    public function __construct(CreditCardInterface $card, array $attributes)
    {
        $this->card = $card;
        $this->attributes = $attributes;
    }

    public function checkPayment()
    {
        $this->card->checkCard($this->attributes);
    }

    public function makePayment()
    {
        $this->card->makePayment();
    }
}