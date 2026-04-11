<?php

namespace App\Types\Settings;

use App\Enums\Email\SmtpContentTypeEnum;
use App\Enums\Email\SmtpEncryptionEnum;
use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;
use Illuminate\Validation\Rules\Enum;

class EmailSettingType implements SettingInterface
{
    use SettingTrait;

    public bool   $email_demo_mode = false;
    public string $smtpHost = '';
    public string $smtpPort = '';
    public string $smtpUsername = '';
    public string $smtpFromEmail = '';
    // Backward compatibility with older setting payloads.
    public string $smtpEmail = '';
    public string $smtpPassword = '';
    public string $smtpEncryption = '';
    public string $smtpContentType = '';
    protected static function getValidationRules(): array
    {
        return [
            'email_demo_mode' => 'nullable|boolean',
            // When demo mode is ON, SMTP fields are fully excluded from validation.
            'smtpHost'        => 'exclude_if:email_demo_mode,1|required|string|max:255',
            'smtpPort'        => 'exclude_if:email_demo_mode,1|required|integer|min:1|max:65535',
            'smtpUsername'    => 'exclude_if:email_demo_mode,1|required|string|max:255',
            'smtpFromEmail'   => 'exclude_if:email_demo_mode,1|required|email|max:255',
            'smtpEmail'       => 'nullable|email|max:255',
            'smtpPassword'    => 'exclude_if:email_demo_mode,1|required|string|max:255',
            'smtpEncryption'  => ['exclude_if:email_demo_mode,1', 'required', new Enum(SmtpEncryptionEnum::class)],
            'smtpContentType' => ['exclude_if:email_demo_mode,1', 'required', new Enum(SmtpContentTypeEnum::class)],
        ];
    }
}
