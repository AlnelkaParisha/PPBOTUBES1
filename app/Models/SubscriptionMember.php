<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Anggota paket family. Satu baris = satu orang yang ikut menanggung tagihan.
class SubscriptionMember extends Model
{
    protected $fillable = ['subscription_id', 'name', 'email'];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
