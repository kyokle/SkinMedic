{{-- resources/views/patient_products.blade.php --}}
@extends('layouts.app')

@section('title', 'Shop — SkinMedic')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/patient_products.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,300&family=Fraunces:ital,wght@0,700;1,500&display=swap" rel="stylesheet">
@endpush

@section('content')
@include('partials.sidebar_patient')

<div class="shop-main">

    {{-- ── TOP BAR ── --}}
    <div class="shop-topbar">
        <div class="shop-topbar-left">
            <h1 class="shop-heading">Shop</h1>
            <span class="shop-sub">{{ $products->count() }} products available</span>
        </div>
        <div class="shop-topbar-right">
            {{-- Search --}}
            <div class="search-wrap">
                <svg class="search-icon" viewBox="0 0 20 20" fill="none">
                    <circle cx="9" cy="9" r="6" stroke="currentColor" stroke-width="1.6"/>
                    <path d="M14 14l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                </svg>
                <input id="searchInput" type="text" placeholder="Search products…" class="search-input">
            </div>
            {{-- Cart Button --}}
            <button class="cart-btn" onclick="toggleCart()">
                <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                    <path d="M16 10a4 4 0 01-8 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Cart
                <span class="cart-count" id="cartCount" style="display:none">0</span>
            </button>
        </div>
    </div>

    {{-- ── CATEGORY FILTER PILLS ── --}}
    <div class="filter-row">
        <button class="filter-pill active" data-cat="all" onclick="filterCat(this,'all')">All</button>
        @foreach($categories as $cat)
            <button class="filter-pill" data-cat="{{ $cat }}" onclick="filterCat(this,'{{ $cat }}')">{{ $cat }}</button>
        @endforeach
    </div>

    {{-- ── PRODUCT GRID ── --}}
    <div class="product-grid" id="productGrid">
        @forelse($products as $p)
        <div class="product-card" data-name="{{ strtolower($p->product_name) }}" data-cat="{{ $p->category }}">

            {{-- Image --}}
            <div class="card-img-wrap">
                @if($p->image)
    <img src="{{ asset('storage/' . $p->image) }}"
         alt="{{ $p->product_name }}"
         onerror="this.style.display='none'; this.parentElement.querySelector('.card-img-placeholder') && (this.parentElement.querySelector('.card-img-placeholder').style.display='flex')">
                @else
                    <div class="card-img-placeholder">
                        <svg viewBox="0 0 48 48" fill="none" width="40" height="40" opacity=".3">
                            <rect x="4" y="4" width="40" height="40" rx="6" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17" cy="19" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 34l10-8 8 7 6-5 16 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                @endif
                {{-- Hidden fallback placeholder shown by onerror when image fails to load --}}
                @if($p->image)
                    <div class="card-img-placeholder" style="display:none">
                        <svg viewBox="0 0 48 48" fill="none" width="40" height="40" opacity=".3">
                            <rect x="4" y="4" width="40" height="40" rx="6" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17" cy="19" r="4" stroke="currentColor" stroke-width="2"/>
                            <path d="M4 34l10-8 8 7 6-5 16 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                @endif
                @if($p->status === 'available' && $p->quantity > 0)
                    <span class="badge-in">In Stock</span>
                @else
                    <span class="badge-out">Out of Stock</span>
                @endif
            </div>

            {{-- Info --}}
            <div class="card-body">
                @if($p->category)
                    <span class="card-cat">{{ $p->category }}</span>
                @endif
                <h3 class="card-name">{{ $p->product_name }}</h3>
                @if($p->description)
                    <p class="card-desc">{{ Str::limit($p->description, 80) }}</p>
                @endif

                <div class="card-footer">
                    <span class="card-price">₱{{ number_format($p->selling_price, 2) }}</span>
                    @if($p->status === 'available' && $p->quantity > 0)
                        <div class="qty-add-row">
                            <div class="qty-stepper">
                                <button class="qty-btn" onclick="changeQty({{ $p->product_id }}, -1)">−</button>
                                <span class="qty-val" id="qty-{{ $p->product_id }}">1</span>
                                <button class="qty-btn" onclick="changeQty({{ $p->product_id }}, 1)">+</button>
                            </div>
                            <button class="add-to-cart-btn"
                                    onclick="addToCart(
                                        {{ $p->product_id }},
                                        '{{ addslashes($p->product_name) }}',
                                        {{ $p->selling_price }},
                                        '{{ $p->image ? asset('storage/'.$p->image) : '' }}'
                                    )">
                                Add to Cart
                            </button>
                        </div>
                    @else
                        <button class="unavailable-btn" disabled>Unavailable</button>
                    @endif
                </div>
            </div>
        </div>
        @empty
            <div class="empty-state">
                <svg viewBox="0 0 64 64" fill="none" width="56" height="56" opacity=".25">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="2"/>
                    <path d="M20 32h24M32 20v24" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <p>No products found.</p>
            </div>
        @endforelse
    </div>

    {{-- ── NO RESULTS MESSAGE ── --}}
    <div class="no-results" id="noResults" style="display:none">
        <p>No products match your search.</p>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CART SLIDE-OVER PANEL
