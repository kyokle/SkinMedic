{{-- resources/views/staff_walkin.blade.php --}}

@extends('layouts.app')

@section('title', 'Walk-in Sale — SkinMedic')

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/css/staff_walkin.css') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@if(session('role') === 'admin')
    @include('partials.sidebar_admin')
@else
    @include('partials.sidebar_staff')
@endif

<div class="walkin-wrap">

    {{-- ── Topbar ── --}}
    <div class="topbar">
        <div>
            <h2>Walk-in Sale</h2>
            <p class="walkin-sub">{{ now()->format('l, F j Y') }}</p>
        </div>
        <div style="display:flex;align-items:center;gap:14px;">
            <div class="toggle-tabs">
                <button class="tab-btn active" id="tabSale" onclick="switchTab('sale')">
                    🛒 Walk-in Sale
                </button>
                <button class="tab-btn" id="tabHistory" onclick="switchTab('history')">
                    🕒 Recent Sales
                    @if($recentSales->isNotEmpty())
                        <span class="tab-badge">{{ $recentSales->count() }}</span>
                    @endif
                </button>
            </div>
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
            @include('partials.notif_bell_staff')
        </div>
    </div>

    {{-- ── Flash messages ── --}}
    @if(session('error'))
        <div class="flash error">⚠ {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="flash success">✓ {{ session('success') }}</div>
    @endif
    @if(session('from_booking'))
        <div class="flash from-booking">
            ✅ {{ session('from_booking') }}
            @if(request('from_appointment'))
                &nbsp;·&nbsp; Appointment #{{ request('from_appointment') }}
            @endif
        </div>
    @endif

    <form method="POST" action="{{ session('role') === 'admin' ? route('admin.walkin.store') : route('staff.walkin.store') }}" id="walkinForm">
        @csrf

        <div class="walkin-grid">

            {{-- ══════════════════════════════════════
                 LEFT COLUMN — Patient + Items
            ══════════════════════════════════════ --}}
            <div class="walkin-left">

                {{-- Patient Select --}}
                <div class="card">
                    <div class="card-title">👤 Patient</div>
                    <select name="user_id" id="patientSelect" required class="full-select">
                        <option value="">— Select registered patient —</option>
                        @foreach($patients as $p)
                            <option value="{{ $p->user_id }}" {{ old('user_id') == $p->user_id ? 'selected' : '' }}>
                                {{ $p->firstName }} {{ $p->lastName }} ({{ $p->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('user_id')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Product Items --}}
                <div class="card">
                    <div class="card-title">🛍 Products</div>
                    <div id="productLines">
                        <div class="product-line" data-index="0">
                            <select name="items[0][product_id]" class="prod-select" onchange="updateLinePrice(this)">
                                <option value="">— Select product —</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->product_id }}"
                                            data-price="{{ $prod->selling_price }}"
                                            data-stock="{{ $prod->quantity }}">
                                        {{ $prod->product_name }}
                                        (₱{{ number_format($prod->selling_price, 2) }})
                                        — {{ $prod->quantity }} in stock
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="items[0][quantity]" class="qty-input"
                                   min="1" value="1" placeholder="Qty" onchange="recalcTotal()">
                            <span class="line-price">₱0.00</span>
                            <button type="button" class="remove-line" onclick="removeLine(this)" title="Remove">✕</button>
                        </div>
                    </div>
                    <button type="button" class="add-line-btn" onclick="addProductLine()">+ Add Product</button>
                </div>

                {{-- Service Add-ons --}}
                <div class="card">
                    <div class="card-title">💆 Service Add-ons <span class="optional-tag">optional</span></div>
                    <div id="serviceLines"></div>
                    <button type="button" class="add-line-btn secondary" onclick="addServiceLine()">+ Add Service</button>
                </div>

            </div>

            {{-- ══════════════════════════════════════
                 RIGHT COLUMN — Summary + Payment
            ══════════════════════════════════════ --}}
            <div class="walkin-right">

                {{-- Order Summary --}}
                <div class="card summary-card">
                    <div class="card-title">🧾 Order Summary</div>
                    <div id="summaryLines" class="summary-lines">
                        <p class="empty-summary">No items added yet.</p>
                    </div>
                    <div class="summary-total">
                        <span>Total</span>
                        <strong id="totalDisplay">₱0.00</strong>
                    </div>
                </div>

                {{-- Payment --}}
                <div class="card">
                    <div class="card-title">💳 Payment</div>

                    <label class="field-label">Method</label>
                    <div class="payment-methods">
                        @foreach(['cash' => '💵 Cash', 'gcash' => '📱 GCash'] as $val => $label)
                        <label class="pay-opt {{ old('payment_method') === $val ? 'selected' : '' }}">
                            <input type="radio" name="payment_method" value="{{ $val }}"
                                   {{ old('payment_method', 'cash') === $val ? 'checked' : '' }}
                                   onchange="toggleTendered(this)">
                            {{ $label }}
                        </label>
                        @endforeach
                    </div>
                    @error('payment_method')
                        <span class="field-error">{{ $message }}</span>
                    @enderror

                    <div id="tenderedRow" class="tendered-row">
                        <label class="field-label">Amount Tendered</label>
                        <div class="tendered-input-wrap">
                            <span class="peso-sign">₱</span>
                            <input type="number" name="amount_tendered" id="amountTendered"
                                   step="0.01" min="0" placeholder="0.00"
                                   value="{{ old('amount_tendered') }}"
                                   oninput="calcChange()">
                        </div>
                        <div id="changeDisplay" class="change-display" style="display:none;">
                            Change: <strong id="changeAmt">₱0.00</strong>
                        </div>
                    </div>

                    <label class="field-label" style="margin-top:14px;">Notes <span class="optional-tag">optional</span></label>
                    <textarea name="notes" rows="2" class="notes-input"
                              placeholder="Any notes for this transaction...">{{ old('notes') }}</textarea>
                </div>

                <button type="submit" class="submit-btn" id="submitBtn" onclick="return confirmSale()">
                    ✓ Complete Sale
                </button>
            </div>

        </div>{{-- /walkin-grid --}}
    </form>

    {{-- Recent Sales Panel (inside walkin-wrap so topbar/padding is shared) --}}
    <div id="historyPanel" class="history-panel" style="display:none;">

        {{-- Search & Filter bar --}}
        <div class="history-filters">
            <div class="hf-search-wrap">
                <span class="hf-icon">🔍</span>
                <input type="text" id="hfSearch" placeholder="Search patient or staff..."
                       oninput="filterSales()" class="hf-search">
            </div>
            <select id="hfPayment" onchange="filterSales()" class="hf-select">
                <option value="">All Payments</option>
                <option value="cash">Cash</option>
                <option value="gcash">GCash</option>
            </select>
            <input type="date" id="hfDate" onchange="filterSales()" class="hf-select"
                   placeholder="Filter by date">
            <button onclick="clearFilters()" class="hf-clear">✕ Clear</button>
        </div>

        @if($recentSales->isNotEmpty())
        <div class="history-table-wrap">
            <table class="recent-table" id="salesTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Staff</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="salesBody">
