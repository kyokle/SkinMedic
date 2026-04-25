<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class AddServiceController extends Controller  // ← was ServiceController
{
    public function index()
    {
        return view('add_service', $this->sidebarData());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'status'      => 'required|in:available,not available',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);

        $imgUrl = null;
if ($request->hasFile('image')) {
    $uploaded = cloudinary()->uploadApi()->upload($request->file('image')->getRealPath());
    $imgUrl = $uploaded['secure_url'];
}

DB::table('services')->insert([
    'name'        => $request->name,
    'description' => $request->description,
    'image'       => $imgUrl,
    'status'      => $request->status,
]);

        return redirect()->route('admin.services')->with('success', 'Service added successfully.');
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'service_id' => 'required|integer',
            'available'  => 'required|integer|in:0,1',
        ]);

        DB::table('services')
            ->where('service_id', $request->service_id)  // ← fixed from 'id' to 'service_id'
            ->update(['available' => $request->available]);

        return response('success', 200);
    }
}