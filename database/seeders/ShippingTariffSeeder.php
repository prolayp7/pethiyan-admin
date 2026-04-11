<?php

namespace Database\Seeders;

use App\Models\DeliveryPartner;
use App\Models\PinZone;
use App\Models\ShippingTariff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ShippingTariffSeeder extends Seeder
{
    public function run(): void
    {
        $csvPath = '/home/prolay/Downloads/shipping_terrif.csv';
        if (!file_exists($csvPath)) {
            $csvPath = base_path('../../shipping_terrif.csv');
        }
        if (!file_exists($csvPath)) {
            $csvPath = base_path('../shipping_terrif.csv');
        }

        if (!file_exists($csvPath)) {
            $this->command->error('shipping_terrif.csv not found.');
            return;
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error('Unable to open shipping tariff csv file.');
            return;
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $this->command->error('CSV header is missing.');
            return;
        }

        $map = array_flip(array_map(fn ($col) => trim((string) $col), $header));
        $required = ['company', 'zone', 'upto250', 'upto500', 'every500', 'perkg', 'kg2', 'above5_surface', 'above5_air', 'fuel_surcharge', 'gst'];
        foreach ($required as $key) {
            if (!array_key_exists($key, $map)) {
                fclose($handle);
                $this->command->error("Missing required column: {$key}");
                return;
            }
        }

        $count = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $companyName = $this->normalizeCompany($row[$map['company']] ?? null);
            $zoneCode = strtoupper(trim((string)($row[$map['zone']] ?? '')));

            if ($companyName === '' || $zoneCode === '') {
                continue;
            }

            $partner = DeliveryPartner::firstOrCreate(
                ['slug' => Str::slug($companyName)],
                ['name' => $companyName, 'is_active' => true]
            );

            $zone = PinZone::firstOrCreate(
                ['code' => $zoneCode],
                ['name' => 'Zone ' . $zoneCode, 'is_active' => true]
            );

            ShippingTariff::updateOrCreate(
                [
                    'delivery_partner_id' => $partner->id,
                    'zone_id' => $zone->id,
                ],
                [
                    'upto_250' => $this->toDecimal($row[$map['upto250']] ?? 0),
                    'upto_500' => $this->toDecimal($row[$map['upto500']] ?? 0),
                    'every_500' => $this->toDecimal($row[$map['every500']] ?? 0),
                    'per_kg' => $this->toDecimal($row[$map['perkg']] ?? 0),
                    'kg_2' => $this->toDecimal($row[$map['kg2']] ?? 0),
                    'above_5_surface' => $this->toDecimal($row[$map['above5_surface']] ?? 0),
                    'above_5_air' => $this->toDecimal($row[$map['above5_air']] ?? 0),
                    'fuel_surcharge_percent' => $this->toDecimal($row[$map['fuel_surcharge']] ?? 0),
                    'gst_percent' => $this->toDecimal($row[$map['gst']] ?? 0),
                    'is_active' => true,
                ]
            );

            $count++;
        }

        fclose($handle);
        $this->command->info("Shipping tariff rows processed: {$count}");
    }

    private function normalizeCompany(?string $rawName): string
    {
        $name = strtolower(trim((string)$rawName));
        if ($name === '') {
            return '';
        }

        return match ($name) {
            'delhiverys' => 'Delhivery',
            'delhivery' => 'Delhivery',
            'indiapost' => 'India Post',
            'dtdc' => 'DTDC',
            'ecom' => 'Ecom',
            'lastmile' => 'Lastmile',
            'wla' => 'WLA',
            default => Str::title($name),
        };
    }

    private function toDecimal(mixed $value): float
    {
        return (float) trim((string) $value);
    }
}

