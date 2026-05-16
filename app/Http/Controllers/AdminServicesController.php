<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AdminServicesController extends Controller
{
    use SidebarDataController;
    public function index()
{
    $services = DB::table('services')->orderBy('name')->get();
    $products = DB::table('products')->orderBy('product_name')->get();

    // Keyed by service_id → [ product_id => quantity_used ]
    $serviceProducts = DB::table('service_products')
        ->get()
        ->groupBy('service_id')
        ->map(fn($rows) => $rows->pluck('quantity_used', 'product_id'));

    return view('admin_services', array_merge(
        $this->sidebarData(),
        compact('services', 'products', 'serviceProducts')
    ));
}

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'price'  => 'required|numeric|min:0',
            'status' => 'required|string',
        ], [
            'price.min' => 'Price cannot be negative.',
        ]);

        $name   = $request->input('name');
        $desc   = $request->input('description');
        $price  = (float) $request->input('price');
        $status = $request->input('status');

        $imgName = 'default.png';
if ($request->hasFile('image') && $request->file('image')->isValid()) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgName = $uploaded['secure_url'];
}

        DB::insert(
            "INSERT INTO services (name, description, price, image, status) VALUES (?, ?, ?, ?, ?)",
            [$name, $desc, $price, $imgName, $status]
        );

        $serviceId = DB::getPdo()->lastInsertId();

        if ($request->has('products')) {
            foreach ($request->input('products') as $prodId) {
                $prodId = (int) $prodId;
                $qty    = (int) ($request->input("qty_{$prodId}") ?? 1);
                DB::insert(
                    "INSERT INTO service_products (service_id, product_id, quantity_used) VALUES (?, ?, ?)",
                    [$serviceId, $prodId, $qty]
                );
            }
        }

        return redirect()->route('admin.services');
    }

    public function update(Request $request)
{
    $request->validate([
        'name'   => 'required|string|max:255',
        'price'  => 'required|numeric|min:0',
        'status' => 'required|string',
    ], [
        'price.min' => 'Price cannot be negative.',
    ]);

    $serviceId = (int) $request->input('service_id');
    $name      = $request->input('name');
    $desc      = $request->input('description');
    $price     = (float) $request->input('price');
    $status    = $request->input('status');

    if ($request->hasFile('image') && $request->file('image')->isValid()) {
        $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
        $imgName  = $uploaded['secure_url'];
        DB::update(
            "UPDATE services SET name=?, description=?, price=?, image=?, status=? WHERE service_id=?",
            [$name, $desc, $price, $imgName, $status, $serviceId]
        );
    } else {
        DB::update(
            "UPDATE services SET name=?, description=?, price=?, status=? WHERE service_id=?",
            [$name, $desc, $price, $status, $serviceId]
        );
    }

    // ── Sync service_products ─────────────────────────────
    DB::delete("DELETE FROM service_products WHERE service_id = ?", [$serviceId]);

    if ($request->has('products')) {
        foreach ($request->input('products') as $prodId) {
            $prodId = (int) $prodId;
            $qty    = (int) ($request->input("qty_{$prodId}") ?? 1);
            DB::insert(
                "INSERT INTO service_products (service_id, product_id, quantity_used) VALUES (?, ?, ?)",
                [$serviceId, $prodId, $qty]
            );
        }
    }
    // ─────────────────────────────────────────────────────

    return redirect()->route('admin.services');
}

    public function delete(Request $request)
    {
        $serviceId = (int) $request->input('service_id');
        DB::delete("DELETE FROM service_products WHERE service_id = ?", [$serviceId]);
        DB::delete("DELETE FROM services WHERE service_id = ?", [$serviceId]);

        return redirect()->route('admin.services');
    }
}