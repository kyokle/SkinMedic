{{-- resources/views/admin_page.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard — SkinMedic Admin')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/admin_page.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_admin')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
    <div class="page-title">Dashboard</div>
    <div class="date-box">
        <p>Today's Date</p>
        <strong>{{ now()->format('Y-m-d') }}</strong>
    </div>
</div>

{{-- Welcome Banner --}}
<div class="welcome-banner">
    <div>
        <h3>Welcome back, {{ $sidebarFirstName }}! 👋</h3>
        <p>Administrator &nbsp;|&nbsp; {{ now()->format('l') }}</p>
    </div>
    <img src="{{ asset('/asset/image/skintransparent.png') }}" alt="SkinMedic">
</div>

    {{-- Stat Cards --}}
    <div class="stats-grid">
        <a href="{{ route('admin.bookings', ['filter' => 'all']) }}" class="stat-card" style="animation-delay:.05s;text-decoration:none;color:inherit;">
            <div class="stat-icon">🗓</div>
            <div class="stat-value">{{ $totalBookings }}</div>
            <div class="stat-label">Total Bookings</div>
            <div class="stat-sub">Today: <span>{{ $todayBooks }}</span></div>
        </a>
        <a href="{{ route('admin.bookings', ['filter' => 'pending']) }}" class="stat-card warn" style="animation-delay:.1s;text-decoration:none;color:inherit;">
            <div class="stat-icon">⏳</div>
            <div class="stat-value">{{ $pendingBooks }}</div>
            <div class="stat-label">Pending Approval</div>
            <div class="stat-sub">Needs attention</div>
        </a>
        <a href="{{ route('admin.users', ['tab' => 'patient']) }}" class="stat-card blue" style="animation-delay:.15s;text-decoration:none;color:inherit;">
            <div class="stat-icon">👥</div>
            <div class="stat-value">{{ $totalPatients }}</div>
            <div class="stat-label">Patients</div>
            <div class="stat-sub">Doctors: <span>{{ $totalDoctors }}</span></div>
        </a>
        <a href="{{ route('admin.bookings', ['filter' => 'completed']) }}" class="stat-card" style="animation-delay:.2s;text-decoration:none;color:inherit;">
            <div class="stat-icon">💰</div>
            <div class="stat-value">₱{{ number_format($totalRevenue, 0) }}</div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-sub">From completed sessions</div>
        </a>
        <a href="{{ route('admin.inventory') }}" class="stat-card danger" style="animation-delay:.25s;grid-column:span 2;text-decoration:none;color:inherit;">
            <div class="stat-icon">📦</div>
            <div class="stat-value">{{ $lowStock + $outOfStock }}</div>
            <div class="stat-label">Stock Alerts</div>
            <div class="stat-sub">
                Low: <span style="color:#f59e0b">{{ $lowStock }}</span>
                &nbsp;·&nbsp;
                Out of stock: <span style="color:#ef4444">{{ $outOfStock }}</span>
            </div>
        </a>
        <a href="{{ route('admin.services') }}" class="stat-card blue" style="animation-delay:.3s;grid-column:span 2;text-decoration:none;color:inherit;">
            <div class="stat-icon">💆</div>
            <div class="stat-value">{{ count($svcLabels) }}</div>
            <div class="stat-label">Active Services</div>
            <div class="stat-sub">Most popular: <span>{{ $svcLabels[0] ?? '—' }}</span></div>
        </a>
    </div>

    {{-- Charts Row 1 --}}
    <div class="charts-grid" style="margin-bottom:20px;">
        <div class="card">
            <div class="card-title">
                Bookings — Last 7 Days
                <a href="{{ route('admin.bookings') }}">View all →</a>
            </div>
            <canvas id="lineChart"></canvas>
        </div>
        <div class="card">
            <div class="card-title">Booking Status Breakdown</div>
            <canvas id="donutChart"></canvas>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="charts-row">
        <div class="card">
            <div class="card-title">Most Popular Services</div>
            <canvas id="barChart"></canvas>
        </div>
        <div class="card">
            <div class="card-title">
                Product Stock Levels
                <a href="{{ route('admin.inventory') }}">Manage →</a>
            </div>
            <canvas id="stockChart"></canvas>
        </div>
    </div>

    {{-- Bottom Row --}}
    <div class="charts-row">

        {{-- Recent Bookings --}}
        <div class="card">
            <div class="card-title">
                Recent Bookings
                <a href="{{ route('admin.bookings') }}">View all →</a>
            </div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $row)
                    <tr>
                        <td>{{ $row->appointment_id }}</td>
                        <td>{{ $row->patient }}</td>
                        <td>{{ $row->service }}</td>
                        <td>{{ $row->appointment_date }}</td>
                        <td>{{ $row->status }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" style="text-align:center;">No bookings found</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Near-Expiry Alerts --}}
        <div class="card">
            <div class="card-title">
                ⚠ Near-Expiry Products
                <a href="{{ route('admin.inventory') }}">Manage →</a>
            </div>
            @forelse($nearExp as $n)
            @php
                $days   = now()->diffInDays($n->expiry_date, false);
                $dotCls = $days <= 3 ? 'red' : 'orange';
            @endphp
            <ul class="alert-list">
                <li>
                    <span class="alert-dot {{ $dotCls }}"></span>
                    <span>
                        <strong>{{ $n->product_name }}</strong><br>
                        Expires <b>{{ $n->expiry_date }}</b> &mdash; Qty: {{ $n->qty }}
                        <span style="color:{{ $dotCls === 'red' ? '#ef4444' : '#f59e0b' }};font-size:11px;">
                            ({{ (int) $days }} days)
                        </span>
                    </span>
                </li>
            </ul>
            @empty
                <p class="empty-state">✅ No products expiring within 14 days.</p>
            @endforelse
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const accent  = '#80a833';
    const accent2 = '#b5d45a';

    new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
        labels: {!! json_encode($lineLabels) !!},
        datasets: [{
            label: 'Bookings',
            data: {!! json_encode($lineValues) !!},
            borderColor: accent,
            backgroundColor: 'rgba(128,168,51,0.1)',
            fill: true,
            tension: 0.4
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});

new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($donutLabels) !!},
        datasets: [{
            label: 'Status',
            data: {!! json_encode($donutData) !!},
            backgroundColor: {!! json_encode($donutColors) !!}
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});

new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($svcLabels) !!},
        datasets: [{
            label: 'Bookings',
            data: {!! json_encode($svcData) !!},
            backgroundColor: accent2
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});

new Chart(document.getElementById('stockChart'), {
    type: 'bar',
    data: {
        labels: {!! json_encode($stockNames) !!},
        datasets: [{
            label: 'Stock Qty',
            data: {!! json_encode($stockQty) !!},
            backgroundColor: '#3b82f6'
        }]
    },
    options: { responsive: true, maintainAspectRatio: true }
});
});
</script>
@endpush