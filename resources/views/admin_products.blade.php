{{-- resources/views/admin/admin_products.blade.php --}}
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
  @else
    @include('partials.sidebar_staff')
  @endif

  <main class="content">
    <header class="header">
      <h2>Product Management</h2>
      <div class="date-box">
        <p>Today's Date</p>
        <strong>{{ now()->format('Y-m-d') }}</strong><br>
        <button class="add-product-btn" onclick="openModal()">+ Add Product</button>
      </div>
    </header>

    <div class="product-list">
      @forelse ($products as $row)
        @php $statusClass = strtolower($row->status) === 'available' ? 'on' : 'off'; @endphp
        <div class="product-card">
          <img src="{{ asset('uploads/' . $row->image) }}"
               alt="{{ $row->product_name }}"
               onerror="this.src='{{ asset('uploads/default.png') }}'">
          <h3>{{ $row->product_name }}</h3>
          <p>{{ $row->description }}</p>
          <p><strong>₱{{ $row->selling_price }}</strong></p>
          <p class="status {{ $statusClass }}">Status: {{ $row->status }}</p>
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

            <form method="POST" action="{{ url('admin/products/delete') }}" style="display:inline;"
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
        <form method="POST" action="{{ url('admin/products/add') }}" enctype="multipart/form-data">
          @csrf
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
          <label>Cost Price</label>
          <input type="number" step="0.01" name="cost_price">
          <label>Selling Price</label>
          <input type="number" step="0.01" name="selling_price">
          <label>Expiry Date</label>
          <input type="date" name="expiry_date">
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
        <form method="POST" action="{{ url('admin/products/update') }}" enctype="multipart/form-data">
          @csrf
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
          <label>Cost Price</label>
          <input type="number" step="0.01" name="cost_price" id="edit_cost">
          <label>Selling Price</label>
          <input type="number" step="0.01" name="selling_price" id="edit_selling">
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
  </script>
</body>
</html>