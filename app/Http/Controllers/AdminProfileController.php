<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class AdminProfileController extends Controller
{
    use SidebarDataController;

    public function uploadPic(Request $request)
    {
        $userId = (int) Session::get('user_id');

        $request->validate([
            'profile_pic' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $path = Storage::disk('cloudinary')->putFile('admin_profiles', $request->file('profile_pic'));
        $url  = Storage::disk('cloudinary')->url($path);

        DB::table('users')
            ->where('user_id', $userId)
            ->update(['profile_image' => $url]);

        return redirect()->back()->with('upload_success', true);
    }
}