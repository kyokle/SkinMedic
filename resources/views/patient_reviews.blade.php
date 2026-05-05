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
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <span class="rv-item-date">
                                        {{ \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}
                                    </span>
                                    {{--
                                        FIX 1: data-comment removed entirely.
                                        Storing comment text in a data-* attribute broke the button whenever
                                        the comment contained double-quotes (") or other special characters,
                                        because they would terminate the attribute string mid-way.
                                        The comment is now stored safely in a hidden <template> tag below,
                                        and JS reads it via template.content.textContent — no escaping issues.
                                    --}}
                                    <button
                                        class="rv-edit-btn"
                                        data-id="{{ $review->review_id }}"
                                        data-rating="{{ $review->rating }}"
                                    >✏ Edit</button>

                                    {{--
                                        FIX 1 (continued): <template> is an inert HTML element — its contents
                                        are never rendered or executed, just stored as raw DOM text.
                                        This means any character (quotes, ampersands, newlines) is safe here.
                                        The id ties this template to the button above via review_id.
                                    --}}
                                    <template id="comment-{{ $review->review_id }}">{{ $review->comment }}</template>
                                </div>
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

{{-- Edit modal — rendered once, reused for every review --}}
<div class="rv-modal-overlay hidden" id="editModalOverlay">
    <div class="rv-modal">

        <button class="rv-modal-close" id="editModalClose" title="Close">×</button>

        <p class="rv-modal-title">✏ Edit your review</p>

        <div id="editSuccess" class="rv-modal-success hidden">✓ &nbsp;Review updated successfully!</div>
        <div id="editError"   class="rv-modal-error   hidden"></div>

        <div class="rv-group">
            <label class="rv-label">Your rating</label>
            <div class="rv-stars" id="editStarRating">
                @for($i = 1; $i <= 5; $i++)
                    <button type="button" class="rv-star edit-star" data-val="{{ $i }}">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#d1c4b8" stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </button>
                @endfor
            </div>
            <p class="rv-rating-label" id="editRatingLabel"></p>
        </div>

        <div class="rv-group">
            <label class="rv-label">
                Your comment <span class="rv-hint">(min. 10 characters)</span>
            </label>
            <textarea id="editCommentInput" class="rv-textarea"
                      placeholder="Update your experience…"
                      maxlength="500"></textarea>
            <p class="rv-char-count"><span id="editCharCount">0</span>/500</p>
        </div>

        <button class="rv-save-btn" id="editSaveBtn">
            <span id="editBtnText">Save Changes</span>
            <span id="editBtnLoader" class="hidden">Saving…</span>
        </button>

    </div>
</div>

@endsection

@push('scripts')
<script>
/*
    FIX 2: Everything is wrapped in DOMContentLoaded.
    Previously, querySelectorAll('.rv-edit-btn') ran the moment the browser
    parsed this <script> tag. If the layout's @stack('scripts') fires before
    the modal HTML exists in the DOM, the NodeList comes back empty and no
    click listeners are ever attached — buttons appear but do nothing.
    DOMContentLoaded guarantees the full DOM (including the modal and every
    .rv-edit-btn) is built before any listener is attached.
*/
document.addEventListener('DOMContentLoaded', function () {

    const ratingLabels = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    let selectedRating = 0;

    // ── Submit-form star logic (scoped away from modal stars) ────────────────
    document.querySelectorAll('.rv-star:not(.edit-star)').forEach(btn => {
        btn.addEventListener('click', () => {
            selectedRating = parseInt(btn.dataset.val);
            document.getElementById('ratingInput').value = selectedRating;
            document.getElementById('ratingLabel').textContent = ratingLabels[selectedRating];
            document.querySelectorAll('.rv-star:not(.edit-star)').forEach((s, i) => {
                s.classList.toggle('active', i < selectedRating);
            });
        });
        btn.addEventListener('mouseenter', () => {
            const val = parseInt(btn.dataset.val);
            document.querySelectorAll('.rv-star:not(.edit-star)').forEach((s, i) => {
                s.querySelector('svg').style.fill   = i < val ? '#f2b84b' : 'none';
                s.querySelector('svg').style.stroke = i < val ? '#f2b84b' : '#d1c4b8';
            });
        });
        btn.addEventListener('mouseleave', () => {
            document.querySelectorAll('.rv-star:not(.edit-star)').forEach((s, i) => {
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
                document.querySelectorAll('.rv-star:not(.edit-star)').forEach(s => {
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

    // ── Edit modal logic ─────────────────────────────────────────────────────

    let editReviewId = null;
    let editRating   = 0;

    const overlay       = document.getElementById('editModalOverlay');
    const editStars     = document.querySelectorAll('.edit-star');
    const editLabel     = document.getElementById('editRatingLabel');
    const editComment   = document.getElementById('editCommentInput');
    const editCharCount = document.getElementById('editCharCount');
    const editSaveBtn   = document.getElementById('editSaveBtn');
    const editSuccess   = document.getElementById('editSuccess');
    const editError     = document.getElementById('editError');

    function paintEditStars(val) {
        editStars.forEach((s, i) => {
            const on = i < val;
            s.querySelector('svg').style.fill   = on ? '#f2b84b' : 'none';
            s.querySelector('svg').style.stroke = on ? '#f2b84b' : '#d1c4b8';
            s.classList.toggle('active', on);
        });
        editLabel.textContent = ratingLabels[val] || '';
    }

    editStars.forEach(btn => {
        btn.addEventListener('click',      () => { editRating = parseInt(btn.dataset.val); paintEditStars(editRating); });
        btn.addEventListener('mouseenter', () => paintEditStars(parseInt(btn.dataset.val)));
        btn.addEventListener('mouseleave', () => paintEditStars(editRating));
    });

    editComment?.addEventListener('input', function () {
        editCharCount.textContent = this.value.length;
    });

    document.querySelectorAll('.rv-edit-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            editReviewId  = btn.dataset.id;
            editRating    = parseInt(btn.dataset.rating);

            /*
                FIX 1 (JS side): Instead of reading data-comment (which broke on special chars),
                we now look up the matching <template id="comment-{review_id}"> and read its
                textContent. The browser stores template contents as raw text, so quotes,
                apostrophes, and any other characters come through perfectly intact.
            */
            const tmpl        = document.getElementById('comment-' + editReviewId);
            editComment.value = tmpl ? tmpl.content.textContent : '';
            editCharCount.textContent = editComment.value.length;

            paintEditStars(editRating);
            editSuccess.classList.add('hidden');
            editError.classList.add('hidden');
            overlay.classList.remove('hidden');
        });
    });

    document.getElementById('editModalClose').addEventListener('click', () => overlay.classList.add('hidden'));

    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.add('hidden');
    });

    editSaveBtn.addEventListener('click', async () => {
        editSuccess.classList.add('hidden');
        editError.classList.add('hidden');

        if (!editRating) {
            editError.textContent = 'Please select a star rating.';
            editError.classList.remove('hidden');
            return;
        }
        if (editComment.value.trim().length < 10) {
            editError.textContent = 'Comment must be at least 10 characters.';
            editError.classList.remove('hidden');
            return;
        }

        document.getElementById('editBtnText').classList.add('hidden');
        document.getElementById('editBtnLoader').classList.remove('hidden');
        editSaveBtn.disabled = true;

        const body = new FormData();
        body.append('_method',  'PUT');
        body.append('_token',   document.querySelector('meta[name="csrf-token"]').content);
        body.append('rating',   editRating);
        body.append('comment',  editComment.value.trim());

        try {
            const res = await fetch(`/patient/reviews/${editReviewId}`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body,
            });

            /*
                FIX: Check res.ok BEFORE calling res.json().
                If the server returns 419 (CSRF expired), 404 (route not found), 405 (wrong HTTP method),
                or 500 (controller crash), the response body is an HTML error page — not JSON.
                Calling .json() on HTML throws a SyntaxError which the old catch block silently swallowed
                and showed as "Network error", hiding the real problem.
                Now we read the raw text first, attempt to parse it, and show a specific status message
                if the server didn't return a successful JSON response.
            */
            const text = await res.text(); // always read as text first — safe for both JSON and HTML responses

            let data;
            try {
                data = JSON.parse(text); // attempt to parse as JSON
            } catch {
                // Server returned HTML (error page) instead of JSON — show the HTTP status code
                editError.textContent = `Server error (${res.status}). Please refresh the page and try again.`;
                editError.classList.remove('hidden');
                return; // exit early — nothing else to do
            }

            if (!res.ok) {
                // Server responded with JSON but a non-2xx status (e.g. 422 validation, 404 not found)
                editError.textContent = data.error || `Request failed (${res.status}).`;
                editError.classList.remove('hidden');
                return;
            }

            if (data.success) {
                editSuccess.classList.remove('hidden');
                setTimeout(() => {
                    overlay.classList.add('hidden');
                    window.location.reload();
                }, 1500);
            } else {
                editError.textContent = data.error || 'Something went wrong.';
                editError.classList.remove('hidden');
            }
        } catch (err) {
            // Only a true network failure reaches here (offline, DNS failure, CORS block, etc.)
            editError.textContent = 'Could not reach the server. Check your connection and try again.';
            editError.classList.remove('hidden');
            console.error('Edit review fetch error:', err); // log the real error to the browser console for debugging
        } finally {
            document.getElementById('editBtnText').classList.remove('hidden');
            document.getElementById('editBtnLoader').classList.add('hidden');
            editSaveBtn.disabled = false;
        }
    });

}); // end DOMContentLoaded
</script>
@endpush