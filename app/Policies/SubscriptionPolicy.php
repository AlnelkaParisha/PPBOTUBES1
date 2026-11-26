<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

// Hanya pemilik yang boleh melihat, mengganti, atau membatalkan langganannya
class SubscriptionPolicy
{
    public function view(User $user, Subscription $subscription): bool
    {
        return $user->id === $subscription->user_id;
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $user->id === $subscription->user_id;
    }
}
