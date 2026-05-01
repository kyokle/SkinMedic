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
        $role   = $user->role ?? 'patient';

        // Get profile picture from the correct table based on role
        $profilePicture = null;

        if ($role === 'patient') {
            $row = DB::table('patient')->where('user_id', $userId)->first();
            $profilePicture = $row->profile_picture ?? null;
        } elseif ($role === 'doctor') {
            $row = DB::table('doctor')->where('user_id', $userId)->first();
            $profilePicture = $row->profile_picture ?? null;
        } elseif ($role === 'staff') {
            $row = DB::table('staff')->where('user_id', $userId)->first();
            $profilePicture = $row->profile_picture ?? null;
        } elseif ($role === 'admin') {
            // Admin saves to users.profile_image
            $profilePicture = $user->profile_image ?? null;
        }

        $sidebarProfile = ($profilePicture && $profilePicture !== 'default.png')
            ? $profilePicture
            : asset('uploads/default.png');

        return [
            'sidebarProfile'   => $sidebarProfile,
            'sidebarFirstName' => $user->firstName ?? '',
            'sidebarLastName'  => $user->lastName  ?? '',
            'sidebarEmail'     => $user->email     ?? '',
            'sidebarRole'      => ucfirst($role),
        ];
    }
}