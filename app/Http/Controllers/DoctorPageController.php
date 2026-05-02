<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;

class DoctorPageController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        $doctorId     = session('user_id');
        $doctorRecord = DB::table('doctor')->where('user_id', $doctorId)->first();
        $doctorId     = $doctorRecord ? $doctorRecord->doctor_id : 0;
        $today        = date('Y-m-d');
        $upcomingStart = date('Y-m-d', strtotime('+1 day'));
        $nextWeek     = date('Y-m-d', strtotime('+7 days'));

        // Doctor name & specialization
        $docData = DB::selectOne("
            SELECT u.firstName, u.lastName, d.specialization
            FROM users u
            LEFT JOIN doctor d ON u.user_id = d.user_id
            WHERE u.user_id = ?
        ", [$doctorId]);

        $docName = ($docData->firstName ?? '') . ' ' . ($docData->lastName ?? '');
        $docSpec  = $docData->specialization ?? '';

        // Stats
        $total          = DB::table('appointments')->where('doctor_id', $doctorId)->count();
        $todayCount     = DB::table('appointments')->where('doctor_id', $doctorId)->where('appointment_date', $today)->count();
        $pendingCount   = DB::table('appointments')->where('doctor_id', $doctorId)->where('status', 'pending')->count();
        $completedCount = DB::table('appointments')->where('doctor_id', $doctorId)->where('status', 'completed')->count();

        // Today's sessions
        $todaySessions = DB::select("
            SELECT a.appointment_id,
                   CONCAT(p.firstName,' ',p.lastName) AS patient_name,
                   s.name AS service_name,
                   a.appointment_time, a.status
            FROM appointments a
            LEFT JOIN users    p ON a.user_id    = p.user_id
            LEFT JOIN services s ON a.service_id = s.service_id
            WHERE a.doctor_id = ? AND a.appointment_date = ? AND a.status = 'approved'
            ORDER BY a.appointment_time ASC
        ", [$doctorId, $today]);

        // Upcoming appointments
        $upcomingAppts = DB::select("
            SELECT a.appointment_id,
                   CONCAT(p.firstName,' ',p.lastName) AS patient_name,
                   s.name AS service_name,
                   a.appointment_date, a.appointment_time, a.status
            FROM appointments a
            LEFT JOIN users    p ON a.user_id    = p.user_id
            LEFT JOIN services s ON a.service_id = s.service_id
            WHERE a.doctor_id = ?
              AND a.appointment_date BETWEEN ? AND ?
            ORDER BY a.appointment_date ASC, a.appointment_time ASC
            LIMIT 10
        ", [$doctorId, $upcomingStart, $nextWeek]);
        $sidebarData = $this->sidebarData();

        return view('doctor_page', array_merge(compact(
    'docName', 'docSpec', 'total', 'todayCount',
    'pendingCount', 'completedCount', 'todaySessions', 'upcomingAppts'
), $sidebarData));
    }
}