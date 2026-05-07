<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = DB::table('products')
            ->where('status', 'available')
            ->where('quantity', '>', 0)
            ->orderBy('date_added', 'desc')
            ->get();

        return view('products', compact('products'));
    }
}