<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class StaffServicesController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $services = DB::table('services')->orderBy('name')->get();
        $products = DB::table('products')->orderBy('product_name')->get();

        return view('staff_services', array_merge(
        $this->sidebarData(),
        compact('services', 'products')
    ));
    }

    public function store(Request $request)
    {
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

        return redirect()->route('staff.services');
    }

    public function update(Request $request)
    {
        $serviceId = (int) $request->input('service_id');
        $name      = $request->input('name');
        $desc      = $request->input('description');
        $price     = (float) $request->input('price');
        $status    = $request->input('status');

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgName = $uploaded['secure_url'];
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

        return redirect()->route('staff.services');
    }

    public function delete(Request $request)
    {
        $serviceId = (int) $request->input('service_id');
        DB::delete("DELETE FROM service_products WHERE service_id = ?", [$serviceId]);
        DB::delete("DELETE FROM services WHERE service_id = ?", [$serviceId]);

        return redirect()->route('staff.services');
    }
}