{{-- resources/views/patient_services.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic Services')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_services.css') }}">
@endpush

@section('content')

@include('partials.sidebar_patient')

<div class="main">

    {{-- Topbar --}}
   <div class="topbar">
    <h2>Services</h2>
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
        @include('partials.notif_bell_patient')
    </div>
</div>

    {{-- Services Grid --}}
    <div class="treatments-section">
        <h3>Here are the treatments available</h3>
        <div class="treatments-container">
            @forelse($services as $service)
            <div class="treatment-card"
                 onclick="openModal(
                    '{{ addslashes($service->name) }}',
                    '{{ addslashes($service->description) }}',
                    '{{ $service->price }}',
                    '{{ asset('uploads/' . $service->image) }}',
                    {{ $service->service_id }}
                 )">
                <img src="{{ asset('uploads/' . $service->image) }}" alt="{{ $service->name }}">
                <div class="details">
                    <h4>{{ $service->name }}</h4>
                    <p>₱{{ $service->price }}</p>
                </div>
            </div>
            @empty
            <p>No services available at the moment.</p>
            @endforelse
        </div>
    </div>

</div>{{-- /main --}}

{{-- Service Detail Modal --}}
<div id="serviceModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <img id="modalImage" src="" alt="">
        <h3 id="modalName"></h3>
        <p id="modalDesc"></p>
        <p class="price" id="modalPrice"></p>
        <a id="bookButton" class="book-btn">Book Appointment</a>
    </div>
</div>

@endsection

@push('scripts')
<script>
const modal      = document.getElementById('serviceModal');
const modalName  = document.getElementById('modalName');
const modalDesc  = document.getElementById('modalDesc');
const modalPrice = document.getElementById('modalPrice');
const modalImage = document.getElementById('modalImage');
const bookButton = document.getElementById('bookButton');

function openModal(name, desc, price, image, treatmentId) {
    modal.style.display   = 'flex';
    modalName.textContent  = name;
    modalDesc.textContent  = desc;
    modalPrice.textContent = '₱' + price;
    modalImage.src         = image;
    bookButton.href        = '{{ url("book-appointment") }}?service_id=' + treatmentId;
}

function closeModal() {
    modal.style.display = 'none';
}

window.onclick = function(event) {
    if (event.target === modal) closeModal();
}
</script>
@endpush