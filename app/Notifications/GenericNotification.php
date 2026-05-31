<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Illuminate\Notifications\Messages\DatabaseMessage;

class GenericNotification
    extends Notification
{
    use Queueable;

    public function __construct(

        public string $title,

        public string $message

    ) {}

    public function via(
        object $notifiable
    ): array {

        return [

            'database',
            'mail',

        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->message);
    }

    public function toDatabase(
        object $notifiable
    ): array {

        return [

            'title' =>

                $this->title,

            'message' =>

                $this->message,

        ];
    }
}
