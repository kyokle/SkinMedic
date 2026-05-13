{{-- resources/views/staff_inventory.blade.php --}}

@extends('layouts.app')

@section('title', 'Inventory — SkinMedic')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/staff_inventory.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>Inventory System</h2>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->format('Y-m-d') }}</strong>
            </div>

            {{-- Inventory-only notification bell --}}
            <div style="position:relative;">
                <button id="invNotifBtn" onclick="toggleInvNotif()"
                    style="background:#f9fff2;border:1px solid #d4edb3;border-radius:10px;
                           padding:8px 14px;cursor:pointer;display:flex;align-items:center;
                           gap:8px;font-family:inherit;font-size:14px;">
                    📦
                    <span id="invNotifBadge"
                          style="display:none;background:red;color:white;border-radius:50%;
                                 font-size:10px;padding:2px 6px;">0</span>
                </button>

                <div id="invNotifDropdown"
                     style="display:none;position:absolute;top:44px;right:0;width:320px;
                            background:white;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.15);
                            z-index:9999;max-height:350px;overflow-y:auto;">
                    <div style="padding:10px 14px;font-weight:600;border-bottom:1px solid #eee;
                                display:flex;justify-content:space-between;align-items:center;">
                        <span>📦 Inventory Alerts</span>
                        <button onclick="markAllInvRead()"
                                style="font-size:11px;color:#80a833;background:none;border:none;cursor:pointer;">
                            Mark all read
                        </button>
                    </div>
                    <div id="invNotifList"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Success / Error flash messages --}}
    @if(session('success'))
        <div class="alert-block success" id="flashSuccess">
            ✅ {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="alert-block danger" id="flashError">
            ❌ {{ session('error') }}
        </div>
    @endif

    {{-- Near Expiry Alert --}}
    @if($nearExpiry->isNotEmpty())
    <div class="alert-block warning">
        <b>⚠ Near Expiry (within 7 days)</b><br>
        @foreach($nearExpiry as $n)
            • {{ $n->product_name }} → Exp: <b>{{ $n->expiry_date }}</b> (Qty: {{ $n->total_qty }})<br>
        @endforeach
    </div>
    @endif

    {{-- Low / Out-of-Stock Alert --}}
    @if($lowStock->isNotEmpty() || $outOfStock->isNotEmpty())
    <div class="alert-block danger">
        <b>⚠ Low / Out-of-Stock Alert</b><br>
        @foreach($lowStock as $l)
            • {{ $l->product_name }} — only <b>{{ $l->quantity }}</b> left<br>
        @endforeach
        @foreach($outOfStock as $z)
            • {{ $z->product_name }} — <b>OUT OF STOCK</b><br>
        @endforeach
    </div>
    @endif

    {{-- Search & Filter Bar --}}
    <div class="search-bar-wrapper">
        <input
            type="text"
            id="inventorySearch"
            class="inventory-search"
            placeholder="Search product name…"
            oninput="filterInventory()"
            autocomplete="off"
        >
        <select id="statusFilter" class="status-filter-select" onchange="filterInventory()">
            <option value="">All Statuses</option>
            <option value="in">✓ In Stock</option>
            <option value="low">⚠ Low Stock</option>
            <option value="out">❌ Out of Stock</option>
        </select>
        <span class="search-count" id="inventoryCount"></span>
    </div>

    {{-- Inventory Table --}}
    <div class="table-wrapper">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Total Stock</th>
                    <th>Reorder At</th>
                    <th>Status</th>
                    <th>Last Added</th>
                    <th>Current Batch Expiry</th>
                    <th>Current Batch Qty</th>
                    <th>Next Batch Expiry</th>
                    <th>Add Stock (Qty + Expiry)</th>
                    <th>Edit Stock</th>
                </tr>
            </thead>
            <tbody id="inventoryTableBody">
                @foreach($products as $row)
                    @php
                        $expClass = 'exp-ok';
                        $daysLeft = null;
                        if (!empty($row->current_expiry)) {
                            $daysLeft = (strtotime($row->current_expiry) - strtotime(now()->toDateString())) / 86400;
                            if ($daysLeft <= 3)     $expClass = 'exp-danger';
                            elseif ($daysLeft <= 7) $expClass = 'exp-warning';
                        }

                        if ($row->quantity == 0)                          $statusKey = 'out';
                        elseif ($row->quantity <= $row->reorder_level)    $statusKey = 'low';
                        else                                               $statusKey = 'in';
                    @endphp
                    <tr data-product-name="{{ strtolower($row->product_name) }}"
                        data-status="{{ $statusKey }}">
                        <td>{{ $row->product_id }}</td>
                        <td class="product-name">{{ $row->product_name }}</td>
                        <td>{{ $row->quantity }}</td>
                        <td>{{ $row->reorder_level }}</td>
                        <td>
                            @if($statusKey === 'out')
                                <span class="status-badge out">❌ Out of Stock</span>
                            @elseif($statusKey === 'low')
                                <span class="status-badge low">⚠ Reorder Needed</span>
                            @else
                                <span class="status-badge in">✓ In Stock</span>
                            @endif
                        </td>
                        <td>{{ $row->last_added ? \Carbon\Carbon::parse($row->last_added)->format('Y-m-d H:i') : '—' }}</td>
                        <td class="{{ $expClass }}">
                            @if($row->current_expiry)
                                {{ $row->current_expiry }}
                                @if($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7)
                                    <br><small>({{ (int) $daysLeft }} days left)</small>
                                @elseif($daysLeft !== null && $daysLeft < 0)
                                    <br><small class="expired-label">(Expired)</small>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $row->current_batch_qty ?? '—' }}</td>
                        <td class="exp-next">{{ $row->next_expiry ?: '—' }}</td>
                        <td>
                            {{-- Add Stock inline form with per-row validation errors --}}
                            <form method="POST"
                                  action="{{ route('staff.inventory.add-stock') }}"
                                  class="add-stock-form"
                                  id="addForm{{ $row->product_id }}"
                                  onsubmit="return validateAddStock({{ $row->product_id }})">
                                @csrf
                                <input type="hidden" name="product_id" value="{{ $row->product_id }}">
                                <input type="number" name="quantity"
                                       id="addQty{{ $row->product_id }}"
                                       required min="1" max="99999"
                                       placeholder="Qty"
                                       class="input-qty">
                                <input type="date" name="expiry_date"
                                       id="addExpiry{{ $row->product_id }}"
                                       required
                                       min="{{ now()->toDateString() }}"
                                       class="input-date">
                                <button type="submit" class="add-btn">Add</button>
                            </form>
                            {{-- Inline validation error for this row --}}
                            @if($errors->has('quantity') || $errors->has('expiry_date'))
                                @php $errProductId = old('product_id'); @endphp
                                @if($errProductId == $row->product_id)
                                    <div class="inline-error">
                                        @foreach($errors->get('quantity') as $e)
                                            <span>{{ $e }}</span>
                                        @endforeach
                                        @foreach($errors->get('expiry_date') as $e)
                                            <span>{{ $e }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </td>
                        <td>
                            <button class="deduct-btn"
                                    onclick="openDeductModal(
                                        {{ $row->product_id }},
                                        '{{ addslashes($row->product_name) }}',
                                        {{ $row->quantity }}
                                    )">
                                ✏ Edit Stock
                            </button>
                        </td>
                    </tr>
                @endforeach
                <tr id="noResultsRow" class="no-results-row" style="display:none;">
                    <td colspan="11">No products match your search.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- Deduct / Edit Stock Modal --}}
