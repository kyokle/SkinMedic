{{-- resources/views/admin_products.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Product Management - SkinMedic</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('asset/css/admin_products.css') }}">
</head>
<body style="background:#f8f8f8;">

  @if(session('role') === 'admin')
    @include('partials.sidebar_admin')
  @elseif(session('role') === 'staff')
    @include('partials.sidebar_staff')
  @else
    @include('partials.sidebar_doctor')
  @endif

  <main class="content">
    <header class="header">
      <h2>Product Management</h2>
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
          <p>Today's Date</p>
          <strong>{{ now()->format('Y-m-d') }}</strong><br>
          <button class="add-product-btn" onclick="openModal()">+ Add Product</button>
        </div>
        @include('partials.notif_bell_admin')
      </div>
    </header>

    {{-- ── Validation errors ── --}}
    @if($errors->any())
    <div class="validation-error-box" id="validationErrors">
        <strong>⚠ Please fix the following before saving:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── Filter bar ── --}}
    <div class="filter-bar">
      <div class="product-search-wrap">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
        </svg>
        <input
          type="text"
          id="productSearch"
          class="product-search"
          placeholder="Search product name…"
          oninput="applyFilters()"
          autocomplete="off"
        >
      </div>

      <div class="category-tabs" id="categoryTabs">
        <button class="cat-tab active" data-cat="all" onclick="selectCat(this)">All</button>
        @php
          $categories = $products->pluck('category')->filter()->unique()->sort()->values();
        @endphp
        @foreach($categories as $cat)
          <button class="cat-tab" data-cat="{{ strtolower($cat) }}" onclick="selectCat(this)">
            {{ $cat }}
          </button>
        @endforeach
      </div>

      <span class="filter-count" id="productCount"></span>
    </div>

    {{-- ── Product grid ── --}}
    <div class="product-list" id="productGrid">
      @forelse ($products as $row)
        @php $statusClass = strtolower($row->status) === 'available' ? 'on' : 'off'; @endphp
        <div class="product-card"
             data-name="{{ strtolower($row->product_name) }}"
             data-category="{{ strtolower($row->category ?? '') }}">
          <img src="{{ $row->image }}"
               alt="{{ $row->product_name }}"
               onerror="this.src='{{ asset('uploads/default.png') }}'">
          <h3>{{ $row->product_name }}</h3>
          <p>{{ $row->description }}</p>
          <p><strong>₱{{ $row->selling_price }}</strong></p>
          <p class="status {{ $statusClass }}">Status: {{ $row->status }}</p>
          @if($row->category)
            <p class="category-badge">
              <span>{{ $row->category }}</span>
            </p>
          @endif
          <div class="action-buttons">
            <button class="edit-btn" onclick="openEditModal(
              '{{ $row->product_id }}',
              '{{ addslashes($row->product_name) }}',
              '{{ addslashes($row->description) }}',
              '{{ addslashes($row->category) }}',
              '{{ addslashes($row->brand) }}',
              '{{ addslashes($row->supplier) }}',
              '{{ addslashes($row->batch_number) }}',
              '{{ $row->cost_price }}',
              '{{ $row->selling_price }}',
              '{{ addslashes($row->storage_location) }}',
              '{{ $row->status }}'
            )">✏ Edit</button>

            <form method="POST" action="{{ route('admin.products.delete') }}" style="display:inline;"
                  onsubmit="return confirm('Delete this product?');">
              @csrf
              <input type="hidden" name="product_id" value="{{ $row->product_id }}">
              <button type="submit" class="delete-btn">🗑 Delete</button>
            </form>
          </div>
        </div>
      @empty
        <p style="text-align:center;color:#666;">No products added yet.</p>
      @endforelse

      <p id="noProductsMsg" class="no-products-msg" style="display:none;">
        No products match your search.
      </p>
    </div>
  </main>

  {{-- ADD PRODUCT MODAL --}}
  <div id="addProductModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add Product</h2>
        <span class="close-btn" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('admin.products.add') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_form" value="add">
          <label>Product Name</label>
          <input type="text" name="product_name" required>
          <label>Description</label>
          <textarea name="description" rows="3" required></textarea>
          <label>Category</label>
          <input type="text" name="category">
          <label>Brand</label>
          <input type="text" name="brand">
          <label>Supplier</label>
          <input type="text" name="supplier">
          <label>Batch Number</label>
          <input type="text" name="batch_number">
          <label>Quantity</label>
          <input type="number" name="quantity" min="0">
          <label>Reorder Level</label>
          <input type="number" name="reorder_level" min="0">
          <label>Cost Price <span class="req">*</span></label>
          <input type="number" step="0.01" name="cost_price" min="0.01" required
                 placeholder="e.g. 150.00"
                 class="{{ $errors->has('cost_price') ? 'input-error' : '' }}"
                 value="{{ old('cost_price') }}">
          @error('cost_price')
              <span class="field-error">{{ $message }}</span>
          @enderror

          <label>Selling Price <span class="req">*</span></label>
          <input type="number" step="0.01" name="selling_price" min="0.01" required
                 placeholder="e.g. 250.00"
                 class="{{ $errors->has('selling_price') ? 'input-error' : '' }}"
                 value="{{ old('selling_price') }}">
          @error('selling_price')
              <span class="field-error">{{ $message }}</span>
          @enderror

          <label>Expiry Date <span class="req">*</span></label>
          <input type="date" name="expiry_date" required
                 min="{{ now()->toDateString() }}"
                 class="{{ $errors->has('expiry_date') ? 'input-error' : '' }}"
                 value="{{ old('expiry_date') }}">
          @error('expiry_date')
              <span class="field-error">{{ $message }}</span>
          @enderror
          <label>Storage Location</label>
          <input type="text" name="storage_location">
          <label>Status</label>
          <select name="status">
            <option value="available">Available</option>
            <option value="not available">Not Available</option>
          </select>
          <label>Product Image</label>
          <input type="file" name="image" accept="image/*">
          <button type="submit">Add Product</button>
        </form>
      </div>
    </div>
  </div>

  {{-- EDIT PRODUCT MODAL --}}
  <div id="editProductModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Product</h2>
        <span class="close-btn" onclick="closeEditModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ route('admin.products.update') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="_form" value="edit">
          <input type="hidden" name="product_id" id="edit_id">
          <label>Product Name</label>
          <input type="text" name="product_name" id="edit_name" required>
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="3" required></textarea>
          <label>Category</label>
          <input type="text" name="category" id="edit_category">
          <label>Brand</label>
          <input type="text" name="brand" id="edit_brand">
          <label>Supplier</label>
          <input type="text" name="supplier" id="edit_supplier">
          <label>Batch Number</label>
          <input type="text" name="batch_number" id="edit_batch">
          <label>Cost Price <span class="req">*</span></label>
          <input type="number" step="0.01" name="cost_price" id="edit_cost" min="0.01" required
                 placeholder="e.g. 150.00">
          @error('cost_price')
              <span class="field-error">{{ $message }}</span>
          @enderror

          <label>Selling Price <span class="req">*</span></label>
          <input type="number" step="0.01" name="selling_price" id="edit_selling" min="0.01" required
                 placeholder="e.g. 250.00">
          @error('selling_price')
              <span class="field-error">{{ $message }}</span>
          @enderror
          <label>Storage Location</label>
          <input type="text" name="storage_location" id="edit_location">
          <label>Status</label>
          <select name="status" id="edit_status">
            <option value="available">Available</option>
            <option value="not available">Not Available</option>
          </select>
          <label>Change Image (optional)</label>
          <input type="file" name="image" accept="image/*">
          <button type="submit">Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <script>
  /* ── Modal helpers ── */
  const addModal  = document.getElementById('addProductModal');
  const editModal = document.getElementById('editProductModal');
  function openModal()      { addModal.style.display  = 'flex'; }
  function closeModal()     { addModal.style.display  = 'none'; }
  function closeEditModal() { editModal.style.display = 'none'; }
  function openEditModal(id, name, desc, category, brand, supplier, batch, cost, selling, location, status) {
    document.getElementById('edit_id').value          = id;
    document.getElementById('edit_name').value        = name;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_category').value    = category;
    document.getElementById('edit_brand').value       = brand;
    document.getElementById('edit_supplier').value    = supplier;
    document.getElementById('edit_batch').value       = batch;
    document.getElementById('edit_cost').value        = cost;
    document.getElementById('edit_selling').value     = selling;
    document.getElementById('edit_location').value    = location;
    document.getElementById('edit_status').value      = status;
    editModal.style.display = 'flex';
  }
  window.onclick = function(e) {
    if (e.target === addModal)  closeModal();
    if (e.target === editModal) closeEditModal();
  };

  /* ── Re-open modal if server returned validation errors ── */
  @if($errors->has('cost_price') || $errors->has('selling_price') || $errors->has('expiry_date') || $errors->has('product_name'))
    @if(old('_form') === 'edit')
      document.addEventListener('DOMContentLoaded', () => editModal.style.display = 'flex');
    @else
      document.addEventListener('DOMContentLoaded', () => addModal.style.display  = 'flex');
    @endif
  @endif

  /* ── Client-side price + expiry guard ── */
  function validatePrices(formEl) {
      const cost    = parseFloat(formEl.querySelector('[name="cost_price"]')?.value);
      const selling = parseFloat(formEl.querySelector('[name="selling_price"]')?.value);
      const expiry  = formEl.querySelector('[name="expiry_date"]');
      const today   = new Date().toISOString().split('T')[0];
      const msgs    = [];

      if (isNaN(cost)    || cost    <= 0) msgs.push('Cost price must be greater than zero.');
      if (isNaN(selling) || selling <= 0) msgs.push('Selling price must be greater than zero.');
      if (expiry && (!expiry.value || expiry.value < today)) {
          msgs.push('Expiry date must be today or a future date.');
      }

      if (msgs.length) {
          alert('⚠ Cannot save:\n\n• ' + msgs.join('\n• '));
          return false;
      }
      return true;
  }

  /* ── Attach to both forms ── */
  document.addEventListener('DOMContentLoaded', () => {
      document.querySelector('#addProductModal form')
          ?.addEventListener('submit', function(e) {
              if (!validatePrices(this)) e.preventDefault();
          });
      document.querySelector('#editProductModal form')
          ?.addEventListener('submit', function(e) {
              if (!validatePrices(this)) e.preventDefault();
          });
  });

  /* ── Filter logic ── */
  let activeCat = 'all';

  function selectCat(btn) {
    document.querySelectorAll('.cat-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    activeCat = btn.getAttribute('data-cat');
    applyFilters();
  }

  function applyFilters() {
    const query = document.getElementById('productSearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('#productGrid .product-card');
    let visible = 0;

    cards.forEach(card => {
      const name = card.getAttribute('data-name');
      const cat  = card.getAttribute('data-category');

      const matchesSearch = !query || name.includes(query);
      const matchesCat    = activeCat === 'all' || cat === activeCat;

      const show = matchesSearch && matchesCat;
      card.style.display = show ? '' : 'none';
      if (show) visible++;
    });

    const noMsg = document.getElementById('noProductsMsg');
    noMsg.style.display = visible === 0 ? 'block' : 'none';

    const countEl = document.getElementById('productCount');
    const isFiltering = query || activeCat !== 'all';
    countEl.textContent = isFiltering ? `${visible} of ${cards.length} product(s)` : '';
  }
  </script>
</body>
</html>