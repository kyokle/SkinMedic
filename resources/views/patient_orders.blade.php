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

                {{-- ── RECEIPT BUTTON — completed orders ── --}}
                @if($order->status === 'completed')
                    <button class="receipt-btn"
                            onclick="openReceipt({{ $order->id }})">
                        <svg viewBox="0 0 20 20" fill="none" width="13" height="13">
                            <path d="M5 2h10a1 1 0 011 1v15l-2-1.5L12 18l-2-1.5L8 18l-2-1.5L4 18V3a1 1 0 011-1z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                            <path d="M7 7h6M7 10h6M7 13h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        </svg>
                        Receipt
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

{{-- ── RECEIPT MODAL ── --}}
<div class="receipt-overlay" id="receiptOverlay" onclick="closeReceipt(event)">
    <div class="receipt-box" id="receiptBox">
        {{-- Header --}}
        <div class="receipt-header">
            <div class="receipt-brand">
                <span class="receipt-brand-name">SkinMedic</span>
                <span class="receipt-brand-tagline">Official Order Receipt</span>
            </div>
            <button class="receipt-close" onclick="closeReceipt()">✕</button>
        </div>

        {{-- Body --}}
        <div class="receipt-body" id="receiptPrintArea">
            <div class="receipt-print-brand">
                <strong>SkinMedic</strong>
                <span>Official Order Receipt</span>
            </div>
            <div class="receipt-divider-dashed"></div>

            <div class="receipt-meta">
                <div class="receipt-meta-row">
                    <span>Order #</span><strong id="r_id"></strong>
                </div>
                <div class="receipt-meta-row">
                    <span>Date</span><span id="r_date"></span>
                </div>
                <div class="receipt-meta-row">
                    <span>Status</span><span id="r_status"></span>
                </div>
            </div>

            <div class="receipt-divider-dashed"></div>

            <p class="receipt-section-label">Items Ordered</p>
            <div id="r_items" class="receipt-items"></div>

            <div class="receipt-divider-dashed"></div>

            <div class="receipt-totals">
                <div class="receipt-totals-row">
                    <span>Subtotal</span><span id="r_subtotal"></span>
                </div>
                <div class="receipt-totals-row receipt-total-final">
                    <span>Total</span><strong id="r_total"></strong>
                </div>
            </div>

            <div class="receipt-divider-dashed"></div>

            <div class="receipt-payment-info">
                <div class="receipt-meta-row">
                    <span>Payment Method</span><span id="r_method"></span>
                </div>
                <div class="receipt-meta-row" id="r_ref_row" style="display:none">
                    <span>GCash Ref #</span><span id="r_ref"></span>
                </div>
                <div class="receipt-meta-row">
                    <span>Payment Status</span><span id="r_pay_status"></span>
                </div>
            </div>

            <div class="receipt-note-row" id="r_note_row" style="display:none">
                <span>📝 Note:</span><span id="r_note"></span>
            </div>

            <div class="receipt-divider-dashed"></div>
            <p class="receipt-thankyou">Thank you for your purchase! 💚<br>
                <small>SkinMedic Clinic · For concerns, please contact us directly.</small>
            </p>
        </div>

        {{-- Footer actions --}}
        <div class="receipt-footer">
            <button class="receipt-print-btn" onclick="printReceipt()">
                <svg viewBox="0 0 20 20" fill="none" width="15" height="15">
                    <path d="M5 7V3h10v4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <rect x="2" y="7" width="16" height="8" rx="2" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M5 11h10M5 14h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    <circle cx="15" cy="10" r="1" fill="currentColor"/>
                </svg>
                Print Receipt
            </button>
        </div>
    </div>
</div>

{{-- Inline order data for receipt JS --}}
<script>
const PATIENT_ORDERS = @json($orders->keyBy('id'));
</script>

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

