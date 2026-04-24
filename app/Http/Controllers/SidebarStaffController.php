<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SidebarStaffController extends Controller
{
    
    public function getSidebarData(): array
    {
        $userId = (int) Session::get('user_id');

        $data = DB::table('users as u')
            ->leftJoin('staff as s', 'u.user_id', '=', 's.user_id')
            ->where('u.user_id', $userId)
            ->select('u.firstName', 'u.lastName', 'u.email', 's.profile_picture')
            ->first();

        return [
            'sidebarFirstName' => $data->firstName     ?? 'User',
            'sidebarLastName'  => $data->lastName      ?? '',
            'sidebarEmail'     => $data->email         ?? '',
            'sidebarProfile' => $data->profile_picture ?? 'default.png',
            'sidebarRole'      => ucfirst(Session::get('role', 'staff')),
        ];
    }
}