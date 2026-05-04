{{-- resources/views/admin/admin_services.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin Service Management - SkinMedic</title>
  <link rel="stylesheet" href="{{ asset('/asset/css/admin_services.css') }}">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background:#f8f8f8;">

  @if(session('role') === 'admin')
    @include('partials.sidebar_admin')
  @else
    @include('partials.sidebar_staff')
  @endif

  <main class="content">
    <header class="header">
    <h2>Admin Service Management</h2>
    <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->format('Y-m-d') }}</strong><br>
            <button class="add-service-btn" onclick="openModal()">+ Add New Service</button>
        </div>
        @include('partials.notif_bell_admin')
    </div>
</header>

    <div class="service-list">
      @forelse ($services as $row)
        @php $statusClass = $row->status === 'available' ? 'on' : 'off'; @endphp
        <div class="service-card">
          <img src="{{ asset('uploads/' . $row->image) }}"
               alt="{{ $row->name }}"
               onerror="this.src='{{ asset('uploads/default.png') }}'">
          <h3>{{ $row->name }}</h3>
          <p>{{ $row->description }}</p>
          <p><strong>₱{{ $row->price }}</strong></p>
          <p class="status {{ $statusClass }}">Status: {{ $row->status }}</p>
          <div class="action-buttons">
            <button class="edit-btn" onclick="openEditModal(
              '{{ $row->service_id }}',
              '{{ addslashes($row->name) }}',
              '{{ addslashes($row->description) }}',
              '{{ $row->price }}',
              '{{ $row->status }}'
            )">✏ Edit</button>

            <form method="POST" action="{{ url('admin/services/delete') }}" style="display:inline;"
                  onsubmit="return confirm('Delete this service?');">
              @csrf
              <input type="hidden" name="service_id" value="{{ $row->service_id }}">
              <button type="submit" class="delete-btn">🗑 Delete</button>
            </form>
          </div>
        </div>
      @empty
        <p style="text-align:center;color:#666;">No services added yet.</p>
      @endforelse
    </div>
  </main>

  {{-- ADD SERVICE MODAL --}}
  <div id="addServiceModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Add New Service</h2>
        <span class="close-btn" onclick="closeModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ url('admin/services/add') }}" enctype="multipart/form-data">
          @csrf
          <label>Service Name</label>
          <input type="text" name="name" required>
          <label>Description</label>
          <textarea name="description" rows="4" required></textarea>
          <label>Price</label>
          <input type="number" name="price" step="0.01" required>
          <label>Upload Image</label>
          <input type="file" name="image" accept="image/*">
          <label>Status</label>
          <select name="status">
            <option value="available">Available</option>
            <option value="not available">Not Available</option>
          </select>
          <label>Products Used in This Service</label>
          @foreach($products as $p)
            <div class="product-check">
              <input type="checkbox" name="products[]" value="{{ $p->product_id }}" id="p_{{ $p->product_id }}">
              <label for="p_{{ $p->product_id }}">{{ $p->product_name }}</label>
              Qty: <input type="number" name="qty_{{ $p->product_id }}" min="1" value="1" style="width:60px;">
            </div>
          @endforeach
          <button type="submit">Add Service</button>
        </form>
      </div>
    </div>
  </div>

  {{-- EDIT SERVICE MODAL --}}
  <div id="editServiceModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Edit Service</h2>
        <span class="close-btn" onclick="closeEditModal()">&times;</span>
      </div>
      <div class="modal-body">
        <form method="POST" action="{{ url('admin/services/update') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="service_id" id="edit_id">
          <label>Service Name</label>
          <input type="text" name="name" id="edit_name" required>
          <label>Description</label>
          <textarea name="description" id="edit_description" rows="4" required></textarea>
          <label>Price</label>
          <input type="number" name="price" id="edit_price" step="0.01" required>
          <label>Change Image (optional)</label>
          <input type="file" name="image" accept="image/*">
          <label>Status</label>
          <select name="status" id="edit_status">
            <option value="available">Available</option>
            <option value="not available">Not Available</option>
          </select>
          <button type="submit">Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <script>
  const modal     = document.getElementById('addServiceModal');
  const editModal = document.getElementById('editServiceModal');
  function openModal()      { modal.style.display     = 'flex'; }
  function closeModal()     { modal.style.display     = 'none'; }
  function closeEditModal() { editModal.style.display = 'none'; }
  function openEditModal(id, name, desc, price, status) {
    document.getElementById('edit_id').value          = id;
    document.getElementById('edit_name').value        = name;
    document.getElementById('edit_description').value = desc;
    document.getElementById('edit_price').value       = price;
    document.getElementById('edit_status').value      = status;
    editModal.style.display = 'flex';
  }
  window.onclick = function(e) {
    if (e.target === modal)     closeModal();
    if (e.target === editModal) closeEditModal();
  };
  </script>
</body>
</html>