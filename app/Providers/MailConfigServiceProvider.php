<?php

namespace App\Providers;

use App\Enums\SettingTypeEnum;
use App\Models\Setting;
use App\Support\InstallationState;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (!InstallationState::isInstalled()) {
            return;
        }
        if (!InstallationState::hasTable('settings')) {
            return;
        }

        $emailSetting = Setting::find(SettingTypeEnum::EMAIL());
        $emailSettings = $emailSetting?->value ?? [];

        if (!is_array($emailSettings) || empty($emailSettings)) {
            return;
        }

        // In email demo mode we intentionally bypass SMTP usage.
        if ((bool)($emailSettings['email_demo_mode'] ?? false)) {
            return;
        }

        $smtpHost = $emailSettings['smtpHost'] ?? null;
        $smtpUsername = $emailSettings['smtpUsername'] ?? ($emailSettings['smtpEmail'] ?? null);
        $smtpFromEmail = $emailSettings['smtpFromEmail'] ?? ($emailSettings['smtpEmail'] ?? null);
        $smtpPortRaw = $emailSettings['smtpPort'] ?? null;
        $smtpPort = is_numeric($smtpPortRaw) ? (int) $smtpPortRaw : null;
        $smtpEncryption = $emailSettings['smtpEncryption'] ?? null;

        if (!empty($smtpHost)) {
            Config::set('mail.mailers.smtp.host', $smtpHost);
        }

        // Symfony DSN requires ?int for port.
        if ($smtpPort !== null) {
            Config::set('mail.mailers.smtp.port', $smtpPort);
        }

        if (!empty($smtpUsername)) {
            Config::set('mail.mailers.smtp.username', $smtpUsername);
        }

        if (!empty($smtpFromEmail) && filter_var($smtpFromEmail, FILTER_VALIDATE_EMAIL)) {
            Config::set('mail.from.address', $smtpFromEmail);
        }

        Config::set('mail.mailers.smtp.password', $emailSettings['smtpPassword'] ?? null);

        if (!empty($smtpEncryption)) {
            Config::set('mail.mailers.smtp.encryption', $smtpEncryption);
        }
//            Config::set('mail.from.name', $emailData['smtpHost']);

    }
}
