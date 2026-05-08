<?php
// app/Http/Controllers/PatientProductsController.php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PatientProductsController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────
    // GET  /patient/products
    // ─────────────────────────────────────────
    public function index()
    {
        // Only show products; no add/edit/delete for patients
        $products = DB::table('products')
            ->select('product_id', 'product_name', 'description', 'category',
                     'selling_price', 'status', 'image')
            ->orderBy('product_name')
            ->get();

        // Collect unique non-null categories for the filter bar
        $categories = DB::table('products')
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return view('patient.products', array_merge(
            $this->sidebarData(),
            compact('products', 'categories')
        ));
    }
}