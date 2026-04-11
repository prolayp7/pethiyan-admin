<?php

namespace App\Services;

use App\Enums\SettingTypeEnum;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailService
{
    protected array $emailConfig;
    protected array $systemConfig;

    public function __construct(SettingService $settingService)
    {
        $setting           = $settingService->getSettingByVariable(SettingTypeEnum::EMAIL());
        $this->emailConfig = $setting?->value ?? [];
        $systemSetting     = $settingService->getSettingByVariable(SettingTypeEnum::SYSTEM());
        $this->systemConfig = $systemSetting?->value ?? [];
    }

    /**
     * Dynamically apply SMTP settings from the database to the mail config,
     * then send a Mailable.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable
     * @param  string|array               $to       Recipient email(s)
     * @param  string|null                $name     Recipient display name
     */
    public function send($mailable, string|array $to, ?string $name = null): bool
    {
        if (empty($this->emailConfig['smtpHost']) || empty($this->resolveSmtpUsername())) {
            Log::warning('[EmailService] SMTP not configured — skipping email.');
            return false;
        }

        try {
            $this->applySmtpConfig();

            $fromAddress = $this->resolveSmtpFromEmail() ?: (string)config('mail.from.address');
            $fromName = $this->resolveFromName();
            if ($fromAddress !== '') {
                $mailable->from($fromAddress, $fromName);
            }

            $mailer = Mail::mailer('smtp');

            if ($name) {
                $mailer->to($to, $name)->send($mailable);
            } else {
                $mailer->to($to)->send($mailable);
            }

            return true;

        } catch (\Throwable $e) {
            Log::error('[EmailService] Send failed: ' . $e->getMessage(), [
                'to'       => $to,
                'mailable' => get_class($mailable),
            ]);
            return false;
        }
    }

    /**
     * Override the runtime mail configuration with stored SMTP settings.
     */
    protected function applySmtpConfig(): void
    {
        $smtpUsername = $this->resolveSmtpUsername();
        $smtpFromEmail = $this->resolveSmtpFromEmail();

        Config::set('mail.mailers.smtp', [
            'transport'  => 'smtp',
            'host'       => $this->emailConfig['smtpHost']        ?? '',
            'port'       => (int)($this->emailConfig['smtpPort']  ?? 587),
            'encryption' => $this->emailConfig['smtpEncryption']  ?? 'tls',
            'username'   => $smtpUsername,
            'password'   => $this->emailConfig['smtpPassword']    ?? '',
            'timeout'    => null,
        ]);

        Config::set('mail.from', [
            'address' => $smtpFromEmail ?: config('mail.from.address'),
            'name'    => $this->resolveFromName(),
        ]);
    }

    public function isConfigured(): bool
    {
        return !empty($this->emailConfig['smtpHost']) && !empty($this->resolveSmtpUsername());
    }

    protected function resolveSmtpUsername(): string
    {
        return (string)($this->emailConfig['smtpUsername'] ?? $this->emailConfig['smtpEmail'] ?? '');
    }

    protected function resolveSmtpFromEmail(): string
    {
        $from = (string)($this->emailConfig['smtpFromEmail'] ?? $this->emailConfig['smtpEmail'] ?? '');
        return filter_var($from, FILTER_VALIDATE_EMAIL) ? $from : '';
    }

    protected function resolveFromName(): string
    {
        $name = (string)($this->systemConfig['appName'] ?? '');
        if ($name !== '') {
            return $name;
        }

        $mailFromName = (string)config('mail.from.name', '');
        if ($mailFromName !== '') {
            return $mailFromName;
        }

        return (string)config('app.name', 'LCommerce');
    }
}
