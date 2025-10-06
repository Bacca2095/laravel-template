<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OtpCodeNotification extends Notification
{
    use Queueable;

    public function __construct(protected string $code, protected int $ttl)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your verification code'))
            ->line(__('Use the following code to complete your sign in:'))
            ->line("**{$this->code}**")
            ->line(__('This code expires in :minutes minutes.', ['minutes' => ceil($this->ttl / 60)]));
    }
}
