<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PinServiceAreaSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = base_path('../../pin_service_updated.csv');

        if (!file_exists($csvPath)) {
            // Try alternate path relative to project root
            $csvPath = base_path('../pin_service_updated.csv');
        }

        if (!file_exists($csvPath)) {
            $this->command->error("CSV file not found. Expected at: " . base_path('../../pin_service_updated.csv'));
            return;
        }

        $this->command->info("Truncating existing pin_service_areas...");
        DB::table('pin_service_areas')->truncate();

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error("Cannot open CSV file.");
            return;
        }

        // Skip header
        fgetcsv($handle);

        $chunk = [];
        $count = 0;
        $chunkSize = 500;
        $now = Carbon::now()->toDateTimeString();

        $this->command->info("Importing pincodes...");

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 8) continue;

            [$id, $pincode, $state, $district, $city, $zone, $zone1, $dtime] = $row;

            $pincode  = trim($pincode);
            $state    = trim($state);
            $district = trim($district);
            $city     = trim($city);
            $zone     = strtoupper(trim($zone));
            $zone1    = trim($zone1);
            $dtime    = trim($dtime);

            if (!$pincode || !in_array($zone, ['A','B','C','D','E'])) continue;

            $chunk[] = [
                'pincode'        => $pincode,
                'state'          => $state,
                'district'       => $district,
                'city'           => $city,
                'zone'           => $zone,
                'zone1'          => $zone1,
                'delivery_time'  => $dtime,
                'is_serviceable' => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $count++;

            if (count($chunk) >= $chunkSize) {
                DB::table('pin_service_areas')->upsert($chunk, ['pincode'], [
                    'state', 'district', 'city', 'zone', 'zone1', 'delivery_time', 'updated_at',
                ]);
                $chunk = [];
                $this->command->getOutput()->write('.');
            }
        }

        if (!empty($chunk)) {
            DB::table('pin_service_areas')->upsert($chunk, ['pincode'], [
                'state', 'district', 'city', 'zone', 'zone1', 'delivery_time', 'updated_at',
            ]);
        }

        fclose($handle);

        $this->command->newLine();
        $this->command->info("Done! Imported {$count} pincodes.");
    }
}
