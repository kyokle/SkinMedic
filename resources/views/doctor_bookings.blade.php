{{-- resources/views/doctor_bookings.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic - My Bookings')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/doctor_bookings.css') }}">
@endpush

@section('content')

@include('partials.sidebar_doctor')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>My Bookings</h2>
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
    </div>

    {{-- Filter Tabs --}}
    <div class="filter-tabs">
        @foreach(['all', 'pending', 'approved', 'completed', 'cancelled'] as $tab)
        <button class="{{ $activeFilter === $tab ? 'active' : '' }}"
                onclick="filterTable('{{ $tab }}', this)">
            {{ ucfirst($tab) }}
        </button>
        @endforeach
    </div>

    {{-- Bookings Table --}}
    <table id="bookingsTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Service</th>
                <th>Date</th>
                <th>Time</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $row)
            @php $cls = strtolower($row->status); @endphp
            <tr data-status="{{ $row->status }}">
                <td>{{ $row->appointment_id }}</td>
                <td>{{ $row->patient_name }}</td>
                <td>{{ $row->service_name }}</td>
                <td>{{ $row->appointment_date }}</td>
                <td>{{ \Carbon\Carbon::parse($row->appointment_time)->format('g:i A') }}</td>
                <td><span class="badge {{ $cls }}">{{ $row->status }}</span></td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:#999;padding:32px;">
                    No appointments found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</div>{{-- /main --}}

@endsection

@push('scripts')
<script>
function filterTable(status, btn) {
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('#bookingsTable tbody tr[data-status]').forEach(row => {
        row.style.display = (status === 'all' || row.dataset.status === status) ? '' : 'none';
    });
}

window.addEventListener('DOMContentLoaded', function () {
    const filter = '{{ $activeFilter }}';
    if (filter && filter !== 'all') {
        const btn = document.querySelector('.filter-tabs button.active');
        if (btn) filterTable(filter, btn);
    }
});
</script>
@endpush