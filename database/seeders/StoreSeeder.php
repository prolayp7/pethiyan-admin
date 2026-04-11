<?php

namespace Database\Seeders;

use App\Enums\Store\StoreFulfillmentTypeEnum;
use App\Enums\Store\StoreStatusEnum;
use App\Enums\Store\StoreVerificationStatusEnum;
use App\Enums\Store\StoreVisibilityStatusEnum;
use App\Models\Store;
use Illuminate\Database\Seeder;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        $stores = [
            [
                'seller_id'            => 1,
                'name'                 => 'Pethiyan Main Store 2',
                'address'              => '123, Industrial Area, Phase 1',
                'city'                 => 'Delhi',
                'state_name'           => 'Delhi',
                'state_id'             => 32,
                'zipcode'              => '600001',
                'country'              => 'India',
                'country_code'         => 'IN',
                'latitude'             => 13.0827,
                'longitude'            => 80.2707,
                'contact_email'        => 'store@pethiyan.com',
                'contact_number'       => '9000000001',
                'description'          => 'Pethiyan Main Store — packaging materials & tape products.',
                'status'               => StoreStatusEnum::ONLINE(),
                'verification_status'  => StoreVerificationStatusEnum::APPROVED(),
                'visibility_status'    => StoreVisibilityStatusEnum::VISIBLE(),
                'fulfillment_type'     => StoreFulfillmentTypeEnum::REGULAR(),
                'max_delivery_distance' => 0,
                'order_preparation_time' => 30,
                'currency_code'        => 'INR',
                'gstin'                => '',
                'state_code'           => '32',
                'gst_registered'       => false,
            ],
        ];

        foreach ($stores as $data) {
            // $existing = Store::where('seller_id', $data['seller_id'])
            //     ->where('name', $data['name'])
            //     ->withTrashed()
            //     ->first();

            // if (!$existing) {
                Store::create($data);
                $this->command->info("Store created: {$data['name']}");
            // } else {
            //     $this->command->info("Store already exists: {$data['name']} (skipped)");
            // }
        }
    }
}
