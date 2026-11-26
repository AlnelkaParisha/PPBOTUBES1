<?php

namespace App\Repositories;

use App\Contracts\SubscriptionRepositoryInterface;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

// Satu-satunya tempat query Subscription. Service tidak menulis query sendiri.
class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);

        return $subscription;
    }

    public function hasActiveOrPending(User $user): bool
    {
        return Subscription::where('user_id', $user->id)
            ->whereIn('status', [SubscriptionStatus::Active->value, SubscriptionStatus::Pending->value])
            ->exists();
    }

    public function forUser(User $user): Collection
    {
        return Subscription::with(['plan', 'previousPlan', 'members'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function syncMembers(Subscription $subscription, array $members): void
    {
        $subscription->members()->delete();

        if ($members !== []) {
            $subscription->members()->createMany($members);
        }
    }
}
