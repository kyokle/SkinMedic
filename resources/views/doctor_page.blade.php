{{-- resources/views/doctor_page.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic — Doctor Dashboard')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/doctor_page.css') }}">
@endpush

@section('content')

@include('partials.sidebar_doctor')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>Home</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_doctor')
        </div>
    </div>

    {{-- Welcome Banner --}}
    <div class="welcome-banner">
        <div>
            <h3>Welcome back, Dr. {{ trim($docName) }}! 👋</h3>
            <p>{{ $docSpec ?: 'SkinMedic Physician' }} &nbsp;|&nbsp; {{ now()->format('l') }}</p>
        </div>
        <img src="{{ asset('/asset/image/skintransparent.png') }}" alt="SkinMedic">
    </div>

    {{-- Stat Cards --}}
    <div class="stats-grid">
        <a href="{{ route('doctor.bookings', ['filter' => 'all']) }}"
           class="stat-card" data-icon="🧾">
            <h2>{{ $total }}</h2>
            <p>Total Appointments</p>
        </a>
        <a href="{{ route('doctor.bookings', ['filter' => 'approved']) }}"
           class="stat-card" data-icon="📅">
            <h2>{{ $todayCount }}</h2>
            <p>Today's Appointments</p>
        </a>
        <a href="{{ route('doctor.bookings', ['filter' => 'pending']) }}"
           class="stat-card" data-icon="⏳">
            <h2>{{ $pendingCount }}</h2>
            <p>Pending</p>
        </a>
        <a href="{{ route('doctor.bookings', ['filter' => 'completed']) }}"
           class="stat-card" data-icon="✅">
            <h2>{{ $completedCount }}</h2>
            <p>Completed</p>
        </a>
    </div>

    {{-- Two Column: Today + Upcoming --}}
    <div class="two-col">

        {{-- Today's Sessions --}}
        <div>
            <div class="section-head">
                <h3>📅 Today's Sessions</h3>
            </div>
            <div class="table-wrap">
                @if(count($todaySessions) > 0)
                <table class="appt-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($todaySessions as $row)
                        @php $pill = 'pill-' . strtolower($row->status); @endphp
                        <tr>
                            <td>{{ $row->patient_name }}</td>
                            <td>{{ $row->service_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                            <td><span class="pill {{ $pill }}">{{ $row->status }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="no-data-box">No sessions today.</div>
                @endif
            </div>
        </div>

        {{-- Upcoming Appointments --}}
        <div>
            <div class="section-head">
                <h3>🗓 Upcoming (Next 7 Days)</h3>
            </div>
            <div class="table-wrap">
                @if(count($upcomingAppts) > 0)
                <table class="appt-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($upcomingAppts as $row)
                        @php $pill = 'pill-' . strtolower($row->status); @endphp
                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse($row->appointment_date)->format('M j') }}<br>
                                <small style="color:#aaa;">{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</small>
                            </td>
                            <td>{{ $row->patient_name }}</td>
                            <td>{{ $row->service_name }}</td>
                            <td><span class="pill {{ $pill }}">{{ $row->status }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <div class="no-data-box">No upcoming appointments.</div>
                @endif
            </div>
        </div>

    </div>{{-- /two-col --}}

    {{-- View All --}}
    <div style="text-align:right;margin-bottom:20px;">
        <a href="{{ route('doctor.bookings') }}" class="view-all-btn">View All Bookings →</a>
    </div>

</div>{{-- /main --}}

@endsection