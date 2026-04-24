<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PatientBookingsController extends Controller
{
    use SidebarDataController;
    public function index(Request $request)
    {
        $userId       = (int) Session::get('user_id');
        $activeFilter = $request->get('filter', 'all');

        $query = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('users as d', 'a.doctor_id', '=', 'd.user_id')
            ->where('a.user_id', $userId)
            ->orderBy('a.appointment_date')
            ->orderBy('a.appointment_time')
            ->select(
                'a.appointment_id',
                's.name as service_name',
                DB::raw("CONCAT(d.firstName, ' ', d.lastName) AS doctor_name"),
                'a.appointment_date',
                'a.appointment_time',
                'a.status'
            );

        if ($activeFilter !== 'all') {
            $query->where('a.status', $activeFilter);
        }

        $appointments = $query->get();

        return view('patient_bookings', array_merge(
            $this->sidebarData(),
            compact('appointments', 'activeFilter')
        ));
    }
}