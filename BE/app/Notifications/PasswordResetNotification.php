<?php

namespace App\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $rawToken,
        public readonly CarbonImmutable $expiresAt,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $baseUrl = rtrim((string) config('auth.password_reset_url'), '/');
        $url = $baseUrl.'?token='.rawurlencode($this->rawToken);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu Smart Fitness')
            ->greeting('Xin chào!')
            ->line('Hệ thống nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $url)
            ->line('Liên kết hết hạn lúc '.$this->expiresAt->format('Y-m-d H:i:s.u').' UTC.')
            ->line('Nếu bạn không gửi yêu cầu này, hãy bỏ qua email.');
    }
}
