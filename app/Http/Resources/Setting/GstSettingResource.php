<?php

namespace App\Http\Resources\Setting;

use App\Traits\PanelAware;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GstSettingResource extends JsonResource
{
    use PanelAware;

    public function toArray(Request $request): array
    {
        // Non-admin consumers only receive the public-facing GST flag and default rate
        if ($this->getPanel() !== 'admin') {
            return [
                'variable' => $this->variable,
                'value' => [
                    'gst_enabled'          => $this->value['gst_enabled']          ?? true,
                    'default_gst_rate'     => $this->value['default_gst_rate']     ?? 18,
                    'show_gst_breakdown'   => $this->value['show_gst_breakdown']   ?? true,
                    'show_hsn_on_invoice'  => $this->value['show_hsn_on_invoice']  ?? true,
                    'seller_state_code'    => $this->value['seller_state_code']    ?? 'MH',
                    'collect_customer_gstin' => $this->value['collect_customer_gstin'] ?? false,
                ],
            ];
        }

        return [
            'variable' => $this->variable,
            'value' => [
                'gst_enabled'             => $this->value['gst_enabled']             ?? true,
                'seller_gstin'            => $this->value['seller_gstin']            ?? '',
                'seller_state_code'       => $this->value['seller_state_code']       ?? 'MH',
                'seller_legal_name'       => $this->value['seller_legal_name']       ?? '',
                'seller_address'          => $this->value['seller_address']          ?? '',
                'collect_customer_gstin'  => $this->value['collect_customer_gstin']  ?? false,
                'show_gst_breakdown'      => $this->value['show_gst_breakdown']      ?? true,
                'show_hsn_on_invoice'     => $this->value['show_hsn_on_invoice']     ?? true,
                'invoice_prefix'          => $this->value['invoice_prefix']          ?? 'INV',
                'invoice_starting_number' => $this->value['invoice_starting_number'] ?? 1001,
                'default_gst_rate'        => $this->value['default_gst_rate']        ?? 18,
                'composition_scheme'      => $this->value['composition_scheme']      ?? false,
                'composition_rate'        => $this->value['composition_rate']        ?? 1.0,
            ],
        ];
    }
}
