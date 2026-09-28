<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class CustomerPasswordResetNotification extends ResetPassword
{
    protected function resetUrl($notifiable)
    {
        return url(route('password.reset', [
            'token' => $this->token,
        ], false)) . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
    }

    protected function buildMailMessage($url)
    {
        return (new MailMessage)
            ->subject('Reset Password Notification')
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $url)
            ->line('This password reset link will expire in ' . config('auth.passwords.' . config('auth.defaults.passwords') . '.expire') . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.')
            ->line('Patepur,Bihar,843110')
            ->line('Contact:9899592110');
    }
}