<?php

namespace App\Models;

use App\Services\Workshop\RepairWorkflowService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairStep extends Model
{
    protected $fillable = [
        'repair_ticket_id',
        'sort_order',
        'title',
        'description',
        'status',
        'performed_by',
        'performed_at',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'sort_order'   => 'integer',
    ];

    protected static function booted(): void
    {
        static::saved(function (RepairStep $step) {
            if ($step->wasRecentlyCreated || ! $step->isDirty('status')) {
                return;
            }

            $ticket = $step->repairTicket;
            if (! $ticket) {
                return;
            }

            if ($step->status === 'in_progress') {
                RepairWorkflowService::onStepStarted($ticket);
                return;
            }

            if ($step->status === 'done') {
                $allDone = $ticket->steps()
                    ->where('status', '!=', 'done')
                    ->doesntExist();

                if ($allDone) {
                    RepairWorkflowService::onAllStepsCompleted($ticket);
                }
            }
        });
    }

    public function repairTicket(): BelongsTo
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
