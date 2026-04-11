<?php

namespace Database\Seeders;

use App\Models\PinCity;
use App\Models\PinDistrict;
use App\Models\PinRegion;
use App\Models\PinServiceArea;
use App\Models\PinZone;
use App\Models\State;
use Illuminate\Database\Seeder;

class PinServiceNormalizationSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedZones();
        $this->seedRegions();
        $this->backfillLocationMastersAndReferences();
    }

    private function seedZones(): void
    {
        $defaults = [
            'A' => ['name' => 'Zone A', 'default_delivery_time' => '1-2 Days'],
            'B' => ['name' => 'Zone B', 'default_delivery_time' => '3-4 Days'],
            'C' => ['name' => 'Zone C', 'default_delivery_time' => '4-6 Days'],
            'D' => ['name' => 'Zone D', 'default_delivery_time' => '5-7 Days'],
            'E' => ['name' => 'Zone E', 'default_delivery_time' => '6-8 Days'],
        ];

        foreach ($defaults as $code => $meta) {
            PinZone::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $meta['name'],
                    'default_delivery_time' => $meta['default_delivery_time'],
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedRegions(): void
    {
        $regionNames = PinServiceArea::query()
            ->select('zone1')
            ->distinct()
            ->pluck('zone1')
            ->map(fn ($name) => $this->normalizeText($name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        foreach ($regionNames as $name) {
            PinRegion::firstOrCreate(['name' => $name], ['is_active' => true]);
        }
    }

    private function backfillLocationMastersAndReferences(): void
    {
        PinServiceArea::query()
            ->orderBy('id')
            ->chunkById(500, function ($rows): void {
                foreach ($rows as $row) {
                    $stateName = $this->normalizeText($row->state);
                    $districtName = $this->normalizeText($row->district);
                    $cityName = $this->normalizeText($row->city);
                    $regionName = $this->normalizeText($row->zone1);
                    $zoneCode = strtoupper($this->normalizeText($row->zone));

                    $state = null;
                    if ($stateName !== '') {
                        $state = State::firstOrCreate(
                            ['name' => $stateName],
                            ['country_id' => 101, 'is_ut' => false]
                        );
                    }

                    $district = null;
                    if ($state && $districtName !== '') {
                        $district = PinDistrict::firstOrCreate(
                            ['state_id' => $state->id, 'name' => $districtName],
                            ['is_active' => true]
                        );
                    }

                    $city = null;
                    if ($state && $district && $cityName !== '') {
                        $city = PinCity::firstOrCreate(
                            [
                                'state_id' => $state->id,
                                'district_id' => $district->id,
                                'name' => $cityName,
                            ],
                            ['is_active' => true]
                        );
                    }

                    $zone = null;
                    if ($zoneCode !== '') {
                        $zone = PinZone::firstOrCreate(
                            ['code' => $zoneCode],
                            ['name' => 'Zone ' . $zoneCode, 'is_active' => true]
                        );
                    }

                    $region = null;
                    if ($regionName !== '') {
                        $region = PinRegion::firstOrCreate(
                            ['name' => $regionName],
                            ['is_active' => true]
                        );
                    }

                    $row->update([
                        'state_id' => $state?->id,
                        'district_id' => $district?->id,
                        'city_id' => $city?->id,
                        'zone_id' => $zone?->id,
                        'region_id' => $region?->id,
                    ]);
                }
            });
    }

    private function normalizeText(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return $value;
    }
}
