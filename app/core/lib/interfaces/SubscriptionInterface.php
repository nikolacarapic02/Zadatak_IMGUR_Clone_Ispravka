<?php

namespace app\core\lib\interfaces;

interface SubscriptionInterface
{
    public function subscribe(array $attributes);

    public function upgrade(array $attributes);

    public function cancelSubscription($user_id);

    public function checkSubscriptionRights($user_id);
}