<div class="d-flex gap-1 justify-content-center flex-wrap">
    {{-- View --}}
    <a href="{{ route('admin.customers.show', $customer->id) }}"
       class="btn btn-sm btn-outline-info"
       title="{{ __('labels.view') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        </svg>
    </a>

    @if($editPermission)
    {{-- Toggle status --}}
    <button type="button"
            class="btn btn-sm {{ $customer->status ? 'btn-outline-warning' : 'btn-outline-success' }}"
            data-customer-toggle="{{ $customer->id }}"
            title="{{ $customer->status ? __('labels.deactivate') : __('labels.activate') }}">
        @if($customer->status)
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18.36 6.64a9 9 0 1 1 -12.73 0"/>
                <line x1="12" y1="2" x2="12" y2="12"/>
            </svg>
        @else
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                <path d="M9 12l2 2l4 -4"/>
            </svg>
        @endif
    </button>

    {{-- Edit --}}
    <button type="button"
            class="btn btn-sm btn-outline-primary"
            data-customer-edit="{{ $customer->id }}"
            title="{{ __('labels.edit') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2 -2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1 -4 9.5 -9.5z"/>
        </svg>
    </button>
    @endif

    @if($deletePermission)
    {{-- Delete --}}
    <button type="button"
            class="btn btn-sm btn-outline-danger"
            data-customer-delete="{{ $customer->id }}"
            title="{{ __('labels.delete') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1 -2 2H7a2 2 0 0 1 -2 -2V6m3 0V4a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/>
        </svg>
    </button>
    @endif
</div>
