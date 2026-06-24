<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create / update / delete actions for the model into the
 * `activity_logs` table so they appear in the Audit History.
 *
 * A model becomes auditable simply by `use Auditable;`.
 *
 * Customisation hooks (optional, declare on the model):
 *   protected array $auditExclude = ['some_column'];  // never logged
 *   public function auditLabel(): ?string             // human description
 */
trait Auditable
{
    /**
     * Attributes that are never recorded in the audit diff.
     */
    protected static array $auditAlwaysExclude = [
        'updated_at',
        'created_at',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /** Runtime switch so bulk jobs / seeders can silence auditing. */
    protected static bool $auditingEnabled = true;

    public static function disableAuditing(): void
    {
        static::$auditingEnabled = false;
    }

    public static function enableAuditing(): void
    {
        static::$auditingEnabled = true;
    }

    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            $model->recordAudit('created', [], $model->auditableAttributes());
        });

        static::updated(function (Model $model): void {
            $changed = $model->auditableChanges();

            if (empty($changed['new'])) {
                return; // only excluded/noise columns changed
            }

            $model->recordAudit('updated', $changed['old'], $changed['new']);
        });

        static::deleted(function (Model $model): void {
            $model->recordAudit('deleted', $model->auditableAttributes(), []);
        });
    }

    /**
     * Persist a single audit entry. Never allowed to break the request.
     */
    protected function recordAudit(string $action, array $oldValues, array $newValues): void
    {
        if (! static::$auditingEnabled) {
            return;
        }

        try {
            ActivityLog::create([
                'company_id'  => session('company_id') ?? $this->company_id ?? null,
                'user_id'     => auth()->id(),
                'module'      => class_basename(static::class),
                'action'      => $action,
                'model_type'  => static::class,
                'model_id'    => $this->getKey(),
                'description' => method_exists($this, 'auditLabel') ? $this->auditLabel() : null,
                'old_values'  => $oldValues,
                'new_values'  => $newValues,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Auditing must never crash a business operation.
        }
    }

    /**
     * Full attribute set with sensitive / noise keys removed.
     */
    protected function auditableAttributes(): array
    {
        return collect($this->attributesToArray())
            ->except($this->auditExcludedKeys())
            ->all();
    }

    /**
     * Old/new diff of the dirty attributes, excluding noise keys.
     */
    protected function auditableChanges(): array
    {
        $excluded = $this->auditExcludedKeys();

        $new = collect($this->getChanges())->except($excluded);

        $old = $new->keys()->mapWithKeys(
            fn (string $key) => [$key => $this->getOriginal($key)]
        );

        return ['old' => $old->all(), 'new' => $new->all()];
    }

    protected function auditExcludedKeys(): array
    {
        return array_merge(
            static::$auditAlwaysExclude,
            property_exists($this, 'auditExclude') ? $this->auditExclude : []
        );
    }
}
