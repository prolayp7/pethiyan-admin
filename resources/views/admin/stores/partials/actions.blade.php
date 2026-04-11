<div class="d-flex align-items-center gap-1">
    <a
        class="btn btn-azure p-1"
        title="View {{ $store->name }}"
        href="{{ route('admin.sellers.store.show', ['id' => $store->id]) }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
             class="icon icon-tabler icons-tabler-outline icon-tabler-eye m-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/>
            <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/>
        </svg>
    </a>
    <a
        class="btn btn-warning p-1"
        title="Edit {{ $store->name }}"
        href="{{ route('admin.sellers.store.edit', ['id' => $store->id]) }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
             class="icon icon-tabler icons-tabler-outline icon-tabler-pencil m-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M4 20h4l10.5 -10.5a2.828 2.828 0 1 0 -4 -4l-10.5 10.5v4"/>
            <path d="M13.5 6.5l4 4"/>
        </svg>
    </a>
    <button
        class="btn btn-danger p-1 delete-store"
        title="Delete {{ $store->name }}"
        data-id="{{ $store->id }}"
        data-url="{{ route('admin.sellers.store.destroy', $store->id) }}"
        data-title="{{ $store->name }}"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
             class="icon icon-tabler icons-tabler-outline icon-tabler-trash m-0">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M4 7l16 0"/>
            <path d="M10 11l0 6"/>
            <path d="M14 11l0 6"/>
            <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/>
            <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>
        </svg>
    </button>
</div>
