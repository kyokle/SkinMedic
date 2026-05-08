{{-- resources/views/patient/patient_products.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Product Catalog - SkinMedic</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('asset/css/patient_products.css') }}">
</head>
<body>

  @include('partials.sidebar_patient')

  <main class="content">

    <header class="header">
      <div>
        <h2>Product Catalog</h2>
        <p class="header-sub">Browse our available skincare products</p>
      </div>
      <div style="display:flex;align-items:center;gap:14px;">
        <div class="date-box">
          <p>Today's Date</p>
          <strong>{{ now()->format('Y-m-d') }}</strong>
        </div>
        @include('partials.notif_bell_staff')
      </div>
    </header>

    {{-- FILTER BAR --}}
    <div class="filter-bar">
      <button class="filter-btn active" data-category="all">All</button>
      @foreach($categories as $cat)
        <button class="filter-btn" data-category="{{ strtolower($cat) }}">{{ $cat }}</button>
      @endforeach
    </div>

    {{-- SEARCH --}}
    <div class="search-wrap">
      <input type="text" id="searchInput" placeholder="Search products..." class="search-input">
    </div>

    {{-- PRODUCT GRID --}}
    <div class="product-list" id="productGrid">
      @forelse ($products as $row)
        @php
          $isAvailable = strtolower($row->status) === 'available';
          $statusClass  = $isAvailable ? 'on' : 'off';
        @endphp
        <div class="product-card {{ $isAvailable ? '' : 'unavailable' }}"
             data-category="{{ strtolower($row->category ?? 'other') }}"
             data-name="{{ strtolower($row->product_name) }}">

          <div class="card-image-wrap">
            <img src="{{ $row->image }}"
                 alt="{{ $row->product_name }}"
                 onerror="this.src='{{ asset('uploads/default.png') }}'">
            @if(!$isAvailable)
              <div class="unavailable-overlay">
                <span>Not Available</span>
              </div>
            @endif
          </div>

          <div class="card-body">
            <span class="category-tag">{{ $row->category ?? 'General' }}</span>
            <h3>{{ $row->product_name }}</h3>
            <p class="description">{{ $row->description }}</p>
            <div class="card-footer">
              <strong class="price">₱{{ number_format($row->selling_price, 2) }}</strong>
              <span class="status-badge {{ $statusClass }}">
                {{ $isAvailable ? '● Available' : '○ Unavailable' }}
              </span>
            </div>
          </div>

        </div>
      @empty
        <div class="empty-state">
          <p>No products available at the moment.</p>
        </div>
      @endforelse
    </div>

    <p id="noResults" style="display:none; text-align:center; color:#666; margin-top:40px;">
      No products match your search.
    </p>

  </main>

  <script>
    const filterBtns   = document.querySelectorAll('.filter-btn');
    const cards        = document.querySelectorAll('.product-card');
    const searchInput  = document.getElementById('searchInput');
    const noResults    = document.getElementById('noResults');
    let activeCategory = 'all';

    function applyFilters() {
      const query = searchInput.value.trim().toLowerCase();
      let visible = 0;
      cards.forEach(card => {
        const matchCat  = activeCategory === 'all' || card.dataset.category === activeCategory;
        const matchName = card.dataset.name.includes(query);
        const show      = matchCat && matchName;
        card.style.display = show ? '' : 'none';
        if (show) visible++;
      });
      noResults.style.display = visible === 0 ? 'block' : 'none';
    }

    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        filterBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        activeCategory = btn.dataset.category;
        applyFilters();
      });
    });

    searchInput.addEventListener('input', applyFilters);
  </script>

</body>
</html>