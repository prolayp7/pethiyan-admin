<div class="d-flex gap-1 justify-content-center">
    @if($editPermission)
    <button type="button"
            class="btn btn-sm btn-outline-primary edit-address-btn"
            data-address="{{ json_encode($address) }}"
            data-customer-id="{{ $customerId }}"
            title="{{ __('labels.edit') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2 -2v-7"/>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1 -4 9.5 -9.5z"/>
        </svg>
    </button>
    @endif

    @if($deletePermission)
    <button type="button"
            class="btn btn-sm btn-outline-danger delete-address-btn"
            data-address-id="{{ $address->id }}"
            data-customer-id="{{ $customerId }}"
            title="{{ __('labels.delete') }}">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6v14a2 2 0 0 1 -2 2H7a2 2 0 0 1 -2 -2V6m3 0V4a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v2"/>
        </svg>
    </button>
    @endif
</div>