@foreach($recentSales as $rs)
<tr class="sale-row"
    data-patient="{{ strtolower($rs->patient_name) }}"
    data-staff="{{ strtolower($rs->staff_name) }}"
    data-payment="{{ $rs->payment_method }}"
    data-date="{{ \Carbon\Carbon::parse($rs->created_at)->format('Y-m-d') }}">
    <td data-label="#">{{ $rs->sale_id }}</td>
    <td data-label="Patient">{{ $rs->patient_name }}</td>
    <td data-label="Staff">{{ $rs->staff_name }}</td>
    <td data-label="Total">₱{{ number_format($rs->total_amount, 2) }}</td>
    <td data-label="Payment"><span class="pay-badge {{ $rs->payment_method }}">{{ strtoupper($rs->payment_method) }}</span></td>
    <td data-label="Date">{{ \Carbon\Carbon::parse($rs->created_at)->format('M j, Y g:i A') }}</td>
    <td>
        <a href="{{ route('staff.walkin.receipt', $rs->sale_id) }}" class="receipt-link">Receipt</a>
    </td>
</tr>
@endforeach
                </tbody>
            </table>
            <p id="noResults" style="display:none;text-align:center;color:#aaa;padding:20px;">No sales match your filters.</p>
        </div>
        @else
        <p style="text-align:center;color:#aaa;padding:30px;">No sales recorded yet.</p>
        @endif

    </div>{{-- /historyPanel --}}

