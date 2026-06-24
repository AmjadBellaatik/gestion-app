<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Scopes\CompanyScope;

class Warehouse extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'company_id',

        'name',

        'code',

        'address',

        'phone',

        'is_active',

    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    protected static function booted(): void
    {
        static::addGlobalScope(
            new CompanyScope
        );

        static::creating(function ($model) {

            if (

                session()->has('company_id')

                &&

                ! $model->company_id

            ) {

                $model->company_id =
                    session('company_id');

            }

            if (blank($model->code)) {
                $model->code = self::generateCode(
                    $model->company_id
                );
            }

        });
    }

    public static function generateCode(
        ?int $companyId = null
    ): string {

        $nextNumber = 1;

        do {
            $code = 'WH-' . str_pad(
                (string) $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );

            $query = self::withoutGlobalScopes()
                ->where(
                    'code',
                    $code
                );

            if ($companyId) {
                $query->where(
                    'company_id',
                    $companyId
                );
            }

            $exists = $query->exists();
            $nextNumber++;
        } while ($exists);

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */

    public function users()
    {
        return $this->belongsToMany(
            User::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Motorcycle Units
    |--------------------------------------------------------------------------
    */

    public function motorcycleUnits()
    {
        return $this->hasMany(
            MotorcycleUnit::class
        );
    }
}
