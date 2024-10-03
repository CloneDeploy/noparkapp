<?php

namespace App\Models;

use App\Models\User;
use Livewire\Livewire;
use App\Models\Scopes\CustomerScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'buttons' => 'array',
        'news' => 'boolean',
        'active' => 'boolean',
        'seen' => 'boolean',
        'data' => 'array',
        'bin' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(new CustomerScope);
    }

    public function screenshots(): HasMany
    {
        return $this->hasMany(related: Screenshot::class);
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::created(function ($user) {
    //         $this->dispatch('customer-created');
    //     });

    //     static::updated(function ($user) {
    //         $this->dispatch('customer-updated');
    //     });

    // }
}
