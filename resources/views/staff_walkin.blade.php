{{-- resources/views/staff_walkin.blade.php --}}

@extends('layouts.app')

@section('title', 'Walk-in Sale — SkinMedic')

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/css/staff_walkin.css') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700&display=swap" rel="stylesheet">
{{-- Tom Select --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css">
<style>
    /* ── Tom Select overrides to match existing design ── */
    .ts-wrapper.full-select,
    .ts-wrapper.prod-select,
    .ts-wrapper.svc-select,
    .ts-wrapper.svc-doctor {
        padding: 0;
        border: none;
        background: transparent;
    }

    .ts-wrapper .ts-control {
        width: 100%;
        padding: 9px 12px;
        border: 1.5px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.88rem;
        font-family: 'DM Sans', sans-serif;
        color: #333;
        background: #fafafa;
        transition: border-color 0.2s;
        box-sizing: border-box;
        box-shadow: none;
        cursor: pointer;
        min-height: unset;
    }

    .ts-wrapper.focus .ts-control,
    .ts-wrapper .ts-control:focus-within {
        border-color: #80a833 !important;
        outline: none;
        background: #fff;
        box-shadow: none;
    }

    /* Dropdown */
    .ts-dropdown {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        border: 1.5px solid #d8e8c0;
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.10);
        margin-top: 3px;
        z-index: 9999;
    }

    .ts-dropdown .option {
        padding: 9px 12px;
        color: #333;
        border-radius: 0;
        transition: background 0.1s;
    }
    .ts-dropdown .option:hover,
    .ts-dropdown .option.active {
        background: #f0f7e6;
        color: #3a6400;
    }

    /* Search input inside dropdown */
    .ts-dropdown .ts-dropdown-content { max-height: 220px; }
    .ts-wrapper .ts-control input {
        font-family: 'DM Sans', sans-serif;
        font-size: 0.88rem;
        color: #333;
    }

    /* Remove default arrow and use a clean one */
    .ts-wrapper:not(.multi) .ts-control .value { color: #333; }
    .ts-wrapper .ts-control::after { border-color: #888 transparent transparent transparent; }

    /* Prefilled Tom Select fields */
    .ts-wrapper.prefilled-ts .ts-control {
        border-color: #80a833 !important;
        background: #f9fff2 !important;
    }

    /* Patient select — slightly taller placeholder area */
    #patientSelect-ts-wrapper .ts-control { min-height: 40px; }
</style>
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
                        <option value="">— Search or select patient —</option>
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
                            <select name="items[0][product_id]" class="prod-select" id="prodSelect_0">
                                <option value="">— Search product —</option>
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
                                   min="1" value="1" placeholder="Qty" oninput="recalcTotal()">
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

    {{-- Recent Sales Panel --}}
    <div id="historyPanel" class="history-panel" style="display:none;">

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
{{-- Tom Select JS --}}
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>

<script>
// ── PHP → JS data ──────────────────────────────────────────
const PRODUCTS = {!! json_encode($products->map(function($p) {
    return ['id' => $p->product_id, 'name' => $p->product_name, 'price' => $p->selling_price, 'stock' => $p->quantity];
})->values()) !!};

const SERVICES = {!! json_encode($services->map(function($s) {
    return ['id' => $s->service_id, 'name' => $s->name, 'price' => $s->price];
})->values()) !!};

const DOCTORS = {!! json_encode($doctors->map(function($d) {
    return ['id' => $d->user_id, 'name' => $d->firstName . ' ' . $d->lastName];
})->values()) !!};

const TODAY                = "{{ now()->toDateString() }}";
const PREFILL_PATIENT      = {{ request('patient_id', 'null') }};
const FROM_APPOINTMENT_ID  = {{ request('from_appointment', 'null') }};
const PREFILL_SERVICE      = {!! json_encode($prefillService) !!};

let productIndex = 1;
let serviceIndex = 0;
let grandTotal   = 0;

// ── Tom Select instances registry ─────────────────────────
// Stores { selectEl: TomSelect instance } so we can destroy/read them
const tsInstances = new WeakMap();

// ── Tom Select factory ────────────────────────────────────
/**
 * Initialise Tom Select on a <select> element.
 * @param {HTMLSelectElement} el
 * @param {object} extraOpts  – merged into TomSelect options
 */
function initTS(el, extraOpts = {}) {
    if (tsInstances.has(el)) return tsInstances.get(el); // already initialised

    const ts = new TomSelect(el, {
        allowEmptyOption: true,
        placeholder: el.dataset.placeholder || 'Search…',
        maxOptions: 200,
        ...extraOpts,
    });

    tsInstances.set(el, ts);
    return ts;
}

/**
 * Destroy and re-create a TomSelect on el with new <option> HTML.
 * Used when we build product/service lines dynamically.
 */
function reinitTS(el, optionsHTML, extraOpts = {}) {
    if (tsInstances.has(el)) {
        tsInstances.get(el).destroy();
        tsInstances.delete(el);
    }
    el.innerHTML = optionsHTML;
    return initTS(el, extraOpts);
}

// ── Option HTML builders (plain <option> strings) ─────────
function productOptionsHTML() {
    return '<option value="">— Search product —</option>' +
        PRODUCTS.map(p =>
            `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock}">` +
            `${p.name} (₱${parseFloat(p.price).toFixed(2)}) — ${p.stock} in stock` +
            `</option>`
        ).join('');
}

function serviceOptionsHTML() {
    return '<option value="">— Search service —</option>' +
        SERVICES.map(s =>
            `<option value="${s.id}" data-price="${s.price}">` +
            `${s.name} (₱${parseFloat(s.price).toFixed(2)})` +
            `</option>`
        ).join('');
}

function doctorOptionsHTML() {
    return '<option value="">— Select doctor —</option>' +
        DOCTORS.map(d =>
            `<option value="${d.id}">${d.name}</option>`
        ).join('');
}

// ── Products ──────────────────────────────────────────────
function addProductLine() {
    const div = document.createElement('div');
    div.className = 'product-line';
    div.dataset.index = productIndex;

    const selId = `prodSelect_${productIndex}`;
    div.innerHTML = `
        <select name="items[${productIndex}][product_id]" class="prod-select" id="${selId}">
            ${productOptionsHTML()}
        </select>
        <input type="number" name="items[${productIndex}][quantity]" class="qty-input"
               min="1" value="1" placeholder="Qty" oninput="recalcTotal()">
        <span class="line-price">₱0.00</span>
        <button type="button" class="remove-line" onclick="removeLine(this)" title="Remove">✕</button>
    `;

    document.getElementById('productLines').appendChild(div);

    // Init Tom Select on the new select
    const sel = div.querySelector('.prod-select');
    const ts  = initTS(sel);
    ts.on('change', () => updateLinePriceByEl(sel));

    productIndex++;
}

function removeLine(btn) {
    const line = btn.closest('.product-line');
    const sel  = line.querySelector('.prod-select');
    if (sel && tsInstances.has(sel)) tsInstances.get(sel).destroy();
    line.remove();
    recalcTotal();
}

function updateLinePriceByEl(sel) {
    const ts    = tsInstances.get(sel);
    const val   = ts ? ts.getValue() : sel.value;
    const opt   = sel.querySelector(`option[value="${val}"]`);
    const price = parseFloat(opt?.dataset?.price || 0);
    const line  = sel.closest('.product-line');
    const qty   = parseInt(line.querySelector('.qty-input').value) || 1;
    line.querySelector('.line-price').textContent = '₱' + (price * qty).toFixed(2);
    recalcTotal();
}

// Legacy helper kept for inline onchange="" on first static line (overridden below)
function updateLinePrice(sel) { updateLinePriceByEl(sel); }

// ── Services ──────────────────────────────────────────────
function addServiceLine(prefill = null) {
    const div = document.createElement('div');
    div.className = 'service-line' + (prefill ? ' prefilled' : '');
    div.dataset.index = serviceIndex;

    const svcId = `svcSelect_${serviceIndex}`;
    const docId = `docSelect_${serviceIndex}`;

    div.innerHTML = `
        <div class="service-line-grid">
            <div class="svc-field">
                <label>Service${prefill ? ' <span class="prefill-tag">from appointment</span>' : ''}</label>
                <select name="services[${serviceIndex}][service_id]"
                        class="svc-select${prefill ? ' prefilled-field' : ''}"
                        id="${svcId}">
                    ${serviceOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Doctor</label>
                <select name="services[${serviceIndex}][doctor_id]"
                        class="svc-doctor${prefill ? ' prefilled-field' : ''}"
                        id="${docId}">
                    ${doctorOptionsHTML()}
                </select>
            </div>
            <div class="svc-field">
                <label>Date</label>
                <input type="date" name="services[${serviceIndex}][appointment_date]"
                       class="svc-date${prefill ? ' prefilled-field' : ''}"
                       min="${TODAY}" onchange="checkSlot(this)">
            </div>
            <div class="svc-field">
                <label>Time</label>
                <input type="time" name="services[${serviceIndex}][appointment_time]"
                       class="svc-time${prefill ? ' prefilled-field' : ''}"
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

    // Init Tom Select on service select
    const svcSel = div.querySelector('.svc-select');
    const svcTS  = initTS(svcSel, { placeholder: 'Search service…' });
    svcTS.on('change', () => {
        updateSvcPriceByEl(svcSel);
        checkSlotFromLine(div);
    });
    if (prefill?.service_id) {
        svcTS.setValue(String(prefill.service_id), true);
        updateSvcPriceByEl(svcSel);
    }
    if (prefill) svcTS.wrapper.classList.add('prefilled-ts');

    // Init Tom Select on doctor select
    const docSel = div.querySelector('.svc-doctor');
    const docTS  = initTS(docSel, { placeholder: 'Select doctor…' });
    docTS.on('change', () => checkSlotFromLine(div));
    if (prefill?.doctor_user_id) {
        docTS.setValue(String(prefill.doctor_user_id), true);
    }
    if (prefill) docTS.wrapper.classList.add('prefilled-ts');

    // Fill date/time if prefilling
    if (prefill?.appointment_date) div.querySelector('.svc-date').value = prefill.appointment_date;
    if (prefill?.appointment_time) div.querySelector('.svc-time').value = prefill.appointment_time.substring(0, 5);

    // Hidden appointment id for prefill
    if (prefill?.appointment_id) {
        const hidden = document.createElement('input');
        hidden.type  = 'hidden';
        hidden.name  = `services[${serviceIndex}][existing_appointment_id]`;
        hidden.value = prefill.appointment_id;
        div.appendChild(hidden);
    }

    serviceIndex++;
    recalcTotal();

    // Pre-fill note
    if (prefill) {
        const note = document.createElement('p');
        note.className = 'prefill-note';
        note.innerHTML = `✅ Service pre-filled from Appointment #${prefill.appointment_id} — you can edit or remove it.`;
        document.getElementById('serviceLines').insertBefore(note, div);
    }
}

function removeServiceLine(btn) {
    const line   = btn.closest('.service-line');
    const svcSel = line.querySelector('.svc-select');
    const docSel = line.querySelector('.svc-doctor');
    if (svcSel && tsInstances.has(svcSel)) tsInstances.get(svcSel).destroy();
    if (docSel && tsInstances.has(docSel)) tsInstances.get(docSel).destroy();

    // Also remove preceding prefill-note if present
    const prev = line.previousElementSibling;
    if (prev && prev.classList.contains('prefill-note')) prev.remove();

    line.remove();
    recalcTotal();
}

function updateSvcPriceByEl(sel) {
    const val   = tsInstances.has(sel) ? tsInstances.get(sel).getValue() : sel.value;
    const opt   = sel.querySelector(`option[value="${val}"]`);
    const price = parseFloat(opt?.dataset?.price || 0);
    sel.closest('.service-line').querySelector('.svc-price-display').textContent = '₱' + price.toFixed(2);
    recalcTotal();
}

// ── Slot checker ─────────────────────────────────────────
function checkSlotFromLine(line) {
    // Get values, accounting for Tom Select
    const docSel   = line.querySelector('.svc-doctor');
    const svcSel   = line.querySelector('.svc-select');
    const doctor   = tsInstances.has(docSel) ? tsInstances.get(docSel).getValue() : docSel?.value;
    const service  = tsInstances.has(svcSel) ? tsInstances.get(svcSel).getValue() : svcSel?.value;
    const date     = line.querySelector('.svc-date')?.value;
    const time     = line.querySelector('.svc-time')?.value;
    const status   = line.querySelector('.svc-slot-status');

    if (!doctor || !date || !time) { status.textContent = ''; return; }

    status.textContent = 'Checking…';
    status.className   = 'svc-slot-status checking';

    fetch(`/get-available-times?doctor_id=${doctor}&date=${date}&service_id=${service}`)
        .then(r => r.json())
        .then(slots => {
            const timeShort = time.substring(0, 5);
            const slot = slots.find(s => s.time === timeShort);

            if (!slot) {
                status.textContent = '✕ Outside doctor\'s schedule';
                status.className   = 'svc-slot-status taken';
            } else if (slot.taken) {
                status.textContent = '✕ Slot taken';
                status.className   = 'svc-slot-status taken';
            } else {
                status.textContent = '✓ Slot available';
                status.className   = 'svc-slot-status available';
            }
        })
        .catch(() => { status.textContent = ''; });
}

// Backwards compat for inline onchange on date/time inputs
function checkSlot(el) { checkSlotFromLine(el.closest('.service-line')); }

// ── Totals ────────────────────────────────────────────────
function recalcTotal() {
    let total = 0;
    let lines = [];

    document.querySelectorAll('.product-line').forEach(line => {
        const sel   = line.querySelector('.prod-select');
        const qty   = parseInt(line.querySelector('.qty-input')?.value) || 0;
        const val   = tsInstances.has(sel) ? tsInstances.get(sel).getValue() : sel?.value;
        const opt   = sel?.querySelector(`option[value="${val}"]`);
        const price = parseFloat(opt?.dataset?.price || 0);

        line.querySelector('.line-price').textContent = '₱' + (price * qty).toFixed(2);

        if (val && qty > 0) {
            total += price * qty;
            lines.push({ name: (opt?.text || '').split('(')[0].trim() + ' ×' + qty, amount: price * qty });
        }
    });

    document.querySelectorAll('.service-line').forEach(line => {
        const sel   = line.querySelector('.svc-select');
        const val   = tsInstances.has(sel) ? tsInstances.get(sel).getValue() : sel?.value;
        const opt   = sel?.querySelector(`option[value="${val}"]`);
        const price = parseFloat(opt?.dataset?.price || 0);

        if (val) {
            total += price;
            lines.push({ name: '💆 ' + (opt?.text || '').split('(')[0].trim(), amount: price });
        }
    });

    grandTotal = total;
    document.getElementById('totalDisplay').textContent = '₱' + total.toFixed(2);
    document.getElementById('summaryLines').innerHTML = lines.length
        ? lines.map(l => `<div class="summary-row"><span>${l.name}</span><span>₱${l.amount.toFixed(2)}</span></div>`).join('')
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
    // Validate patient (Tom Select)
    const patientTS  = tsInstances.get(document.getElementById('patientSelect'));
    const patientVal = patientTS ? patientTS.getValue() : document.getElementById('patientSelect').value;
    if (!patientVal) { alert('Please select a patient.'); return false; }

    const hasProduct = [...document.querySelectorAll('.prod-select')].some(s => {
        return tsInstances.has(s) ? !!tsInstances.get(s).getValue() : !!s.value;
    });
    const hasService = [...document.querySelectorAll('.svc-select')].some(s => {
        return tsInstances.has(s) ? !!tsInstances.get(s).getValue() : !!s.value;
    });
    if (!hasProduct && !hasService) { alert('Add at least one product or service.'); return false; }

    const lineHtml = [];

    document.querySelectorAll('.product-line').forEach(line => {
        const sel   = line.querySelector('.prod-select');
        const val   = tsInstances.has(sel) ? tsInstances.get(sel).getValue() : sel?.value;
        const qty   = parseInt(line.querySelector('.qty-input')?.value) || 0;
        const opt   = sel?.querySelector(`option[value="${val}"]`);
        const price = parseFloat(opt?.dataset?.price || 0);
        if (val && qty > 0)
            lineHtml.push(`<div class="cm-row"><span>${(opt?.text || '').split('(')[0].trim()} ×${qty}</span><span>₱${(price * qty).toFixed(2)}</span></div>`);
    });

    document.querySelectorAll('.service-line').forEach(line => {
        const sel   = line.querySelector('.svc-select');
        const val   = tsInstances.has(sel) ? tsInstances.get(sel).getValue() : sel?.value;
        const opt   = sel?.querySelector(`option[value="${val}"]`);
        const price = parseFloat(opt?.dataset?.price || 0);
        if (val)
            lineHtml.push(`<div class="cm-row"><span>💆 ${(opt?.text || '').split('(')[0].trim()}</span><span>₱${price.toFixed(2)}</span></div>`);
    });

    // Patient display name
    const patientEl  = document.getElementById('patientSelect');
    const patientOpt = patientEl.querySelector(`option[value="${patientVal}"]`);
    document.getElementById('cm-patient').textContent = (patientOpt?.text || '').split('(')[0].trim();

    document.getElementById('cm-items').innerHTML   = lineHtml.join('');
    document.getElementById('cm-total').textContent = '₱' + grandTotal.toFixed(2);

    const method = document.querySelector('input[name="payment_method"]:checked')?.value || '';
    document.getElementById('cm-payment').textContent = method.toUpperCase();

    const tendered  = parseFloat(document.getElementById('amountTendered')?.value) || 0;
    const changeRow = document.getElementById('cm-change-row');
    if (method === 'cash' && tendered > 0) {
        changeRow.style.display = 'block';
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
    const saleGrid     = document.querySelector('.walkin-grid');
    const historyPanel = document.getElementById('historyPanel');
    const tabSale      = document.getElementById('tabSale');
    const tabHistory   = document.getElementById('tabHistory');

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

// ── Init on DOM ready ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {

    // ── 1. Patient Tom Select ──
    const patientEl = document.getElementById('patientSelect');
    const patientTS = initTS(patientEl, {
        placeholder: 'Search patient by name or email…',
        searchField: ['text'],   // searches the option text (name + email)
    });

    // Auto-select patient if redirected from booking
    if (PREFILL_PATIENT) {
        patientTS.setValue(String(PREFILL_PATIENT), true);
        patientTS.wrapper.style.borderColor = '#80a833';
    }

    // ── 2. First product line Tom Select ──
    const firstProdSel = document.querySelector('#prodSelect_0');
    if (firstProdSel) {
        // Replace server-rendered options with JS-built ones (so data-* attrs are consistent)
        firstProdSel.innerHTML = productOptionsHTML();
        const ts = initTS(firstProdSel, { placeholder: 'Search product…' });
        ts.on('change', () => updateLinePriceByEl(firstProdSel));
    }

    // ── 3. Prefill service from appointment ──
    if (PREFILL_SERVICE) {
        addServiceLine(PREFILL_SERVICE);
    }

    // ── 4. Default payment toggle ──
    const defaultPay = document.querySelector('input[name="payment_method"]:checked');
    if (defaultPay) toggleTendered(defaultPay);

    recalcTotal();
});
</script>
@endpush

@endsection