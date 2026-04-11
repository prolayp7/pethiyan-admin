<div class="d-flex gap-1">
    <button class="btn btn-sm btn-outline-primary btn-edit-rate"
            data-id="{{ $rate->id }}"
            data-delivery_partner_id="{{ $rate->delivery_partner_id }}"
            data-zone_id="{{ $rate->zone_id }}"
            data-upto_250="{{ $rate->upto_250 }}"
            data-upto_500="{{ $rate->upto_500 }}"
            data-every_500="{{ $rate->every_500 }}"
            data-per_kg="{{ $rate->per_kg }}"
            data-kg_2="{{ $rate->kg_2 }}"
            data-above_5_surface="{{ $rate->above_5_surface }}"
            data-above_5_air="{{ $rate->above_5_air }}"
            data-fuel_surcharge_percent="{{ $rate->fuel_surcharge_percent }}"
            data-gst_percent="{{ $rate->gst_percent }}"
            data-is_active="{{ $rate->is_active ? 1 : 0 }}"
            data-notes="{{ $rate->notes }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"/><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"/></svg>
        Edit
    </button>
    <button class="btn btn-sm btn-outline-danger btn-delete-rate" data-id="{{ $rate->id }}" data-name="{{ $rate->deliveryPartner?->name }} - {{ $rate->zone?->code }}">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><line x1="4" y1="7" x2="20" y2="7"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
        Delete
    </button>
</div>