</div>{{-- /walkin-wrap --}}

{{-- ── Confirmation Modal ── --}}
<div id="confirmModal" class="cm-overlay" onclick="closeCM(event)">
    <div class="cm-box">
        <div class="cm-header">
            <span class="cm-icon">🧾</span>
            <h3>Confirm Sale</h3>
            <button class="cm-close" onclick="document.getElementById('confirmModal').style.display='none'">×</button>
        </div>

        <div class="cm-patient-row">
            <span class="cm-label">Patient</span>
            <span id="cm-patient" class="cm-value"></span>
        </div>

        <div class="cm-section-label">Items</div>
        <div id="cm-items" class="cm-items"></div>

        <div class="cm-divider"></div>

        <div class="cm-total-row">
            <span>Total</span>
            <strong id="cm-total"></strong>
        </div>
        <div class="cm-total-row">
            <span>Payment</span>
            <span id="cm-payment" class="cm-pay-badge"></span>
        </div>
        <div id="cm-change-row" class="cm-change-block" style="display:none;">
            <div class="cm-total-row">
                <span>Tendered</span>
                <span id="cm-tendered"></span>
            </div>
            <div class="cm-total-row">
                <span>Change</span>
                <span id="cm-change" class="cm-change-amt"></span>
            </div>
        </div>

        <div class="cm-actions">
            <button type="button" class="cm-cancel-btn"
                    onclick="document.getElementById('confirmModal').style.display='none'">
                Cancel
            </button>
            <button type="button" class="cm-confirm-btn" onclick="submitSale()">
                ✓ Confirm &amp; Generate Receipt
            </button>
        </div>
    </div>
</div>


@push('scripts')
<script>
// Data passed from PHP as JSON
const PRODUCTS = {!! json_encode($products->map(function($p) { return ['id' => $p->product_id, 'name' => $p->product_name, 'price' => $p->selling_price, 'stock' => $p->quantity]; })->values()) !!};
const SERVICES = {!! json_encode($services->map(function($s) { return ['id' => $s->service_id, 'name' => $s->name, 'price' => $s->price]; })->values()) !!};
const DOCTORS  = {!! json_encode($doctors->map(function($d) { return ['id' => $d->user_id, 'name' => $d->firstName . ' ' . $d->lastName]; })->values()) !!};
const TODAY    = "{{ now()->toDateString() }}";
const PREFILL_PATIENT     = {{ request('patient_id', 'null') }};
const FROM_APPOINTMENT_ID = {{ request('from_appointment', 'null') }};
const PREFILL_SERVICE = {!! json_encode($prefillService) !!};

let productIndex = 1;
let serviceIndex = 0;
let grandTotal   = 0;

// ── Option builders ───────────────────────────────────────
function productOptionsHTML() {
    return '<option value="">— Select product —</option>' +
        PRODUCTS.map(p => `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">${p.name} (₱${parseFloat(p.price).toFixed(2)}) — ${p.stock} in stock</option>`).join('');
}
function serviceOptionsHTML() {
    return '<option value="">— Select service —</option>' +
        SERVICES.map(s => `<option value="${s.id}" data-price="${s.price}">${s.name} (₱${parseFloat(s.price).toFixed(2)})</option>`).join('');
}
function doctorOptionsHTML() {
    return '<option value="">— Select doctor —</option>' +
        DOCTORS.map(d => `<option value="${d.id}">${d.name}</option>`).join('');
}

