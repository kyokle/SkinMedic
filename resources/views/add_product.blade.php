<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Add Product - SkinMedic</title>
    <link rel="stylesheet" href="{{ asset('asset/css/add_product.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
</head>
<body style="background:#fff;">

    <a href="{{ route('product.management') }}" style="text-decoration:none; color:#80a833; font-weight:bold;">
        ← Back to Products
    </a>

    <main class="content">
        <header class="header">
            <h2>Add New Product</h2>
            <div class="date-box">
                <p>Today's Date</p>
                <strong>{{ now()->toDateString() }}</strong>
            </div>
        </header>

        <section class="add-product-container">
            <h2>Product Details</h2>

            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">
                @csrf

                <label>Product Name</label>
                <input type="text" name="product_name" required>

                <label>Description</label>
                <textarea name="description" rows="4" required></textarea>

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

                <label>Upload Image</label>
                <input type="file" name="image" accept="image/*">

                <button type="submit">Add Product</button>
            </form>
        </section>
    </main>

</body>
</html>