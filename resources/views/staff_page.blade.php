{{-- resources/views/staff_page.blade.php --}}

@extends('layouts.app')

@section('title', 'Dashboard — SkinMedic Staff')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/staff_page.css') }}">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>Home</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>

            {{-- Notification Bell --}}
            <div class="notif-wrapper">
                <div class="notif-bell {{ $notifCount > 0 ? 'has-alerts' : '' }}"
                     onclick="toggleNotif()" title="Notifications">
                    🔔
                    @if($notifCount > 0)
                        <span class="notif-badge">{{ $notifCount > 9 ? '9+' : $notifCount }}</span>
                    @endif
                </div>

                <div class="notif-dropdown" id="notifDropdown">
                    <div class="notif-header">
                        <span>🔔 Alerts</span>
                        <small>{{ $notifCount }} notification{{ $notifCount !== 1 ? 's' : '' }}</small>
                    </div>
                    <div class="notif-list">
                        @if($notifCount === 0)
                            <div class="notif-empty">✅ All good — no alerts right now.</div>
                        @else
                            @foreach($notifications as $n)
                                <div class="notif-item {{ $n['type'] }}">
                                    <div class="notif-icon">{{ $n['icon'] }}</div>
                                    <div class="notif-text">{{ $n['msg'] }}</div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="notif-footer">
                        <a href="{{ route('staff.inventory') }}">Go to Inventory →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Welcome Banner --}}
    <div class="welcome-banner">
        <div>
            <h3>Welcome back, {{ $sidebarFirstName }}! 👋</h3>
            <p>Staff &nbsp;|&nbsp; {{ now()->format('l') }}</p>
        </div>
        <img src="{{ asset('/asset/image/skintransparent.png') }}" alt="SkinMedic">
    </div>

    {{-- Critical Alert Banners --}}
    @if($outOfStockNames->isNotEmpty())
    <div class="alert-banner danger">
        ❌ <strong>Out of Stock:</strong> {{ $outOfStockNames->join(', ') }} — reorder immediately.
    </div>
    @endif

    @if($nearExpiryNames->isNotEmpty())
    <div class="alert-banner">
        🕐 <strong>Expiring soon:</strong> {{ $nearExpiryNames->join(', ') }} — check inventory.
    </div>
    @endif

    {{-- Stat Cards --}}
    <div class="stats-grid">
        <a href="{{ route('staff.bookings', ['filter' => 'pending']) }}" class="stat-card" style="animation-delay:.05s;text-decoration:none;color:inherit;">
            <div class="stat-icon">🧾</div>
            <div class="stat-value">{{ $newBookings }}</div>
            <div class="stat-label">Pending Bookings</div>
            <div class="stat-sub">Awaiting approval</div>
        </a>
        <a href="{{ route('staff.bookings', ['filter' => 'all']) }}" class="stat-card warn" style="animation-delay:.1s;text-decoration:none;color:inherit;">
            <div class="stat-icon">📋</div>
            <div class="stat-value">{{ $todaySessions }}</div>
            <div class="stat-label">Today's Sessions</div>
            <div class="stat-sub">Approved + Completed today</div>
        </a>
        <a href="{{ route('staff.bookings', ['filter' => 'completed']) }}" class="stat-card blue" style="animation-delay:.15s;text-decoration:none;color:inherit;">
            <div class="stat-icon">✅</div>
            <div class="stat-value">{{ $completedToday }}</div>
            <div class="stat-label">Completed Today</div>
            <div class="stat-sub">Finished appointments</div>
        </a>
        <a href="{{ route('staff.services') }}" class="stat-card" style="animation-delay:.2s;text-decoration:none;color:inherit;">
            <div class="stat-icon">💆</div>
            <div class="stat-value">{{ $totalServices }}</div>
            <div class="stat-label">Active Services</div>
            <div class="stat-sub">Available to book</div>
        </a>
    </div>

    {{-- Upcoming Appointments --}}
    <div class="card">
        <div class="card-title">
            📅 Upcoming Appointments — Next 7 Days
            <a href="{{ route('staff.bookings') }}">View all →</a>
        </div>
        @if($upcoming->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Patient</th>
                    <th>Service</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @php
                $pillMap = [
                    'pending'   => 'badge-pending',
                    'approved'  => 'badge-approved',
                    'completed' => 'badge-completed',
                    'cancelled' => 'badge-cancelled',
                ];
            @endphp
            @foreach($upcoming as $row)
            @php $badge = $pillMap[$row->status] ?? 'badge-pending'; @endphp
            <tr>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                <td>{{ $row->patient_name }}</td>
                <td>{{ $row->service_name }}</td>
                <td><span class="badge {{ $badge }}">{{ $row->status }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
            <p class="empty-state">🗓️ No upcoming appointments in the next 7 days.</p>
        @endif
    </div>

</div>{{-- /main --}}

@endsection

@push('scripts')
<script>
function toggleNotif() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    const wrapper = document.querySelector('.notif-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('notifDropdown').classList.remove('open');
    }
});
</script>
@endpush