═══════════════════════════════════════════ --}}
<div class="cart-overlay" id="cartOverlay" onclick="closeCart(event)">
    <div class="cart-panel" id="cartPanel">
        <div class="cart-header">
            <h2>Your Cart</h2>
            <button class="cart-close" onclick="toggleCart()">✕</button>
        </div>

        <div class="cart-items" id="cartItems">
            <div class="cart-empty" id="cartEmpty">
                <svg viewBox="0 0 48 48" fill="none" width="44" height="44" opacity=".3">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <line x1="3" y1="6" x2="21" y2="6" stroke="currentColor" stroke-width="2"/>
                </svg>
                <p>Your cart is empty</p>
            </div>
        </div>

        <div class="cart-footer" id="cartFooter" style="display:none">
            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal (<span id="cartItemCount">0</span> items)</span>
                    <span id="cartSubtotal">₱0.00</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span id="cartTotal">₱0.00</span>
                </div>
            </div>
            <button class="checkout-btn" onclick="proceedCheckout()">
                Proceed to Checkout
                <svg viewBox="0 0 20 20" fill="none" width="16" height="16">
                    <path d="M4 10h12M10 4l6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            <button class="clear-cart-btn" onclick="clearCart()">Clear cart</button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════
     CHECKOUT MODAL
