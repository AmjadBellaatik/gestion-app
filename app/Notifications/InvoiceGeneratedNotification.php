<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGeneratedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Application');
        $docUrl = url(route('filament.admin.resources.documents.view', $this->document->id, false));

        return (new MailMessage)
            ->subject(__('messages.invoice_generated_subject', ['number' => $this->document->document_number]))
            ->greeting(__('messages.hello') . ', ' . $notifiable->name . '!')
            ->line(__('messages.invoice_generated_intro', ['number' => $this->document->document_number]))
            ->when(
                isset($this->document->total_amount),
                fn ($msg) => $msg->line(__('messages.total') . ': ' . number_format($this->document->total_amount, 2) . ' MAD')
            )
            ->action(__('messages.view_invoice'), $docUrl)
            ->salutation(__('messages.regards') . ',<br>' . $appName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'       => __('messages.invoice_generated_subject', ['number' => $this->document->document_number]),
            'message'     => __('messages.invoice_generated_intro', ['number' => $this->document->document_number]),
            'type'        => 'invoice_generated',
            'document_id' => $this->document->id,
            'url'         => route('filament.admin.resources.documents.view', $this->document->id),
        ];
    }
}
