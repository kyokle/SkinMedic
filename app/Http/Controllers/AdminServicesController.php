<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AdminServicesController extends Controller
{
    use SidebarDataController;
    // ─────────────────────────────────────────
    // GET  /admin/services
    // ─────────────────────────────────────────
    public function index()
    {
        $services = DB::table('services')->orderBy('name')->get();
        $products = DB::table('products')->orderBy('product_name')->get();

        return view('admin_services', array_merge(
        $this->sidebarData(),
        compact('services', 'products')
    ));
    }

    // ─────────────────────────────────────────
    // POST /admin/services/add
    // ─────────────────────────────────────────
    public function add(Request $request)
    {
        $imgName = 'default.png';
if ($request->hasFile('image')) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgName = $uploaded['secure_url'];
}

        $serviceId = DB::table('services')->insertGetId([
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'price'       => (float) $request->input('price'),
            'image'       => $imgName,
            'status'      => $request->input('status'),
        ]);

        // Link products used in this service
        foreach ($request->input('products', []) as $prodId) {
            $prodId = (int) $prodId;
            $qty    = (int) $request->input('qty_' . $prodId, 1);
            DB::table('service_products')->insert([
                'service_id'    => $serviceId,
                'product_id'    => $prodId,
                'quantity_used' => $qty,
            ]);
        }

        return redirect()->route('admin.services');
    }

    // ─────────────────────────────────────────
    // POST /admin/services/update
    // ─────────────────────────────────────────
    public function update(Request $request)
    {
        $serviceId = (int) $request->input('service_id');
        $data = [
            'name'        => $request->input('name'),
            'description' => $request->input('description'),
            'price'       => (float) $request->input('price'),
            'status'      => $request->input('status'),
        ];

        if ($request->hasFile('image')) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $data['image'] = $uploaded['secure_url'];
}

        DB::table('services')->where('service_id', $serviceId)->update($data);

        return redirect()->route('admin.services');
    }

    // ─────────────────────────────────────────
    // POST /admin/services/delete
    // ─────────────────────────────────────────
    public function delete(Request $request)
    {
        $serviceId = (int) $request->input('service_id');
        DB::table('service_products')->where('service_id', $serviceId)->delete();
        DB::table('services')->where('service_id', $serviceId)->delete();

        return redirect()->route('admin.services');
    }
}