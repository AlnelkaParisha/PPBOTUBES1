<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Subscription;
use App\Models\User;

// Satu-satunya tempat aturan "siapa boleh menonton apa"
class ContentAccessService
{
    public function canWatch(User $user, Content $content): bool
    {
        $subscription = $this->currentSubscription($user);

        if ($subscription === null) {
            return false;
        }

        return $content->plans()->whereKey($subscription->plan_id)->exists();
    }

    // Langganan terbaru yang masih boleh dipakai (aktif, atau dibatalkan tapi periodenya belum habis)
    private function currentSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)
            ->latest('id')
            ->get()
            ->first(fn (Subscription $subscription) => $subscription->hasAccess());
    }
}
