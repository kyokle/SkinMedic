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
            @include('partials.notif_bell_admin')
        </div>
    </div>

    {{-- ── Quick filter tabs ── --}}
    @php
        $today   = now()->toDateString();
        $yw      = now()->startOfWeek()->toDateString();
        $yy      = now()->startOfYear()->toDateString();
        $lmStart = now()->subMonthNoOverflow()->startOfMonth()->toDateString();
        $lmEnd   = now()->subMonthNoOverflow()->endOfMonth()->toDateString();

        $quickPresets = [
            'today'  => ['Today',      $today,   $today],
            'week'   => ['This Week',  $yw,      $today],
            'lmonth' => ['Last Month', $lmStart, $lmEnd],
            'year'   => ['This Year',  $yy,      $today],
        ];

        // Month sub-presets (Jan–Dec of current year)
        $monthPresets = [];
        for ($m = 1; $m <= 12; $m++) {
            $base = now()->copy()->month($m);
            $monthPresets["m{$m}"] = [
                $base->format('M'),
                $base->startOfMonth()->toDateString(),
                $base->copy()->endOfMonth()->toDateString(),
            ];
        }

        // Quarter sub-presets
        $yr = now()->year;
        $quarterPresets = [
            'q1' => ['Q1', "{$yr}-01-01", "{$yr}-03-31"],
            'q2' => ['Q2', "{$yr}-04-01", "{$yr}-06-30"],
            'q3' => ['Q3', "{$yr}-07-01", "{$yr}-09-30"],
            'q4' => ['Q4', "{$yr}-10-01", "{$yr}-12-31"],
        ];

        // Detect active preset
        $activePreset = null;
        foreach (array_merge($quickPresets, $monthPresets, $quarterPresets) as $key => [$label, $from, $to]) {
            if ($dateFrom === $from && $dateTo === $to) { $activePreset = $key; break; }
        }
    @endphp

    <div class="quick-filters">

        {{-- Simple presets --}}
        @foreach($quickPresets as $key => [$label, $from, $to])
        <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
           class="quick-btn {{ $activePreset === $key ? 'active' : '' }}">{{ $label }}</a>
        @endforeach

        {{-- Monthly dropdown --}}
        <div class="dropdown-wrap">
            <button class="quick-btn {{ str_starts_with($activePreset ?? '', 'm') ? 'active' : '' }}"
                    onclick="toggleDropdown('dd-monthly', event)" type="button">Monthly ▾</button>
            <div class="dropdown-menu" id="dd-monthly" style="display:none; grid-template-columns:repeat(4,1fr);">
                @foreach($monthPresets as $key => [$label, $from, $to])
                <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
                   class="quick-btn {{ $activePreset === $key ? 'active' : '' }}"
                   style="height:28px;font-size:0.75rem;justify-content:center;">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        {{-- Quarterly dropdown --}}
        <div class="dropdown-wrap">
            <button class="quick-btn {{ str_starts_with($activePreset ?? '', 'q') ? 'active' : '' }}"
                    onclick="toggleDropdown('dd-quarterly', event)" type="button">Quarterly ▾</button>
            <div class="dropdown-menu" id="dd-quarterly" style="display:none; grid-template-columns:repeat(4,1fr);">
                @foreach($quarterPresets as $key => [$label, $from, $to])
                <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
                   class="quick-btn {{ $activePreset === $key ? 'active' : '' }}"
                   style="height:28px;font-size:0.75rem;justify-content:center;">{{ $label }}</a>
                @endforeach
            </div>
        </div>

    </div>{{-- /quick-filters --}}

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

    {{-- ── Summary cards — Row 1 ── --}}
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

    {{-- ── Summary cards — Row 2 ── --}}
    <div class="summary-cards" style="margin-top:-8px;">
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
        @for($i = $paymentBreakdown->count(); $i < 3; $i++)
            <div style="flex:1;min-width:160px;"></div>
        @endfor
    </div>

    {{-- ── Daily revenue chart ── --}}
    @if($dailyRevenue->isNotEmpty())
    <div class="chart-card">
        <p class="section-label">📈 Revenue Over Period</p>
        <p style="font-size:0.78rem;color:#aaa;margin-top:2px;">Walk-in sales · hover bars for amount</p>
        <div class="bar-chart-wrapper">
            <div class="bar-chart" id="barChart"></div>
        </div>
    </div>
    @endif

    {{-- ── Product & Service tables ── --}}
    <div class="tables-grid">
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
                    <tr><th>#</th><th>Product</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th></tr>
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
                    <tr><th>#</th><th>Service</th><th class="text-right">Sessions</th><th class="text-right">Revenue</th></tr>
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
    </div>

    {{-- ── Top performers ── --}}
    <div class="tables-grid">
        <div class="report-card">
            <div class="report-card-header">
                <p class="section-label">🏆 Top Products</p>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="section-meta">by revenue</span>
                    <button class="view-all-btn" onclick="openViewAll('modal-top-products')" type="button">
                        View All ({{ $allProducts->count() }})
                    </button>
                </div>
            </div>
            @php $topProducts = $productRows->take(5); $maxPRev = $topProducts->max('total_revenue') ?: 1; @endphp
            @if($topProducts->isEmpty())
                <p class="empty-state">No data.</p>
            @else
                <div style="padding:10px 0 6px;">
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

        <div class="report-card">
            <div class="report-card-header">
                <p class="section-label">🏆 Top Services</p>
                <div style="display:flex;align-items:center;gap:10px;">
                    <span class="section-meta">by revenue</span>
                    <button class="view-all-btn" onclick="openViewAll('modal-top-services')" type="button">
                        View All ({{ $allServices->count() }})
                    </button>
                </div>
            </div>
            @php $topServices = $serviceRows->take(5); $maxSRev = $topServices->max('total_revenue') ?: 1; @endphp
            @if($topServices->isEmpty())
                <p class="empty-state">No data.</p>
            @else
                <div style="padding:10px 0 6px;">
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
    </div>

    {{-- ── Payment Breakdown ── --}}
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
                    @php $walkinTotal = $paymentBreakdown->sum('total'); @endphp
                    @foreach($paymentBreakdown as $pm)
                    <tr>
                        <td><span class="badge {{ strtolower($pm->payment_method) }}">{{ $pm->payment_method }}</span></td>
                        <td class="text-right">{{ number_format($pm->count) }}</td>
                        <td class="text-right fw-bold">₱{{ number_format($pm->total, 2) }}</td>
                        <td class="text-right">₱{{ $pm->count > 0 ? number_format($pm->total / $pm->count, 2) : '0.00' }}</td>
                        <td class="text-right">{{ $walkinTotal > 0 ? number_format($pm->total / $walkinTotal * 100, 1) : 0 }}%</td>
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

