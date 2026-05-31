<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SaleCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Sale $sale) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Application');
        $saleUrl = url(route('filament.admin.resources.sales.view', $this->sale->id, false));

        return (new MailMessage)
            ->subject(__('messages.sale_created_subject', ['number' => $this->sale->sale_number]))
            ->greeting(__('messages.hello') . ', ' . $notifiable->name . '!')
            ->line(__('messages.sale_created_intro', ['number' => $this->sale->sale_number]))
            ->line(__('messages.sale_total') . ': ' . number_format($this->sale->total, 2) . ' MAD')
            ->when($this->sale->client, fn ($msg) => $msg->line(
                __('messages.client') . ': ' . $this->sale->client->display_name
            ))
            ->action(__('messages.view_sale'), $saleUrl)
            ->salutation(__('messages.regards') . ',<br>' . $appName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => __('messages.sale_created_subject', ['number' => $this->sale->sale_number]),
            'message' => __('messages.sale_total') . ': ' . number_format($this->sale->total, 2) . ' MAD',
            'type'    => 'sale_created',
            'sale_id' => $this->sale->id,
            'url'     => route('filament.admin.resources.sales.view', $this->sale->id),
        ];
    }
}
