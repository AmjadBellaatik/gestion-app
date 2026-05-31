<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product, public int $currentStock) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Application');
        $productUrl = url(route('filament.admin.resources.products.edit', $this->product->id, false));

        return (new MailMessage)
            ->subject(__('messages.low_stock_subject', ['product' => $this->product->name]))
            ->greeting(__('messages.hello') . ', ' . $notifiable->name . '!')
            ->line(__('messages.low_stock_intro', ['product' => $this->product->name]))
            ->line(__('messages.current_stock') . ': ' . $this->currentStock)
            ->line(__('messages.minimum_stock') . ': ' . ($this->product->minimum_stock ?? 5))
            ->action(__('messages.view_product'), $productUrl)
            ->salutation(__('messages.regards') . ',<br>' . $appName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'      => __('messages.low_stock_subject', ['product' => $this->product->name]),
            'message'    => __('messages.current_stock') . ': ' . $this->currentStock,
            'type'       => 'low_stock',
            'product_id' => $this->product->id,
            'url'        => route('filament.admin.resources.products.edit', $this->product->id),
        ];
    }
}
