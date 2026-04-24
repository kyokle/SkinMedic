<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SidebarDoctorController extends Controller
{
    /**
     * Fetch the data needed to render the doctor sidebar.
     * Call this from any doctor page controller and merge
     * the result into the view data.
     *
     * Usage in a doctor page controller:
     *
     *   $sidebarData = (new SidebarDoctorController)->getSidebarData();
     *   return view('doctor_page', array_merge(compact('bookings'), $sidebarData));
     */
    public function getSidebarData(): array
    {
        $userId = (int) Session::get('user_id');

        $data = DB::table('users as u')
            ->leftJoin('doctor as d', 'u.user_id', '=', 'd.user_id')
            ->where('u.user_id', $userId)
            ->select('u.firstName', 'u.lastName', 'u.email', 'd.profile_picture')
            ->first();

        return [
            'sidebarFirstName' => $data->firstName     ?? 'Doctor',
            'sidebarLastName'  => $data->lastName      ?? '',
            'sidebarEmail'     => $data->email         ?? '',
            'sidebarProfile' => $data->profile_picture ?? 'default.png',
            'sidebarRole'      => ucfirst(Session::get('role', 'doctor')),
        ];
    }
}