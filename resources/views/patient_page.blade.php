{{-- resources/views/patient_page.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic — Patient Home')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_page.css') }}">
@endpush

@section('content')

@include('partials.sidebar_patient')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>Home</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_patient')
        </div>
    </div>

    @if(Session::has('success'))
    <div id="successToast" style="
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: #16a34a;
        color: white;
        padding: 14px 20px;
        border-radius: 10px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: Arial, sans-serif;
        font-size: 0.95rem;
        max-width: 360px;
    ">
        <span>✅</span>
        <span>{{ Session::get('success') }}</span>
        <button onclick="document.getElementById('successToast').remove()"
                style="margin-left:12px;background:none;border:none;color:white;font-size:1.1rem;cursor:pointer;line-height:1;">✕</button>
    </div>
    <script>
        setTimeout(() => {
            const t = document.getElementById('successToast');
            if (t) t.remove();
        }, 4000);
    </script>
    @endif

    {{-- Welcome Banner --}}
    <div class="welcome-banner">
        <div>
            <h3>Welcome back, {{ Session::get('firstName') }}! 👋</h3>
            <p>Manage your appointments and stay on top of your skin health.</p>
            <a href="{{ route('patient.services') }}" class="book-now-btn">+ Book Appointment</a>
        </div>
        <img src="{{ asset('/asset/image/skintransparent.png') }}" alt="SkinMedic">
    </div>

    {{-- Stat Cards --}}
    <section class="status">
        <a href="{{ route('patient.bookings', ['filter' => 'all']) }}"
           class="card" data-icon="🧾"
           style="text-decoration:none;color:inherit;display:block;cursor:pointer;">
            <h2>{{ $totalCount }}</h2>
            <p>Total Appointments</p>
        </a>
        <a href="{{ route('patient.bookings', ['filter' => 'pending']) }}"
           class="card" data-icon="⏳"
           style="text-decoration:none;color:inherit;display:block;cursor:pointer;">
            <h2>{{ $pendingCount }}</h2>
            <p>Pending</p>
        </a>
        <a href="{{ route('patient.bookings', ['filter' => 'completed']) }}"
           class="card" data-icon="✅"
           style="text-decoration:none;color:inherit;display:block;cursor:pointer;">
            <h2>{{ $completedCount }}</h2>
            <p>Completed</p>
        </a>
    </section>

    {{-- Upcoming Appointments --}}
    <div class="section-head">
        <h3>📅 Your Upcoming Appointments</h3>
        <a href="{{ route('patient.bookings') }}">View All →</a>
    </div>

    <div class="table-wrap">
        @if($appointments->count() > 0)
        <table class="appt-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Doctor</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @php
                $pillMap = [
                    'pending'   => 'pill-pending',
                    'approved'  => 'pill-approved',
                    'completed' => 'pill-completed',
                    'cancelled' => 'pill-cancelled',
                ];
            @endphp
            @foreach($appointments as $row)
            @php
                $pill = $pillMap[$row->status] ?? 'pill-pending';
                $doc  = $row->doctor_name ? 'Dr. ' . $row->doctor_name : 'Not Assigned';
            @endphp
            <tr class="clickable-row" onclick="openModal(
                '{{ addslashes($row->service_name) }}',
                '{{ addslashes($row->doctor_name) }}',
                '{{ $row->appointment_date }}',
                '{{ $row->appointment_time }}',
                '{{ $row->status }}',
                '{{ $row->appointment_id }}'
            )">
                <td>{{ $row->service_name }}</td>
                <td>{{ $doc }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ $row->appointment_time }}</td>
                <td><span class="pill {{ $pill }}">{{ $row->status }}</span></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @else
        <div class="no-data-box">
            🗓️ No upcoming appointments.<br>
            <a href="{{ route('patient.services') }}" style="color:#80a833;font-weight:600;">Book one now →</a>
        </div>
        @endif
    </div>

</div>{{-- /main --}}

{{-- Appointment Detail Modal --}}
<div id="bookingModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Appointment Details</h3>
        <div class="modal-row"><span>Service</span><span id="modalService"></span></div>
        <div class="modal-row"><span>Doctor</span><span id="modalDoctor"></span></div>
        <div class="modal-row"><span>Date</span><span id="modalDate"></span></div>
        <div class="modal-row"><span>Time</span><span id="modalTime"></span></div>
        <div class="modal-row"><span>Status</span><span id="modalStatus"></span></div>
        <form method="POST" action="{{ route('patient.cancel') }}" id="cancelForm">
            @csrf
            <input type="hidden" name="cancel_id" id="modalCancelId">
            <button type="submit" class="cancel-btn"
                onclick="return confirm('Are you sure you want to cancel this appointment?');">
                Cancel Appointment
            </button>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openModal(service, doctor, date, time, status, id) {
    document.getElementById('modalService').innerText = service;
    document.getElementById('modalDoctor').innerText  = doctor ? 'Dr. ' + doctor : 'Not Assigned';
    document.getElementById('modalDate').innerText    = date;
    document.getElementById('modalTime').innerText    = time;
    document.getElementById('modalStatus').innerText  = status;
    document.getElementById('modalCancelId').value    = id;
    document.getElementById('cancelForm').style.display = 'block';
    if (status === 'completed' || status === 'cancelled') {
        document.getElementById('cancelForm').style.display = 'none';
    }
    document.getElementById('bookingModal').classList.add('open');
}
function closeModal() {
    document.getElementById('bookingModal').classList.remove('open');
}
window.onclick = function(e) {
    if (e.target === document.getElementById('bookingModal')) closeModal();
}
</script>
@endpush