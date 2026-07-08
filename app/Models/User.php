<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'branch_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => 'string',
        ];
    }

    // Scopes
    public function scopeByRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role);
    }

    // Relaciones
    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
