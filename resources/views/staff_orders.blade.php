<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Online Orders — SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/staff_orders.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body>

@if(session('role') === 'admin')
    @include('partials.sidebar_admin')
@else
    @include('partials.sidebar_staff')
@endif

<div class="main">

    {{-- ── TOP BAR ── --}}
    <div class="topbar">
        <h2>Online Orders</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>
            @include('partials.notif_bell_staff')
        </div>
    </div>

    {{-- ── FLASH MESSAGES ── --}}
    @if(session('success'))
        <div class="flash flash-success">✔ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="flash flash-error">✕ {{ session('error') }}</div>
    @endif

    {{-- ── STATUS TABS ── --}}
    <div class="filter-tabs">
        <button class="{{ $activeFilter === 'all'       ? 'active' : '' }}" onclick="setTab('all',       this)">All</button>
        <button class="{{ $activeFilter === 'pending'   ? 'active' : '' }}" onclick="setTab('pending',   this)">Pending</button>
        <button class="{{ $activeFilter === 'confirmed' ? 'active' : '' }}" onclick="setTab('confirmed', this)">Confirmed</button>
        <button class="{{ $activeFilter === 'packing'   ? 'active' : '' }}" onclick="setTab('processing',   this)">Packing</button>
        <button class="{{ $activeFilter === 'ready'     ? 'active' : '' }}" onclick="setTab('ready_for_pickup',     this)">Ready for Pick-up</button>
        <button class="{{ $activeFilter === 'completed' ? 'active' : '' }}" onclick="setTab('completed', this)">Completed</button>
        <button class="{{ $activeFilter === 'cancelled' ? 'active' : '' }}" onclick="setTab('cancelled', this)">Cancelled</button>
    </div>

    {{-- ── SEARCH BAR ── --}}
    <div class="search-bar">
        <svg viewBox="0 0 24 24" fill="none" width="15" height="15" stroke="#aaa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0">
            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
        <input type="text" id="q" placeholder="Search patient, product, or order #…" oninput="applyFilters()">

        <label for="payFilter">Payment</label>
        <select id="payFilter" onchange="applyFilters()">
            <option value="">All</option>
            <option value="cash">Cash</option>
            <option value="gcash">GCash</option>
        </select>

        <label for="dateFrom">From</label>
        <input type="date" id="dateFrom" style="width:132px" onchange="applyFilters()">
        <label for="dateTo">To</label>
        <input type="date" id="dateTo" style="width:132px" onchange="applyFilters()">

        <button class="reset-btn" onclick="resetFilters()">↺ Reset</button>
        <span class="result-count" id="resultCount"></span>
    </div>

    {{-- ── ORDERS TABLE ── --}}
    <table class="data-table" id="ordersTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Patient</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Pay Status</th>
                <th>Order Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr data-status="{{ $order->status }}"
                data-payment="{{ $order->payment_method }}"
                data-date="{{ \Carbon\Carbon::parse($order->created_at)->format('Y-m-d') }}"
                data-search="{{ strtolower('#'.$order->id.' '.$order->patient_name.' '.$order->items->pluck('product_name')->implode(' ')) }}"
                onclick="openModal({{ $order->id }})"
                style="cursor:pointer;">
                <td><strong>#{{ $order->id }}</strong></td>
                <td>{{ $order->patient_name }}</td>
                <td>
                    <span class="items-preview">
                        {{ $order->items->take(2)->pluck('product_name')->implode(', ') }}
                        @if($order->items->count() > 2)
                            <em>+{{ $order->items->count() - 2 }} more</em>
                        @endif
                    </span>
                </td>
                <td><strong>₱{{ number_format($order->total, 2) }}</strong></td>
                <td>
                    <span class="pay-method-chip pay-{{ $order->payment_method }}">
                        {{ $order->payment_method === 'gcash' ? 'GCash' : 'Cash' }}
                    </span>
                </td>
                <td>
                    <span class="badge pay-status-{{ $order->payment_status ?? 'unpaid' }}">
                        {{ ucfirst($order->payment_status ?? 'unpaid') }}
                    </span>
                </td>
                <td>
                    <span class="badge status-{{ $order->status }}">
                        {{ ['processing' => 'Packing', 'ready_for_pickup' => 'Ready for Pick-up'][$order->status] ?? ucfirst($order->status) }}
                    </span>
                </td>
                <td>{{ \Carbon\Carbon::parse($order->created_at)->format('M d, Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($orders->isEmpty())
        <div class="empty-state">
            <p>No online orders yet.</p>
        </div>
    @endif

</div>

{{-- ══════════════════════════════════════════
     ORDER DETAIL MODAL
═══════════════════════════════════════════ --}}
<div id="orderModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Order Details</h2>
            <span class="close-btn" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">

            {{-- Patient & order info --}}
            <div class="modal-section">
                <div class="modal-row"><span>Order #</span><strong id="m_id"></strong></div>
                <div class="modal-row"><span>Patient</span><span id="m_patient"></span></div>
                <div class="modal-row"><span>Date Placed</span><span id="m_date"></span></div>
                <div class="modal-row"><span>Note</span><span id="m_note">—</span></div>
            </div>

            {{-- Items --}}
            <div class="modal-section">
                <p class="section-label">Items Ordered</p>
                <div id="m_items" class="modal-items"></div>
            </div>

            {{-- Payment info --}}
            <div class="modal-section">
                <p class="section-label">Payment</p>
                <div class="modal-row"><span>Method</span><span id="m_payment"></span></div>
                <div class="modal-row"><span>Status</span><span id="m_pay_status"></span></div>
                <div class="modal-row" id="refRow" style="display:none">
                    <span>Reference #</span><span id="m_reference"></span>
                </div>
                <div class="modal-row"><span>Total</span><strong id="m_total"></strong></div>
            </div>

            {{-- GCash proof --}}
            <div id="proofSection" class="modal-section" style="display:none">
                <p class="section-label">Payment Proof</p>
                <div class="proof-img-wrap">
                    <img id="m_proof_img" src="" alt="Payment proof">
                </div>
            </div>

            {{-- GCash proof missing notice --}}
            <div id="proofMissingSection" class="modal-section" style="display:none">
                <p class="section-label">Payment Proof</p>
                <div class="proof-missing-notice">
                    ⚠ No proof uploaded yet. This order may have been placed before the GCash upload feature was added, or the patient did not upload a screenshot.
                </div>
            </div>

            {{-- Order status --}}
            <div class="modal-section">
                <p class="section-label">Order Status</p>
                <div class="status-timeline" id="modalTimeline"></div>
            </div>

            {{-- Action buttons --}}
            <form method="POST"
                  action="{{ session('role') === 'admin'
                      ? route('admin.orders.update-status')
                      : route('staff.orders.update-status') }}"
                  id="orderStatusForm">
                @csrf
                <input type="hidden" name="order_id"    id="form_order_id">
                <input type="hidden" name="status"      id="form_status">
                <input type="hidden" name="pay_status"  id="form_pay_status">

                <div id="actionArea" class="action-area"></div>
            </form>

        </div>
    </div>
</div>

{{-- Inline order data for JS --}}
<script>
const ORDERS = @json($orders->keyBy('id'));
</script>

<script>
let activeTab = '{{ $activeFilter }}';

/* ── TAB FILTER ── */
function setTab(tab, btn) {
    activeTab = tab;
    document.querySelectorAll('.filter-tabs button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    applyFilters();
}

/* ── SEARCH + FILTER ── */
function applyFilters() {
    const q      = document.getElementById('q').value.toLowerCase().trim();
    const pay    = document.getElementById('payFilter').value;
    const from   = document.getElementById('dateFrom').value;
    const to     = document.getElementById('dateTo').value;
    const rows   = document.querySelectorAll('#ordersTable tbody tr[data-status]');
    let visible  = 0;

    rows.forEach(tr => {
        const matchTab  = activeTab === 'all' || tr.dataset.status === activeTab;
        const matchQ    = !q   || tr.dataset.search.includes(q);
        const matchPay  = !pay || tr.dataset.payment === pay;
        const matchFrom = !from || tr.dataset.date >= from;
        const matchTo   = !to   || tr.dataset.date <= to;
        const show = matchTab && matchQ && matchPay && matchFrom && matchTo;
        tr.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const total = rows.length;
    document.getElementById('resultCount').textContent =
        visible === total ? `${total} orders` : `${visible} of ${total}`;
}

function resetFilters() {
    document.getElementById('q').value = '';
    document.getElementById('payFilter').value = '';
    document.getElementById('dateFrom').value = '';
    document.getElementById('dateTo').value = '';
    activeTab = 'all';
    document.querySelectorAll('.filter-tabs button').forEach((b,i) => b.classList.toggle('active', i===0));
    applyFilters();
}

/* ── MODAL ── */
function openModal(id) {
    const order = ORDERS[id];
    if (!order) return;

    document.getElementById('modalTitle').textContent  = 'Order #' + order.id;
    document.getElementById('m_id').textContent        = '#' + order.id;
    document.getElementById('m_patient').textContent   = order.patient_name;
    document.getElementById('m_date').textContent      = new Date(order.created_at).toLocaleString('en-PH', {dateStyle:'medium', timeStyle:'short'});
    document.getElementById('m_note').textContent      = order.note || '—';
    document.getElementById('m_total').textContent     = '₱' + parseFloat(order.total).toLocaleString('en-PH', {minimumFractionDigits:2});
    document.getElementById('m_payment').textContent   = order.payment_method === 'gcash' ? 'GCash' : 'Cash on Pick-up';
    document.getElementById('m_pay_status').textContent = ucFirst(order.payment_status || 'unpaid');
    document.getElementById('form_order_id').value     = order.id;

    // Reference #
    if (order.reference) {
        document.getElementById('refRow').style.display    = '';
        document.getElementById('m_reference').textContent = order.reference;
    } else {
        document.getElementById('refRow').style.display = 'none';
    }

    // GCash proof image
    const proofSection        = document.getElementById('proofSection');
    const proofMissingSection = document.getElementById('proofMissingSection');

    if (order.payment_method === 'gcash') {
        if (order.payment_proof) {
            proofSection.style.display        = '';
            proofMissingSection.style.display = 'none';
            document.getElementById('m_proof_img').src = order.payment_proof;
        } else {
            proofSection.style.display        = 'none';
            proofMissingSection.style.display = '';
        }
    } else {
        proofSection.style.display        = 'none';
        proofMissingSection.style.display = 'none';
    }

    // Items
    document.getElementById('m_items').innerHTML = order.items.map(item => `
        <div class="modal-item-row">
            <span class="mi-name">${item.product_name} <em>×${item.quantity}</em></span>
            <span class="mi-price">₱${parseFloat(item.subtotal).toLocaleString('en-PH', {minimumFractionDigits:2})}</span>
        </div>
    `).join('');

    // Timeline
    renderTimeline(order.status);

    // Action buttons
    renderActions(order.status, order.payment_method, order.payment_status);

    document.getElementById('orderModal').style.display = 'flex';
}

function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
}

/* ── TIMELINE ── */
function renderTimeline(currentStatus) {
    const steps = [
        { key: 'pending',   icon: '🕐', label: 'Order Placed' },
        { key: 'confirmed', icon: '✅', label: 'Confirmed' },
        { key: 'processing',   icon: '📦', label: 'Packing' },
        { key: 'ready_for_pickup',     icon: '🏪', label: 'Ready for Pick-up' },
        { key: 'completed', icon: '✔',  label: 'Completed' },
    ];

    if (currentStatus === 'cancelled') {
        document.getElementById('modalTimeline').innerHTML =
            '<span class="cancelled-label">✕ This order was cancelled</span>';
        return;
    }

    const keys   = steps.map(s => s.key);
    const curIdx = keys.indexOf(currentStatus);

    document.getElementById('modalTimeline').innerHTML = steps.map((step, idx) => {
        const done   = idx < curIdx;
        const active = idx === curIdx;
        const cls    = done ? 'done' : active ? 'active' : '';
        return `
            <div class="tl-step ${cls}">
                <div class="tl-dot">${done ? '✓' : step.icon}</div>
                <span class="tl-label">${step.label}</span>
            </div>
            ${idx < steps.length - 1 ? `<div class="tl-line ${done ? 'done' : ''}"></div>` : ''}
        `;
    }).join('');
}

/* ── ACTION BUTTONS ── */
function renderActions(status, paymentMethod, paymentStatus) {
    const area = document.getElementById('actionArea');

    // Workflow:
    // pending   → [Verify & Confirm] (gcash: check proof first) + [Cancel]
    // confirmed → [Start Packing] + [Cancel]
    // packing   → [Ready for Pick-up] + [Cancel]
    // ready     → [Mark Completed]
    // completed / cancelled → no actions

    let html = '';

    if (status === 'pending') {
        // For GCash: show "Verify Payment & Confirm" — staff must check proof
        const confirmLabel = paymentMethod === 'gcash'
            ? '✅ Verify Payment & Confirm Order'
            : '✅ Confirm Order';
        html = `
            <p class="action-hint">
                ${paymentMethod === 'gcash'
                    ? '⚠ Please check the GCash proof and reference number above before confirming.'
                    : 'Confirm this order to start processing.'}
            </p>
            <div class="action-buttons">
                <button type="button" class="btn-confirm" onclick="submitAction('confirmed', '${paymentMethod === 'gcash' ? 'paid' : ''}')">
                    ${confirmLabel}
                </button>
                <button type="button" class="btn-cancel" onclick="submitAction('cancelled', '')">
                    ✕ Cancel Order
                </button>
            </div>`;
    } else if (status === 'confirmed') {
        html = `
            <p class="action-hint">Order confirmed. Start packing the items for this order.</p>
            <div class="action-buttons">
                <button type="button" class="btn-pack" onclick="submitAction('processing', '')">
                    📦 Start Packing
                </button>
                <button type="button" class="btn-cancel" onclick="submitAction('cancelled', '')">
                    ✕ Cancel Order
                </button>
            </div>`;
    } else if (status === 'processing') {
        html = `
            <p class="action-hint">Items are being packed. Mark as ready when done.</p>
            <div class="action-buttons">
                <button type="button" class="btn-ready" onclick="submitAction('ready_for_pickup', '')">
                    🏪 Mark as Ready for Pick-up
                </button>
                <button type="button" class="btn-cancel" onclick="submitAction('cancelled', '')">
                    ✕ Cancel Order
                </button>
            </div>`;
    } else if (status === 'ready_for_pickup') {
        html = `
            <p class="action-hint">Patient has been notified. Mark as completed once picked up.</p>
            <div class="action-buttons">
                <button type="button" class="btn-complete" onclick="submitAction('completed', 'paid')">
                    ✔ Mark as Completed
                </button>
            </div>`;
    } else {
        html = `<p class="action-hint no-action">No further actions available for this order.</p>`;
    }

    area.innerHTML = html;
}

function submitAction(status, payStatus) {
    document.getElementById('form_status').value     = status;
    document.getElementById('form_pay_status').value = payStatus;
    document.getElementById('orderStatusForm').submit();
}

function ucFirst(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

window.onclick = e => {
    if (e.target === document.getElementById('orderModal')) closeModal();
};

window.addEventListener('DOMContentLoaded', () => applyFilters());
</script>

</body>
</html>