// ── Products ─────────────────────────────────────────────
function addProductLine() {
    const div = document.createElement('div');
    div.className = 'product-line';
    div.dataset.index = productIndex;
    div.innerHTML = `
        <select name="items[${productIndex}][product_id]" class="prod-select" onchange="updateLinePrice(this)">
            ${productOptionsHTML()}
        </select>
        <input type="number" name="items[${productIndex}][quantity]" class="qty-input"
               min="1" value="1" placeholder="Qty" oninput="recalcTotal()">
        <span class="line-price">₱0.00</span>
        <button type="button" class="remove-line" onclick="removeLine(this)" title="Remove">✕</button>
    `;
    document.getElementById('productLines').appendChild(div);
    productIndex++;
}

function removeLine(btn) {
    btn.closest('.product-line').remove();
    recalcTotal();
}

function updateLinePrice(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const price = parseFloat(opt?.dataset?.price || 0);
    const line  = sel.closest('.product-line');
    const qty   = parseInt(line.querySelector('.qty-input').value) || 1;
    line.querySelector('.line-price').textContent = '₱' + (price * qty).toFixed(2);
    recalcTotal();
}

// ── Services ─────────────────────────────────────────────
function addServiceLine() {
    const div = document.createElement('div');
    div.className = 'service-line';
    div.dataset.index = serviceIndex;
    div.innerHTML = `
        <div class="service-line-grid">
            <div class="svc-field">
                <label>Service</label>
                <select name="services[${serviceIndex}][service_id]" class="svc-select" onchange="updateSvcPrice(this)">
                    ${serviceOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Doctor</label>
                <select name="services[${serviceIndex}][doctor_id]" class="svc-doctor" onchange="checkSlot(this)">
                    ${doctorOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Date</label>
                <input type="date" name="services[${serviceIndex}][appointment_date]"
                       class="svc-date" min="${TODAY}" onchange="checkSlot(this)">
            </div>
            <div class="svc-field">
                <label>Time</label>
                <input type="time" name="services[${serviceIndex}][appointment_time]"
                       class="svc-time" onchange="checkSlot(this)">
            </div>
            <div class="svc-field svc-price-field">
                <label>Price</label>
                <span class="svc-price-display">₱0.00</span>
            </div>
            <div class="svc-slot-status"></div>
        </div>
        <button type="button" class="remove-line svc-remove" onclick="removeServiceLine(this)" title="Remove">✕</button>
    `;
    document.getElementById('serviceLines').appendChild(div);
    serviceIndex++;
    recalcTotal();
}

function removeServiceLine(btn) {
    btn.closest('.service-line').remove();
    recalcTotal();
}

function updateSvcPrice(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const price = parseFloat(opt?.dataset?.price || 0);
    sel.closest('.service-line').querySelector('.svc-price-display').textContent = '₱' + price.toFixed(2);
    recalcTotal();
}

// ── Slot checker ─────────────────────────────────────────
function checkSlot(el) {
    const line      = el.closest('.service-line');
    const doctor    = line.querySelector('.svc-doctor')?.value;
    const date      = line.querySelector('.svc-date')?.value;
    const time      = line.querySelector('.svc-time')?.value;
    const status    = line.querySelector('.svc-slot-status');
    const existingId = line.querySelector('input[name*="existing_appointment_id"]')?.value || '';
    if (!doctor || !date || !time) { status.textContent = ''; return; }
    status.textContent = 'Checking...';
    status.className   = 'svc-slot-status checking';
    const url = `{{ session('role') === 'admin' ? route('admin.walkin.check-slot') : route('staff.walkin.check-slot') }}?doctor_id=${doctor}&date=${date}&time=${time}`
              + (existingId ? `&exclude_appointment_id=${existingId}` : '');
    fetch(url)
        .then(r => r.json())
        .then(data => {
            status.textContent = data.available ? '✓ Slot available' : '✕ Slot taken';
            status.className   = 'svc-slot-status ' + (data.available ? 'available' : 'taken');
        })
        .catch(() => { status.textContent = ''; });
}

