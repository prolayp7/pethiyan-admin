<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class SmsSettingType implements SettingInterface
{
    use SettingTrait;

    public bool   $enabled            = false;
    public bool   $otp_demo_mode      = false;
    public string $gateway            = 'msg91';
    public int    $otp_length         = 6;
    public int    $otp_expiry_minutes = 10;

    // MSG91
    public string $msg91_auth_key     = '';
    public string $msg91_template_id  = '';
    public string $msg91_sender_id    = '';

    // Twilio
    public string $twilio_account_sid = '';
    public string $twilio_auth_token  = '';
    public string $twilio_from_number = '';

    protected static function getValidationRules(): array
    {
        return [
            'enabled'            => 'nullable|boolean',
            'otp_demo_mode'      => 'nullable|boolean',
            'gateway'            => 'nullable|string|in:msg91,twilio,log',
            'otp_length'         => 'nullable|integer|min:4|max:8',
            'otp_expiry_minutes' => 'nullable|integer|min:1|max:60',
            'msg91_auth_key'     => 'nullable|string',
            'msg91_template_id'  => 'nullable|string',
            'msg91_sender_id'    => 'nullable|string|max:6',
            'twilio_account_sid' => 'nullable|string',
            'twilio_auth_token'  => 'nullable|string',
            'twilio_from_number' => 'nullable|string',
        ];
    }
}
