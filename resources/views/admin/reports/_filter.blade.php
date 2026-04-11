<div class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label">From</label>
                <input type="date" class="form-control" id="dateFrom">
            </div>
            <div class="col-auto">
                <label class="form-label">To</label>
                <input type="date" class="form-control" id="dateTo">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" id="applyFilter">Apply</button>
            </div>
            <div class="col-auto">
                <button class="btn btn-secondary" id="quickLast7">Last 7 days</button>
                <button class="btn btn-secondary ms-1" id="quickLast30">Last 30 days</button>
                <button class="btn btn-secondary ms-1" id="quickThisMonth">This Month</button>
            </div>
        </div>
    </div>
</div>

<script>
    function setRange(from, to) {
        $('#dateFrom').val(from);
        $('#dateTo').val(to);
    }
    $('#quickLast7').on('click', function () {
        const to   = new Date().toISOString().split('T')[0];
        const from = new Date(Date.now() - 6 * 86400000).toISOString().split('T')[0];
        setRange(from, to);
        $('#applyFilter').trigger('click');
    });
    $('#quickLast30').on('click', function () {
        const to   = new Date().toISOString().split('T')[0];
        const from = new Date(Date.now() - 29 * 86400000).toISOString().split('T')[0];
        setRange(from, to);
        $('#applyFilter').trigger('click');
    });
    $('#quickThisMonth').on('click', function () {
        const now  = new Date();
        const from = new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        const to   = now.toISOString().split('T')[0];
        setRange(from, to);
        $('#applyFilter').trigger('click');
    });
</script>
