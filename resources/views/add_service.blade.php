<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/add_service.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background: #fff;">

    <a href="{{ url('doctor/services') }}" style="text-decoration:none; color:#008080; font-weight:bold;">
        ← Back to Services
    </a>

    <main class="content">
        <header class="header">
            <h2>Add New Service</h2>
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
        </header>

        <section class="add-service-container">
            <h2>Add Service Details</h2>

            <form method="POST" action="{{ route('services.store') }}" enctype="multipart/form-data">
                @csrf

                <label for="name">Service Name</label>
                <input type="text" name="name" id="name" placeholder="Enter service name" required>

                <label for="description">Description</label>
                <textarea name="description" id="description" rows="4" placeholder="Write a short description..." required></textarea>

                <label for="image">Upload Image</label>
                <input type="file" name="image" id="image" accept="image/*">

                <label for="status">Status</label>
                <select name="status" id="status">
                    <option value="available">Available</option>
                    <option value="not available">Not Available</option>
                </select>

                <button type="submit">Add Service</button>
            </form>
        </section>
    </main>

    <script src="{{ asset('js/script.js') }}"></script>
</body>
</html>