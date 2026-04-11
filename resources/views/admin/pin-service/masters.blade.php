@extends('layouts.admin.app', ['page' => 'pin_service', 'sub_page' => ''])

@section('title', 'Pin Service Master Data')

@section('header_data')
    @php
        $page_title = 'Pin Service Master Data';
        $page_pretitle = 'Settings';
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'Settings', 'url' => route('admin.settings.index')],
        ['title' => 'Pin Service Areas', 'url' => route('admin.pin-service.index')],
        ['title' => 'Master Data', 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Pin Service Master Data</h2>
                <x-breadcrumb :items="$breadcrumbs" />
            </div>
            <div class="col-auto ms-auto">
                <a href="{{ route('admin.pin-service.index') }}" class="btn btn-outline-secondary">Back to Pin Service</a>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Districts</h3>
                </div>
                <div class="card-body border-bottom">
                    <form method="POST" action="{{ route('admin.pin-service.masters.districts.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-5">
                            <label class="form-label required">State</label>
                            <select name="state_id" class="form-select" required>
                                <option value="">Select state</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label required">District</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <form class="mb-3" method="GET" action="{{ route('admin.pin-service.masters.index') }}">
                        <div class="input-group">
                            <input type="text" class="form-control" name="district_search" value="{{ $districtSearch }}" placeholder="Search districts...">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                            <tr>
                                <th>State</th>
                                <th>District</th>
                                <th class="w-1">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($districts as $district)
                                <tr>
                                    <td style="min-width: 180px;">
                                        <form method="POST" action="{{ route('admin.pin-service.masters.districts.update', $district->id) }}" class="d-flex gap-2">
                                            @csrf
                                            <select name="state_id" class="form-select form-select-sm" required>
                                                @foreach($states as $state)
                                                    <option value="{{ $state->id }}" @selected((int) $district->state_id === (int) $state->id)>{{ $state->name }}</option>
                                                @endforeach
                                            </select>
                                    </td>
                                    <td style="min-width: 180px;">
                                            <input type="text" name="name" value="{{ $district->name }}" class="form-control form-control-sm" required maxlength="100">
                                    </td>
                                    <td class="text-nowrap">
                                            <button class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.pin-service.masters.districts.destroy', $district->id) }}" class="d-inline" onsubmit="return confirm('Delete this district?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted">No districts found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $districts->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Cities</h3>
                </div>
                <div class="card-body border-bottom">
                    <form method="POST" action="{{ route('admin.pin-service.masters.cities.store') }}" class="row g-2">
                        @csrf
                        <div class="col-md-4">
                            <label class="form-label required">State</label>
                            <select name="state_id" id="addCityState" class="form-select" required>
                                <option value="">Select state</option>
                                @foreach($states as $state)
                                    <option value="{{ $state->id }}">{{ $state->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">District</label>
                            <select name="district_id" id="addCityDistrict" class="form-select" required disabled>
                                <option value="">Select district</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">City</label>
                            <input type="text" name="name" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <form class="mb-3" method="GET" action="{{ route('admin.pin-service.masters.index') }}">
                        <div class="input-group">
                            <input type="text" class="form-control" name="city_search" value="{{ $citySearch }}" placeholder="Search cities...">
                            <button class="btn btn-outline-secondary" type="submit">Search</button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                            <tr>
                                <th>State</th>
                                <th>District</th>
                                <th>City</th>
                                <th class="w-1">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($cities as $city)
                                <tr>
                                    <td style="min-width: 150px;">
                                        <form method="POST" action="{{ route('admin.pin-service.masters.cities.update', $city->id) }}" class="d-flex gap-2">
                                            @csrf
                                            <select name="state_id" class="form-select form-select-sm js-city-state" required>
                                                @foreach($states as $state)
                                                    <option value="{{ $state->id }}" @selected((int) $city->state_id === (int) $state->id)>{{ $state->name }}</option>
                                                @endforeach
                                            </select>
                                    </td>
                                    <td style="min-width: 170px;">
                                            <select
                                                name="district_id"
                                                class="form-select form-select-sm js-city-district"
                                                data-selected-district="{{ $city->district_id }}"
                                                required
                                                disabled
                                            >
                                                <option value="">Select district</option>
                                            </select>
                                    </td>
                                    <td style="min-width: 150px;">
                                            <input type="text" name="name" value="{{ $city->name }}" class="form-control form-control-sm" required maxlength="100">
                                    </td>
                                    <td class="text-nowrap">
                                            <button class="btn btn-sm btn-outline-primary">Save</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.pin-service.masters.cities.destroy', $city->id) }}" class="d-inline" onsubmit="return confirm('Delete this city?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-muted">No cities found.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $cities->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
@endsection

@php
    $districtOptionsJson = $districtOptions->map(function ($d) {
        return [
            'id' => (int) $d->id,
            'name' => $d->name,
            'state_id' => (int) $d->state_id,
        ];
    })->values()->toJson();
@endphp

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            const districtOptions = {!! $districtOptionsJson !!};

            function fillDistrictSelect(selectEl, stateId, selectedDistrictId = null) {
                const sid = Number(stateId || 0);
                const filtered = districtOptions.filter(d => d.state_id === sid);

                selectEl.innerHTML = '<option value="">Select district</option>';
                filtered.forEach((d) => {
                    const opt = document.createElement('option');
                    opt.value = String(d.id);
                    opt.textContent = d.name;
                    if (selectedDistrictId && Number(selectedDistrictId) === Number(d.id)) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });
                selectEl.disabled = filtered.length === 0;
            }

            // Add City form
            const addState = document.getElementById('addCityState');
            const addDistrict = document.getElementById('addCityDistrict');
            if (addState && addDistrict) {
                addState.addEventListener('change', function () {
                    fillDistrictSelect(addDistrict, addState.value);
                });
            }

            // Edit City rows
            document.querySelectorAll('.js-city-state').forEach((stateSelect) => {
                const row = stateSelect.closest('tr');
                const districtSelect = row?.querySelector('.js-city-district');
                if (!districtSelect) return;

                const selectedDistrict = districtSelect.dataset.selectedDistrict || null;
                fillDistrictSelect(districtSelect, stateSelect.value, selectedDistrict);

                stateSelect.addEventListener('change', function () {
                    fillDistrictSelect(districtSelect, stateSelect.value);
                });
            });
        });
    </script>
@endpush