<div id="deductModal" class="modal-overlay" onclick="closeDeductModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeDeductModal()">×</button>
        <h3 id="deductTitle">Edit Stock</h3>
        <p id="deductCurrent" class="deduct-current"></p>

        {{-- Mode toggle --}}
        <div class="modal-toggle-row">
            <button type="button" id="modeDeductBtn"
                    class="modal-toggle-btn active"
                    onclick="setDeductMode('deduct')">Deduct Units</button>
            <button type="button" id="modeSetBtn"
                    class="modal-toggle-btn"
                    onclick="setDeductMode('set')">Set Exact Qty</button>
        </div>
        <p id="modeHint" class="mode-hint">Enter how many units to remove from stock.</p>

        <form method="POST" action="{{ route('staff.inventory.deduct-stock') }}" id="deductForm">
            @csrf
            <input type="hidden" name="product_id" id="deductProductId">
            <input type="hidden" name="action"     id="deductAction"    value="deduct">
            <div class="deduct-row">
                <label id="deductQtyLabel">Quantity to Deduct</label>
                <input type="number" name="quantity" id="deductQty"
                       min="1" required placeholder="Enter quantity">
                <span id="deductQtyHint" class="qty-hint"></span>
            </div>
            <button type="submit" class="save-deduct-btn" id="deductSubmitBtn">Save Changes</button>
        </form>
    </div>
</div>

