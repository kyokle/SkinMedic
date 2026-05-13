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
        $quickPresets = [
            'today'   => ['Today',     now()->toDateString(),                     now()->toDateString()],
            'week'    => ['This Week',  now()->startOfWeek()->toDateString(),       now()->toDateString()],
            'month'   => ['This Month', now()->startOfMonth()->toDateString(),      now()->toDateString()],
            'year'    => ['This Year',  now()->startOfYear()->toDateString(),       now()->toDateString()],
            'lmonth'  => ['Last Month', now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                                         now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
        ];

        // Detect active preset
        $activePreset = null;
        foreach ($quickPresets as $key => [$label, $from, $to]) {
            if ($dateFrom === $from && $dateTo === $to) {
                $activePreset = $key;
                break;
            }
        }
    @endphp

    <div class="quick-filters">
        @foreach($quickPresets as $key => [$label, $from, $to])
        <a href="{{ route('admin.reports.sales', ['date_from' => $from, 'date_to' => $to]) }}"
           class="quick-btn {{ $activePreset === $key ? 'active' : '' }}">
            {{ $label }}
        </a>
        @endforeach
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

    {{-- ── Summary cards ── --}}
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
        <div class="summary-card teal">
            <p class="card-label">Total Transactions</p>
            <p class="card-value">{{ number_format($totalTransactions) }}</p>
            <p class="card-sub">Walk-in sales</p>
        </div>

        {{-- Payment method breakdown --}}
        @foreach($paymentBreakdown as $pm)
        <div class="summary-card {{ $pm->payment_method === 'cash' ? 'amber' : 'purple' }}">
            <p class="card-label">{{ strtoupper($pm->payment_method) }}</p>
            <p class="card-value">₱{{ number_format($pm->total, 2) }}</p>
            <p class="card-sub">{{ $pm->count }} transaction{{ $pm->count !== 1 ? 's' : '' }}</p>
        </div>
        @endforeach
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
                <span class="section-meta">by revenue</span>
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
                <span class="section-meta">by revenue</span>
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
</script>
@endpush

@endsection