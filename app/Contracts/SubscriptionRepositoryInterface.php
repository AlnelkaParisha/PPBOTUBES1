<?php

namespace App\Contracts;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionRepositoryInterface
{
    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): Subscription;

    public function hasActiveOrPending(User $user): bool;

    public function forUser(User $user): Collection;

    // Ganti seluruh daftar anggota family. Array kosong = hapus semua anggota.
    public function syncMembers(Subscription $subscription, array $members): void;
}