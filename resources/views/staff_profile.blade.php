{{-- resources/views/staff_profile.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic - My Profile')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/staff_profile.css') }}">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>My Profile</h2>
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
    </div>

    {{-- Profile Banner --}}
    <div class="profile-banner">
        <form action="{{ route('staff.profile.upload-pic') }}" method="POST" enctype="multipart/form-data" id="picForm">
            @csrf
            <img src="{{ asset($profilePic) }}"
                 alt="Profile Picture"
                 title="Click to change photo"
                 onerror="this.src='{{ asset('uploads/default.png') }}'"
                 onclick="document.getElementById('picInput').click()">
            <input type="file" id="picInput" name="profile_pic" style="display:none"
                   accept="image/*" onchange="document.getElementById('picForm').submit()">
        </form>
        <div class="banner-info">
            <h3>{{ trim($staff->firstName . ' ' . $staff->lastName) ?: 'Staff Member' }}</h3>
            <p>{{ $staff->position   ?: '—' }}</p>
            <p>{{ $staff->email      ?: '—' }}</p>
        </div>
    </div>

    {{-- Two Column Forms --}}
    <div class="two-col">

        {{-- Personal Information --}}
        <form action="{{ route('staff.profile.update-personal') }}" method="POST">
            @csrf
            <div class="form-card">
                <h4>Personal Information</h4>

                <div class="form-row">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="{{ $staff->firstName }}" required>
                </div>
                <div class="form-row">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="{{ $staff->lastName }}" required>
                </div>
                <div class="form-row">
                    <label>Email</label>
                    <input type="email" value="{{ $staff->email }}" readonly>
                </div>
                <div class="form-row">
                    <label>Gender</label>
                    <select name="gender">
                        <option value="">-- Select --</option>
                        @foreach(['male' => 'Male', 'female' => 'Female', 'others' => 'Others'] as $val => $label)
                            <option value="{{ $val }}" {{ $staff->gender === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <label>Phone</label>
                    <input type="text" name="phone_no" value="{{ $staff->phone_no }}">
                </div>
                <div class="form-row">
                    <label>Address</label>
                    <textarea name="address" rows="2">{{ $staff->address }}</textarea>
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>

        {{-- Employment Information --}}
        <form action="{{ route('staff.profile.update-employment') }}" method="POST">
            @csrf
            <div class="form-card">
                <h4>Employment Information</h4>

                <div class="form-row">
                    <label>Staff ID</label>
                    <input type="text" value="{{ $staff->staff_id }}" readonly>
                </div>
                <div class="form-row">
                    <label>Hire Date</label>
                    <input type="date" name="hire_date" value="{{ $staff->hire_date ?? '' }}">
                </div>
                <div class="form-row">
                    <label>Position</label>
                    <input type="text" name="position" value="{{ $staff->position }}">
                </div>
                <div class="form-row">
                    <label>Department</label>
                    <input type="text" name="department" value="{{ $staff->department }}">
                </div>
                <div class="form-row">
                    <label>Shift Schedule</label>
                    <input type="text" name="shift_schedule" value="{{ $staff->shift_schedule }}">
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>

    </div>{{-- /two-col --}}

</div>{{-- /main --}}

@endsection