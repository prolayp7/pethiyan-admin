<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IndiaStatesSeeder extends Seeder
{
    // India country_id in the countries table is 101
    private const INDIA_COUNTRY_ID = 101;

    public function run(): void
    {
        // Columns: name, state_code, gst_code, is_ut
        // gst_code is the 2-digit GST registration prefix used on GSTIN
        $states = [
            // States
            ['name' => 'Andhra Pradesh',           'state_code' => 'AP', 'gst_code' => '37', 'is_ut' => false],
            ['name' => 'Arunachal Pradesh',         'state_code' => 'AR', 'gst_code' => '12', 'is_ut' => false],
            ['name' => 'Assam',                     'state_code' => 'AS', 'gst_code' => '18', 'is_ut' => false],
            ['name' => 'Bihar',                     'state_code' => 'BR', 'gst_code' => '10', 'is_ut' => false],
            ['name' => 'Chhattisgarh',              'state_code' => 'CG', 'gst_code' => '22', 'is_ut' => false],
            ['name' => 'Goa',                       'state_code' => 'GA', 'gst_code' => '30', 'is_ut' => false],
            ['name' => 'Gujarat',                   'state_code' => 'GJ', 'gst_code' => '24', 'is_ut' => false],
            ['name' => 'Haryana',                   'state_code' => 'HR', 'gst_code' => '06', 'is_ut' => false],
            ['name' => 'Himachal Pradesh',          'state_code' => 'HP', 'gst_code' => '02', 'is_ut' => false],
            ['name' => 'Jharkhand',                 'state_code' => 'JH', 'gst_code' => '20', 'is_ut' => false],
            ['name' => 'Karnataka',                 'state_code' => 'KA', 'gst_code' => '29', 'is_ut' => false],
            ['name' => 'Kerala',                    'state_code' => 'KL', 'gst_code' => '32', 'is_ut' => false],
            ['name' => 'Madhya Pradesh',            'state_code' => 'MP', 'gst_code' => '23', 'is_ut' => false],
            ['name' => 'Maharashtra',               'state_code' => 'MH', 'gst_code' => '27', 'is_ut' => false],
            ['name' => 'Manipur',                   'state_code' => 'MN', 'gst_code' => '14', 'is_ut' => false],
            ['name' => 'Meghalaya',                 'state_code' => 'ML', 'gst_code' => '17', 'is_ut' => false],
            ['name' => 'Mizoram',                   'state_code' => 'MZ', 'gst_code' => '15', 'is_ut' => false],
            ['name' => 'Nagaland',                  'state_code' => 'NL', 'gst_code' => '13', 'is_ut' => false],
            ['name' => 'Odisha',                    'state_code' => 'OD', 'gst_code' => '21', 'is_ut' => false],
            ['name' => 'Punjab',                    'state_code' => 'PB', 'gst_code' => '03', 'is_ut' => false],
            ['name' => 'Rajasthan',                 'state_code' => 'RJ', 'gst_code' => '08', 'is_ut' => false],
            ['name' => 'Sikkim',                    'state_code' => 'SK', 'gst_code' => '11', 'is_ut' => false],
            ['name' => 'Tamil Nadu',                'state_code' => 'TN', 'gst_code' => '33', 'is_ut' => false],
            ['name' => 'Telangana',                 'state_code' => 'TS', 'gst_code' => '36', 'is_ut' => false],
            ['name' => 'Tripura',                   'state_code' => 'TR', 'gst_code' => '16', 'is_ut' => false],
            ['name' => 'Uttar Pradesh',             'state_code' => 'UP', 'gst_code' => '09', 'is_ut' => false],
            ['name' => 'Uttarakhand',               'state_code' => 'UK', 'gst_code' => '05', 'is_ut' => false],
            ['name' => 'West Bengal',               'state_code' => 'WB', 'gst_code' => '19', 'is_ut' => false],

            // Union Territories
            ['name' => 'Andaman and Nicobar Islands',                    'state_code' => 'AN', 'gst_code' => '35', 'is_ut' => true],
            ['name' => 'Chandigarh',                                     'state_code' => 'CH', 'gst_code' => '04', 'is_ut' => true],
            ['name' => 'Dadra and Nagar Haveli and Daman and Diu',       'state_code' => 'DH', 'gst_code' => '26', 'is_ut' => true],
            ['name' => 'Delhi',                                          'state_code' => 'DL', 'gst_code' => '07', 'is_ut' => true],
            ['name' => 'Jammu and Kashmir',                              'state_code' => 'JK', 'gst_code' => '01', 'is_ut' => true],
            ['name' => 'Ladakh',                                         'state_code' => 'LA', 'gst_code' => '38', 'is_ut' => true],
            ['name' => 'Lakshadweep',                                    'state_code' => 'LD', 'gst_code' => '31', 'is_ut' => true],
            ['name' => 'Puducherry',                                     'state_code' => 'PY', 'gst_code' => '34', 'is_ut' => true],
        ];

        $existing = State::where('country_id', self::INDIA_COUNTRY_ID)->count();
        if ($existing > 0) {
            $this->command->info("India states already seeded ({$existing} records). Skipping.");
            return;
        }

        $now = now();
        $rows = array_map(fn($s) => array_merge($s, [
            'country_id' => self::INDIA_COUNTRY_ID,
            'created_at' => $now,
            'updated_at' => $now,
        ]), $states);

        DB::table('states')->insert($rows);

        $this->command->info('India states seeded: ' . count($rows) . ' records (28 states + 8 UTs).');
    }
}
