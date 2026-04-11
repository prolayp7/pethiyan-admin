<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PinCity;
use App\Models\PinDistrict;
use App\Models\State;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PinLocationMasterController extends Controller
{
    public function index(Request $request)
    {
        $states = State::query()->orderBy('name')->get(['id', 'name']);

        $districtSearch = trim((string) $request->input('district_search', ''));
        $citySearch = trim((string) $request->input('city_search', ''));

        $districts = PinDistrict::query()
            ->with('state:id,name')
            ->when($districtSearch !== '', function ($q) use ($districtSearch) {
                $q->where('name', 'like', '%' . $districtSearch . '%');
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'district_page')
            ->appends($request->query());

        $cities = PinCity::query()
            ->with(['state:id,name', 'district:id,name'])
            ->when($citySearch !== '', function ($q) use ($citySearch) {
                $q->where('name', 'like', '%' . $citySearch . '%');
            })
            ->orderBy('name')
            ->paginate(15, ['*'], 'city_page')
            ->appends($request->query());

        $districtOptions = PinDistrict::query()
            ->with('state:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'state_id']);

        return view('admin.pin-service.masters', compact(
            'states',
            'districts',
            'cities',
            'districtOptions',
            'districtSearch',
            'citySearch'
        ));
    }

    public function storeDistrict(Request $request): RedirectResponse
    {
        $data = $this->validateDistrict($request);
        PinDistrict::create($data + ['is_active' => true]);

        return back()->with('success', 'District added successfully.');
    }

    public function updateDistrict(Request $request, int $id): RedirectResponse
    {
        $district = PinDistrict::findOrFail($id);
        $data = $this->validateDistrict($request, $id);
        $district->update($data);

        return back()->with('success', 'District updated successfully.');
    }

    public function destroyDistrict(int $id): RedirectResponse
    {
        $district = PinDistrict::findOrFail($id);
        $district->delete();

        return back()->with('success', 'District deleted successfully.');
    }

    public function storeCity(Request $request): RedirectResponse
    {
        $data = $this->validateCity($request);
        PinCity::create($data + ['is_active' => true]);

        return back()->with('success', 'City added successfully.');
    }

    public function updateCity(Request $request, int $id): RedirectResponse
    {
        $city = PinCity::findOrFail($id);
        $data = $this->validateCity($request, $id);
        PinCity::query()->where('id', $id)->update($data);

        return back()->with('success', 'City updated successfully.');
    }

    public function destroyCity(int $id): RedirectResponse
    {
        $city = PinCity::findOrFail($id);
        $city->delete();

        return back()->with('success', 'City deleted successfully.');
    }

    private function validateDistrict(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'state_id' => ['required', 'integer', Rule::exists('states', 'id')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('districts', 'name')
                    ->where(fn ($q) => $q->where('state_id', $request->integer('state_id')))
                    ->ignore($ignoreId),
            ],
        ]);
    }

    private function validateCity(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'state_id' => ['required', 'integer', Rule::exists('states', 'id')],
            'district_id' => ['required', 'integer', Rule::exists('districts', 'id')],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('cities', 'name')
                    ->where(fn ($q) => $q->where('district_id', $request->integer('district_id')))
                    ->ignore($ignoreId),
            ],
        ]);

        $district = PinDistrict::query()->find($data['district_id']);
        if (!$district || (int) $district->state_id !== (int) $data['state_id']) {
            throw ValidationException::withMessages([
                'district_id' => ['Selected district does not belong to selected state.'],
            ]);
        }

        return $data;
    }
}
