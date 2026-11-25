<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'billing_cycle', 'discount_percent', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'billing_cycle' => BillingCycle::class,
            'discount_percent' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function contents(): BelongsToMany
    {
        return $this->belongsToMany(Content::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
