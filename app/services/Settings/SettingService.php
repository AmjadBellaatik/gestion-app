<?php

namespace App\Services\Settings;

use App\Models\Setting;

class SettingService
{
    public static function get(

        string $group,

        string $key,

        mixed $default = null

    ): mixed {

        return Setting::query()

            ->where('group', $group)

            ->where('key', $key)

            ->value('value')

            ?? $default;
    }

    public static function set(

        string $group,

        string $key,

        mixed $value,

        string $type = 'string'

    ): Setting {

        return Setting::updateOrCreate(

            [

                'company_id' =>
                    session('company_id'),

                'group' => $group,

                'key' => $key,

            ],

            [

                'value' => $value,

                'type' => $type,

            ]

        );
    }
}