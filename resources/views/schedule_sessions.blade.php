{{-- resources/views/schedule_sessions.blade.php --}}

@extends('layouts.app')

@section('title', 'Scheduled Sessions')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/schedule_sessions.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

<h2>📅 Your Scheduled Sessions</h2>

<table>
    <thead>
        <tr>
            <th>Service</th>
            <th>Date</th>
            <th>Time</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        @forelse($appointments as $row)
        <tr>
            <td>{{ $row->service_name }}</td>
            <td>{{ $row->appointment_date }}</td>
            <td>{{ $row->appointment_time }}</td>
            <td>{{ $row->status }}</td>
            <td>
                <button class="delete-btn"
                        onclick="confirmDelete({{ $row->appointment_id }})">
                    Delete
                </button>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">No scheduled sessions found.</td>
        </tr>
        @endforelse
    </tbody>
</table>

@endsection

@push('scripts')
<script>
function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this appointment?')) {
        fetch('{{ route("schedule.delete") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ appointment_id: id })
        }).then(() => {
            alert('Appointment deleted successfully!');
            window.location.reload();
        });
    }
}
</script>
@endpush