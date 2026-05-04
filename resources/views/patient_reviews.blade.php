{{-- resources/views/patient_reviews.blade.php --}}

@extends('layouts.app')

@section('title', 'SkinMedic — My Reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_reviews.css') }}">
@endpush

@section('content')

@include('partials.sidebar_patient')

<div class="main">

    {{-- Topbar --}}
   <div class="topbar">
    <div class="topbar-text">
        <h2>⭐ Reviews</h2>
        <p>Share your experience to help others and improve our services.</p>
    </div>
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
        @include('partials.notif_bell_patient')
    </div>
</div>

    {{-- Grid --}}
    <div class="rv-grid">

        {{-- LEFT: Submit review --}}
        <div class="rv-card">
            <p class="rv-card-title">Leave a review</p>

            @if($appointments->count() > 0)

                <div id="reviewSuccess" class="rv-success hidden">
                    ✓ &nbsp;Review submitted successfully!
                </div>
                <div id="reviewError" class="rv-error hidden"></div>

                <form id="reviewForm">
                    @csrf

                    <div class="rv-group">
                        <label class="rv-label">Select appointment</label>
                        <select name="appointment_id" id="appointmentSelect" class="rv-select" required>
                            <option value="">Choose a completed appointment…</option>
                            @foreach($appointments as $apt)
                                <option value="{{ $apt->appointment_id }}"
                                        data-service="{{ $apt->service_name ?? 'General Consultation' }}">
                                    {{ \Carbon\Carbon::parse($apt->appointment_date)->format('M d, Y') }}
                                    — {{ $apt->service_name ?? 'General Consultation' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="rv-group">
                        <label class="rv-label">Your rating</label>
                        <div class="rv-stars" id="starRating">
                            @for($i = 1; $i <= 5; $i++)
                                <button type="button" class="rv-star" data-val="{{ $i }}">
                                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d1c4b8" stroke-width="1.5">
                                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                    </svg>
                                </button>
                            @endfor
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="">
                        <p class="rv-rating-label" id="ratingLabel"></p>
                    </div>

                    <div class="rv-group">
                        <label class="rv-label">
                            Your comment <span class="rv-hint">(min. 10 characters)</span>
                        </label>
                        <textarea name="comment" id="commentInput" class="rv-textarea"
                                  placeholder="Tell us about your experience…"
                                  maxlength="500" required></textarea>
                        <p class="rv-char-count"><span id="charCount">0</span>/500</p>
                    </div>

                    <button type="submit" class="rv-submit-btn" id="reviewSubmitBtn">
                        <span class="rv-btn-text">Submit Review</span>
                        <span class="rv-btn-loader hidden">Submitting…</span>
                    </button>
                </form>

            @else
                <div class="rv-empty">
                    <div class="rv-empty-icon">💬</div>
                    <p class="rv-empty-title">No appointments to review yet</p>
                    <p class="rv-empty-sub">
                        Once you complete an appointment, you can leave a review here.<br><br>
                        <a href="{{ route('patient.services') }}">Book an appointment →</a>
                    </p>
                </div>
            @endif
        </div>

        {{-- RIGHT: Past reviews --}}
        <div class="rv-card">
            <p class="rv-card-title">My submitted reviews</p>

            @if($myReviews->count() > 0)
                <div class="rv-list">
                    @foreach($myReviews as $review)
                        <div class="rv-item">
                            <div class="rv-item-top">
                                <div class="rv-item-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <svg width="13" height="13" viewBox="0 0 24 24"
                                             fill="{{ $i <= $review->rating ? '#f2b84b' : 'none' }}"
                                             stroke="{{ $i <= $review->rating ? '#f2b84b' : '#d1c4b8' }}"
                                             stroke-width="1.5">
                                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                        </svg>
                                    @endfor
                                </div>
                                <span class="rv-item-date">
                                    {{ \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}
                                </span>
                            </div>
                            @if($review->service_name)
                                <p class="rv-item-service">{{ $review->service_name }}</p>
                            @endif
                            <p class="rv-item-comment">{{ $review->comment }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rv-no-reviews">You haven't submitted any reviews yet.</p>
            @endif
        </div>

    </div>{{-- /rv-grid --}}

</div>{{-- /main --}}

@endsection

@push('scripts')
<script>
const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
let selectedRating = 0;

document.querySelectorAll('.rv-star').forEach(btn => {
    btn.addEventListener('click', () => {
        selectedRating = parseInt(btn.dataset.val);
        document.getElementById('ratingInput').value = selectedRating;
        document.getElementById('ratingLabel').textContent = ratingLabels[selectedRating];
        document.querySelectorAll('.rv-star').forEach((s, i) => {
            s.classList.toggle('active', i < selectedRating);
        });
    });
    btn.addEventListener('mouseenter', () => {
        const val = parseInt(btn.dataset.val);
        document.querySelectorAll('.rv-star').forEach((s, i) => {
            s.querySelector('svg').style.fill   = i < val ? '#f2b84b' : 'none';
            s.querySelector('svg').style.stroke = i < val ? '#f2b84b' : '#d1c4b8';
        });
    });
    btn.addEventListener('mouseleave', () => {
        document.querySelectorAll('.rv-star').forEach((s, i) => {
            const active = i < selectedRating;
            s.querySelector('svg').style.fill   = active ? '#f2b84b' : 'none';
            s.querySelector('svg').style.stroke = active ? '#f2b84b' : '#d1c4b8';
        });
    });
});

document.getElementById('commentInput')?.addEventListener('input', function () {
    document.getElementById('charCount').textContent = this.value.length;
});

document.getElementById('reviewForm')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const successEl = document.getElementById('reviewSuccess');
    const errorEl   = document.getElementById('reviewError');
    const submitBtn = document.getElementById('reviewSubmitBtn');

    successEl.classList.add('hidden');
    errorEl.classList.add('hidden');

    if (!selectedRating) {
        errorEl.textContent = 'Please select a star rating.';
        errorEl.classList.remove('hidden');
        return;
    }

    submitBtn.querySelector('.rv-btn-text').classList.add('hidden');
    submitBtn.querySelector('.rv-btn-loader').classList.remove('hidden');
    submitBtn.disabled = true;

    try {
        const res  = await fetch('{{ route("reviews.store") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            body: new FormData(this),
        });
        const data = await res.json();

        if (data.success) {
            successEl.classList.remove('hidden');
            this.reset();
            selectedRating = 0;
            document.getElementById('ratingLabel').textContent = '';
            document.getElementById('charCount').textContent = '0';
            document.querySelectorAll('.rv-star').forEach(s => {
                s.classList.remove('active');
                s.querySelector('svg').style.fill   = 'none';
                s.querySelector('svg').style.stroke = '#d1c4b8';
            });
            setTimeout(() => window.location.reload(), 1500);
        } else {
            errorEl.textContent = data.error || 'Something went wrong.';
            errorEl.classList.remove('hidden');
        }
    } catch {
        errorEl.textContent = 'Network error. Please try again.';
        errorEl.classList.remove('hidden');
    } finally {
        submitBtn.querySelector('.rv-btn-text').classList.remove('hidden');
        submitBtn.querySelector('.rv-btn-loader').classList.add('hidden');
        submitBtn.disabled = false;
    }
});
</script>
@endpush