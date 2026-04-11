@extends('layouts.admin.app', ['page' => $menuAdmin['settings']['active'] ?? "", 'sub_page' => $menuAdmin['settings']['route']['storage']['sub_active'] ?? "" ])

@section('title', __('labels.storage_settings'))

@section('header_data')
    @php
        $page_title = __('labels.storage_settings');
        $page_pretitle = __('labels.admin') . " " . __('labels.settings');
        $currentDriver = $settings['storageDriver'] ?? 'local';
        $publicPath = rtrim(str_replace('\\', '/', public_path()), '/');
    @endphp
@endsection

@php
    $breadcrumbs = [
        ['title' => __('labels.home'), 'url' => route('admin.dashboard')],
        ['title' => __('labels.settings'), 'url' => route('admin.settings.index')],
        ['title' => __('labels.storage_settings'), 'url' => null],
    ];
@endphp

@section('admin-content')
    <div class="page-header d-print-none">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">{{ __('labels.storage_settings') }}</h2>
                <x-breadcrumb :items="$breadcrumbs"/>
            </div>
        </div>
    </div>
    <!-- BEGIN PAGE BODY -->
    <div class="page-body">
        <div class="container-xl">
            <div class="row g-5">
                <div class="col-sm-2 d-none d-lg-block">
                    <div class="sticky-top">
                        <h3>{{ __('labels.menu') }}</h3>
                        <nav class="nav nav-vertical nav-pills" id="pills">
                            <a class="nav-link" href="#pills-driver">Storage Driver</a>
                            <a class="nav-link" href="#pills-local">Local Storage</a>
                            {{-- <a class="nav-link" href="#pills-aws">{{ __('labels.aws_s3') }}</a> --}}
                        </nav>
                    </div>
                </div>
                <div class="col-sm" data-bs-spy="scroll" data-bs-target="#pills" data-bs-offset="0">
                    <div class="row row-cards">
                        <div class="col-12">
                            <form action="{{ route('admin.settings.store') }}" class="form-submit" method="post">
                                @csrf
                                <input type="hidden" name="type" value="storage">

                                {{-- ── Storage Driver ── --}}
                                <div class="card mb-4" id="pills-driver">
                                    <div class="card-header">
                                        <h4 class="card-title">Storage Driver</h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label required">Active Storage Driver</label>
                                            <select class="form-select" name="storageDriver" id="storageDriverSelect">
                                                <option value="local" {{ $currentDriver === 'local' ? 'selected' : '' }}>
                                                    Local Storage (public disk)
                                                </option>
                                                {{-- <option value="s3" {{ $currentDriver === 's3' ? 'selected' : '' }}>
                                                    AWS S3
                                                </option> --}}
                                            </select>
                                            <small class="form-hint">Choose where uploaded files are stored. Changing this does not migrate existing files.</small>
                                        </div>
                                    </div>
                                </div>

                                {{-- ── Local Storage ── --}}
                                <div class="card mb-4" id="pills-local">
                                    <div class="card-header">
                                        <h4 class="card-title">Local Storage <span class="badge bg-green-lt ms-1">public disk</span></h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="alert alert-info mb-4">
                                            <div class="d-flex">
                                                <div class="me-3">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="9"/><line x1="12" y1="8" x2="12.01" y2="8"/><polyline points="11 12 12 12 12 16 13 16"/></svg>
                                                </div>
                                                <div>
                                                    Files are stored under <code>storage/app/public/</code> and served via the <code>public/storage</code> symlink.<br>
                                                    Run <code>php artisan storage:link</code> once to create the symlink if not done already.
                                                </div>
                                            </div>
                                        </div>

                                        <label class="form-label">Upload Directories</label>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Type</th>
                                                        <th>Storage Path</th>
                                                        <th>Public URL Path</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $dirs = [
                                                            ['type' => 'Products',    'path' => 'products'],
                                                            ['type' => 'Categories',  'path' => 'categories'],
                                                            ['type' => 'Brands',      'path' => 'brands'],
                                                            ['type' => 'Banners',     'path' => 'banners'],
                                                            ['type' => 'Users',       'path' => 'users'],
                                                            ['type' => 'Stores',      'path' => 'stores'],
                                                            ['type' => 'Invoices',    'path' => 'invoices'],
                                                        ];
                                                    @endphp
                                                    @foreach($dirs as $dir)
                                                    <tr>
                                                        <td>{{ $dir['type'] }}</td>
                                                        <td><code>storage/app/public/{{ $dir['path'] }}</code></td>
                                                        <td><code>{{ url('storage/' . $dir['path']) }}</code></td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="mt-3">
                                            <label class="form-label">Symlink Status</label>
                                            @php
                                                $symlinkExists = file_exists(public_path('storage')) && is_link(public_path('storage'));
                                            @endphp
                                            @if($symlinkExists)
                                                <div class="alert alert-success py-2 mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                                    <code>public/storage</code> symlink is active.
                                                </div>
                                            @else
                                                <div class="alert alert-warning py-2 mb-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                                                    Symlink not found. Run <code>php artisan storage:link</code> in your terminal.
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- ── AWS S3 ── --}}
                                {{-- <div class="card mb-4" id="pills-aws">
                                    <div class="card-header">
                                        <h4 class="card-title">{{ __('labels.aws_s3') }}</h4>
                                    </div>
                                    <div class="card-body" id="awsFields">
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_access_key_id') }}</label>
                                            <input type="text" class="form-control" name="awsAccessKeyId"
                                                   placeholder="{{ __('labels.aws_access_key_id_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['awsAccessKeyId'] ?? '****'), '****', 3, 8) : ($settings['awsAccessKeyId'] ?? '') }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_secret_access_key') }}</label>
                                            <input type="text" class="form-control" name="awsSecretAccessKey"
                                                   placeholder="{{ __('labels.aws_secret_access_key_placeholder') }}"
                                                   value="{{ ($systemSettings['demoMode'] ?? false) ? Str::mask(($settings['awsSecretAccessKey'] ?? '****'), '****', 3, 8) : ($settings['awsSecretAccessKey'] ?? '') }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_region') }}</label>
                                            <input type="text" class="form-control" name="awsRegion"
                                                   placeholder="{{ __('labels.aws_region_placeholder') }}"
                                                   value="{{ $settings['awsRegion'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_bucket') }}</label>
                                            <input type="text" class="form-control" name="awsBucket"
                                                   placeholder="{{ __('labels.aws_bucket_placeholder') }}"
                                                   value="{{ $settings['awsBucket'] ?? '' }}"/>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label required">{{ __('labels.aws_asset_url') }}</label>
                                            <input type="url" class="form-control" name="awsAssetUrl"
                                                   placeholder="{{ __('labels.aws_asset_url_placeholder') }}"
                                                   value="{{ $settings['awsAssetUrl'] ?? '' }}"/>
                                        </div>
                                    </div>
                                </div> --}}

                                <div class="card-footer text-end">
                                    <div class="d-flex">
                                        @can('updateSetting', [\App\Models\Setting::class, 'storage'])
                                            <button type="submit" class="btn btn-primary ms-auto">{{ __('labels.submit') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
(function () {
    const select   = document.getElementById('storageDriverSelect');
    const awsCard  = document.getElementById('pills-aws');

    const toggle = () => {
        if (!select || !awsCard) return;
        awsCard.style.opacity = select.value === 's3' ? '1' : '0.45';
        awsCard.querySelectorAll('input').forEach(el => {
            el.disabled = select.value !== 's3';
        });
    };

    if (select) {
        select.addEventListener('change', toggle);
    }
    toggle();
})();
</script>
@endpush
