<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'symbol',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}
