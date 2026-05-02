<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\SidebarDataController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PatientPageController extends Controller
{
    use SidebarDataController;
    /**
     * Show the patient home / dashboard.
     * GET /patient/home
     */
    public function index()
    {
        $userId = (int) Session::get('user_id');
        $today  = now()->toDateString();

        // Stat counts
        $totalCount     = DB::table('appointments')->where('user_id', $userId)->count();
        $pendingCount   = DB::table('appointments')->where('user_id', $userId)->where('status', 'pending')->count();
        $completedCount = DB::table('appointments')->where('user_id', $userId)->where('status', 'completed')->count();

        // Upcoming appointments (today and future)
        $appointments = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('doctor as doc', 'a.doctor_id', '=', 'doc.doctor_id')
            ->leftJoin('users as d', 'doc.user_id', '=', 'd.user_id')
            ->where('a.user_id', $userId)
            ->where('a.appointment_date', '>=', $today)
            ->orderBy('a.appointment_date')
            ->orderBy('a.appointment_time')
            ->select(
                'a.appointment_id',
                's.name as service_name',
                DB::raw("CONCAT(d.firstName, ' ', d.lastName) AS doctor_name"),
                'a.appointment_date',
                'a.appointment_time',
                'a.status'
            )
            ->get();

        return view('patient_page', array_merge(
            $this->sidebarData(),
            compact('totalCount', 'pendingCount', 'completedCount', 'appointments')
        ));
    }

    /**
     * Cancel an appointment.
     * POST /patient/cancel
     */
    public function cancel(Request $request)
    {
        $userId   = (int) Session::get('user_id');
        $cancelId = (int) $request->input('cancel_id');

        $appointment = DB::table('appointments')
            ->where('appointment_id', $cancelId)
            ->where('user_id', $userId)
            ->first();

        if ($appointment && in_array($appointment->status, ['pending', 'approved'])) {
            DB::table('appointments')
                ->where('appointment_id', $cancelId)
                ->update(['status' => 'cancelled']);
        }

        return redirect()->route('patient.home');
    }
}