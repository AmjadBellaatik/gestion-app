<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $temporaryPassword = '') {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $appName = config('app.name', 'Application');
        $loginUrl = url(route('filament.admin.auth.login', [], false));

        $message = (new MailMessage)
            ->subject(__('messages.welcome_subject', ['app' => $appName]))
            ->greeting(__('messages.hello') . ', ' . $notifiable->name . '!')
            ->line(__('messages.welcome_intro', ['app' => $appName]))
            ->line(__('messages.welcome_email_info', ['email' => $notifiable->email]));

        if ($this->temporaryPassword) {
            $message->line(__('messages.welcome_temp_password', ['password' => $this->temporaryPassword]));
        }

        return $message
            ->action(__('messages.welcome_login_action'), $loginUrl)
            ->line(__('messages.welcome_change_password'))
            ->salutation(__('messages.regards') . ',<br>' . $appName);
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => __('messages.welcome_notification_title'),
            'message' => __('messages.welcome_notification_body', ['app' => config('app.name')]),
            'type'    => 'welcome',
        ];
    }
}
