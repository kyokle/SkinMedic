<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AddProductController extends Controller
{
    public function index()
    {
        return view('add_product', $this->sidebarData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_name'  => 'required|string|max:255',
            'description'   => 'required|string',
            'quantity'      => 'nullable|integer|min:0',
            'reorder_level' => 'nullable|integer|min:0',
            'cost_price'    => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'expiry_date'   => 'nullable|date',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $imgName = null;
if ($request->hasFile('image')) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgName = $uploaded['secure_url'];
}

        DB::table('products')->insert([
            'product_name'     => $request->product_name,
            'description'      => $request->description,
            'category'         => $request->category,
            'brand'            => $request->brand,
            'supplier'         => $request->supplier,
            'batch_number'     => $request->batch_number,
            'quantity'         => $request->quantity,
            'reorder_level'    => $request->reorder_level,
            'cost_price'       => $request->cost_price,
            'selling_price'    => $request->selling_price,
            'expiry_date'      => $request->expiry_date,
            'storage_location' => $request->storage_location,
            'status'           => $request->status,
            'date_added'       => now(),
            'image'            => $imgName,
        ]);

        return redirect()->route('admin.products')->with('success', 'Product added successfully.');
    }
}