@extends('layouts.admin.app', ['page' => $menuAdmin['tax_rates']['active'] ?? ""])

@section('title', __('labels.tax_rates'))

@section('header_data')
    @php
        $page_title = __('labels.tax_rates');
        $page_pretitle = __('labels.list');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => 'GST / Tax Rates', 'url' => null],
    ];
    $taxClassBreadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.tax_group'), 'url' => null],
    ];
    $gstSlabs = [
        '0'  => ['label' => '0% — Nil (Exempt)',          'cgst' => 0,   'sgst' => 0,   'igst' => 0],
        '5'  => ['label' => '5% — Basic Necessities',     'cgst' => 2.5, 'sgst' => 2.5, 'igst' => 5],
        '12' => ['label' => '12% — Paper / Paperboard',   'cgst' => 6,   'sgst' => 6,   'igst' => 12],
        '18' => ['label' => '18% — Plastic / Electronics','cgst' => 9,   'sgst' => 9,   'igst' => 18],
        '28' => ['label' => '28% — Luxury / Demerit',     'cgst' => 14,  'sgst' => 14,  'igst' => 28],
    ];
@endphp

@section('admin-content')

    {{-- ── GST REFERENCE CARD ──────────────────────────────────── --}}
    <div class="row row-cards mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                             class="icon me-2 text-primary">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                            <path d="M12 8l0 4l2 2"/>
                        </svg>
                        Indian GST Slabs (2026)
                    </h3>
                    <div class="card-options">
                        <small class="text-muted">Intra-state: CGST + SGST &nbsp;|&nbsp; Inter-state: IGST only</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-vcenter table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>GST Slab</th>
                                    <th>CGST</th>
                                    <th>SGST</th>
                                    <th>IGST</th>
                                    <th>Common Goods</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><span class="badge bg-secondary-lt fw-bold">0% — Nil</span></td>
                                    <td>0%</td><td>0%</td><td>0%</td>
                                    <td class="text-muted small">Fresh vegetables, milk, eggs, printed books, newspapers</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-success-lt fw-bold">5%</span></td>
                                    <td>2.5%</td><td>2.5%</td><td>5%</td>
                                    <td class="text-muted small">Sugar, tea, edible oils, handloom, eco/biodegradable bags</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-info-lt fw-bold">12%</span></td>
                                    <td>6%</td><td>6%</td><td>12%</td>
                                    <td class="text-muted small">Paper &amp; paperboard packaging (HSN 4819), computers, processed food</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-warning-lt fw-bold">18%</span></td>
                                    <td>9%</td><td>9%</td><td>18%</td>
                                    <td class="text-muted small">Plastic packaging &amp; pouches (HSN 3923), electronics, most services</td>
                                </tr>
                                <tr>
                                    <td><span class="badge bg-danger-lt fw-bold">28%</span></td>
                                    <td>14%</td><td>14%</td><td>28%</td>
                                    <td class="text-muted small">Luxury goods, automobiles, tobacco, aerated drinks</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TAX RATES DATATABLE ──────────────────────────────────── --}}
    <div class="row row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.tax_rates') }}</h3>
                        <x-breadcrumb :items="$breadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($createPermission ?? false)
                                    <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal"
                                       data-bs-target="#tax-rate-modal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                        </svg>
                                        Add Tax Rate
                                    </a>
                                @endif
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary" id="refresh">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round"
                                         class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                    </svg>
                                    {{ __('labels.refresh') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="tax-rates-table" :columns="$columns"
                                     route="{{ route('admin.tax-rates.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── TAX CLASSES DATATABLE ────────────────────────────────── --}}
    <div class="row row-cards mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h3 class="card-title">{{ __('labels.tax_groups') }}</h3>
                        <x-breadcrumb :items="$taxClassBreadcrumbs"/>
                    </div>
                    <div class="card-actions">
                        <div class="row g-2">
                            <div class="col-auto">
                                @if($taxClassCreatePermission ?? false)
                                    <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal"
                                       data-bs-target="#tax-class-modal">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                             stroke-linecap="round" stroke-linejoin="round" class="icon icon-2">
                                            <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                        </svg>
                                        Create Tax Group
                                    </a>
                                @endif
                            </div>
                            <div class="col-auto">
                                <button class="btn btn-outline-primary refresh-table">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                         viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                         stroke-linecap="round" stroke-linejoin="round"
                                         class="icon icon-tabler icons-tabler-outline icon-tabler-refresh">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4"/>
                                        <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4"/>
                                    </svg>
                                    {{ __('labels.refresh') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-table">
                    <div class="row w-full p-3">
                        <x-datatable id="tax-group-table" :columns="$classColumns"
                                     route="{{ route('admin.tax-classes.datatable') }}"
                                     :options="['order' => [[0, 'desc']],'pageLength' => 10,]"/>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── ADD / EDIT TAX RATE MODAL ────────────────────────────── --}}
    @if(($createPermission ?? false) || ($editPermission ?? false))
        <div class="modal modal-blur fade" id="tax-rate-modal" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit" action="{{ route('admin.tax-rates.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="tax-rate-modal-label">Add Tax Rate</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required">Title</label>
                                    <input type="text" class="form-control" name="title"
                                           placeholder="e.g. Plastic Packaging 18%" required/>
                                </div>

                                {{-- GST Slab selector (auto-fills CGST/SGST/IGST) --}}
                                <div class="col-md-6">
                                    <label class="form-label required">GST Slab</label>
                                    <select class="form-select" name="gst_slab" id="gst-slab-select" required>
                                        <option value="">— Select GST Slab —</option>
                                        @foreach($gstSlabs as $pct => $info)
                                            <option value="{{ $pct }}"
                                                    data-cgst="{{ $info['cgst'] }}"
                                                    data-sgst="{{ $info['sgst'] }}"
                                                    data-igst="{{ $info['igst'] }}">
                                                {{ $info['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Rate (%)</label>
                                    <input type="number" class="form-control" name="rate" id="rate-input"
                                           min="0" max="100" step="0.5" placeholder="18" readonly/>
                                    <small class="form-hint">Auto-filled from GST slab selection</small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">CGST Rate (%)</label>
                                    <input type="number" class="form-control bg-light" name="cgst_rate" id="cgst-rate"
                                           min="0" max="50" step="0.5" readonly/>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">SGST Rate (%)</label>
                                    <input type="number" class="form-control bg-light" name="sgst_rate" id="sgst-rate"
                                           min="0" max="50" step="0.5" readonly/>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">IGST Rate (%)</label>
                                    <input type="number" class="form-control bg-light" name="igst_rate" id="igst-rate"
                                           min="0" max="100" step="0.5" readonly/>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="2"
                                              placeholder="Goods covered under this rate..."></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_gst" value="1" checked/>
                                        <span class="form-check-label">Is GST Rate</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked/>
                                        <span class="form-check-label">Active</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-2">
                                    <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                </svg>
                                Save Tax Rate
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ── ADD / EDIT TAX CLASS MODAL ──────────────────────────── --}}
    @if(($taxClassEditPermission ?? false) || ($taxClassCreatePermission ?? false))
        <div class="modal modal-blur fade" id="tax-class-modal" tabindex="-1" role="dialog" aria-hidden="true"
             data-bs-backdrop="static">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form class="form-submit tax-class-form" action="{{ route('admin.tax-classes.store') }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Create Tax Group</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label required">Title</label>
                                    <input type="text" class="form-control" name="title" id="class-title"
                                           placeholder="e.g. Plastic Packaging — 18% GST" required/>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="2"
                                              placeholder="Describe this tax group..."></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label required">Tax Rates (GST slabs)</label>
                                    <select class="form-select" id="select-tax-rate" name="tax_rate_ids[]" multiple></select>
                                    <small class="form-hint">Search and select the applicable GST rates</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" checked/>
                                        <span class="form-check-label">Active</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn" data-bs-dismiss="modal">{{ __('labels.cancel') }}</a>
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                     stroke-linejoin="round" class="icon icon-2">
                                    <path d="M12 5l0 14"/><path d="M5 12l14 0"/>
                                </svg>
                                Create Tax Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

@endsection

<script>
document.addEventListener("DOMContentLoaded", function () {

    // ── GST Slab auto-fill ──────────────────────────────────────
    const slabSelect = document.getElementById('gst-slab-select');
    if (slabSelect) {
        slabSelect.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            document.getElementById('rate-input').value  = opt.value || '';
            document.getElementById('cgst-rate').value   = opt.dataset.cgst || '';
            document.getElementById('sgst-rate').value   = opt.dataset.sgst || '';
            document.getElementById('igst-rate').value   = opt.dataset.igst || '';
        });
    }

    // ── Refresh button ──────────────────────────────────────────
    document.getElementById('refresh')?.addEventListener('click', function () {
        $('#tax-rates-table').DataTable().ajax.reload();
    });
    document.querySelector('.refresh-table')?.addEventListener('click', function () {
        $('#tax-group-table').DataTable().ajax.reload();
    });

    // ── TomSelect for tax rate search ───────────────────────────
    const el = document.getElementById("select-tax-rate");
    if (el && window.TomSelect) {
        new TomSelect(el, {
            valueField: 'value',
            labelField: 'title',
            searchField: 'title',
            copyClassesToDropdown: false,
            dropdownParent: "body",
            controlInput: "<input>",
            render: {
                item:   function (data, escape) { return "<div>" + escape(data.title) + "</div>"; },
                option: function (data, escape) { return "<div>" + escape(data.title) + "</div>"; },
            },
            load: function (query, callback) {
                fetch(base_url + '/admin/tax-rates/search?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(json => callback(json))
                    .catch(() => callback());
            },
        });
    }
});
</script>
