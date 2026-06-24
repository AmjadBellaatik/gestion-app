<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use \App\Models\Concerns\Auditable;

    protected $fillable = [

        'name',

        'legal_name',

        /*
        |--------------------------------------------------------------------------
        | Branding
        |--------------------------------------------------------------------------
        */

        'logo',

        'stamp',

        'signature',

        'footer',

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */

        'phone',

        'email',

        'website',

        /*
        |--------------------------------------------------------------------------
        | Legal Information
        |--------------------------------------------------------------------------
        */

        'ice',

        'rc',

        'if',

        'patente',

        'cnss',

        'rib',

        'bank_name',

        'tax_number',

        /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */

        'address',

        'legal_address',

        'city',

        'country',

        /*
        |--------------------------------------------------------------------------
        | Invoice
        |--------------------------------------------------------------------------
        */

        'invoice_footer',

        'currency',

        'default_language',

        'tax_rate',

        'primary_color',

        'secondary_color',

        'accent_color',

        'is_active',

        /*
        |--------------------------------------------------------------------------
        | Mail Settings
        |--------------------------------------------------------------------------
        */

        'mail_host',

        'mail_port',

        'mail_encryption',

        'mail_username',

        'mail_password',

        'mail_from_address',

        'mail_from_name',

    ];

    public function users()
    {
        return $this->belongsToMany(
            User::class
        );
    }

    public function brands()
    {
        return $this->hasMany(
            Brand::class
        );
    }

    public function documents()
    {
        return $this->hasMany(
            Document::class
        );
    }

    public function documentTemplates()
    {
        return $this->hasMany(
            DocumentTemplate::class
        );
    }
}