@push('scripts')
<script>
/* ── Search + Status filter ── */
function filterInventory() {
    const query  = document.getElementById('inventorySearch').value.toLowerCase().trim();
    const status = document.getElementById('statusFilter').value;
    const rows   = document.querySelectorAll('#inventoryTableBody tr[data-product-name]');
    let visible  = 0;

    rows.forEach(row => {
        const nameMatch   = !query  || row.getAttribute('data-product-name').includes(query);
        const statusMatch = !status || row.getAttribute('data-status') === status;
        const show = nameMatch && statusMatch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const noResults = document.getElementById('noResultsRow');
    noResults.style.display = visible === 0 ? '' : 'none';

    const countEl = document.getElementById('inventoryCount');
    const total   = rows.length;
    countEl.textContent = (query || status) ? `${visible} of ${total} product(s)` : '';
}

/* ── Add-stock client-side validation ── */
function validateAddStock(productId) {
    const qty    = document.getElementById('addQty' + productId);
    const expiry = document.getElementById('addExpiry' + productId);
    const today  = new Date().toISOString().split('T')[0];

    if (!qty.value || parseInt(qty.value) < 1) {
        alert('Quantity must be at least 1.');
        qty.focus();
        return false;
    }
    if (parseInt(qty.value) > 99999) {
        alert('Quantity cannot exceed 99,999 per batch.');
        qty.focus();
        return false;
    }
    if (!expiry.value) {
        alert('Please select an expiry date.');
        expiry.focus();
        return false;
    }
    if (expiry.value < today) {
        alert('Expiry date must be today or a future date.');
        expiry.focus();
        return false;
    }
    return true;
}

/* ── Edit Stock modal ── */
let _currentStock = 0;

function openDeductModal(id, name, qty) {
    _currentStock = qty;
    document.getElementById('deductModal').style.display = 'flex';
    document.getElementById('deductProductId').value     = id;
    document.getElementById('deductTitle').textContent   = 'Edit Stock — ' + name;
    document.getElementById('deductCurrent').textContent = 'Current quantity: ' + qty + ' units';
    document.getElementById('deductQty').value           = '';
    setDeductMode('deduct'); // always open in deduct mode
}

function setDeductMode(mode) {
    const action    = document.getElementById('deductAction');
    const label     = document.getElementById('deductQtyLabel');
    const hint      = document.getElementById('modeHint');
    const qtyInput  = document.getElementById('deductQty');
    const submitBtn = document.getElementById('deductSubmitBtn');
    const qtyHint   = document.getElementById('deductQtyHint');
    const btnDeduct = document.getElementById('modeDeductBtn');
    const btnSet    = document.getElementById('modeSetBtn');

    if (mode === 'deduct') {
        action.value      = 'deduct';
        label.textContent = 'Quantity to Deduct';
        hint.textContent  = 'Enter how many units to remove from stock.';
        qtyInput.max      = _currentStock;
        qtyInput.placeholder = 'e.g. 5';
        qtyHint.textContent  = 'Max: ' + _currentStock;
        submitBtn.textContent = 'Deduct Stock';
        btnDeduct.classList.add('active');
        btnSet.classList.remove('active');
    } else {
        action.value      = 'set';
        label.textContent = 'Set Stock To';
        hint.textContent  = 'Enter the new total quantity. Must be less than current stock.';
        qtyInput.max      = _currentStock - 1;
        qtyInput.placeholder = 'e.g. ' + Math.max(0, _currentStock - 1);
        qtyHint.textContent  = 'Current: ' + _currentStock + ' — enter new total (must be lower)';
        submitBtn.textContent = 'Set Exact Qty';
        btnDeduct.classList.remove('active');
        btnSet.classList.add('active');
    }

    qtyInput.value = '';
}

function closeDeductModal(event) {
    if (!event || event.target === document.getElementById('deductModal')) {
        document.getElementById('deductModal').style.display = 'none';
    }
}

/* ── Notification helpers ── */
function loadInvUnreadCount() {
    fetch('/notifications/unread-inventory')
        .then(r => r.json())
        .then(data => {
            const badge = document.getElementById('invNotifBadge');
            badge.textContent = data.count;
            badge.style.display = data.count > 0 ? 'inline-block' : 'none';
        });
}

function toggleInvNotif() {
    const dropdown = document.getElementById('invNotifDropdown');
    const isOpen   = dropdown.style.display === 'block';
    dropdown.style.display = isOpen ? 'none' : 'block';
    if (!isOpen) loadInvNotifications();
}

function loadInvNotifications() {
    fetch('/notifications/inventory')
        .then(r => r.json())
        .then(items => {
            const list = document.getElementById('invNotifList');
            if (!items.length) {
                list.innerHTML = '<p style="padding:12px;color:#999;text-align:center;">No inventory alerts</p>';
                return;
            }
            list.innerHTML = items.map(n => `
                <div onclick="markInvRead(${n.id}, this)"
                     style="padding:12px 14px;border-bottom:1px solid #f0f0f0;cursor:pointer;
                            background:${n.is_read ? '#fff' : '#f9fff2'}">
                    <div style="font-weight:${n.is_read ? '400' : '600'};font-size:13px;">${n.title}</div>
                    <div style="font-size:12px;color:#666;margin-top:2px;">${n.message}</div>
                    <div style="font-size:11px;color:#aaa;margin-top:4px;">${n.created_at}</div>
                </div>`).join('');
        });
}

function markInvRead(id, el) {
    fetch('/notifications/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ id })
    }).then(() => {
        el.style.background = '#fff';
        el.querySelector('div').style.fontWeight = '400';
        loadInvUnreadCount();
    });
}

function markAllInvRead() {
    fetch('/notifications/read-all-inventory', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        loadInvUnreadCount();
        document.querySelectorAll('#invNotifList > div').forEach(el => {
            el.style.background = '#fff';
        });
    });
}

document.addEventListener('click', function(e) {
    const btn      = document.getElementById('invNotifBtn');
    const dropdown = document.getElementById('invNotifDropdown');
    if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

/* ── Auto-dismiss flash messages after 4s ── */
['flashSuccess', 'flashError'].forEach(id => {
    const el = document.getElementById(id);
    if (el) setTimeout(() => el.style.display = 'none', 4000);
});

loadInvUnreadCount();
setInterval(loadInvUnreadCount, 30000);
</script>
@endpush

@endsection