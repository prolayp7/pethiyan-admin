{{--
    Reusable address form fields.
    $prefix = 'new'  → IDs like newAddrLine1  (used in Add Customer modal)
    $prefix = 'edit' → IDs like editAddrLine1 (used in Edit Customer modal)
--}}
<div class="row g-3">
    <div class="col-12">
        <label class="form-label">Address Line 1</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrLine1" placeholder="House/Flat No., Street">
    </div>
    <div class="col-12">
        <label class="form-label">Address Line 2</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrLine2" placeholder="Area, Colony (optional)">
    </div>
    <div class="col-md-6">
        <label class="form-label">City</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrCity">
    </div>
    <div class="col-md-6">
        <label class="form-label">State</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrState">
    </div>
    <div class="col-md-6">
        <label class="form-label">Pincode / Zipcode</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrZip">
    </div>
    <div class="col-md-6">
        <label class="form-label">Country</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrCountry" value="India">
    </div>
    <div class="col-md-6">
        <label class="form-label">Contact Mobile</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrMobile" placeholder="For this address">
    </div>
    <div class="col-md-6">
        <label class="form-label">Address Type</label>
        <select class="form-select" id="{{ $prefix }}AddrType">
            <option value="home">Home</option>
            <option value="work">Work</option>
            <option value="other">Other</option>
        </select>
    </div>
    <div class="col-12">
        <label class="form-label">Landmark</label>
        <input type="text" class="form-control" id="{{ $prefix }}AddrLandmark" placeholder="Near, opposite…">
    </div>
</div>
