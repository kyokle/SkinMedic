{{-- resources/views/patient_profile.blade.php --}}

@extends('layouts.app')

@section('title', 'My Profile - SkinMedic')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_profile.css') }}">
@endpush

@section('content')

{{-- Upload success toast --}}
@if(session('upload_success'))
<div id="toast" style="
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    background: #2e7d32; color: white;
    padding: 14px 24px; border-radius: 8px;
    font-size: 14px; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    animation: fadeIn 0.3s ease;">
    ✅ Profile picture updated successfully!
</div>
<style>
@keyframes fadeIn { from { opacity:0; transform:translateY(-10px); } to { opacity:1; transform:translateY(0); } }
</style>
<script>
    setTimeout(() => {
        const t = document.getElementById('toast');
        if (t) t.style.display = 'none';
    }, 3000);
</script>
@endif


@include('partials.sidebar_patient')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>My Profile</h2>
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
    </div>

    {{-- Profile Header --}}
    <div class="profile-header-card">
        <div class="profile-pic-wrapper">
            <img src="{{ asset($profilePic) }}" alt="Profile"
                 onerror="this.src='{{ asset('uploads/default.png') }}'">
            <form method="POST"
                  action="{{ route('patient.profile.upload-pic') }}"
                  enctype="multipart/form-data"
                  id="picForm">
                @csrf
                <input type="file" name="profile_pic" id="picInput"
                       hidden accept="image/jpg,image/jpeg,image/png"
                       onchange="document.getElementById('picForm').submit();">
                <div class="pic-upload-overlay"
                     onclick="document.getElementById('picInput').click()">
                    CHANGE PHOTO
                </div>
            </form>
        </div>
        <div class="profile-info">
            <h2>{{ trim($data->firstName . ' ' . $data->lastName) ?: 'Patient' }}</h2>
            <p>
                Patient ID: #{{ $data->patient_id ?? '—' }}<br>
                {{ $data->email ?? '—' }}<br>
                {{ $data->phone_no ?? 'No phone on file' }}
            </p>
        </div>
    </div>

    {{-- Two Panels --}}
    <div class="panels-row">

        {{-- Personal Details --}}
        <div class="panel-card">
            <div class="panel-tab">Personal Details</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('patient.profile.update-personal') }}">
                    @csrf

                    <div class="field-row">
                        <label>First Name</label>
                        <input type="text" name="firstName"
                               value="{{ old('firstName', $data->firstName) }}" required>
                    </div>
                    <div class="field-row">
                        <label>Last Name</label>
                        <input type="text" name="lastName"
                               value="{{ old('lastName', $data->lastName) }}" required>
                    </div>
                    <div class="field-row">
                        <label>Email</label>
                        <input type="email" value="{{ $data->email }}" readonly>
                    </div>
                    <div class="field-row">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="male"   {{ $data->gender === 'male'   ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ $data->gender === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="others" {{ $data->gender === 'others' ? 'selected' : '' }}>Others</option>
                        </select>
                    </div>
                    <div class="field-row">
                        <label>Phone No.</label>
                        <input type="tel" name="phone_no"
                               value="{{ old('phone_no', $data->phone_no) }}">
                    </div>
                    <div class="field-row">
                        <label>Address</label>
                        <input type="text" name="address"
                               value="{{ old('address', $data->address) }}">
                    </div>

                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
            </div>
        </div>

        {{-- Medical Details --}}
        <div class="panel-card">
            <div class="panel-tab">Medical Details</div>
            <div class="panel-body">
                <form method="POST" action="{{ route('patient.profile.update-medical') }}">
                    @csrf

                    <div class="field-row">
                        <label>Patient ID</label>
                        <input type="text" value="{{ $data->patient_id ?? '—' }}" readonly>
                    </div>
                    <div class="field-row">
                        <label>Medical History</label>
                        <textarea name="medical_history" rows="3">{{ old('medical_history', $data->medical_history) }}</textarea>
                    </div>
                    <div class="field-row">
                        <label>Allergies</label>
                        <textarea name="allergies" rows="2">{{ old('allergies', $data->allergies) }}</textarea>
                    </div>
                    <div class="field-row">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact_name"
                               value="{{ old('emergency_contact_name', $data->emergency_contact_name) }}"
                               placeholder="Contact name">
                    </div>
                    <div class="field-row">
                        <label>Emergency Phone</label>
                        <input type="tel" name="emergency_contact_phone"
                               value="{{ old('emergency_contact_phone', $data->emergency_contact_phone) }}"
                               placeholder="Contact phone">
                    </div>

                    <button type="submit" class="save-btn">Save Changes</button>
                </form>
            </div>
        </div>

    </div>{{-- /panels-row --}}

</div>{{-- /main --}}

@endsection