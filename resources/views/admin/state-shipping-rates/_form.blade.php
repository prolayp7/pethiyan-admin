<div class="row g-3">
    @php
        $currentPartnerId = (int) ($rate?->delivery_partner_id ?? 0);
        $selectablePartners = $partners->filter(function ($partner) use ($currentPartnerId) {
            return (bool) $partner->is_active || (int) $partner->id === $currentPartnerId;
        })->values();
    @endphp

    <div class="col-md-6">
        <label class="form-label required">Delivery Partner</label>
        <select class="form-select" name="delivery_partner_id" required>
            <option value="">Select partner…</option>
            @foreach($selectablePartners as $partner)
                <option value="{{ $partner->id }}" {{ (string)($rate?->delivery_partner_id ?? '') === (string)$partner->id ? 'selected' : '' }}>
                    {{ $partner->name }}{{ $partner->is_active ? '' : ' (Inactive)' }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label required">Zone</label>
        <select class="form-select" name="zone_id" required>
            <option value="">Select zone…</option>
            @foreach($zones as $zone)
                <option value="{{ $zone->id }}" {{ (string)($rate?->zone_id ?? '') === (string)$zone->id ? 'selected' : '' }}>
                    {{ $zone->code }} - {{ $zone->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Status</label>
        <div class="mt-2">
            <label class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="is_active" value="1"
                    {{ ($rate?->is_active ?? true) ? 'checked' : '' }}>
                <span class="form-check-label">Active</span>
            </label>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label required">Upto 250g (₹)</label>
        <div class="input-group">
            <span class="input-group-text">₹</span>
            <input type="number" class="form-control" name="upto_250" step="0.01" min="0"
                   value="{{ $rate?->upto_250 ?? 0 }}" placeholder="0.00" required>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Upto 500g (₹)</label>
        <div class="input-group">
            <span class="input-group-text">₹</span>
            <input type="number" class="form-control" name="upto_500" step="0.01" min="0"
                   value="{{ $rate?->upto_500 ?? 0 }}" placeholder="0.00" required>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Every 500g (₹)</label>
        <div class="input-group">
            <span class="input-group-text">₹</span>
            <input type="number" class="form-control" name="every_500" step="0.01" min="0"
                   value="{{ $rate?->every_500 ?? 0 }}" placeholder="0.00" required>
        </div>
    </div>

    <div class="col-md-4">
        <label class="form-label required">Per KG (₹)</label>
        <input type="number" class="form-control" name="per_kg" min="0" step="0.01"
               value="{{ $rate?->per_kg ?? 0 }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label required">2 KG (₹)</label>
        <input type="number" class="form-control" name="kg_2" min="0" step="0.01"
               value="{{ $rate?->kg_2 ?? 0 }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Above 5 KG Surface (₹)</label>
        <input type="number" class="form-control" name="above_5_surface" min="0" step="0.01"
               value="{{ $rate?->above_5_surface ?? 0 }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label required">Above 5 KG Air (₹)</label>
        <input type="number" class="form-control" name="above_5_air" min="0" step="0.01"
               value="{{ $rate?->above_5_air ?? 0 }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Fuel Surcharge (%)</label>
        <input type="number" class="form-control" name="fuel_surcharge_percent" min="0" max="100" step="0.01"
               value="{{ $rate?->fuel_surcharge_percent ?? 0 }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label required">GST (%)</label>
        <input type="number" class="form-control" name="gst_percent" min="0" max="100" step="0.01"
               value="{{ $rate?->gst_percent ?? 0 }}" required>
    </div>

    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes…">{{ $rate?->notes ?? '' }}</textarea>
    </div>
</div>
