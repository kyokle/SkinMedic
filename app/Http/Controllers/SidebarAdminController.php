<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SidebarAdminController extends Controller
{
    
    public function getSidebarData(): array
    {
        return [
            'sidebarFirstName' => Session::get('firstName', 'Admin'),
            'sidebarLastName'  => Session::get('lastName',  ''),
            'sidebarEmail'     => Session::get('email',     ''),
            'sidebarProfile' => $data->profile_picture ?? 'default.png',
            'sidebarRole'      => ucfirst(Session::get('role', 'admin')),
        ];
    }

    /**
     * Handle admin profile picture upload.
     * POST /admin/profile/upload-pic
     */
    public function uploadPic(Request $request)
    {
        $userId = (int) Session::get('user_id');

        $request->validate([
            'profile_pic' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:5120',
        ]);

        $file    = $request->file('profile_pic');
        $newName = 'profile_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
        $file->move(public_path('uploads/profiles'), $newName);

        DB::table('users')
            ->where('user_id', $userId)
            ->update(['profile_image' => $newName]);

        Session::put('profile_image', $newName);

        return redirect()->back();
    }
}