// ── Totals ────────────────────────────────────────────────
function recalcTotal() {
    let total = 0;
    let lines = [];

    document.querySelectorAll('.product-line').forEach(line => {
        const sel   = line.querySelector('.prod-select');
        const qty   = parseInt(line.querySelector('.qty-input')?.value) || 0;
        const opt   = sel?.options[sel.selectedIndex];
        const price = parseFloat(opt?.dataset?.price || 0);
        line.querySelector('.line-price').textContent = '₱' + (price * qty).toFixed(2);
        if (sel?.value && qty > 0) {
            total += price * qty;
            lines.push({ name: opt.text.split('(')[0].trim() + ' ×' + qty, amount: price * qty });
        }
    });

    document.querySelectorAll('.service-line').forEach(line => {
        const sel         = line.querySelector('.svc-select');
        const opt         = sel?.options[sel.selectedIndex];
        const price       = parseFloat(opt?.dataset?.price || 0);
        const isPrefilled = !!line.querySelector('input[name*="existing_appointment_id"]');
        if (sel?.value) {
            if (isPrefilled) {
                // Prefilled (completed appointment) — this IS billed now
                total += price;
                lines.push({ name: '💆 ' + opt.text.split('(')[0].trim(), amount: price });
            } else {
                // New add-on — booked for future, NOT charged now
                lines.push({ name: '💆 ' + opt.text.split('(')[0].trim(), amount: null, addon: true });
            }
        }
    });

    grandTotal = total;
    document.getElementById('totalDisplay').textContent = '₱' + total.toFixed(2);
    document.getElementById('summaryLines').innerHTML = lines.length
        ? lines.map(l => l.addon
            ? `<div class="summary-row"><span>${l.name}</span><span style="color:#888;font-style:italic;font-size:0.8rem;">Billed on completion</span></div>`
            : `<div class="summary-row"><span>${l.name}</span><span>₱${l.amount.toFixed(2)}</span></div>`
          ).join('')
        : '<p class="empty-summary">No items added yet.</p>';
    calcChange();
}

// ── Payment ──────────────────────────────────────────────
function toggleTendered(radio) {
    document.getElementById('tenderedRow').style.display = radio.value === 'cash' ? 'block' : 'none';
    calcChange();
}

function calcChange() {
    const tendered = parseFloat(document.getElementById('amountTendered')?.value) || 0;
    const changeEl = document.getElementById('changeDisplay');
    if (tendered >= grandTotal && grandTotal > 0) {
        changeEl.style.display = 'block';
        document.getElementById('changeAmt').textContent = '₱' + (tendered - grandTotal).toFixed(2);
    } else {
        changeEl.style.display = 'none';
    }
}