═══════════════════════════════════════════ --}}
<div class="modal-overlay" id="checkoutModal" onclick="closeCheckoutModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="closeCheckoutModal()">✕</button>
        <h2 class="modal-title">Order Summary</h2>

        {{-- Order items list --}}
        <div id="checkoutItems" class="checkout-items"></div>

        {{-- Totals --}}
        <div class="checkout-totals" id="checkoutTotals">
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="checkoutSubtotal">₱0.00</span>
            </div>
            <div class="summary-row gcash-fee-row" id="gcashFeeRow" style="display:none">
                <span>GCash Convenience Fee</span>
                <span>₱20.00</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span id="checkoutTotal">₱0.00</span>
            </div>
        </div>

        {{-- Pickup-only notice --}}
        <div class="pickup-notice">
            <svg viewBox="0 0 20 20" fill="none" width="16" height="16" style="flex-shrink:0;margin-top:2px">
                <circle cx="10" cy="10" r="8" stroke="currentColor" stroke-width="1.6"/>
                <path d="M10 6v4.5M10 13.5v.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
            <span>Products are available for <strong>pick-up only</strong> at our clinic. We will contact you to confirm your schedule.</span>
        </div>

        {{-- Payment method --}}
        <div class="payment-method-section">
            <p class="payment-label">Payment Method</p>
            <div class="payment-options">
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="cash" checked onchange="onPaymentChange()">
                    <span class="payment-option-box">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                            <rect x="2" y="6" width="20" height="13" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <circle cx="12" cy="12.5" r="2.5" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M6 9.5h.01M18 9.5h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                        Cash on Pick-up
                    </span>
                </label>
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="gcash" onchange="onPaymentChange()">
                    <span class="payment-option-box">
                        <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
                            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M3 9h18" stroke="currentColor" stroke-width="1.6"/>
                            <path d="M7 14h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                        </svg>
                        GCash
                    </span>
                </label>
            </div>
        </div>

        {{-- GCash payment details (shown only when GCash is selected) --}}
        <div class="gcash-details" id="gcashDetails" style="display:none">
            <div class="gcash-info-box">
                <svg viewBox="0 0 24 24" fill="none" width="32" height="32">
                    <rect x="3" y="5" width="18" height="14" rx="2" fill="#007bff" opacity=".12" stroke="#007bff" stroke-width="1.4"/>
                    <path d="M3 9h18" stroke="#007bff" stroke-width="1.4"/>
                    <path d="M7 14h4" stroke="#007bff" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <div class="gcash-info-text">
                    <p class="gcash-label">Send payment to:</p>
                    <p class="gcash-number">09165936995</p>
                    <p class="gcash-name">Cinderella Dianne Hoseña</p>
                    <p class="gcash-fee-note">Note: A ₱20.00 convenience fee is added for GCash payments.</p>
                </div>
            </div>

            <div class="gcash-fields">
                <div class="gcash-field">
                    <label for="gcashReference">GCash Reference Number <span class="required">*</span></label>
                    <input type="text" id="gcashReference" placeholder="e.g. 1234 5678 9012"
                           maxlength="30" class="gcash-input">
                    <span class="gcash-field-hint">Found in your GCash transaction receipt</span>
                </div>

                <div class="gcash-field">
                    <label>Payment Screenshot <span class="required">*</span></label>
                    <div class="proof-upload-area" id="proofUploadArea"
                         onclick="document.getElementById('gcashProofFile').click()">
                        {{-- File input lives INSIDE the form below; triggered via JS click --}}
                        <div class="proof-placeholder" id="proofPlaceholder">
                            <svg viewBox="0 0 24 24" fill="none" width="28" height="28" opacity=".4">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M17 8l-5-5-5 5M12 3v12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Tap to upload screenshot</span>
                            <span class="proof-hint">JPG, PNG up to 5MB</span>
                        </div>
                        <img id="proofPreview" class="proof-preview" src="" alt="Payment proof" style="display:none">
                    </div>
                </div>
            </div>
        </div>

        {{-- Note --}}
        <div class="checkout-note">
            <label for="orderNote">Note (optional)</label>
            <textarea id="orderNote" rows="2" placeholder="Any special instructions…"></textarea>
        </div>

        {{-- Form — multipart because of file upload --}}
        <form method="POST" action="{{ route('patient.order.place') }}"
              id="checkoutForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="items"          id="checkoutItemsInput">
            <input type="hidden" name="note"           id="checkoutNoteInput">
            <input type="hidden" name="payment_method" id="checkoutPaymentInput">
            <input type="hidden" name="reference"      id="checkoutReferenceInput">
            {{-- File input MUST be inside the form for multipart upload to work --}}
            <input type="file" id="gcashProofFile" name="payment_proof"
                   accept="image/*" style="display:none"
                   onchange="handleProofUpload(this)">
            <div id="confirmBtnWrap">
                <button type="submit" class="confirm-order-btn" id="confirmOrderBtn">
                    Confirm Order
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
/* ── STATE ───────────────────────────────────── */
let cart = JSON.parse(localStorage.getItem('skinmedic_cart') || '[]');
const GCASH_FEE = 20;

/* ── CLEAR CART ON SUCCESSFUL ORDER ─────────── */
@if(session('success'))
    localStorage.removeItem('skinmedic_cart');
    cart = [];
@endif

/* ── QTY STEPPER ─────────────────────────────── */
function changeQty(id, delta) {
    const el  = document.getElementById('qty-' + id);
    let   val = parseInt(el.textContent) + delta;
    if (val < 1) val = 1;
    if (val > 99) val = 99;
    el.textContent = val;
}

/* ── ADD TO CART ─────────────────────────────── */
function addToCart(id, name, price, image) {
    const qty      = parseInt(document.getElementById('qty-' + id).textContent);
    const existing = cart.find(i => i.id === id);
    if (existing) {
        existing.qty += qty;
    } else {
        cart.push({ id, name, price, image, qty });
    }
    saveCart();
    renderCart();
    showToast(name + ' added to cart!');
    document.getElementById('qty-' + id).textContent = 1;
}

