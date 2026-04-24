{{-- resources/views/doctor_profile.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic - My Profile')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/doctor_profile.css') }}">
@endpush

@section('content')

@include('partials.sidebar_doctor')

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
        <form action="{{ route('doctor.profile.upload-pic') }}" method="POST" enctype="multipart/form-data" id="picForm">
            @csrf
            <img src="{{ asset($profilePic) }}"
                 alt="Profile Picture"
                 title="Click to change photo"
                 onclick="document.getElementById('picInput').click()">
            <input type="file" id="picInput" name="profile_pic" style="display:none"
                   accept="image/*" onchange="document.getElementById('picForm').submit()">
        </form>
        <div class="banner-info">
            <h3>Dr. {{ $doctor['firstName'] }} {{ $doctor['lastName'] }}</h3>
            <p>{{ $doctor['specialization'] ?: 'No specialization set' }}</p>
            <p>{{ $doctor['email'] }}</p>
        </div>
    </div>

    {{-- Two Column Forms --}}
    <div class="two-col">

        {{-- Personal Information --}}
        <form action="{{ route('doctor.profile.update-personal') }}" method="POST">
            @csrf
            <div class="form-card">
                <h4>Personal Information</h4>

                <div class="form-row">
                    <label>First Name</label>
                    <input type="text" name="firstName" value="{{ $doctor['firstName'] }}" required>
                </div>
                <div class="form-row">
                    <label>Last Name</label>
                    <input type="text" name="lastName" value="{{ $doctor['lastName'] }}" required>
                </div>
                <div class="form-row">
                    <label>Email</label>
                    <input type="email" value="{{ $doctor['email'] }}" readonly>
                </div>
                <div class="form-row">
                    <label>Gender</label>
                    <select name="gender" required>
                        <option value="">-- Select --</option>
                        @foreach(['male' => 'Male', 'female' => 'Female', 'others' => 'Others'] as $val => $label)
                            <option value="{{ $val }}" {{ $doctor['gender'] === $val ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row">
                    <label>Phone</label>
                    <input type="text" name="phone_no" value="{{ $doctor['phone_no'] }}">
                </div>
                <div class="form-row">
                    <label>Address</label>
                    <textarea name="address" rows="2">{{ $doctor['address'] }}</textarea>
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>

        {{-- Professional Information --}}
        <form action="{{ route('doctor.profile.update-doctor') }}" method="POST">
            @csrf
            <div class="form-card">
                <h4>Professional Information</h4>

                <div class="form-row">
                    <label>License Number</label>
                    <input type="text" name="license_number" value="{{ $doctor['license_number'] }}">
                </div>
                <div class="form-row">
                    <label>Specialization</label>
                    <input type="text" name="specialization" value="{{ $doctor['specialization'] }}">
                </div>
                <div class="form-row">
                    <label>Years of Experience</label>
                    <input type="number" name="years_of_experience" min="0" value="{{ $doctor['years_of_experience'] }}">
                </div>
                <div class="form-row">
                    <label>Consultation Fee (₱)</label>
                    <input type="number" name="consultation_fee" min="0" step="0.01" value="{{ $doctor['consultation_fee'] }}">
                </div>
                <div class="form-row">
                    <label>Availability Schedule</label>
                    <textarea name="availability_schedule" rows="2">{{ $doctor['availability_schedule'] }}</textarea>
                </div>

                <button type="submit" class="save-btn">Save Changes</button>
            </div>
        </form>

    </div>{{-- /two-col --}}

</div>{{-- /main --}}

@endsection