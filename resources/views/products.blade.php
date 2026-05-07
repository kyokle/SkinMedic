<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Skin Medic — All Products</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('asset/css/index.css') }}">
</head>
<body>

  <aside class="sidebar">
    <div class="logo-wrap">
      <img src="{{ asset('asset/image/skintransparent.png') }}" alt="Skin Medic">
    </div>
    <nav class="menu">
      <a href="{{ url('/') }}#book-appointment">Book Appointment</a>
      <a href="{{ url('/') }}#ar-skin-analysis">AR Skin Analysis</a>
      <a href="{{ url('/') }}#treatments">Treatment and Services</a>
      <a href="{{ url('/') }}#shop" class="active">Products</a>
      <a href="{{ url('/') }}#reviews">Reviews</a>
      <a href="{{ url('/') }}#locations">Location</a>
    </nav>
  </aside>

  <main class="main-content">
    <header class="topbar">
      <div class="top-actions"></div>
      <a class="login-pill" href="{{ url('/') }}?login=true">Log in / Sign up</a>
    </header>

    <section class="section">
      <div class="section-header">
        <p class="kicker">OUR PRODUCTS</p>
        <h2 class="section-title">All Products</h2>
        <p class="section-sub">Browse our complete collection of skincare products</p>
      </div>

      <div class="shop-grid">
        @if($products->count() > 0)
          @foreach($products as $product)
            <article class="shop-card">
              <div class="shop-image">
                <img src="{{ str_starts_with($product->image, 'http') ? $product->image : asset('uploads/' . $product->image) }}"
                     alt="{{ $product->product_name }}"
                     onerror="this.src='https://i.ibb.co/2s7sG4v/placeholder.png'"/>
              </div>
              <h3>{{ $product->product_name }}</h3>
              <p class="price">₱{{ number_format($product->selling_price, 2) }}</p>
            </article>
          @endforeach
        @else
          <p style="text-align:center; color:#666; grid-column:1/-1;">No products available.</p>
        @endif
      </div>

      <div class="center-btn" style="margin-top:32px;">
        <a class="view-all" href="{{ url('/') }}#shop">← Back to Home</a>
      </div>
    </section>
  </main>

</body>
</html>