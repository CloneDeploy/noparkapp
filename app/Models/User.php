<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Filament\Panel;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Storage;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\HasSuperAdmin;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([UserObserver::class])]
class User extends Authenticatable implements FilamentUser, HasAvatar
{
    use HasFactory, HasRoles, HasSuperAdmin, SoftDeletes, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canImpersonate()
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    public function canBeImpersonated()
    {
        return !$this->isSuperAdmin();
    }

    public function getSuperAttribute(): bool
    {
        return $this->isSuperAdmin();
    }
    public function getAdminAttribute(): bool
    {
        return $this->isAdmin();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->avatar_url ? Storage::url($this->avatar_url) : null;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
        'custom_fields',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'custom_fields' => 'array',
        ];
    }


    public function numbers(): HasMany
    {
        return $this->hasMany(Number::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function commands(): HasMany
    {
        return $this->hasMany(Command::class);
    }
}
