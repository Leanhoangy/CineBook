<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Email đặt lại mật khẩu bằng tiếng Việt */
class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu CineBook')
            ->greeting("Xin chào {$notifiable->name},")
            ->line('Bạn nhận được email này vì chúng tôi đã nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.')
            ->action('Đặt lại mật khẩu', $this->resetUrl($notifiable))
            ->line("Liên kết sẽ hết hạn sau {$minutes} phút.")
            ->line('Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này.')
            ->salutation('Trân trọng, đội ngũ CineBook');
    }
}
