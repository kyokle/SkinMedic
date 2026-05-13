{{-- resources/views/patient_orders.blade.php --}}
@extends('layouts.app')

@section('title', 'My Orders — SkinMedic')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_orders.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Fraunces:ital,wght@0,700;1,500&display=swap" rel="stylesheet">
@endpush

@section('content')
@include('partials.sidebar_patient')

<div class="orders-main">

    {{-- ── TOP BAR ── --}}
    <div class="orders-topbar">
        <div>
            <h1 class="orders-heading">My Orders</h1>
            <span class="orders-sub">{{ $orders->count() }} order{{ $orders->count() !== 1 ? 's' : '' }} total</span>
        </div>
        <a href="{{ route('patient.products') }}" class="shop-again-btn">
            <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            Shop Again
        </a>
    </div>

    {{-- ── FLASH MESSAGES ── --}}
    @if(session('success'))
        <div class="flash flash-success">
            <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/>
                <path d="M6.5 10l2.5 2.5 4.5-4.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="flash flash-error">
            <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/>
                <path d="M10 6v4.5M10 13.5v.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            {{ session('error') }}
        </div>
    @endif

    {{-- ── FILTER TABS ── --}}
    <div class="order-tabs">
        <button class="order-tab active" data-filter="all" onclick="filterOrders(this, 'all')">All</button>
        <button class="order-tab" data-filter="pending"    onclick="filterOrders(this, 'pending')">Pending</button>
        <button class="order-tab" data-filter="confirmed"  onclick="filterOrders(this, 'confirmed')">Confirmed</button>
        <button class="order-tab" data-filter="ready"      onclick="filterOrders(this, 'ready')">Ready for Pick-up</button>
        <button class="order-tab" data-filter="completed"  onclick="filterOrders(this, 'completed')">Completed</button>
        <button class="order-tab" data-filter="cancelled"  onclick="filterOrders(this, 'cancelled')">Cancelled</button>
    </div>

    {{-- ── ORDERS LIST ── --}}
    @forelse($orders as $order)
    <div class="order-card" data-status="{{ $order->status }}">

        {{-- Order Header --}}
        <div class="order-card-header">
            <div class="order-meta">
                <span class="order-number">Order #{{ $order->id }}</span>
                <span class="order-date">{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y · h:i A') }}</span>
            </div>
            <div class="order-badges">
                {{-- Order status badge --}}
                <span class="status-badge status-{{ $order->status }}">
                    @switch($order->status)
                        @case('pending')    🕐 Pending       @break
                        @case('confirmed')  ✅ Confirmed     @break
                        @case('packing')    📦 Being Packed  @break
                        @case('ready')      🏪 Ready for Pick-up @break
                        @case('completed')  ✔ Completed     @break
                        @case('cancelled')  ✕ Cancelled     @break
                        @default            {{ ucfirst($order->status) }}
                    @endswitch
                </span>
                {{-- Payment status badge --}}
                <span class="pay-badge pay-{{ $order->payment_status ?? 'unpaid' }}">
                    {{ ucfirst($order->payment_status ?? 'unpaid') }}
                </span>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="order-items-list">
            @foreach($order->items as $item)
            <div class="order-item-row">
                <div class="oi-img-wrap">
                    @if($item->image)
                        <img src="{{ $item->image }}" alt="{{ $item->product_name }}"
                             onerror="this.style.display='none'">
                    @else
                        <div class="oi-img-placeholder">
                            <svg viewBox="0 0 24 24" fill="none" width="16" height="16" opacity=".3">
                                <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.6"/>
                                <circle cx="8.5" cy="8.5" r="1.5" stroke="currentColor" stroke-width="1.4"/>
                                <path d="M3 16l5-4 4 3.5 3-2.5 6 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="oi-info">
                    <p class="oi-name">{{ $item->product_name }}</p>
                    <p class="oi-qty">Qty: {{ $item->quantity }} × ₱{{ number_format($item->unit_price, 2) }}</p>
                </div>
                <span class="oi-subtotal">₱{{ number_format($item->subtotal, 2) }}</span>
            </div>
            @endforeach
        </div>

        {{-- Order Footer --}}
        <div class="order-card-footer">
            <div class="order-footer-left">
                {{-- Payment method --}}
                <span class="payment-chip">
                    @if($order->payment_method === 'gcash')
                        <svg viewBox="0 0 20 20" fill="none" width="13" height="13">
                            <rect x="2" y="4" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.4"/>
                            <path d="M2 8h16" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                        GCash
                    @else
                        <svg viewBox="0 0 20 20" fill="none" width="13" height="13">
                            <rect x="2" y="5" width="16" height="11" rx="2" stroke="currentColor" stroke-width="1.4"/>
                            <circle cx="10" cy="10.5" r="2" stroke="currentColor" stroke-width="1.4"/>
                        </svg>
                        Cash on Pick-up
                    @endif
                </span>

                {{-- GCash reference if available --}}
                @if($order->reference)
                    <span class="ref-chip">Ref: {{ $order->reference }}</span>
                @endif

                {{-- Note --}}
                @if($order->note)
                    <span class="note-chip" title="{{ $order->note }}">
                        📝 {{ Str::limit($order->note, 40) }}
                    </span>
                @endif
            </div>

            <div class="order-footer-right">
                {{-- View proof button for GCash --}}
                @if($order->payment_proof)
                    <button class="view-proof-btn"
                            onclick="openProof('{{ $order->payment_proof }}', '{{ $order->reference }}')">
                        🧾 View Proof
                    </button>
                @endif
                <div class="order-total-wrap">
                    <span class="total-label">Total</span>
                    <span class="total-amount">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Order Status Timeline --}}
        <div class="order-timeline">
            @php
                $steps = [
                    'pending'   => ['🕐', 'Order Placed'],
                    'confirmed' => ['✅', 'Confirmed'],
                    'packing'   => ['📦', 'Packing'],
                    'ready'     => ['🏪', 'Ready for Pick-up'],
                    'completed' => ['✔', 'Completed'],
                ];
                $stepKeys  = array_keys($steps);
                $currentIdx = array_search($order->status, $stepKeys);
                if ($order->status === 'cancelled') $currentIdx = -1;
            @endphp

            @if($order->status === 'cancelled')
                <div class="timeline-cancelled">Order was cancelled</div>
            @else
                @foreach($steps as $key => [$icon, $label])
                @php
                    $idx   = array_search($key, $stepKeys);
                    $done  = $idx < $currentIdx;
                    $active= $idx === $currentIdx;
                @endphp
                <div class="timeline-step {{ $done ? 'done' : '' }} {{ $active ? 'active' : '' }}">
                    <div class="tl-dot">{{ $done ? '✓' : $icon }}</div>
                    <span class="tl-label">{{ $label }}</span>
                </div>
                @if(!$loop->last)
                    <div class="tl-line {{ $done ? 'done' : '' }}"></div>
                @endif
                @endforeach
            @endif
        </div>

    </div>
    @empty
        <div class="orders-empty">
            <svg viewBox="0 0 64 64" fill="none" width="56" height="56" opacity=".2">
                <path d="M12 4L6 12v40a4 4 0 004 4h44a4 4 0 004-4V12l-6-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="6" y1="12" x2="58" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M40 20a8 8 0 01-16 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <p>You haven't placed any orders yet.</p>
            <a href="{{ route('patient.products') }}" class="shop-again-btn" style="margin-top:16px">Browse Products</a>
        </div>
    @endforelse

</div>

{{-- ── PROOF IMAGE LIGHTBOX ── --}}
<div class="proof-lightbox" id="proofLightbox" onclick="closeProof(event)">
    <div class="proof-lightbox-box">
        <button class="proof-lightbox-close" onclick="closeProof()">✕</button>
        <p class="proof-lightbox-ref" id="proofRef"></p>
        <img id="proofLightboxImg" src="" alt="Payment Proof">
    </div>
</div>

@push('scripts')
<script>
/* ── FILTER TABS ── */
function filterOrders(btn, filter) {
    document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.order-card').forEach(card => {
        const show = filter === 'all' || card.dataset.status === filter;
        card.style.display = show ? '' : 'none';
    });
}

/* ── PROOF LIGHTBOX ── */
function openProof(url, ref) {
    document.getElementById('proofLightboxImg').src = url;
    document.getElementById('proofRef').textContent = ref ? 'Reference #: ' + ref : '';
    document.getElementById('proofLightbox').style.display = 'flex';
}

function closeProof(e) {
    if (!e || e.target === document.getElementById('proofLightbox')) {
        document.getElementById('proofLightbox').style.display = 'none';
    }
}
</script>
@endpush

@endsection