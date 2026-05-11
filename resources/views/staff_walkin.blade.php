{{-- resources/views/staff_walkin.blade.php --}}

@extends('layouts.app')

@section('title', 'Walk-in Sale — SkinMedic')

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/css/staff_walkin.css') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="walkin-wrap">

    {{-- ── Header ── --}}
    <div class="walkin-header">
        <div>
            <h2>Walk-in Sale</h2>
            <p class="walkin-sub">{{ now()->format('l, F j Y') }}</p>
        </div>
        <a href="{{ route('staff.home') }}" class="back-btn">← Back to Dashboard</a>
    </div>

    {{-- ── Flash messages ── --}}
    @if(session('error'))
        <div class="flash error">⚠ {{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="flash success">✓ {{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('staff.walkin.store') }}" id="walkinForm">
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
                                {{ $p->name }} ({{ $p->email }})
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
                        @foreach(['cash' => '💵 Cash', 'gcash' => '📱 GCash', 'card' => '💳 Card', 'other' => '🔄 Other'] as $val => $label)
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

    {{-- ── Recent Sales ── --}}
    @if($recentSales->isNotEmpty())
    <div class="card recent-card">
        <div class="card-title">🕒 Recent Sales</div>
        <table class="recent-table">
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
            <tbody>
                @foreach($recentSales as $rs)
                <tr>
                    <td>{{ $rs->sale_id }}</td>
                    <td>{{ $rs->patient_name }}</td>
                    <td>{{ $rs->staff_name }}</td>
                    <td>₱{{ number_format($rs->total_amount, 2) }}</td>
                    <td><span class="pay-badge {{ $rs->payment_method }}">{{ strtoupper($rs->payment_method) }}</span></td>
                    <td>{{ \Carbon\Carbon::parse($rs->created_at)->format('M j, Y g:i A') }}</td>
                    <td>
                        <a href="{{ route('staff.walkin.receipt', $rs->sale_id) }}" class="receipt-link">Receipt</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

</div>{{-- /walkin-wrap --}}

{{-- Hidden template for service lines --}}
<template id="serviceTpl">
    <div class="service-line" data-index="__IDX__">
        <div class="service-line-grid">
            <div class="svc-field">
                <label>Service</label>
                <select name="services[__IDX__][service_id]" class="svc-select" onchange="updateSvcPrice(this)">
                    <option value="">— Select service —</option>
                    @foreach($services as $svc)
                        <option value="{{ $svc->service_id }}" data-price="{{ $svc->price }}">
                            {{ $svc->name }} (₱{{ number_format($svc->price, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="svc-field">
                <label>Doctor</label>
                <select name="services[__IDX__][doctor_id]" class="svc-doctor" onchange="checkSlot(this)">
                    <option value="">— Select doctor —</option>
                    @foreach($doctors as $doc)
                        <option value="{{ $doc->user_id }}">{{ $doc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="svc-field">
                <label>Date</label>
                <input type="date" name="services[__IDX__][appointment_date]"
                       class="svc-date" min="{{ now()->toDateString() }}" onchange="checkSlot(this)">
            </div>
            <div class="svc-field">
                <label>Time</label>
                <input type="time" name="services[__IDX__][appointment_time]"
                       class="svc-time" onchange="checkSlot(this)">
            </div>
            <div class="svc-field svc-price-field">
                <label>Price</label>
                <span class="svc-price-display">₱0.00</span>
            </div>
            <div class="svc-slot-status"></div>
        </div>
        <button type="button" class="remove-line svc-remove" onclick="removeServiceLine(this)" title="Remove">✕</button>
    </div>
</template>

@push('scripts')
<script>
let productIndex = 1;   // 0 already rendered
let serviceIndex = 0;
let grandTotal   = 0;

// ── Products ─────────────────────────────────────────────
function addProductLine() {
    const container = document.getElementById('productLines');
    const div = document.createElement('div');
    div.className = 'product-line';
    div.dataset.index = productIndex;
    div.innerHTML = `
        <select name="items[${productIndex}][product_id]" class="prod-select" onchange="updateLinePrice(this)">
            <option value="">— Select product —</option>
            ${getProductOptions()}
        </select>
        <input type="number" name="items[${productIndex}][quantity]" class="qty-input"
               min="1" value="1" placeholder="Qty" onchange="recalcTotal()">
        <span class="line-price">₱0.00</span>
        <button type="button" class="remove-line" onclick="removeLine(this)" title="Remove">✕</button>
    `;
    container.appendChild(div);
    productIndex++;
}

function getProductOptions() {
    // Clone options from first select
    const src = document.querySelector('.prod-select');
    return src ? src.innerHTML : '';
}

function removeLine(btn) {
    const line = btn.closest('.product-line');
    line.remove();
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
    const tpl     = document.getElementById('serviceTpl').innerHTML
                      .replaceAll('__IDX__', serviceIndex);
    const wrapper = document.createElement('div');
    wrapper.innerHTML = tpl;
    document.getElementById('serviceLines').appendChild(wrapper.firstElementChild);
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
    const line  = sel.closest('.service-line');
    line.querySelector('.svc-price-display').textContent = '₱' + price.toFixed(2);
    recalcTotal();
}

// ── Slot checker ─────────────────────────────────────────
function checkSlot(el) {
    const line   = el.closest('.service-line');
    const doctor = line.querySelector('.svc-doctor')?.value;
    const date   = line.querySelector('.svc-date')?.value;
    const time   = line.querySelector('.svc-time')?.value;
    const status = line.querySelector('.svc-slot-status');

    if (!doctor || !date || !time) { status.textContent = ''; return; }

    status.textContent = 'Checking...';
    status.className   = 'svc-slot-status checking';

    fetch(`{{ route('staff.walkin.check-slot') }}?doctor_id=${doctor}&date=${date}&time=${time}`)
        .then(r => r.json())
        .then(data => {
            if (data.available) {
                status.textContent = '✓ Slot available';
                status.className   = 'svc-slot-status available';
            } else {
                status.textContent = '✕ Slot taken';
                status.className   = 'svc-slot-status taken';
            }
        })
        .catch(() => { status.textContent = ''; });
}

// ── Totals ────────────────────────────────────────────────
function recalcTotal() {
    let total = 0;
    const summaryEl = document.getElementById('summaryLines');
    let lines = [];

    // Products
    document.querySelectorAll('.product-line').forEach(line => {
        const sel   = line.querySelector('.prod-select');
        const qty   = parseInt(line.querySelector('.qty-input')?.value) || 0;
        const opt   = sel?.options[sel.selectedIndex];
        const price = parseFloat(opt?.dataset?.price || 0);
        const name  = opt?.text?.split('(')[0]?.trim();

        // Update inline price display
        line.querySelector('.line-price').textContent = '₱' + (price * qty).toFixed(2);

        if (sel?.value && qty > 0) {
            total += price * qty;
            lines.push({ name: name + ' ×' + qty, amount: price * qty });
        }
    });

    // Services
    document.querySelectorAll('.service-line').forEach(line => {
        const sel   = line.querySelector('.svc-select');
        const opt   = sel?.options[sel.selectedIndex];
        const price = parseFloat(opt?.dataset?.price || 0);
        const name  = opt?.text?.split('(')[0]?.trim();

        if (sel?.value) {
            total += price;
            lines.push({ name: '💆 ' + name, amount: price });
        }
    });

    grandTotal = total;
    document.getElementById('totalDisplay').textContent = '₱' + total.toFixed(2);
    summaryEl.innerHTML = lines.length
        ? lines.map(l => `
            <div class="summary-row">
                <span>${l.name}</span>
                <span>₱${l.amount.toFixed(2)}</span>
            </div>`).join('')
        : '<p class="empty-summary">No items added yet.</p>';

    calcChange();
}

// ── Payment ──────────────────────────────────────────────
function toggleTendered(radio) {
    document.getElementById('tenderedRow').style.display =
        radio.value === 'cash' ? 'block' : 'none';
    calcChange();
}

function calcChange() {
    const tendered = parseFloat(document.getElementById('amountTendered')?.value) || 0;
    const changeEl = document.getElementById('changeDisplay');
    const changeAmt= document.getElementById('changeAmt');

    if (tendered >= grandTotal && grandTotal > 0) {
        changeEl.style.display = 'block';
        changeAmt.textContent  = '₱' + (tendered - grandTotal).toFixed(2);
    } else {
        changeEl.style.display = 'none';
    }
}

function confirmSale() {
    const patient = document.getElementById('patientSelect').value;
    if (!patient) { alert('Please select a patient.'); return false; }

    const hasProduct = [...document.querySelectorAll('.prod-select')].some(s => s.value);
    const hasService = [...document.querySelectorAll('.svc-select')].some(s => s.value);
    if (!hasProduct && !hasService) {
        alert('Add at least one product or service.');
        return false;
    }
    return confirm(`Complete sale for ₱${grandTotal.toFixed(2)}?`);
}

// Init
document.addEventListener('DOMContentLoaded', () => {
    // Show/hide tendered based on default payment selection
    const defaultPay = document.querySelector('input[name="payment_method"]:checked');
    if (defaultPay) toggleTendered(defaultPay);
    recalcTotal();
});
</script>
@endpush

@endsection