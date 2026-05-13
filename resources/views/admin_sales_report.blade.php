{{-- resources/views/admin_sales_report.blade.php --}}

@extends('layouts.app')

@section('title', 'Sales Report — SkinMedic Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/css/admin_sales_report.css') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_admin')

<div class="main">

    {{-- ── Topbar ── --}}
    <div class="topbar">
        <h2>Sales Report</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_staff')
        </div>
    </div>

    {{-- ── Quick filter tabs ── --}}
    @php
        $today     = now()->toDateString();
        $yw        = now()->startOfWeek()->toDateString();
        $ym        = now()->startOfMonth()->toDateString();
        $yy        = now()->startOfYear()->toDateString();
        $lmStart   = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lmEnd     = now()->subMonthNoOverflow()->endOfMonth()->toDateString();
        $q1s = now()->year.'-01-01'; $q1e = now()->year.'-03-31';
        $q2s = now()->year.'-04-01'; $q2e = now()->year.'-06-30';
        $q3s = now()->year.'-07-01'; $q3e = now()->year.'-09-30';
        $q4s = now()->year.'-10-01'; $q4e = now()->year.'-12-31';
        $currentMonth = now()->month;
        $currentQ = ceil($currentMonth / 3);

        $quickPresets = [
            'today'   => ['Today',         $today,  $today],
            'week'    => ['This Week',      $yw,     $today],
            'month'   => ['This Month',     $ym,     $today],
            'lmonth'  => ['Last Month',     $lmStart, $lmEnd],
            'year'    => ['This Year',      $yy,     $today],
        ];

        // Month sub-presets (Jan–Dec of current year)
        $monthPresets = [];
        for ($m = 1; $m <= 12; $m++) {
            $ms = now()->setMonth($m)->startOfMonth()->toDateString();
            $me = now()->setMonth($m)->endOfMonth()->toDateString();
            $monthPresets["m{$m}"] = [now()->setMonth($m)->format('M'), $ms, $me];
        }

        // Quarter sub-presets
        $quarterPresets = [
            'q1' => ['Q1', $q1s, $q1e],
            'q2' => ['Q2', $q2s, $q2e],
            'q3' => ['Q3', $q3s, $q3e],
            'q4' => ['Q4', $q4s, $q4e],
        ];

        // Detect active preset
        $activePreset = null;
        foreach (array_merge($quickPresets, $monthPresets, $quarterPresets) as $key => [$label, $from, $to]) {
            if ($dateFrom === $from && $dateTo === $to) { $activePreset = $key; break; }
        }
    @endphp

    <div class="quick-filters">
        @foreach($quickPresets as $key => [$label, $from, $to])
        <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
           class="quick-btn {{ $activePreset === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach

        {{-- Month picker toggle --}}
<div style="position:relative;display:inline-flex;">
    <button class="quick-btn {{ str_starts_with($activePreset ?? '', 'm') ? 'active' : '' }}"
            onclick="toggleSubMenu(this)"
            type="button">Monthly ▾</button>
    <div class="sub-menu" style="display:none;position:absolute;top:38px;left:0;background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:200;padding:8px;grid-template-columns:repeat(4,1fr);gap:4px;min-width:200px;">
        @foreach($monthPresets as $key => [$label, $from, $to])
        <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
           class="quick-btn {{ $activePreset === $key ? 'active' : '' }}" style="height:28px;font-size:0.75rem;justify-content:center;">{{ $label }}</a>
        @endforeach
    </div>
</div>

{{-- Quarter picker toggle --}}
<div style="position:relative;display:inline-flex;">
    <button class="quick-btn {{ str_starts_with($activePreset ?? '', 'q') ? 'active' : '' }}"
            onclick="toggleSubMenu(this)"
            type="button">Quarterly ▾</button>
    <div class="sub-menu" style="display:none;position:absolute;top:38px;left:0;background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.1);z-index:200;padding:8px;display:flex;gap:4px;min-width:180px;">
        @foreach($quarterPresets as $key => [$label, $from, $to])
        <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
           class="quick-btn {{ $activePreset === $key ? 'active' : '' }}" style="height:28px;font-size:0.75rem;flex:1;justify-content:center;">{{ $label }}</a>
        @endforeach
    </div>
