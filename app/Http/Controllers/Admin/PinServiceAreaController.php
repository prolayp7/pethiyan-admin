<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinCity;
use App\Models\PinDistrict;
use App\Models\PinRegion;
use App\Models\PinServiceArea;
use App\Models\PinZone;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PinServiceAreaController extends Controller
{
    public function index()
    {
        $totalPins    = PinServiceArea::count();
        $serviceable  = PinServiceArea::where('is_serviceable', true)->count();
        $states       = State::query()->orderBy('name')->pluck('name');
        $regions      = PinRegion::query()->orderBy('name')->pluck('name');

        if ($states->isEmpty()) {
            $states = PinServiceArea::query()
                ->whereNotNull('state')
                ->where('state', '!=', '')
                ->select('state')
                ->distinct()
                ->orderBy('state')
                ->pluck('state');
        }

        if ($regions->isEmpty()) {
            $regions = PinServiceArea::query()
                ->whereNotNull('zone1')
                ->where('zone1', '!=', '')
                ->select('zone1')
                ->distinct()
                ->orderBy('zone1')
                ->pluck('zone1');
        }

        $zones = PinZone::query()
            ->orderBy('code')
            ->get(['code', 'default_delivery_time'])
            ->mapWithKeys(fn ($z) => [$z->code => $z->default_delivery_time ?? ''])
            ->toArray();

        if (empty($zones)) {
            $zones = ['A' => '1-2 Days', 'B' => '3-4 Days', 'C' => '4-6 Days', 'D' => '5-7 Days', 'E' => '6-8 Days'];
        }

        return view('admin.pin-service.index', compact('totalPins', 'serviceable', 'states', 'zones', 'regions'));
    }

    public function districts(Request $request)
    {
        $request->validate([
            'state' => 'required|string|max:100',
        ]);

        $stateName = $this->normalizeText($request->string('state')->toString());

        $state = State::query()->where('name', $stateName)->first();

        if (!$state) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $districts = PinDistrict::query()
            ->where('state_id', $state->id)
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json(['success' => true, 'data' => $districts]);
    }

    public function cities(Request $request)
    {
        $request->validate([
            'state' => 'required|string|max:100',
            'district' => 'required|string|max:100',
        ]);

        $stateName = $this->normalizeText($request->string('state')->toString());
        $districtName = $this->normalizeText($request->string('district')->toString());

        $state = State::query()->where('name', $stateName)->first();
        if (!$state) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $district = PinDistrict::query()
            ->where('state_id', $state->id)
            ->where('name', $districtName)
            ->first();

        if (!$district) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $cities = PinCity::query()
            ->where('state_id', $state->id)
            ->where('district_id', $district->id)
            ->orderBy('name')
            ->pluck('name')
            ->values();

        return response()->json(['success' => true, 'data' => $cities]);
    }

    public function datatable(Request $request)
    {
        $query = PinServiceArea::query()
            ->leftJoin('states', 'states.id', '=', 'pin_service_areas.state_id')
            ->leftJoin('districts', 'districts.id', '=', 'pin_service_areas.district_id')
            ->leftJoin('cities', 'cities.id', '=', 'pin_service_areas.city_id')
            ->leftJoin('pin_zones', 'pin_zones.id', '=', 'pin_service_areas.zone_id')
            ->select('pin_service_areas.*')
            ->selectRaw('COALESCE(states.name, pin_service_areas.state) as state_name')
            ->selectRaw('COALESCE(districts.name, pin_service_areas.district) as district_name')
            ->selectRaw('COALESCE(cities.name, pin_service_areas.city) as city_name')
            ->selectRaw('COALESCE(pin_zones.code, pin_service_areas.zone) as zone_code');

        // Global search
        if ($search = $request->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('pin_service_areas.pincode', 'like', "%{$search}%")
                  ->orWhereRaw('COALESCE(states.name, pin_service_areas.state) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('COALESCE(districts.name, pin_service_areas.district) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('COALESCE(cities.name, pin_service_areas.city) LIKE ?', ["%{$search}%"]);
            });
        }

        // Column filters
        if ($state = $request->input('filter_state')) {
            $query->where(function ($q) use ($state) {
                $q->where('states.name', $state)
                  ->orWhere('pin_service_areas.state', $state);
            });
        }
        if ($zone = $request->input('filter_zone')) {
            $query->where(function ($q) use ($zone) {
                $q->where('pin_zones.code', $zone)
                  ->orWhere('pin_service_areas.zone', $zone);
            });
        }
        if ($request->input('filter_serviceable') !== null && $request->input('filter_serviceable') !== '') {
            $query->where('pin_service_areas.is_serviceable', (bool) $request->input('filter_serviceable'));
        }

        $total    = PinServiceArea::count();
        $filtered = (clone $query)->count('pin_service_areas.id');

        $columns = ['pin_service_areas.pincode', 'state_name', 'district_name', 'city_name', 'zone_code', 'pin_service_areas.delivery_time', 'pin_service_areas.is_serviceable'];
        $orderCol = $columns[$request->input('order.0.column', 0)] ?? 'pincode';
        $orderDir = $request->input('order.0.dir', 'asc');

        $dataQuery = $query;
        if (in_array($orderCol, ['state_name', 'district_name', 'city_name', 'zone_code'], true)) {
            $dataQuery->orderByRaw("{$orderCol} {$orderDir}");
        } else {
            $dataQuery->orderBy($orderCol, $orderDir);
        }

        $data = $dataQuery
            ->skip($request->input('start', 0))
            ->take($request->input('length', 25))
            ->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'pincode'        => $p->pincode,
                'state'          => $p->state_name,
                'district'       => $p->district_name,
                'city'           => $p->city_name,
                'zone'           => $p->zone_code,
                'delivery_time'  => $p->delivery_time,
                'is_serviceable' => $p->is_serviceable,
            ]);

        return response()->json([
            'draw'            => $request->input('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
            'data'            => $data,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'pincode'        => 'required|digits:6|unique:pin_service_areas,pincode',
            'state'          => 'required|string|max:100',
            'district'       => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'zone'           => 'required|in:A,B,C,D,E',
            'zone1'          => 'nullable|string|max:50',
            'delivery_time'  => 'nullable|string|max:30',
            'is_serviceable' => 'boolean',
        ]);

        $refs = $this->resolveReferenceIds(
            state: $data['state'] ?? null,
            district: $data['district'] ?? null,
            city: $data['city'] ?? null,
            zone: $data['zone'] ?? null,
            region: $data['zone1'] ?? null,
            createMissing: false
        );

        $this->assertValidLocationReferences($refs);

        $data = array_merge($data, $refs);
        $pin = PinServiceArea::create($data);

        return response()->json(['success' => true, 'data' => $pin]);
    }

    public function show(int $id)
    {
        $pin = PinServiceArea::with(['stateRef', 'districtRef', 'cityRef', 'zoneRef', 'regionRef'])->findOrFail($id);
        return response()->json([
            'id' => $pin->id,
            'pincode' => $pin->pincode,
            'state' => $pin->stateRef?->name ?? $pin->state,
            'district' => $pin->districtRef?->name ?? $pin->district,
            'city' => $pin->cityRef?->name ?? $pin->city,
            'zone' => $pin->zoneRef?->code ?? $pin->zone,
            'zone1' => $pin->regionRef?->name ?? $pin->zone1,
            'delivery_time' => $pin->delivery_time,
            'is_serviceable' => (bool) $pin->is_serviceable,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $pin = PinServiceArea::findOrFail($id);

        $data = $request->validate([
            'pincode'        => 'required|digits:6|unique:pin_service_areas,pincode,' . $id,
            'state'          => 'required|string|max:100',
            'district'       => 'required|string|max:100',
            'city'           => 'required|string|max:100',
            'zone'           => 'required|in:A,B,C,D,E',
            'zone1'          => 'nullable|string|max:50',
            'delivery_time'  => 'nullable|string|max:30',
            'is_serviceable' => 'boolean',
        ]);

        $refs = $this->resolveReferenceIds(
            state: $data['state'] ?? null,
            district: $data['district'] ?? null,
            city: $data['city'] ?? null,
            zone: $data['zone'] ?? null,
            region: $data['zone1'] ?? null,
            createMissing: false
        );

        $this->assertValidLocationReferences($refs);

        $data = array_merge($data, $refs);
        $pin->update($data);

        return response()->json(['success' => true, 'data' => $pin]);
    }

    public function toggleServiceable(int $id)
    {
        $pin = PinServiceArea::findOrFail($id);
        $pin->update(['is_serviceable' => !$pin->is_serviceable]);

        return response()->json(['success' => true, 'is_serviceable' => $pin->is_serviceable]);
    }

    public function destroy(int $id)
    {
        PinServiceArea::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function bulkToggle(Request $request)
    {
        $request->validate([
            'ids'            => 'required|array',
            'ids.*'          => 'integer',
            'is_serviceable' => 'required|boolean',
        ]);

        PinServiceArea::whereIn('id', $request->ids)->update([
            'is_serviceable' => $request->is_serviceable,
        ]);

        return response()->json(['success' => true]);
    }

    public function importCsv(Request $request)
    {
        $request->validate(['csv_file' => 'required|file|mimes:csv,txt']);

        $file   = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        fgetcsv($handle); // skip header

        $chunk  = [];
        $count  = 0;
        $now    = now()->toDateTimeString();

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 7) continue;

            // Support both 7-col (no id) and 8-col (with id) formats
            $offset   = count($row) >= 8 ? 1 : 0;
            $pincode  = trim($row[$offset]);
            $state    = trim($row[$offset + 1]);
            $district = trim($row[$offset + 2]);
            $city     = trim($row[$offset + 3]);
            $zone     = strtoupper(trim($row[$offset + 4]));
            $zone1    = trim($row[$offset + 5]);
            $dtime    = trim($row[$offset + 6]);

            if (!$pincode || !in_array($zone, ['A','B','C','D','E'])) continue;

            $refs = $this->resolveReferenceIds(
                state: $state,
                district: $district,
                city: $city,
                zone: $zone,
                region: $zone1,
                createMissing: true
            );

            $chunk[] = [
                'pincode'        => $pincode,
                'state'          => $state,
                'district'       => $district,
                'city'           => $city,
                'zone'           => $zone,
                'zone1'          => $zone1,
                'state_id'       => $refs['state_id'],
                'district_id'    => $refs['district_id'],
                'city_id'        => $refs['city_id'],
                'zone_id'        => $refs['zone_id'],
                'region_id'      => $refs['region_id'],
                'delivery_time'  => $dtime,
                'is_serviceable' => true,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            $count++;

            if (count($chunk) >= 500) {
                DB::table('pin_service_areas')->upsert($chunk, ['pincode'], [
                    'state','district','city','zone','zone1','state_id','district_id','city_id','zone_id','region_id','delivery_time','updated_at',
                ]);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            DB::table('pin_service_areas')->upsert($chunk, ['pincode'], [
                'state','district','city','zone','zone1','state_id','district_id','city_id','zone_id','region_id','delivery_time','updated_at',
            ]);
        }

        fclose($handle);

        return response()->json(['success' => true, 'imported' => $count]);
    }

    private function resolveReferenceIds(
        ?string $state,
        ?string $district,
        ?string $city,
        ?string $zone,
        ?string $region,
        bool $createMissing = false
    ): array
    {
        $stateName = $this->normalizeText($state);
        $districtName = $this->normalizeText($district);
        $cityName = $this->normalizeText($city);
        $zoneCode = strtoupper($this->normalizeText($zone));
        $regionName = $this->normalizeText($region);

        $stateModel = null;
        if ($stateName !== '') {
            $stateModel = $createMissing
                ? State::firstOrCreate(['name' => $stateName], ['country_id' => 101, 'is_ut' => false])
                : State::query()->where('name', $stateName)->first();
        }

        $districtModel = null;
        if ($stateModel && $districtName !== '') {
            $districtModel = $createMissing
                ? PinDistrict::firstOrCreate(
                    ['state_id' => $stateModel->id, 'name' => $districtName],
                    ['is_active' => true]
                )
                : PinDistrict::query()
                    ->where('state_id', $stateModel->id)
                    ->where('name', $districtName)
                    ->first();
        }

        $cityModel = null;
        if ($stateModel && $districtModel && $cityName !== '') {
            $cityModel = $createMissing
                ? PinCity::firstOrCreate(
                    [
                        'state_id' => $stateModel->id,
                        'district_id' => $districtModel->id,
                        'name' => $cityName,
                    ],
                    ['is_active' => true]
                )
                : PinCity::query()
                    ->where('state_id', $stateModel->id)
                    ->where('district_id', $districtModel->id)
                    ->where('name', $cityName)
                    ->first();
        }

        $zoneModel = null;
        if ($zoneCode !== '') {
            $zoneModel = $createMissing
                ? PinZone::firstOrCreate(
                    ['code' => $zoneCode],
                    ['name' => 'Zone ' . $zoneCode, 'is_active' => true]
                )
                : PinZone::query()->where('code', $zoneCode)->first();
        }

        $regionModel = null;
        if ($regionName !== '') {
            $regionModel = $createMissing
                ? PinRegion::firstOrCreate(
                    ['name' => $regionName],
                    ['is_active' => true]
                )
                : PinRegion::query()->where('name', $regionName)->first();
        }

        return [
            'state' => $stateName,
            'district' => $districtName !== '' ? $districtName : null,
            'city' => $cityName !== '' ? $cityName : null,
            'zone' => $zoneCode,
            'zone1' => $regionName !== '' ? $regionName : null,
            'state_id' => $stateModel?->id,
            'district_id' => $districtModel?->id,
            'city_id' => $cityModel?->id,
            'zone_id' => $zoneModel?->id,
            'region_id' => $regionModel?->id,
        ];
    }

    /**
     * For manual admin add/edit we enforce strict normalized references.
     * This prevents typo-driven ghost locations and guarantees hierarchy integrity.
     *
     * @throws ValidationException
     */
    private function assertValidLocationReferences(array $refs): void
    {
        $errors = [];

        if (empty($refs['state_id'])) {
            $errors['state'] = ['Selected state is not available in master data.'];
        }
        if (empty($refs['district_id'])) {
            $errors['district'] = ['Selected district does not belong to the selected state.'];
        }
        if (empty($refs['city_id'])) {
            $errors['city'] = ['Selected city does not belong to the selected district/state.'];
        }
        if (empty($refs['zone_id'])) {
            $errors['zone'] = ['Selected zone is not available in master data.'];
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
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
