{{-- resources/views/patient_orders.blade.php --}}
@extends('layouts.app')

@section('title', 'My Orders — SkinMedic')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_orders.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Fraunces:ital,wght@0,700;1,500&display=swap" rel="stylesheet">
    <style>
        /* ── INLINE CANCEL PANEL ── */
        .cancel-trigger-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 8px;
            border: 1.5px solid #f5c2c7;
            background: #fff5f6;
            color: #c0392b;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.18s, border-color 0.18s;
        }
        .cancel-trigger-btn:hover {
            background: #fce8ea;
            border-color: #e57373;
        }
        .cancel-trigger-btn svg {
            flex-shrink: 0;
        }

        /* Panel wrapper — hidden by default */
        .cancel-panel {
            display: none;
            flex-direction: column;
            gap: 12px;
            margin-top: 14px;
            padding: 16px 18px;
            border-radius: 10px;
            border: 1.5px solid #f5c2c7;
            background: #fff9f9;
            animation: panelSlideIn 0.2s ease;
        }
        .cancel-panel.open {
            display: flex;
        }
        @keyframes panelSlideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .cancel-panel-title {
            font-size: 0.82rem;
            font-weight: 600;
            color: #b91c1c;
            margin: 0 0 2px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .cancel-panel label {
            font-size: 0.78rem;
            font-weight: 500;
            color: #555;
            margin-bottom: 4px;
            display: block;
        }

        .cancel-panel select,
        .cancel-panel textarea {
            width: 100%;
            box-sizing: border-box;
            border-radius: 7px;
            border: 1.4px solid #e2c4c4;
            background: #fff;
            font-size: 0.8rem;
            color: #333;
            padding: 8px 11px;
            outline: none;
            font-family: inherit;
            transition: border-color 0.15s;
        }
        .cancel-panel select:focus,
        .cancel-panel textarea:focus {
            border-color: #e57373;
        }
        .cancel-panel textarea {
            resize: vertical;
            min-height: 68px;
        }

        .cancel-panel-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .cancel-back-btn {
            padding: 7px 16px;
            border-radius: 8px;
            border: 1.4px solid #ddd;
            background: #fff;
            color: #555;
            font-size: 0.78rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s;
        }
        .cancel-back-btn:hover {
            background: #f5f5f5;
        }

        .cancel-confirm-btn {
            padding: 7px 16px;
            border-radius: 8px;
            border: none;
            background: #c0392b;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s, opacity 0.15s;
        }
        .cancel-confirm-btn:hover {
            background: #a93226;
        }
        .cancel-confirm-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }

        /* ── CANCELLATION DETAILS BOX (on cancelled orders) ── */
        .cancellation-details {
            margin-top: 14px;
            padding: 13px 16px;
            border-radius: 10px;
            border: 1.4px solid #f5c2c7;
            background: #fff5f6;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .cancellation-details-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
        }
        .cancellation-detail-row {
            font-size: 0.8rem;
            color: #555;
        }
        .cancellation-detail-row strong {
            color: #333;
        }
    </style>
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

    {{-- ── FILTER TABS + SEARCH ROW ── --}}
    <div class="orders-controls">
        <div class="order-tabs">
            <button class="order-tab active" data-filter="all" onclick="filterOrders(this, 'all')">All</button>
            <button class="order-tab" data-filter="pending"    onclick="filterOrders(this, 'pending')">Pending</button>
            <button class="order-tab" data-filter="confirmed"  onclick="filterOrders(this, 'confirmed')">Confirmed</button>
            <button class="order-tab" data-filter="ready"      onclick="filterOrders(this, 'ready')">Ready for Pick-up</button>
            <button class="order-tab" data-filter="completed"  onclick="filterOrders(this, 'completed')">Completed</button>
            <button class="order-tab" data-filter="cancelled"  onclick="filterOrders(this, 'cancelled')">Cancelled</button>
        </div>
        <div class="orders-search-wrap">
            <svg class="orders-search-icon" viewBox="0 0 20 20" fill="none" width="15" height="15">
                <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6"/>
                <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
            </svg>
            <input type="text" id="orderSearch" class="orders-search-input"
                   placeholder="Search by product or order #…"
                   oninput="applyOrderFilters()">
            <button class="orders-search-clear" id="searchClear" onclick="clearSearch()" style="display:none">✕</button>
        </div>
    </div>

    {{-- ── RESULTS COUNT ── --}}
    <div class="orders-result-count" id="ordersResultCount" style="display:none"></div>

    {{-- ── ORDERS LIST ── --}}
    @forelse($orders as $order)
    <div class="order-card" data-status="{{ $order->status }}"
         data-search="{{ strtolower('#' . $order->id . ' ' . $order->items->pluck('product_name')->implode(' ')) }}">

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

                {{-- ── CANCEL TRIGGER — only for pending / confirmed ── --}}
                @if(in_array($order->status, ['pending', 'confirmed']))
                    <button class="cancel-trigger-btn"
                            onclick="toggleCancelPanel({{ $order->id }}, this)"
                            id="cancel-trigger-{{ $order->id }}">
                        <svg viewBox="0 0 20 20" fill="none" width="13" height="13">
                            <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                            <path d="M7 7l6 6M13 7l-6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        Cancel Order
                    </button>
                @endif

                <div class="order-total-wrap">
                    <span class="total-label">Total</span>
                    <span class="total-amount">₱{{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- ── INLINE CANCEL PANEL ── --}}
        @if(in_array($order->status, ['pending', 'confirmed']))
        <div class="cancel-panel" id="cancel-panel-{{ $order->id }}">
            <p class="cancel-panel-title">
                <svg viewBox="0 0 20 20" fill="none" width="14" height="14">
                    <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M10 6v4.5M10 13.5v.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                </svg>
                Cancel Order #{{ $order->id }}
            </p>

            <form method="POST" action="{{ route('patient.orders.cancel', $order->id) }}"
                  id="cancel-form-{{ $order->id }}"
                  onsubmit="return confirmCancel({{ $order->id }})">
                @csrf
                @method('PATCH')

                <div>
                    <label for="cancel-reason-{{ $order->id }}">Reason for cancellation <span style="color:#c0392b">*</span></label>
                    <select name="cancel_reason"
                            id="cancel-reason-{{ $order->id }}"
                            required
                            onchange="updateCancelBtn({{ $order->id }}, this.value)">
                        <option value="" disabled selected>Select a reason…</option>
                        <option value="changed_mind">I changed my mind</option>
                        <option value="wrong_item">Ordered the wrong item</option>
                        <option value="found_better_price">Found a better price elsewhere</option>
                        <option value="too_long">Taking too long</option>
                        <option value="duplicate_order">Duplicate order</option>
                        <option value="other">Other</option>
                    </select>
                </div>

                <div>
                    <label for="cancel-notes-{{ $order->id }}">Additional notes <span style="color:#aaa;font-weight:400">(optional)</span></label>
                    <textarea name="cancel_notes"
                              id="cancel-notes-{{ $order->id }}"
                              placeholder="Tell us more (optional)…"
                              maxlength="500"></textarea>
                </div>

                <div class="cancel-panel-actions">
                    <button type="button" class="cancel-back-btn"
                            onclick="toggleCancelPanel({{ $order->id }})">
                        ← Back
                    </button>
                    <button type="submit" class="cancel-confirm-btn" disabled
                            id="cancel-submit-{{ $order->id }}">
                        Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
        @endif

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

        {{-- ── CANCELLATION DETAILS (visible on cancelled orders) ── --}}
        @if($order->status === 'cancelled' && ($order->cancel_reason || $order->cancel_notes))
        <div class="cancellation-details">
            <p class="cancellation-details-title">✕ Cancellation Details</p>
            @if($order->cancel_reason)
            <p class="cancellation-detail-row">
                <strong>Reason:</strong>
                @php
                    $reasonLabels = [
                        'changed_mind'       => 'I changed my mind',
                        'wrong_item'         => 'Ordered the wrong item',
                        'found_better_price' => 'Found a better price elsewhere',
                        'too_long'           => 'Taking too long',
                        'duplicate_order'    => 'Duplicate order',
                        'other'              => 'Other',
                    ];
                @endphp
                {{ $reasonLabels[$order->cancel_reason] ?? ucfirst(str_replace('_', ' ', $order->cancel_reason)) }}
            </p>
            @endif
            @if($order->cancel_notes)
            <p class="cancellation-detail-row">
                <strong>Notes:</strong> {{ $order->cancel_notes }}
            </p>
            @endif
        </div>
        @endif

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
let activeFilter = 'all';

