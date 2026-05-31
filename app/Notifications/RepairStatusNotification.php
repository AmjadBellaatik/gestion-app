<?php

namespace App\Notifications;

use App\Models\RepairTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RepairStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RepairTicket $ticket,
        public string $oldStatus,
        public string $newStatus,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Application');
        $ticketUrl = url(route('filament.admin.resources.repair-tickets.view', $this->ticket->id, false));

        return (new MailMessage)
            ->subject(__('messages.repair_status_subject', [
                'ticket' => $this->ticket->ticket_number,
                'status' => __('messages.' . $this->newStatus),
            ]))
            ->greeting(__('messages.hello') . ', ' . $notifiable->name . '!')
            ->line(__('messages.repair_status_intro', ['ticket' => $this->ticket->ticket_number]))
            ->line(__('messages.repair_status_changed', [
                'old' => __('messages.' . $this->oldStatus),
                'new' => __('messages.' . $this->newStatus),
            ]))
            ->when($this->ticket->client, fn ($msg) => $msg->line(
                __('messages.repair_client') . ': ' . $this->ticket->client->display_name
            ))
            ->when($this->ticket->vehicle_display, fn ($msg) => $msg->line(
                __('messages.vehicle') . ': ' . $this->ticket->vehicle_display
            ))
            ->action(__('messages.view_ticket'), $ticketUrl)
            ->salutation(__('messages.regards') . ',<br>' . $appName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'     => __('messages.repair_status_subject', [
                'ticket' => $this->ticket->ticket_number,
                'status' => __('messages.' . $this->newStatus),
            ]),
            'message'   => __('messages.repair_status_changed', [
                'old' => __('messages.' . $this->oldStatus),
                'new' => __('messages.' . $this->newStatus),
            ]),
            'type'      => 'repair_status',
            'ticket_id' => $this->ticket->id,
            'url'       => route('filament.admin.resources.repair-tickets.view', $this->ticket->id),
        ];
    }
}
