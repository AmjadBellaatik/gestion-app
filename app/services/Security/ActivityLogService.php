<?php

namespace App\Services\Security;

use App\Models\ActivityLog;

class ActivityLogService
{
    public static function log(

        string $module,

        string $action,

        mixed $model = null,

        array $oldValues = [],

        array $newValues = [],

        ?string $description = null

    ): ActivityLog {

        return ActivityLog::create([

            'company_id' =>
                session('company_id'),

            'user_id' =>
                auth()->id(),

            'module' => $module,

            'action' => $action,

            'model_type' =>
                $model ? get_class($model) : null,

            'model_id' =>
                $model?->id,

            'description' =>
                $description,

            'old_values' =>
                $oldValues,

            'new_values' =>
                $newValues,

            'ip_address' =>
                request()->ip(),

            'user_agent' =>
                request()->userAgent(),

        ]);
    }
}