<?php

namespace App\Types\Settings;

use App\Interfaces\SettingInterface;
use App\Traits\SettingTrait;

class GstSettingType implements SettingInterface
{
    use SettingTrait;

    // Master toggle
    public bool   $gst_enabled                = true;

    // Platform seller registration details
    public string $seller_gstin               = '';
    public string $seller_state_code          = 'MH'; // 2-letter state code
    public string $seller_legal_name          = '';
    public string $seller_address             = '';

    // B2B / customer GSTIN collection
    public bool   $collect_customer_gstin     = false;

    // Invoice display options
    public bool   $show_gst_breakdown         = true;   // show CGST/SGST/IGST line items
    public bool   $show_hsn_on_invoice        = true;   // show HSN code per item
    public string $invoice_prefix             = 'INV';
    public int    $invoice_starting_number    = 1001;

    // Default GST slab for new products (%)
    public int    $default_gst_rate           = 18;

    // Composition scheme
    public bool   $composition_scheme         = false;  // if seller is under composition scheme
    public float  $composition_rate           = 1.0;    // applicable rate (%)

    protected static function getValidationRules(): array
    {
        return [
            'gst_enabled'              => 'nullable|boolean',
            'seller_gstin'             => 'nullable|string|max:15|regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
            'seller_state_code'        => 'nullable|string|size:2',
            'seller_legal_name'        => 'nullable|string|max:200',
            'seller_address'           => 'nullable|string|max:500',
            'collect_customer_gstin'   => 'nullable|boolean',
            'show_gst_breakdown'       => 'nullable|boolean',
            'show_hsn_on_invoice'      => 'nullable|boolean',
            'invoice_prefix'           => 'nullable|string|max:10',
            'invoice_starting_number'  => 'nullable|integer|min:1',
            'default_gst_rate'         => 'nullable|integer|in:0,5,12,18,28',
            'composition_scheme'       => 'nullable|boolean',
            'composition_rate'         => 'nullable|numeric|min:0|max:5',
        ];
    }

    protected static function getValidationMessages(): array
    {
        return [
            'seller_gstin.regex' => 'The GSTIN must be a valid 15-character GST number.',
            'seller_state_code.size' => 'State code must be exactly 2 characters.',
            'default_gst_rate.in' => 'Default GST rate must be one of: 0, 5, 12, 18, 28.',
        ];
    }
}
