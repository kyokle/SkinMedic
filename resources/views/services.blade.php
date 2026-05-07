<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Skin Medic — All Treatments</title>
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
      <a href="{{ url('/') }}#treatments" class="active">Treatment and Services</a>
      <a href="{{ url('/') }}#shop">Products</a>
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
        <p class="kicker">OUR EXPERTISE</p>
        <h2 class="section-title">All Treatments & Services</h2>
        <p class="section-sub">Discover our complete range of transformative treatments</p>
      </div>

      <div class="treatments-grid">
        @if($services->count() > 0)
          @foreach($services as $service)
            <article class="treatment-card">
              <div class="treatment-image">
                <img src="{{ str_starts_with($service->image, 'http') ? $service->image : asset('uploads/' . $service->image) }}"
                     alt="{{ $service->name }}"
                     onerror="this.src='https://i.ibb.co/2s7sG4v/placeholder.png'"/>
              </div>
              <h3>{{ $service->name }}</h3>
              <p>{{ $service->description }}</p>
              <p class="price">₱{{ number_format($service->price, 2) }}</p>
            </article>
          @endforeach
        @else
          <p style="text-align:center; color:#666; grid-column:1/-1;">No services found.</p>
        @endif
      </div>

      <div class="center-btn" style="margin-top:32px;">
        <a class="view-all" href="{{ url('/') }}#treatments">← Back to Home</a>
      </div>
    </section>
  </main>

</body>
</html>