/* ── SAVE / RENDER CART ──────────────────────── */
function saveCart() {
    localStorage.setItem('skinmedic_cart', JSON.stringify(cart));
    const total = cart.reduce((s, i) => s + i.qty, 0);
    const badge = document.getElementById('cartCount');
    badge.textContent = total;
    badge.style.display = total > 0 ? 'flex' : 'none';
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const empty     = document.getElementById('cartEmpty');
    const footer    = document.getElementById('cartFooter');

    if (!cart.length) {
        empty.style.display = 'flex';
        footer.style.display = 'none';
        container.innerHTML = '';
        container.appendChild(empty);
        return;
    }

    empty.style.display = 'none';
    footer.style.display = 'block';

    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    document.getElementById('cartSubtotal').textContent  = '₱' + subtotal.toFixed(2);
    document.getElementById('cartTotal').textContent     = '₱' + subtotal.toFixed(2);
    document.getElementById('cartItemCount').textContent = cart.reduce((s, i) => s + i.qty, 0);

    container.innerHTML = cart.map(item => `
        <div class="cart-item" data-id="${item.id}">
            <div class="ci-img-wrap">
                ${item.image
                    ? `<img src="${item.image}" class="ci-img" alt="${item.name}">`
                    : `<div class="ci-img-placeholder"></div>`}
            </div>
            <div class="ci-info">
                <p class="ci-name">${item.name}</p>
                <p class="ci-price">₱${item.price.toFixed(2)} each</p>
                <div class="ci-controls">
                    <div class="qty-stepper small">
                        <button class="qty-btn" onclick="updateCartQty(${item.id}, -1)">−</button>
                        <span class="qty-val">${item.qty}</span>
                        <button class="qty-btn" onclick="updateCartQty(${item.id}, 1)">+</button>
                    </div>
                    <span class="ci-subtotal">₱${(item.price * item.qty).toFixed(2)}</span>
                </div>
            </div>
            <button class="ci-remove" onclick="removeFromCart(${item.id})" title="Remove">✕</button>
        </div>
    `).join('');
}

function updateCartQty(id, delta) {
    const item = cart.find(i => i.id === id);
    if (!item) return;
    item.qty += delta;
    if (item.qty <= 0) cart = cart.filter(i => i.id !== id);
    saveCart();
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(i => i.id !== id);
    saveCart();
    renderCart();
}

function clearCart() {
    cart = [];
    saveCart();
    renderCart();
}

/* ── CART PANEL TOGGLE ───────────────────────── */
function toggleCart() {
    const overlay = document.getElementById('cartOverlay');
    const panel   = document.getElementById('cartPanel');
    const isOpen  = overlay.classList.contains('open');
    overlay.classList.toggle('open', !isOpen);
    panel.classList.toggle('open', !isOpen);
    if (!isOpen) renderCart();
}

function closeCart(e) {
    if (e.target === document.getElementById('cartOverlay')) toggleCart();
}

/* ── PAYMENT METHOD TOGGLE ───────────────────── */
function onPaymentChange() {
    const isGcash   = document.querySelector('input[name="payment_method"]:checked').value === 'gcash';
    const subtotal  = cart.reduce((s, i) => s + i.price * i.qty, 0);
    const total     = isGcash ? subtotal + GCASH_FEE : subtotal;

    document.getElementById('gcashDetails').style.display  = isGcash ? 'block' : 'none';
    document.getElementById('gcashFeeRow').style.display   = isGcash ? 'flex'  : 'none';
    document.getElementById('checkoutTotal').textContent   = '₱' + total.toFixed(2);

    // Require/unrequire GCash fields
    document.getElementById('gcashReference').required = isGcash;
    document.getElementById('gcashProofFile').required  = isGcash;
}

/* ── PROOF IMAGE UPLOAD PREVIEW ──────────────── */
function handleProofUpload(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const preview = document.getElementById('proofPreview');
        preview.src   = e.target.result;
        preview.style.display       = 'block';
        document.getElementById('proofPlaceholder').style.display = 'none';
        document.getElementById('proofUploadArea').classList.add('has-image');
    };
    reader.readAsDataURL(file);
}

