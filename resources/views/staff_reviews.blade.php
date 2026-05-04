{{-- resources/views/staff_reviews.blade.php --}}

@extends('layouts.app')

@section('title', 'Manage Reviews — SkinMedic Admin')
<meta name="csrf-token" content="{{ csrf_token() }}">

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/staff_reviews.css') }}">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="main">

    {{-- Topbar --}}
   <div class="topbar">
    <div class="topbar-text">
        <h2>Manage Reviews</h2>
        <p>View and moderate patient feedback to maintain quality care.</p>
    </div>
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->toDateString() }}</strong>
        </div>
        @include('partials.notif_bell_staff')
    </div>
</div>

    <div class="mr-page">

       
        {{-- Summary Stats --}}
        <div class="mr-stats">
            <div class="mr-stat-card">
                <p class="mr-stat-num">{{ $total }}</p>
                <p class="mr-stat-label">Total reviews</p>
            </div>
            <div class="mr-stat-card">
                <p class="mr-stat-num">{{ $avgRating > 0 ? $avgRating : '—' }}</p>
                <p class="mr-stat-label">Average rating</p>
            </div>
            <div class="mr-stat-card">
                <div class="mr-avg-stars">
                    @for($i = 1; $i <= 5; $i++)
                        <svg width="18" height="18" viewBox="0 0 24 24"
                             fill="{{ $i <= round($avgRating) ? '#f2b84b' : 'none' }}"
                             stroke="{{ $i <= round($avgRating) ? '#f2b84b' : '#d1c4b8' }}"
                             stroke-width="1.5">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    @endfor
                </div>
                <p class="mr-stat-label">Overall rating</p>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="mr-filter-bar">
            <button class="mr-filter-btn active" data-filter="all">All</button>
            <button class="mr-filter-btn" data-filter="5">★★★★★</button>
            <button class="mr-filter-btn" data-filter="4">★★★★</button>
            <button class="mr-filter-btn" data-filter="3">★★★</button>
            <button class="mr-filter-btn" data-filter="2">★★</button>
            <button class="mr-filter-btn" data-filter="1">★</button>
        </div>

        {{-- Reviews Table --}}
        @if($reviews->count() > 0)
            <div class="mr-table-wrap">
                <table class="mr-table" id="reviewsTable">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Service</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reviews as $review)
                            <tr data-rating="{{ $review->rating }}" id="row-{{ $review->review_id }}">
                                <td class="mr-td-patient">
                                    <div class="mr-avatar">{{ strtoupper(substr($review->patient_name, 0, 1)) }}</div>
                                    <span>{{ $review->patient_name }}</span>
                                </td>
                                <td class="mr-td-service">{{ $review->service_name ?? 'General' }}</td>
                                <td class="mr-td-rating">
                                    <div class="mr-inline-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg width="13" height="13" viewBox="0 0 24 24"
                                                 fill="{{ $i <= $review->rating ? '#f2b84b' : 'none' }}"
                                                 stroke="{{ $i <= $review->rating ? '#f2b84b' : '#d1c4b8' }}"
                                                 stroke-width="1.5">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                            </svg>
                                        @endfor
                                    </div>
                                </td>
                                <td class="mr-td-comment">{{ $review->comment }}</td>
                                <td class="mr-td-date">{{ \Carbon\Carbon::parse($review->created_at)->format('M d, Y') }}</td>
                                <td>
                                    <button class="mr-delete-btn"
                                            onclick="deleteReview({{ $review->review_id }}, this)"
                                            title="Delete review">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6l-1 14H6L5 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                            <path d="M9 6V4h6v2"/>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="mr-empty">
                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#b5a898" stroke-width="1">
                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                </svg>
                <p>No reviews yet.</p>
            </div>
        @endif

        </div>{{-- /mr-page --}}
</div>{{-- /main --}}

@endsection

@push('scripts')
<script>
// Filter by star rating
document.querySelectorAll('.mr-filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.mr-filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('#reviewsTable tbody tr').forEach(row => {
            row.classList.toggle('hidden', filter !== 'all' && row.dataset.rating !== filter);
        });
    });
});

// Delete review
async function deleteReview(id, btn) {
    if (!confirm('Delete this review? This cannot be undone.')) return;
    btn.disabled = true;
    try {
        const res  = await fetch(`/admin/reviews/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });
        const data = await res.json();
        if (data.success) {
            const row = document.getElementById(`row-${id}`);
            row.style.opacity    = '0';
            row.style.transition = 'opacity .3s';
            setTimeout(() => row.remove(), 300);
        } else {
            alert('Could not delete review.');
            btn.disabled = false;
        }
    } catch {
        alert('Network error.');
        btn.disabled = false;
    }
}
</script>
@endpush