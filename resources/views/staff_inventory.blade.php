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
                    <th>Actions</th>
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
                            <button class="manage-btn"
                                    onclick="openManageModal(
                                        {{ $row->product_id }},
                                        '{{ addslashes($row->product_name) }}',
                                        {{ $row->quantity }},
                                        {{ $row->reorder_level }}
                                    )">
                                ⚙ Manage Stock
                            </button>
                        </td>
                    </tr>
                @endforeach
                <tr id="noResultsRow" class="no-results-row" style="display:none;">
                    <td colspan="10">No products match your search.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{{-- ══════════════════════════════════════
     MANAGE STOCK MODAL
══════════════════════════════════════ --}}
<div id="manageModal" class="modal-overlay" onclick="closeManageModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeManageModal()">×</button>

        <div class="modal-product-header">
            <span class="modal-product-icon">📦</span>
            <div>
                <h3 id="manageTitle">Manage Stock</h3>
                <p id="manageCurrent" class="deduct-current"></p>
            </div>
        </div>

        {{-- Tab pills --}}
        <div class="manage-tabs">
            <button class="manage-tab active" id="tabAdd"    onclick="switchManageTab('add')">
                ➕ Add Stock
            </button>
            <button class="manage-tab" id="tabDeduct" onclick="switchManageTab('deduct')">
                ➖ Deduct
            </button>
            <button class="manage-tab" id="tabSet"    onclick="switchManageTab('set')">
                ✏ Set Exact
            </button>
            <button class="manage-tab" id="tabReorder" onclick="switchManageTab('reorder')">
                🔔 Reorder Level
            </button>
            <button class="manage-tab tab-danger" id="tabRemove" onclick="switchManageTab('remove')">
                🗑 Remove
            </button>
        </div>

        {{-- ── ADD STOCK panel ── --}}
        <div id="panelAdd" class="manage-panel">
            <div class="panel-hint panel-hint-add">
                <strong>Adding new stock?</strong> Enter the quantity you received and its expiry date. This will be saved as a new batch and added to the total.
            </div>
            <form method="POST" action="{{ route('staff.inventory.add-stock') }}" id="addStockForm">
                @csrf
                <input type="hidden" name="product_id" id="addProductId">
                <div class="modal-field-row">
                    <div class="modal-field">
                        <label>Quantity Received</label>
                        <input type="number" name="quantity" id="addQtyModal"
                               min="1" max="99999" placeholder="e.g. 50" required>
                        <span class="field-hint">How many units are you adding?</span>
                    </div>
                    <div class="modal-field">
                        <label>Expiry Date</label>
                        <input type="date" name="expiry_date" id="addExpiryModal"
                               min="{{ now()->toDateString() }}" required>
                        <span class="field-hint">The expiry date printed on the batch.</span>
                    </div>
                </div>
                <button type="submit" class="modal-action-btn btn-add"
                        onclick="return validateModalAdd()">
                    ➕ Add to Inventory
                </button>
            </form>
        </div>

        {{-- ── DEDUCT panel ── --}}
        <div id="panelDeduct" class="manage-panel" style="display:none;">
            <div class="panel-hint panel-hint-deduct">
                <strong>Deducting stock?</strong> Use this when items were used, damaged, or sold without going through the system. Enter the number of units to remove.
            </div>
            <form method="POST" action="{{ route('staff.inventory.deduct-stock') }}" id="deductStockForm">
                @csrf
                <input type="hidden" name="product_id" id="deductProductId">
                <input type="hidden" name="action" value="deduct">
                <div class="modal-field">
                    <label>Units to Remove</label>
                    <input type="number" name="quantity" id="deductQtyModal"
                           min="1" placeholder="e.g. 3" required>
                    <span id="deductQtyHint" class="field-hint"></span>
                </div>
                <button type="submit" class="modal-action-btn btn-deduct">
                    ➖ Deduct from Inventory
                </button>
            </form>
        </div>

        {{-- ── SET EXACT panel ── --}}
        <div id="panelSet" class="manage-panel" style="display:none;">
            <div class="panel-hint panel-hint-set">
                <strong>Correcting the count?</strong> Use this after a physical stock count. Enter the exact number of units you physically counted — this will replace the current quantity.
            </div>
            <form method="POST" action="{{ route('staff.inventory.deduct-stock') }}" id="setStockForm">
                @csrf
                <input type="hidden" name="product_id" id="setProductId">
                <input type="hidden" name="action" value="set">
                <div class="modal-field">
                    <label>Correct Quantity</label>
                    <input type="number" name="quantity" id="setQtyModal"
                           min="0" placeholder="e.g. 42" required>
                    <span id="setQtyHint" class="field-hint"></span>
                </div>
                <button type="submit" class="modal-action-btn btn-set">
                    ✏ Update to This Quantity
                </button>
            </form>
        </div>

        {{-- ── REORDER LEVEL panel ── --}}
        <div id="panelReorder" class="manage-panel" style="display:none;">
            <div class="panel-hint panel-hint-reorder">
                <strong>Updating the reorder level?</strong> This is the minimum stock count that triggers a low-stock alert. When the total quantity drops to or below this number, the product will be flagged as <em>Reorder Needed</em>.
            </div>
            <form method="POST" action="{{ route('staff.inventory.update-reorder') }}" id="reorderForm">
                @csrf
                <input type="hidden" name="product_id" id="reorderProductId">
                <div class="modal-field">
                    <label>New Reorder Level</label>
                    <input type="number" name="reorder_level" id="reorderLevelInput"
                           min="0" max="99999" placeholder="e.g. 10" required>
                    <span id="reorderHint" class="field-hint"></span>
                </div>
                <button type="submit" class="modal-action-btn btn-reorder">
                    🔔 Update Reorder Level
                </button>
            </form>
        </div>

        {{-- ── REMOVE panel ── --}}
        <div id="panelRemove" class="manage-panel" style="display:none;">
            <div class="panel-hint panel-hint-remove">
                <strong>⚠ Remove all stock?</strong> This will set the quantity to zero and clear all batch data for this product. Only use this if the product is being discontinued or fully written off.
            </div>
            <form method="POST" action="{{ route('staff.inventory.deduct-stock') }}" id="removeStockForm">
                @csrf
                <input type="hidden" name="product_id" id="removeProductId">
                <input type="hidden" name="action" value="set">
                <input type="hidden" name="quantity" value="0">
                <div class="remove-confirm-block">
                    <label class="remove-confirm-label">
                        <input type="checkbox" id="removeConfirmCheck" onchange="toggleRemoveBtn()">
                        I understand this will zero out all stock for <strong id="removeProductName"></strong>.
                    </label>
                </div>
                <button type="submit" class="modal-action-btn btn-remove" id="removeSubmitBtn" disabled>
                    🗑 Remove All Stock
                </button>
            </form>
        </div>

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