/* ── CHECKOUT MODAL OPEN ─────────────────────── */
function proceedCheckout() {
    if (!cart.length) return;

    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    document.getElementById('checkoutSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('checkoutTotal').textContent    = '₱' + subtotal.toFixed(2);
    document.getElementById('gcashFeeRow').style.display    = 'none';
    document.getElementById('gcashDetails').style.display   = 'none';

    // Reset payment to cash
    document.querySelectorAll('input[name="payment_method"]').forEach(r => r.checked = r.value === 'cash');

    document.getElementById('checkoutItems').innerHTML = cart.map(item => `
        <div class="checkout-item-row">
            <span class="co-name">${item.name} <em>×${item.qty}</em></span>
            <span class="co-price">₱${(item.price * item.qty).toFixed(2)}</span>
        </div>
    `).join('');

    document.getElementById('checkoutModal').style.display = 'flex';
    toggleCart();
}

function closeCheckoutModal(e) {
    if (!e || e.target === document.getElementById('checkoutModal')) {
        document.getElementById('checkoutModal').style.display = 'none';
    }
}

/* ── FORM SUBMIT ─────────────────────────────── */
document.getElementById('checkoutForm').addEventListener('submit', async function (e) {
    e.preventDefault(); // Always prevent default — we use fetch instead

    const isGcash = document.querySelector('input[name="payment_method"]:checked').value === 'gcash';

    // Validate GCash fields
    if (isGcash) {
        const ref   = document.getElementById('gcashReference').value.trim();
        const proof = document.getElementById('gcashProofFile').files[0];
        if (!ref) {
            showToast('Please enter your GCash reference number.');
            document.getElementById('gcashReference').focus();
            return;
        }
        if (!proof) {
            showToast('Please upload your GCash payment screenshot.');
            return;
        }
    }

    // Disable button to prevent double submit
    const btn = document.getElementById('confirmOrderBtn');
    btn.disabled    = true;
    btn.textContent = 'Placing Order…';

    // Build FormData
    const fd = new FormData();
    fd.append('_token',         document.querySelector('input[name="_token"]').value);
    fd.append('items',          JSON.stringify(cart.map(i => ({ id: i.id, name: i.name, price: i.price, qty: i.qty }))));
    fd.append('note',           document.getElementById('orderNote').value);
    fd.append('payment_method', isGcash ? 'gcash' : 'cash');
    fd.append('reference',      isGcash ? document.getElementById('gcashReference').value.trim() : '');

    if (isGcash) {
        const proofFile = document.getElementById('gcashProofFile').files[0];
        if (proofFile) fd.append('payment_proof', proofFile);
    }

    try {
        const response = await fetch('{{ route("patient.order.place") }}', {
            method: 'POST',
            body:   fd,
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        const data = await response.json();

        if (data.success) {
            // Clear cart
            localStorage.removeItem('skinmedic_cart');
            cart = [];
            saveCart();
            // Redirect to orders page with success
            window.location.href = data.redirect;
        } else {
            showToast(data.message || 'Something went wrong. Please try again.');
            btn.disabled    = false;
            btn.textContent = 'Confirm Order';
        }
    } catch (err) {
        showToast('Network error. Please try again.');
        btn.disabled    = false;
        btn.textContent = 'Confirm Order';
    }
});

/* ── SEARCH ──────────────────────────────────── */
document.getElementById('searchInput').addEventListener('input', function () {
    applyFilters();
});

/* ── CATEGORY FILTER ─────────────────────────── */
let activeCategory = 'all';
function filterCat(btn, cat) {
    document.querySelectorAll('.filter-pill').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    activeCategory = cat;
    applyFilters();
}

function applyFilters() {
    const query = document.getElementById('searchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.product-card');
    let visible = 0;

    cards.forEach(card => {
        const nameMatch = card.dataset.name.includes(query);
        const catMatch  = activeCategory === 'all' || card.dataset.cat === activeCategory;
        const show      = nameMatch && catMatch;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

/* ── TOAST ───────────────────────────────────── */
function showToast(msg) {
    let toast = document.getElementById('shopToast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'shopToast';
        toast.className = 'shop-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
}

/* ── INIT ────────────────────────────────────── */
saveCart();
</script>
@endpush

@endsection