/* ── TAB FILTER ── */
function filterOrders(btn, filter) {
    document.querySelectorAll('.order-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = filter;
    applyOrderFilters();
}

/* ── SEARCH + FILTER COMBINED ── */
function applyOrderFilters() {
    const query      = document.getElementById('orderSearch').value.toLowerCase().trim();
    const clearBtn   = document.getElementById('searchClear');
    const countEl    = document.getElementById('ordersResultCount');
    const cards      = document.querySelectorAll('.order-card');

    clearBtn.style.display = query ? 'flex' : 'none';

    let visible = 0;
    cards.forEach(card => {
        const statusMatch = activeFilter === 'all' || card.dataset.status === activeFilter;
        const searchMatch = !query || card.dataset.search.includes(query);
        const show = statusMatch && searchMatch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const isFiltering = query || activeFilter !== 'all';
    if (isFiltering) {
        countEl.style.display = 'block';
        countEl.textContent   = visible === 0
            ? 'No orders match your search.'
            : visible + ' order' + (visible !== 1 ? 's' : '') + ' found';
    } else {
        countEl.style.display = 'none';
    }
}

/* ── CLEAR SEARCH ── */
function clearSearch() {
    document.getElementById('orderSearch').value = '';
    applyOrderFilters();
    document.getElementById('orderSearch').focus();
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

/* ── CANCEL PANEL ── */

/**
 * Toggle the inline cancel panel for a given order.
 * Closes any other open panels first.
 */
function toggleCancelPanel(orderId, triggerBtn) {
    const panel   = document.getElementById('cancel-panel-' + orderId);
    const trigger = triggerBtn || document.getElementById('cancel-trigger-' + orderId);
    const isOpen  = panel.classList.contains('open');

    // Close all open panels first
    document.querySelectorAll('.cancel-panel.open').forEach(p => p.classList.remove('open'));

    if (!isOpen) {
        panel.classList.add('open');
        // Scroll panel into view smoothly
        setTimeout(() => panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' }), 50);
    }
}

/**
 * Enable/disable the submit button based on whether a reason is selected.
 */
function updateCancelBtn(orderId, value) {
    const btn = document.getElementById('cancel-submit-' + orderId);
    btn.disabled = !value;
}

/**
 * Final confirmation before submitting the cancel form.
 */
function confirmCancel(orderId) {
    const reason = document.getElementById('cancel-reason-' + orderId).value;
    if (!reason) {
        alert('Please select a cancellation reason.');
        return false;
    }
    return confirm('Are you sure you want to cancel this order? This cannot be undone.');
}
</script>
@endpush

@endsection