</div>

    {{-- ── Date range filter ── --}}
    <form method="GET" action="{{ route('admin.reports.sales') }}" class="filter-bar">
        <div class="filter-group">
            <label for="date_from">From</label>
            <input type="date" id="date_from" name="date_from" value="{{ $dateFrom }}">
        </div>
        <div class="filter-group">
            <label for="date_to">To</label>
            <input type="date" id="date_to" name="date_to" value="{{ $dateTo }}">
        </div>
        <button type="submit" class="btn-apply">Apply</button>
        <a href="{{ route('admin.reports.sales') }}" class="btn-reset">Reset</a>

        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="{{ route('admin.reports.sales.export', ['format'=>'csv','date_from'=>$dateFrom,'date_to'=>$dateTo]) }}"
               class="export-btn csv">⬇ Export CSV</a>
            <a href="{{ route('admin.reports.sales.export', ['format'=>'pdf','date_from'=>$dateFrom,'date_to'=>$dateTo]) }}"
               class="export-btn pdf" target="_blank">🖨 Export PDF</a>
        </div>
    </form>

    {{-- ── Summary cards — Row 1: main metrics ── --}}
    <div class="summary-cards">
        <div class="summary-card green">
            <p class="card-label">Product Revenue</p>
            <p class="card-value">₱{{ number_format($productTotals['revenue'], 2) }}</p>
            <p class="card-sub">{{ number_format($productTotals['qty']) }} units sold</p>
        </div>
        <div class="summary-card blue">
            <p class="card-label">Service Revenue</p>
            <p class="card-value">₱{{ number_format($serviceTotals['revenue'], 2) }}</p>
            <p class="card-sub">{{ number_format($serviceTotals['count']) }} sessions</p>
        </div>
        <div class="summary-card dark">
            <p class="card-label">Grand Total</p>
            <p class="card-value">₱{{ number_format($grandTotal, 2) }}</p>
            <p class="card-sub">{{ $dateFrom }} → {{ $dateTo }}</p>
        </div>
        <div class="summary-card rose">
            <p class="card-label">Avg Daily Revenue</p>
            <p class="card-value">₱{{ number_format($avgDailyRevenue, 2) }}</p>
            <p class="card-sub">{{ $activeDays }} active day{{ $activeDays !== 1 ? 's' : '' }}</p>
        </div>
    </div>

    {{-- ── Summary cards — Row 2: transactions + payment breakdown ── --}}
    <div class="summary-cards" style="margin-top: -8px;">
        <div class="summary-card teal">
            <p class="card-label">Total Transactions</p>
            <p class="card-value">{{ number_format($totalTransactions) }}</p>
            <p class="card-sub">Walk-in sales</p>
        </div>
        @foreach($paymentBreakdown as $pm)
        <div class="summary-card {{ $pm->payment_method === 'cash' ? 'amber' : ($pm->payment_method === 'gcash' ? 'purple' : 'blue') }}">
            <p class="card-label">{{ strtoupper($pm->payment_method) }}</p>
            <p class="card-value">₱{{ number_format($pm->total, 2) }}</p>
            <p class="card-sub">{{ $pm->count }} transaction{{ $pm->count !== 1 ? 's' : '' }}</p>
        </div>
        @endforeach
        {{-- Pad remaining slots so the row doesn't stretch oddly --}}
        @for($i = $paymentBreakdown->count(); $i < 3; $i++)
        <div style="flex:1;min-width:160px;"></div>
        @endfor
    </div>

    {{-- ── Daily revenue mini-chart ── --}}
    @if($dailyRevenue->isNotEmpty())
    <div class="chart-card">
        <p class="section-label">📈 Revenue Over Period</p>
        <p style="font-size:0.78rem;color:#aaa;margin-top:2px;">Walk-in sales · hover bars for amount</p>
        <div class="bar-chart" id="barChart"></div>
    </div>
    @endif

    {{-- ── Product & Service tables ── --}}
    <div class="tables-grid">

        {{-- ── Product Sales table ── --}}
        <div class="report-card">
            <div class="report-card-header">
                <p class="section-label">🛍 Product Sales</p>
                <span class="section-meta">Online orders + Walk-in</span>
            </div>
            @if($productRows->isEmpty())
                <p class="empty-state">No product sales in this period.</p>
            @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($productRows as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->name }}</td>
                        <td class="text-right">{{ number_format($row->total_qty) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($productTotals['qty']) }}</td>
                        <td class="text-right">₱{{ number_format($productTotals['revenue'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>

        {{-- ── Service Sales table ── --}}
        <div class="report-card">
            <div class="report-card-header">
                <p class="section-label">💆 Service Sales</p>
                <span class="section-meta">Appointments + Walk-in</span>
            </div>
            @if($serviceRows->isEmpty())
                <p class="empty-state">No service sales in this period.</p>
            @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th class="text-right">Sessions</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($serviceRows as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->service_name }}</td>
                        <td class="text-right">{{ number_format($row->total_count) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="2">Total</td>
                        <td class="text-right">{{ number_format($serviceTotals['count']) }}</td>
                        <td class="text-right">₱{{ number_format($serviceTotals['revenue'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>

    </div>{{-- /tables-grid --}}

    {{-- ── Top performers ── --}}
<div class="tables-grid">

    {{-- Top Products by Revenue (visual bars) --}}
    <div class="report-card">
        <div class="report-card-header">
            <p class="section-label">🏆 Top Products</p>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="section-meta">by revenue</span>
                <button class="view-all-btn" onclick="openModal('modal-top-products')">
                    View All ({{ $allProducts->count() }})
                </button>
            </div>
        </div>
        @php $topProducts = $productRows->take(5); $maxPRev = $topProducts->max('total_revenue') ?: 1; @endphp
        @if($topProducts->isEmpty())
            <p class="empty-state">No data.</p>
        @else
            <div style="padding: 10px 0 6px;">
                @foreach($topProducts as $row)
                <div class="top-bar-row">
                    <span class="top-bar-name">{{ Str::limit($row->name, 28) }}</span>
                    <div class="top-bar-track">
                        <div class="top-bar-fill" style="width:{{ round($row->total_revenue / $maxPRev * 100) }}%"></div>
                    </div>
                    <span class="top-bar-val">₱{{ number_format($row->total_revenue, 0) }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Top Services by Revenue (visual bars) --}}
    <div class="report-card">
        <div class="report-card-header">
            <p class="section-label">🏆 Top Services</p>
            <div style="display:flex;align-items:center;gap:10px;">
                <span class="section-meta">by revenue</span>
                <button class="view-all-btn" onclick="openModal('modal-top-services')">
                    View All ({{ $allServices->count() }})
                </button>
            </div>
        </div>
        @php $topServices = $serviceRows->take(5); $maxSRev = $topServices->max('total_revenue') ?: 1; @endphp
        @if($topServices->isEmpty())
            <p class="empty-state">No data.</p>
        @else
            <div style="padding: 10px 0 6px;">
                @foreach($topServices as $row)
                <div class="top-bar-row">
                    <span class="top-bar-name">{{ Str::limit($row->service_name, 28) }}</span>
                    <div class="top-bar-track">
                        <div class="top-bar-fill" style="width:{{ round($row->total_revenue / $maxSRev * 100) }}%"></div>
                    </div>
                    <span class="top-bar-val">₱{{ number_format($row->total_revenue, 0) }}</span>
                </div>
                @endforeach
            </div>
        @endif
    </div>

</div>{{-- /tables-grid --}}

{{-- ── View All: Products Modal ── --}}
<div class="modal-overlay" id="modal-top-products">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Products</h3>
            <button class="modal-close" onclick="closeModal('modal-top-products')">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Qty Sold</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allProducts as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->name }}</td>
                        <td class="text-right">₱{{ number_format($row->selling_price, 2) }}</td>
                        <td class="text-right">{{ $row->total_qty > 0 ? number_format($row->total_qty) : '—' }}</td>
                        <td class="text-right fw-bold">{{ $row->total_revenue > 0 ? '₱'.number_format($row->total_revenue, 2) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- ── View All: Services Modal ── --}}
<div class="modal-overlay" id="modal-top-services">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Services</h3>
            <button class="modal-close" onclick="closeModal('modal-top-services')">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Sessions</th>
                        <th class="text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allServices as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->service_name }}</td>
                        <td class="text-right">₱{{ number_format($row->price, 2) }}</td>
                        <td class="text-right">{{ $row->total_count > 0 ? number_format($row->total_count) : '—' }}</td>
                        <td class="text-right fw-bold">{{ $row->total_revenue > 0 ? '₱'.number_format($row->total_revenue, 2) : '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

    {{-- ── Payment Transactions table ── --}}
    <div class="tables-grid full">
        <div class="report-card">
            <div class="report-card-header">
                <p class="section-label">💳 Payment Breakdown</p>
                <span class="section-meta">Walk-in transactions only</span>
            </div>
            @if($paymentBreakdown->isEmpty())
                <p class="empty-state">No payment data for this period.</p>
            @else
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th class="text-right">Transactions</th>
                        <th class="text-right">Total Revenue</th>
                        <th class="text-right">Avg per Txn</th>
                        <th class="text-right">% of Sales</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentBreakdown as $pm)
                    <tr>
                        <td><span class="badge {{ strtolower($pm->payment_method) }}">{{ $pm->payment_method }}</span></td>
                        <td class="text-right">{{ number_format($pm->count) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($pm->total, 2) }}</td>
                        <td class="text-right">₱{{ $pm->count > 0 ? number_format($pm->total / $pm->count, 2) : '0.00' }}</td>
                        <td class="text-right">
                            @php $walkinTotal = $paymentBreakdown->sum('total'); @endphp
                            {{ $walkinTotal > 0 ? number_format($pm->total / $walkinTotal * 100, 1) : 0 }}%
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td>Total</td>
                        <td class="text-right">{{ number_format($paymentBreakdown->sum('count')) }}</td>
                        <td class="text-right">₱{{ number_format($paymentBreakdown->sum('total'), 2) }}</td>
                        <td class="text-right"></td>
                        <td class="text-right">100%</td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
    </div>

{{-- ── View-All Modals ── --}}
<div class="modal-overlay" id="modal-top-products">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Products — by Revenue</h3>
            <button class="modal-close" onclick="closeModal('modal-top-products')">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead><tr><th>#</th><th>Product</th><th class="text-right">Qty</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                    @foreach($productRows as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->name }}</td>
                        <td class="text-right">{{ number_format($row->total_qty) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="2">Total</td>
                    <td class="text-right">{{ number_format($productTotals['qty']) }}</td>
                    <td class="text-right">₱{{ number_format($productTotals['revenue'], 2) }}</td>
                </tr></tfoot>
            </table>
        </div>
    </div>
</div>

<div class="modal-overlay" id="modal-top-services">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Services — by Revenue</h3>
            <button class="modal-close" onclick="closeModal('modal-top-services')">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead><tr><th>#</th><th>Service</th><th class="text-right">Sessions</th><th class="text-right">Revenue</th></tr></thead>
                <tbody>
                    @foreach($serviceRows as $i => $row)
                    <tr>
                        <td class="row-num">{{ $i + 1 }}</td>
                        <td>{{ $row->service_name }}</td>
                        <td class="text-right">{{ number_format($row->total_count) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($row->total_revenue, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot><tr>
                    <td colspan="2">Total</td>
                    <td class="text-right">{{ number_format($serviceTotals['count']) }}</td>
                    <td class="text-right">₱{{ number_format($serviceTotals['revenue'], 2) }}</td>
                </tr></tfoot>
            </table>
        </div>
    </div>
</div>

</div>{{-- /main --}}

@push('scripts')
<script>
// ── Simple CSS bar chart from PHP daily data ──────────────
const dailyData = @json($dailyRevenue);
const entries   = Object.entries(dailyData);
const maxVal    = Math.max(...entries.map(([, v]) => parseFloat(v)), 1);
const container = document.getElementById('barChart');

if (container && entries.length) {
    container.innerHTML = entries.map(([date, val]) => {
        const pct   = (parseFloat(val) / maxVal * 100).toFixed(1);
        const label = new Date(date + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric' });
        return `
            <div class="bar-group">
                <div class="bar-wrap">
                    <div class="bar" style="height:${pct}%" title="₱${parseFloat(val).toLocaleString('en-PH', {minimumFractionDigits:2})}"></div>
                </div>
                <span class="bar-label">${label}</span>
            </div>`;
    }).join('');
}

// ── Sub-menu dropdowns ──────────────────────────────────
function toggleSubMenu(btn) {
    const menu    = btn.nextElementSibling;
    const isOpen  = menu.style.display !== 'none';

    // Close all open menus first
    document.querySelectorAll('.sub-menu').forEach(m => m.style.display = 'none');

    // Toggle the clicked one
    if (!isOpen) {
        // Use grid for monthly (has grid-template-columns), flex for quarterly
        const isGrid = menu.style.gridTemplateColumns || menu.getAttribute('style').includes('grid-template-columns');
        menu.style.display = isGrid ? 'grid' : 'flex';
    }
}

// Close when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.quick-filters > div')) {
        document.querySelectorAll('.sub-menu').forEach(m => m.style.display = 'none');
    }
});

// ── Modal helpers ───────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeModal(overlay.id);
    });
});
</script>
@endpush

@endsection