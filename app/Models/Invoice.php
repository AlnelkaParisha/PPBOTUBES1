<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'subscription_id', 'invoice_number', 'type', 'amount', 'credit_applied', 'total',
        'status', 'period_start', 'period_end', 'due_date', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => InvoiceType::class,
            'status' => InvoiceStatus::class,
            'amount' => 'integer',
            'credit_applied' => 'integer',
            'total' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    // Riwayat percobaan bayar, terbaru di atas
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }
}