/* ── RECEIPT MODAL ── */
function openReceipt(orderId) {
    const order = PATIENT_ORDERS[orderId];
    if (!order) return;

    document.getElementById('r_id').textContent   = '#' + order.id;
    document.getElementById('r_date').textContent = new Date(order.created_at)
        .toLocaleString('en-PH', { dateStyle: 'long', timeStyle: 'short' });
    document.getElementById('r_status').textContent = '✔ Completed';

    // Payment
    document.getElementById('r_method').textContent =
        order.payment_method === 'gcash' ? 'GCash' : 'Cash on Pick-up';
    document.getElementById('r_pay_status').textContent =
        order.payment_status ? order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1) : 'Paid';

    const refRow = document.getElementById('r_ref_row');
    if (order.reference) {
        refRow.style.display = '';
        document.getElementById('r_ref').textContent = order.reference;
    } else {
        refRow.style.display = 'none';
    }

    // Note
    const noteRow = document.getElementById('r_note_row');
    if (order.note) {
        noteRow.style.display = '';
        document.getElementById('r_note').textContent = order.note;
    } else {
        noteRow.style.display = 'none';
    }

    // Items
    let subtotal = 0;
    document.getElementById('r_items').innerHTML = order.items.map(item => {
        subtotal += parseFloat(item.subtotal);
        return `
            <div class="receipt-item-row">
                <div class="ri-name">${item.product_name}</div>
                <div class="ri-qty">×${item.quantity} @ ₱${parseFloat(item.unit_price).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                <div class="ri-sub">₱${parseFloat(item.subtotal).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
            </div>
        `;
    }).join('');

    document.getElementById('r_subtotal').textContent =
        '₱' + subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 });
    document.getElementById('r_total').textContent =
        '₱' + parseFloat(order.total).toLocaleString('en-PH', { minimumFractionDigits: 2 });

    document.getElementById('receiptOverlay').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeReceipt(e) {
    if (!e || e.target === document.getElementById('receiptOverlay')) {
        document.getElementById('receiptOverlay').style.display = 'none';
        document.body.style.overflow = '';
    }
}

function printReceipt() {
    const area    = document.getElementById('receiptPrintArea');
    const clone   = area.cloneNode(true);
    const win     = window.open('', '_blank', 'width=420,height=680');
    win.document.write(`
        <!DOCTYPE html><html><head>
        <meta charset="UTF-8">
        <title>SkinMedic Receipt</title>
        <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; }
            body { font-family: 'DM Sans', sans-serif; background: #fff; color: #1e2318;
                   padding: 24px; font-size: 13px; max-width: 360px; margin: 0 auto; }
            .receipt-print-brand { text-align: center; margin-bottom: 12px; }
            .receipt-print-brand strong { display: block; font-size: 20px; color: #5a7a1f; letter-spacing: 1px; }
            .receipt-print-brand span   { font-size: 12px; color: #6b7260; }
            .receipt-divider-dashed { border: none; border-top: 1.5px dashed #ccc; margin: 10px 0; }
            .receipt-section-label { font-size: 10px; font-weight: 700; text-transform: uppercase;
                                     letter-spacing: .08em; color: #80a833; margin-bottom: 6px; }
            .receipt-meta-row { display: flex; justify-content: space-between; padding: 4px 0;
                                font-size: 12.5px; color: #444; }
            .receipt-meta-row span:first-child { color: #888; }
            .receipt-meta-row strong { color: #1e2318; }
            .receipt-item-row { display: flex; align-items: flex-start; justify-content: space-between;
                                gap: 8px; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
            .ri-name { flex: 1; font-weight: 600; font-size: 12.5px; }
            .ri-qty  { font-size: 11.5px; color: #6b7260; white-space: nowrap; }
            .ri-sub  { font-weight: 700; color: #5a7a1f; white-space: nowrap; }
            .receipt-totals-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 13px; }
            .receipt-total-final { font-size: 14px; font-weight: 700; color: #5a7a1f; }
            .receipt-note-row { background: #f9fbf4; border: 1px dashed #c5dba0; border-radius: 6px;
                                padding: 8px 10px; font-size: 12px; color: #4a5c2a; margin-top: 4px;
                                display: flex; gap: 6px; }
            .receipt-thankyou { text-align: center; font-size: 12.5px; color: #6b7260; line-height: 1.6; padding-top: 4px; }
            .receipt-thankyou small { font-size: 11px; }
        </style></head><body>${clone.outerHTML}</body></html>
    `);
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 400);
}
</script>
@endpush

@endsection