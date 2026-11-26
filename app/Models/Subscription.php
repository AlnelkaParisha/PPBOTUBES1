<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'user_id', 'plan_id', 'previous_plan_id', 'status',
        'period_start', 'period_end', 'members_count', 'credit_balance', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'period_start' => 'date',
            'period_end' => 'date',
            'members_count' => 'integer',
            'credit_balance' => 'integer',
            'cancelled_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class)->withTrashed();
    }

    public function previousPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'previous_plan_id')->withTrashed();
    }

    // Anggota paket family (kosong untuk paket lain)
    public function members(): HasMany
    {
        return $this->hasMany(SubscriptionMember::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    // Langganan yang sudah dibatalkan tetap boleh dipakai sampai periodenya habis
    public function hasAccess(): bool
    {
        $statusOk = in_array($this->status, [SubscriptionStatus::Active, SubscriptionStatus::Cancelled], true);

        return $statusOk && $this->period_end->greaterThanOrEqualTo(today());
    }
}
