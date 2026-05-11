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
    <img src="{{ $p->image }}"
         alt="{{ $p->product_name }}"
         onerror="this.src='{{ asset('uploads/default.png') }}'">
                @else
                    <div class="card-img-placeholder">
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

        <div id="checkoutItems" class="checkout-items"></div>

        <div class="checkout-totals">
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="checkoutSubtotal">₱0.00</span>
            </div>
            <div class="summary-row summary-total">
                <span>Total</span>
                <span id="checkoutTotal">₱0.00</span>
            </div>
        </div>

        <div class="checkout-note">
            <label for="orderNote">Note (optional)</label>
            <textarea id="orderNote" rows="2" placeholder="Any special instructions…"></textarea>
        </div>

        <form method="POST" action="{{ route('patient.order.place') }}" id="checkoutForm">
            @csrf
            <input type="hidden" name="items" id="checkoutItemsInput">
            <input type="hidden" name="note"  id="checkoutNoteInput">
            <button type="submit" class="confirm-order-btn" onclick="prepareCheckout()">
                Confirm Order
            </button>
        </form>
    </div>
</div>

@push('scripts')
<script>
/* ── STATE ───────────────────────────────────── */
let cart = JSON.parse(localStorage.getItem('skinmedic_cart') || '[]');

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
    const qty   = parseInt(document.getElementById('qty-' + id).textContent);
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
    document.getElementById('cartSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('cartTotal').textContent     = '₱' + subtotal.toFixed(2);
    document.getElementById('cartItemCount').textContent  = cart.reduce((s, i) => s + i.qty, 0);

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
    if (item.qty <= 0) {
        cart = cart.filter(i => i.id !== id);
    }
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

/* ── CHECKOUT ────────────────────────────────── */
function proceedCheckout() {
    if (!cart.length) return;
    const subtotal = cart.reduce((s, i) => s + i.price * i.qty, 0);
    document.getElementById('checkoutSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('checkoutTotal').textContent     = '₱' + subtotal.toFixed(2);
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

function prepareCheckout() {
    document.getElementById('checkoutItemsInput').value = JSON.stringify(cart);
    document.getElementById('checkoutNoteInput').value  = document.getElementById('orderNote').value;
}

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
    setTimeout(() => toast.classList.remove('show'), 2500);
}

/* ── INIT ────────────────────────────────────── */
saveCart();
</script>
@endpush

@endsection