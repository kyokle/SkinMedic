<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SidebarPatientController extends Controller
{
    public function getSidebarData(): array
    {
        $userId = (int) Session::get('user_id');

        $data = DB::table('users as u')
            ->leftJoin('Patient as p', 'u.user_id', '=', 'p.user_id')
            ->where('u.user_id', $userId)
            ->select('u.firstName', 'u.lastName', 'u.email', 'p.profile_picture')
            ->first();

        return [
            'sidebarFirstName' => $data->firstName     ?? 'Patient',
            'sidebarLastName'  => $data->lastName      ?? '',
            'sidebarEmail'     => $data->email         ?? '',
            'sidebarProfile' => $data->profile_picture ?? 'default.png',
            'sidebarRole'      => ucfirst(Session::get('role', 'patient')),
        ];
    }
}