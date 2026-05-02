<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;

class DoctorBookingsController extends Controller
{
    use SidebarDataController;
    public function index(Request $request)
    {
        $doctorId     = session('user_id');
        $doctorRecord = DB::table('doctor')->where('user_id', $doctorId)->first();
        $doctorId     = $doctorRecord ? $doctorRecord->doctor_id : 0;
        $activeFilter = $request->query('filter', 'all');

        $bookings = DB::select("
            SELECT
                a.appointment_id,
                CONCAT(p.firstName, ' ', p.lastName) AS patient_name,
                s.name AS service_name,
                a.appointment_date,
                a.appointment_time,
                a.status
            FROM appointments a
            LEFT JOIN users    p ON a.user_id    = p.user_id
            LEFT JOIN services s ON a.service_id = s.service_id
            WHERE a.doctor_id = ?
            ORDER BY a.appointment_date, a.appointment_time", [$doctorId]);
        $sidebarData = (new SidebarDoctorController)->getSidebarData();
        return view('doctor_bookings', array_merge(compact('bookings', 'activeFilter'), $sidebarData));
    }
}