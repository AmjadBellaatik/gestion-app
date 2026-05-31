<?php

namespace App\Models;

use App\Notifications\PasswordResetNotification;
use App\Notifications\WelcomeNotification;

use Filament\Models\Contracts\HasAvatar;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Notifications\Notifiable;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar
{
    use Notifiable;
    use HasRoles;

    protected static function booted(): void
    {
        static::created(function (User $user) {
            try {
                $user->notify(new WelcomeNotification);
            } catch (\Throwable) {
                // Silently fail if mail is not yet configured
            }
        });
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }

    protected $fillable = [

        'name',
        'email',
        'password',
        'phone',
        'address',
        'profile_picture',
        'language',
        'status',
        'last_login_at',

    ];

    protected $hidden = [

        'password',
        'remember_token',

    ];

    protected function casts(): array
    {
        return [

            'email_verified_at' => 'datetime',

            'password' => 'hashed',

            'status' => 'boolean',

            'last_login_at' => 'datetime',

        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Companies
    |--------------------------------------------------------------------------
    */

    public function companies()
    {
        return $this->belongsToMany(

            Company::class,

            'company_user'

        )->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouses
    |--------------------------------------------------------------------------
    */

    public function warehouses()
    {
        return $this->belongsToMany(
            Warehouse::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Access
    |--------------------------------------------------------------------------
    */

    public function hasWarehouseAccess(
        $warehouseId
    ): bool {

        if (

            $this->hasRole(
                'Super Admin'
            )

            ||

            $this->hasRole(
                'Admin'
            )

        ) {

            return true;
        }

        return $this->warehouses()

            ->where(
                'warehouse_id',
                $warehouseId
            )

            ->exists();
    }

    public function getFilamentAvatarUrl(): ?string
    {
        if (! $this->profile_picture) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->profile_picture);
    }
}
