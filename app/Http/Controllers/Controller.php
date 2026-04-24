<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

abstract class Controller
{
    use SidebarDataController;
    protected function sidebarData(): array
    {
        $userId = (int) Session::get('user_id');

        $user = DB::table('users as u')
            ->leftJoin('patient as p', 'p.user_id', '=', 'u.user_id')
            ->where('u.user_id', $userId)
            ->select(
                'u.firstName',
                'u.lastName',
                'u.email',
                'u.role',
                DB::raw("COALESCE(p.profile_picture, 'uploads/default.png') AS profile_picture")
            )
            ->first();

        return [
            'sidebarFirstName' => $user->firstName    ?? '',
            'sidebarLastName'  => $user->lastName     ?? '',
            'sidebarEmail'     => $user->email        ?? '',
            'sidebarRole'      => ucfirst($user->role ?? 'patient'),
            'sidebarProfile'   => $user->profile_picture ?? 'uploads/default.png',
        ];
    }
}