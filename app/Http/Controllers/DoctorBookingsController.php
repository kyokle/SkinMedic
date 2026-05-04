<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use App\Helpers\NotificationHelper;

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
        $sidebarData = $this->sidebarData();
        return view('doctor_bookings', array_merge(compact('bookings', 'activeFilter'), $sidebarData));
    }

    public function reschedule(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'new_date'       => 'required|date|after:today',
            'new_time'       => 'required',
        ]);

        $apptId = (int) $request->appointment_id;

        $appt = DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->first();

        DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->update([
                'appointment_date' => $request->new_date,
                'appointment_time' => $request->new_time,
                'status'           => 'pending',
                'is_rescheduled'   => true,
            ]);

        $title    = 'Appointment Rescheduled';
        $msg      = 'Your appointment has been rescheduled to ' . $request->new_date . ' at ' . $request->new_time . '.';
        $staffMsg = 'An appointment has been rescheduled to ' . $request->new_date . ' at ' . $request->new_time . '.';

        $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
        if ($patient) NotificationHelper::send($patient->user_id, $title, $msg, 'rescheduled', $apptId);

        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'rescheduled', $apptId);

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) NotificationHelper::send($u->user_id, $title, $staffMsg, 'booking', $apptId);

        return back()->with('success', 'Appointment rescheduled successfully.');
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
        ]);

        $apptId = (int) $request->appointment_id;

        $appt = DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->first();

        if (!$appt || $appt->status === 'cancelled') {
            return back()->with('error', 'This appointment cannot be cancelled.');
        }

        DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->update(['status' => 'cancelled']);

        $title    = 'Appointment Cancelled';
        $msg      = 'Your appointment on ' . $appt->appointment_date . ' at ' . $appt->appointment_time . ' has been cancelled by the doctor.';
        $staffMsg = 'An appointment on ' . $appt->appointment_date . ' at ' . $appt->appointment_time . ' has been cancelled by the doctor.';

        $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
        if ($patient) NotificationHelper::send($patient->user_id, $title, $msg, 'cancelled', $apptId);

        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'booking', $apptId);

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) NotificationHelper::send($u->user_id, $title, $staffMsg, 'booking', $apptId);

        return back()->with('success', 'Appointment cancelled successfully.');
    }
}