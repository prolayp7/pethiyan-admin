<?php

namespace App\Http\Resources\Setting;

use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SmsSettingResource extends JsonResource
{
    use PanelAware;

    public function toArray(Request $request): array
    {
        // Non-admin panels only see whether SMS is enabled and the active gateway
        if ($this->getPanel() !== 'admin') {
            return [
                'variable' => $this->variable,
                'value' => [
                    'enabled'       => $this->value['enabled']       ?? false,
                    'otp_demo_mode' => $this->value['otp_demo_mode'] ?? false,
                    'gateway'       => $this->value['gateway']       ?? 'msg91',
                ],
            ];
        }

        return [
            'variable' => $this->variable,
            'value' => [
                'enabled'            => $this->value['enabled']            ?? false,
                'otp_demo_mode'      => $this->value['otp_demo_mode']      ?? false,
                'gateway'            => $this->value['gateway']            ?? 'msg91',
                'otp_length'         => $this->value['otp_length']         ?? 6,
                'otp_expiry_minutes' => $this->value['otp_expiry_minutes'] ?? 10,
                // MSG91
                'msg91_auth_key'     => $this->value['msg91_auth_key']     ?? '',
                'msg91_template_id'  => $this->value['msg91_template_id']  ?? '',
                'msg91_sender_id'    => $this->value['msg91_sender_id']    ?? '',
                // Twilio
                'twilio_account_sid' => $this->value['twilio_account_sid'] ?? '',
                'twilio_auth_token'  => $this->value['twilio_auth_token']  ?? '',
                'twilio_from_number' => $this->value['twilio_from_number'] ?? '',
            ],
        ];
    }
}
