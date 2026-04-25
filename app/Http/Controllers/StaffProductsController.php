<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class StaffProductsController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $products = collect(DB::select("SELECT * FROM products"));
        return view('staff_products', array_merge(
            $this->sidebarData(),
            compact('products')
            ));
    }

    public function store(Request $request)
    {
        $name            = $request->input('product_name');
        $desc            = $request->input('description');
        $category        = $request->input('category');
        $brand           = $request->input('brand');
        $supplier        = $request->input('supplier');
        $batchNumber     = $request->input('batch_number');
        $quantity        = (int) $request->input('quantity');
        $reorderLevel    = (int) $request->input('reorder_level');
        $costPrice       = (float) $request->input('cost_price');
        $sellingPrice    = (float) $request->input('selling_price');
        $expiryDate      = $request->input('expiry_date');
        $storageLocation = $request->input('storage_location');
        $status          = $request->input('status');

        $imgName = 'default.png';
if ($request->hasFile('image') && $request->file('image')->isValid()) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgName = $uploaded['secure_url'];
}

        DB::insert("
            INSERT INTO products
                (product_name, description, category, brand, supplier, batch_number, quantity, reorder_level,
                 cost_price, selling_price, storage_location, status, date_added, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ", [
            $name, $desc, $category, $brand, $supplier, $batchNumber,
            $quantity, $reorderLevel, $costPrice, $sellingPrice,
            $storageLocation, $status, $imgName,
        ]);

        $productId = DB::getPdo()->lastInsertId();

        // Create first inventory log (IN) for the initial stock
        DB::insert(
            "INSERT INTO inventory_logs (product_id, quantity, type, expiry_date, created_at)
             VALUES (?, ?, 'IN', ?, NOW())",
            [$productId, $quantity, $expiryDate]
        );

        return redirect()->route('staff.products');
    }

    /* =========================================
       UPDATE — edit a product (no qty/expiry change here)
       PUT /staff/products/{id}
    ========================================= */
    public function update(Request $request)
    {
        $productId       = (int) $request->input('product_id');
        $name            = $request->input('product_name');
        $desc            = $request->input('description');
        $category        = $request->input('category');
        $brand           = $request->input('brand');
        $supplier        = $request->input('supplier');
        $batchNumber     = $request->input('batch_number');
        $costPrice       = (float) $request->input('cost_price');
        $sellingPrice    = (float) $request->input('selling_price');
        $storageLocation = $request->input('storage_location');
        $status          = $request->input('status');

        DB::update("
            UPDATE products
            SET product_name=?, description=?, category=?, brand=?, supplier=?, batch_number=?,
                cost_price=?, selling_price=?, storage_location=?, status=?
            WHERE product_id=?
        ", [
            $name, $desc, $category, $brand, $supplier, $batchNumber,
            $costPrice, $sellingPrice, $storageLocation, $status, $productId,
        ]);

        return redirect()->route('staff.products');
    }

    /* =========================================
       DELETE — remove a product and its logs
       DELETE /staff/products/{id}
    ========================================= */
    public function delete(Request $request)
    {
        $productId = (int) $request->input('product_id');
        DB::delete("DELETE FROM inventory_logs WHERE product_id = ?", [$productId]);
        DB::delete("DELETE FROM products WHERE product_id = ?", [$productId]);

        return redirect()->route('staff.products');
    }
}