// ── Confirmation modal ────────────────────────────────────
function confirmSale() {
    const patient = document.getElementById('patientSelect').value;
    if (!patient) { alert('Please select a patient.'); return false; }
    const hasProduct = [...document.querySelectorAll('.prod-select')].some(s => s.value);
    const hasService = [...document.querySelectorAll('.svc-select')].some(s => s.value);
    if (!hasProduct && !hasService) { alert('Add at least one product or service.'); return false; }

    const lines = [];
    document.querySelectorAll('.product-line').forEach(line => {
        const sel = line.querySelector('.prod-select');
        const qty = parseInt(line.querySelector('.qty-input')?.value) || 0;
        const opt = sel?.options[sel.selectedIndex];
        const price = parseFloat(opt?.dataset?.price || 0);
        if (sel?.value && qty > 0)
            lines.push(`<div class="cm-row"><span>${opt.text.split('(')[0].trim()} ×${qty}</span><span>₱${(price*qty).toFixed(2)}</span></div>`);
    });
    document.querySelectorAll('.service-line').forEach(line => {
        const sel         = line.querySelector('.svc-select');
        const opt         = sel?.options[sel.selectedIndex];
        const price       = parseFloat(opt?.dataset?.price || 0);
        const isPrefilled = !!line.querySelector('input[name*="existing_appointment_id"]');
        if (sel?.value) {
            if (isPrefilled) {
                // Prefilled (completed appointment) — billed now
                lines.push(`<div class="cm-row"><span>💆 ${opt.text.split('(')[0].trim()}</span><span>₱${price.toFixed(2)}</span></div>`);
            } else {
                // New add-on — future appointment, not charged now
                lines.push(`<div class="cm-row"><span>💆 ${opt.text.split('(')[0].trim()}</span><span style="color:#888;font-style:italic;">Billed on completion</span></div>`);
            }
        }
    });

    const patientSel = document.getElementById('patientSelect');
    document.getElementById('cm-patient').textContent = patientSel.options[patientSel.selectedIndex].text.split('(')[0].trim();
    document.getElementById('cm-items').innerHTML   = lines.join('');
    document.getElementById('cm-total').textContent = '₱' + grandTotal.toFixed(2);
    const method = document.querySelector('input[name="payment_method"]:checked')?.value || '';
    document.getElementById('cm-payment').textContent = method.toUpperCase();
    const tendered  = parseFloat(document.getElementById('amountTendered')?.value) || 0;
    const changeRow = document.getElementById('cm-change-row');
    if (method === 'cash' && tendered > 0) {
        changeRow.style.display = 'flex';
        document.getElementById('cm-tendered').textContent = '₱' + tendered.toFixed(2);
        document.getElementById('cm-change').textContent   = '₱' + Math.max(0, tendered - grandTotal).toFixed(2);
    } else {
        changeRow.style.display = 'none';
    }
    document.getElementById('confirmModal').style.display = 'flex';
    return false;
}

function submitSale() {
    document.getElementById('confirmModal').style.display = 'none';
    document.getElementById('walkinForm').submit();
}

function closeCM(event) {
    if (event.target === document.getElementById('confirmModal'))
        document.getElementById('confirmModal').style.display = 'none';
}

// ── Tab toggle ────────────────────────────────────────────
function switchTab(tab) {
    const saleForm    = document.getElementById('walkinForm');
    const saleGrid    = document.querySelector('.walkin-grid');
    const historyPanel = document.getElementById('historyPanel');
    const tabSale     = document.getElementById('tabSale');
    const tabHistory  = document.getElementById('tabHistory');

    if (tab === 'sale') {
        saleGrid.style.display     = 'grid';
        historyPanel.style.display = 'none';
        tabSale.classList.add('active');
        tabHistory.classList.remove('active');
    } else {
        saleGrid.style.display     = 'none';
        historyPanel.style.display = 'block';
        tabSale.classList.remove('active');
        tabHistory.classList.add('active');
    }
}

