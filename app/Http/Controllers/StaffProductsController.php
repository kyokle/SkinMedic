<?php
// app/Http/Controllers/Admin/AdminProductsController.php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class StaffProductsController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────
    // GET  /admin/products
    // ─────────────────────────────────────────
    public function index()
    {
        $products = DB::table('products')->get();
        return view('admin_products', array_merge(
            $this->sidebarData(),
            compact('products')
        ));
    }

    // ─────────────────────────────────────────
    // POST /admin/products/add
    // ─────────────────────────────────────────
    public function add(Request $request)
    {
        $imgName = 'default.png';
        if ($request->hasFile('image')) {
            $imgName = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('uploads'), $imgName);
        }

        $productId = DB::table('products')->insertGetId([
            'product_name'     => $request->input('product_name'),
            'description'      => $request->input('description'),
            'category'         => $request->input('category'),
            'brand'            => $request->input('brand'),
            'supplier'         => $request->input('supplier'),
            'batch_number'     => $request->input('batch_number'),
            'quantity'         => (int) $request->input('quantity', 0),
            'reorder_level'    => (int) $request->input('reorder_level', 0),
            'cost_price'       => (float) $request->input('cost_price', 0),
            'selling_price'    => (float) $request->input('selling_price', 0),
            'storage_location' => $request->input('storage_location'),
            'status'           => $request->input('status'),
            'date_added'       => now(),
            'image'            => $imgName,
        ]);

        // Log the initial stock batch
        DB::table('inventory_logs')->insert([
            'product_id'  => $productId,
            'quantity'    => (int) $request->input('quantity', 0),
            'type'        => 'IN',
            'expiry_date' => $request->input('expiry_date'),
            'created_at'  => now(),
        ]);

        return redirect()->route('admin.products');
    }

    // ─────────────────────────────────────────
    // POST /admin/products/update
    // ─────────────────────────────────────────
    public function update(Request $request)
    {
        $productId = (int) $request->input('product_id');
        $data = [
            'product_name'     => $request->input('product_name'),
            'description'      => $request->input('description'),
            'category'         => $request->input('category'),
            'brand'            => $request->input('brand'),
            'supplier'         => $request->input('supplier'),
            'batch_number'     => $request->input('batch_number'),
            'cost_price'       => (float) $request->input('cost_price'),
            'selling_price'    => (float) $request->input('selling_price'),
            'storage_location' => $request->input('storage_location'),
            'status'           => $request->input('status'),
        ];

        if ($request->hasFile('image')) {
            $imgName       = time() . '_' . $request->file('image')->getClientOriginalName();
            $request->file('image')->move(public_path('uploads'), $imgName);
            $data['image'] = $imgName;
        }

        DB::table('products')->where('product_id', $productId)->update($data);

        return redirect()->route('admin.products');
    }

    // ─────────────────────────────────────────
    // POST /admin/products/delete
    // ─────────────────────────────────────────
    public function delete(Request $request)
    {
        $productId = (int) $request->input('product_id');
        DB::table('inventory_logs')->where('product_id', $productId)->delete();
        DB::table('products')->where('product_id', $productId)->delete();

        return redirect()->route('staff.products');
    }
}