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

{{-- NOTE: This partial is rendered BEFORE jQuery loads (jQuery comes in _scripts.blade.php
     after @yield('content')). We therefore use plain vanilla JS here and fire a
     native CustomEvent that each report page's jQuery ready-handler can listen for. --}}
<script>
(function () {
    function fmt(d) {
        return d.toISOString().split('T')[0];
    }

    function setRange(from, to) {
        document.getElementById('dateFrom').value = from;
        document.getElementById('dateTo').value   = to;
    }

    function fireApply() {
        // Fire both a native event (caught by vanilla listeners) AND
        // trigger the #applyFilter click after a tick so jQuery is ready.
        document.dispatchEvent(new CustomEvent('reportFilterApply'));
        // Fallback: also programmatically click the Apply button —
        // jQuery will have registered its handler by the time any user
        // clicks a quick-date button (DOM ready fires after all scripts load).
        var btn = document.getElementById('applyFilter');
        if (btn) { btn.click(); }
    }

    document.getElementById('quickLast7').addEventListener('click', function () {
        var to   = fmt(new Date());
        var from = fmt(new Date(Date.now() - 6 * 86400000));
        setRange(from, to);
        fireApply();
    });

    document.getElementById('quickLast30').addEventListener('click', function () {
        var to   = fmt(new Date());
        var from = fmt(new Date(Date.now() - 29 * 86400000));
        setRange(from, to);
        fireApply();
    });

    document.getElementById('quickThisMonth').addEventListener('click', function () {
        var now  = new Date();
        var from = fmt(new Date(now.getFullYear(), now.getMonth(), 1));
        var to   = fmt(now);
        setRange(from, to);
        fireApply();
    });
}());
</script>
