<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $token
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = (string) config('app.frontend_url', 'http://localhost:5173')
            .'/reset-password?'
            .http_build_query([
                'email' => $notifiable->getEmailForPasswordReset(),
                'token' => $this->token,
            ]);

        return (new MailMessage)
            ->subject('Приглашение в портфолио кафедры')
            ->greeting('Здравствуйте, '.$notifiable->name)
            ->line('Вам создана учётная запись. Нажмите кнопку, чтобы задать пароль.')
            ->action('Установить пароль', $url)
            ->line('Если письмо не для вас, ничего не предпринимайте.');
    }
}
