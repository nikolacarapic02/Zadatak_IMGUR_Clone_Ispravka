<?php

namespace app\core\lib\interfaces;

interface Subscription
{
    public function subscribe(array $attributes);

    public function cancelSubscription($user_id);
}