// ── Sales filter ──────────────────────────────────────────
function filterSales() {
    const search  = document.getElementById('hfSearch').value.toLowerCase();
    const payment = document.getElementById('hfPayment').value;
    const date    = document.getElementById('hfDate').value;
    const rows    = document.querySelectorAll('.sale-row');
    let visible   = 0;

    rows.forEach(row => {
        const matchSearch  = !search  || row.dataset.patient.includes(search) || row.dataset.staff.includes(search);
        const matchPayment = !payment || row.dataset.payment === payment;
        const matchDate    = !date    || row.dataset.date === date;

        if (matchSearch && matchPayment && matchDate) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

function clearFilters() {
    document.getElementById('hfSearch').value  = '';
    document.getElementById('hfPayment').value = '';
    document.getElementById('hfDate').value    = '';
    filterSales();
}

document.addEventListener('DOMContentLoaded', () => {
 
    // Populate the first product line with JS-built options
    const firstSelect = document.querySelector('.prod-select');
    if (firstSelect) firstSelect.innerHTML = productOptionsHTML();
 
    // ── Auto-select patient if redirected from a completed booking ──
    if (PREFILL_PATIENT) {
        const patientSel = document.getElementById('patientSelect');
        if (patientSel) {
            patientSel.value = PREFILL_PATIENT;
            patientSel.style.borderColor = '#80a833';
            patientSel.style.background  = '#f0f7e6';
        }
    }
 
    // ── Auto-populate service from completed appointment ──
    // PREFILL_SERVICE is passed from PHP via WalkinSaleController@index
    // It contains the service, doctor, date, and time from the appointment
    if (PREFILL_SERVICE) {
        prefillFromAppointment(PREFILL_SERVICE);
    }
 
    const defaultPay = document.querySelector('input[name="payment_method"]:checked');
    if (defaultPay) toggleTendered(defaultPay);
    recalcTotal();
});
 
// ── Prefill service add-on from appointment data ──────────
function prefillFromAppointment(svc) {
    // Build a new service line (same as addServiceLine() but pre-filled)
    const div = document.createElement('div');
    div.className = 'service-line prefilled';
    div.dataset.index = serviceIndex;
 
    div.innerHTML = `
        <div class="service-line-grid">
            <div class="svc-field">
                <label>Service <span class="prefill-tag">from appointment</span></label>
                <select name="services[${serviceIndex}][service_id]"
                        class="svc-select prefilled-field"
                        onchange="updateSvcPrice(this)">
                    ${serviceOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Doctor</label>
                <select name="services[${serviceIndex}][doctor_id]"
                        class="svc-doctor prefilled-field"
                        onchange="checkSlot(this)">
                    ${doctorOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Date</label>
                <input type="date"
                       name="services[${serviceIndex}][appointment_date]"
                       class="svc-date prefilled-field"
                       min="${TODAY}"
                       onchange="checkSlot(this)">
            </div>
            <div class="svc-field">
                <label>Time</label>
                <input type="time"
                       name="services[${serviceIndex}][appointment_time]"
                       class="svc-time prefilled-field"
                       onchange="checkSlot(this)">
            </div>
            <div class="svc-field svc-price-field">
                <label>Price</label>
                <span class="svc-price-display">₱0.00</span>
            </div>
            <div class="svc-slot-status"></div>
        </div>
        <button type="button" class="remove-line svc-remove"
                onclick="removeServiceLine(this)" title="Remove">✕</button>
    `;
 
    document.getElementById('serviceLines').appendChild(div);

    // Add hidden field to mark this as already-completed (no re-booking needed)
const hiddenAppt = document.createElement('input');
hiddenAppt.type  = 'hidden';
hiddenAppt.name  = `services[${serviceIndex}][existing_appointment_id]`;
hiddenAppt.value = svc.appointment_id;
div.appendChild(hiddenAppt);
 
    // Now set the values
    const svcSelect    = div.querySelector('.svc-select');
    const doctorSelect = div.querySelector('.svc-doctor');
    const dateInput    = div.querySelector('.svc-date');
    const timeInput    = div.querySelector('.svc-time');
    const priceDisplay = div.querySelector('.svc-price-display');
 
    // Set service
    if (svc.service_id) {
        svcSelect.value = svc.service_id;
        // Trigger price update
        const selOpt = svcSelect.options[svcSelect.selectedIndex];
        const price  = parseFloat(selOpt?.dataset?.price || svc.service_price || 0);
        priceDisplay.textContent = '₱' + price.toFixed(2);
    }
 
    // Set doctor
    if (svc.doctor_user_id) {
        doctorSelect.value = svc.doctor_user_id;
    }
 
    // Set date
    if (svc.appointment_date) {
        dateInput.value = svc.appointment_date;
    }
 
    // Set time (format HH:mm)
    if (svc.appointment_time) {
        timeInput.value = svc.appointment_time.substring(0, 5);
    }
 
    serviceIndex++;
    recalcTotal();
 
    // Show a subtle note that this came from the appointment
    const note = document.createElement('p');
    note.className = 'prefill-note';
    note.innerHTML = `✅ Service pre-filled from Appointment #${svc.appointment_id} — you can edit or remove it.`;
    document.getElementById('serviceLines').insertBefore(note, div);
}
</script>
@endpush

@endsection