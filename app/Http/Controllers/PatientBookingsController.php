<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\WaitlistController;

class PatientBookingsController extends Controller
{
    use SidebarDataController;

    public function index(Request $request)
    {
        $userId       = (int) Session::get('user_id');
        $activeFilter = $request->get('filter', 'all');

        $query = DB::table('appointments as a')
    ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
    ->leftJoin('doctor as doc', 'a.doctor_id', '=', 'doc.doctor_id')
    ->leftJoin('users as d', 'doc.user_id', '=', 'd.user_id')
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

    public function reschedule(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer',
            'new_date'       => 'required|date|after:today',
            'new_time'       => 'required',
        ]);

        $apptId  = (int) $request->appointment_id;
        $userId  = (int) Session::get('user_id');

        // Make sure the appointment belongs to this patient
        $appt = DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->where('user_id', $userId)
            ->first();

        if (!$appt || !in_array($appt->status, ['pending', 'approved'])) {
            return back()->with('error', 'This appointment cannot be rescheduled.');
        }

        DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->update([
                'appointment_date' => $request->new_date,
                'appointment_time' => $request->new_time,
                'status'           => 'pending',
                'is_rescheduled'   => true,
            ]);

        $title    = 'Appointment Rescheduled by Patient';
        $msg      = 'Your appointment has been rescheduled to ' . $request->new_date . ' at ' . $request->new_time . '.';
        $staffMsg = 'A patient has rescheduled their appointment to ' . $request->new_date . ' at ' . $request->new_time . '.';

        // Notify the patient themselves
        NotificationHelper::send($userId, $title, $msg, 'rescheduled', $apptId);

        // Notify the doctor
        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) {
            NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'rescheduled', $apptId);
        }

        // Notify admin/staff
        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) {
            NotificationHelper::send($u->user_id, $title, $staffMsg, 'booking', $apptId);
        }

        return back()->with('success', 'Appointment rescheduled successfully.');
    }
public function cancel(Request $request)
{
    $request->validate([
        'appointment_id' => 'required|integer',
    ]);

    $apptId = (int) $request->appointment_id;
    $userId = (int) Session::get('user_id');

    $appt = DB::table('appointments')
        ->where('appointment_id', $apptId)
        ->where('user_id', $userId)
        ->first();

    if (!$appt || $appt->status === 'cancelled') {
        return back()->with('error', 'This appointment cannot be cancelled.');
    }

    DB::table('appointments')
        ->where('appointment_id', $apptId)
        ->update([
            'status'        => 'cancelled',
            'cancel_reason' => 'patient_request',
            'updated_at'    => now(), 
        ]);

    $title    = 'Appointment Cancelled by Patient';
    $msg      = 'Your appointment on ' . $appt->appointment_date . ' at ' . $appt->appointment_time . ' has been cancelled.';
    $staffMsg = 'A patient has cancelled their appointment on ' . $appt->appointment_date . ' at ' . $appt->appointment_time . '.';

    NotificationHelper::send($userId, $title, $msg, 'cancelled', $apptId);

    $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
    if ($doctor && $doctor->user_id) {
        NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'booking', $apptId);
    }

    $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
    foreach ($adminStaff as $u) {
        NotificationHelper::send($u->user_id, $title, $staffMsg, 'booking', $apptId);
    }

    // ── Always trigger waitlist — patient cancels always free the slot ──

    WaitlistController::notifyNext(
    $appt->appointment_date,
    $appt->appointment_time
);

    return back()->with('success', 'Appointment cancelled successfully.');
}
}