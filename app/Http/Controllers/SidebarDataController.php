<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

trait SidebarDataController
{
    protected function sidebarData(): array
    {
        $userId = Session::get('user_id');
    $user   = DB::table('users')->where('user_id', $userId)->first();

    $profileImage = $user->profile_image ?? null;

    return [
        'sidebarProfile'   => $profileImage && file_exists(public_path('uploads/' . $profileImage))
                                ? $profileImage
                                : 'default.png',
        'sidebarFirstName' => $user->firstName ?? '',
        'sidebarLastName'  => $user->lastName  ?? '',
        'sidebarEmail'     => $user->email     ?? '',
        'sidebarRole'      => ucfirst($user->role ?? ''),
    ];
    }
}