</div>{{-- /main --}}

{{-- ══ View All: Products Modal ══ --}}
<div class="modal-overlay" id="modal-top-products" style="display:none;">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Products</h3>
            <button class="modal-close" onclick="closeViewAll('modal-top-products')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead>
                    <tr><th>#</th><th>Product</th><th class="text-right">Unit Price</th><th class="text-right">Qty Sold</th><th class="text-right">Revenue</th></tr>
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

{{-- ══ View All: Services Modal ══ --}}
<div class="modal-overlay" id="modal-top-services" style="display:none;">
    <div class="modal-box">
        <div class="modal-head">
            <h3>🏆 All Services</h3>
            <button class="modal-close" onclick="closeViewAll('modal-top-services')" type="button">✕</button>
        </div>
        <div class="modal-body">
            <table class="report-table">
                <thead>
                    <tr><th>#</th><th>Service</th><th class="text-right">Price</th><th class="text-right">Sessions</th><th class="text-right">Revenue</th></tr>
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

@push('scripts')
<script>
// ── Bar chart ─────────────────────────────────────────────
const dailyData = @json($dailyRevenue);
const entries   = Object.entries(dailyData);
const maxVal    = Math.max(...entries.map(([, v]) => parseFloat(v)), 1);
const container = document.getElementById('barChart');

if (container && entries.length) {
    container.style.width = (entries.length * 46) + 'px';
    container.innerHTML = entries.map(([date, val]) => {
        const pct    = (parseFloat(val) / maxVal * 100).toFixed(1);
        const label  = new Date(date + 'T00:00:00').toLocaleDateString('en-PH', { month:'short', day:'numeric' });
        const amount = parseFloat(val).toLocaleString('en-PH', { minimumFractionDigits: 2 });
        return `
            <div class="bar-group" title="₱${amount}">
                <div class="bar-wrap"><div class="bar" style="height:${pct}%"></div></div>
                <span class="bar-label">${label}</span>
            </div>`;
    }).join('');
}

// ── Dropdown toggles ──────────────────────────────────────
function toggleDropdown(id, event) {
    event.stopPropagation();
    const menu   = document.getElementById(id);
    const isOpen = menu.style.display !== 'none';
    // Close all first
    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
    // Open this one if it was closed
    if (!isOpen) menu.style.display = 'grid';
}

// Close dropdowns on outside click
document.addEventListener('click', function () {
    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
});

// ── View All modals ───────────────────────────────────────
function openViewAll(id) {
    document.getElementById(id).style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeViewAll(id) {
    document.getElementById(id).style.display = 'none';
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) closeViewAll(overlay.id);
    });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.style.display = 'none');
        document.body.style.overflow = '';
    }
});
</script>
@endpush

@endsection