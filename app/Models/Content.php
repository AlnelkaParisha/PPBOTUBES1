<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Content extends Model
{
    protected $fillable = ['title', 'type', 'description'];

    // Paket mana saja yang boleh menonton konten ini (tabel pivot content_plan)
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class);
    }
}