/* ══════════════════════════════════════
   MANAGE STOCK MODAL
══════════════════════════════════════ */
let _currentStock    = 0;
let _currentProduct  = '';

function openManageModal(id, name, qty, reorder) {
    _currentStock   = qty;
    _currentProduct = name;

    document.getElementById('manageTitle').textContent   = name;
    document.getElementById('manageCurrent').textContent = 'Current stock: ' + qty + ' units · Reorder at: ' + reorder;

    // Populate hidden product_id fields in all forms
    document.getElementById('addProductId').value     = id;
    document.getElementById('deductProductId').value  = id;
    document.getElementById('setProductId').value     = id;
    document.getElementById('reorderProductId').value = id;
    document.getElementById('removeProductId').value  = id;
    document.getElementById('removeProductName').textContent = name;

    // Reset hints
    document.getElementById('deductQtyHint').textContent  = 'Max you can deduct: ' + qty + ' units';
    document.getElementById('setQtyHint').textContent     = 'Current count is ' + qty + '. Enter the correct number.';
    document.getElementById('reorderHint').textContent    = 'Current reorder level: ' + reorder + '. Enter the new threshold.';
    document.getElementById('reorderLevelInput').value    = reorder;
    document.getElementById('deductQtyModal').max = qty;

    // Reset remove checkbox
    document.getElementById('removeConfirmCheck').checked = false;
    document.getElementById('removeSubmitBtn').disabled = true;

    // Always open on Add tab
    switchManageTab('add');

    document.getElementById('manageModal').style.display = 'flex';
}

function closeManageModal(event) {
    if (!event || event.target === document.getElementById('manageModal')) {
        document.getElementById('manageModal').style.display = 'none';
    }
}

function switchManageTab(tab) {
    const tabs = ['add', 'deduct', 'set', 'reorder', 'remove'];
    tabs.forEach(t => {
        document.getElementById('tab' + t.charAt(0).toUpperCase() + t.slice(1)).classList.toggle('active', t === tab);
        document.getElementById('panel' + t.charAt(0).toUpperCase() + t.slice(1)).style.display = t === tab ? 'block' : 'none';
    });
}

function validateModalAdd() {
    const qty    = document.getElementById('addQtyModal');
    const expiry = document.getElementById('addExpiryModal');
    const today  = new Date().toISOString().split('T')[0];

    // Clear previous inline errors
    ['addQtyError', 'addExpiryError'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.remove();
    });

    let valid = true;

    if (!qty.value || parseInt(qty.value) < 1) {
        showInlineError(qty, 'addQtyError', 'Quantity must be at least 1.');
        valid = false;
    }
    if (!expiry.value || expiry.value < today) {
        showInlineError(expiry, 'addExpiryError', 'Expiry date must be today or a future date. Expired stock cannot be added.');
        valid = false;
    }
    return valid;
}

function showInlineError(inputEl, errorId, message) {
    inputEl.style.borderColor = '#dc2626';
    const err = document.createElement('span');
    err.id        = errorId;
    err.className = 'inline-error-msg';
    err.textContent = '⚠ ' + message;
    inputEl.parentNode.insertBefore(err, inputEl.nextSibling);
    inputEl.addEventListener('input', function cleanup() {
        const existing = document.getElementById(errorId);
        if (existing) existing.remove();
        inputEl.style.borderColor = '';
        inputEl.removeEventListener('input', cleanup);
    }, { once: true });
}

function toggleRemoveBtn() {
    document.getElementById('removeSubmitBtn').disabled =
        !document.getElementById('removeConfirmCheck').checked;
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