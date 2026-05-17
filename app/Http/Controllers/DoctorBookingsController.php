<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\SidebarDataController;
use App\Helpers\NotificationHelper;

class DoctorBookingsController extends Controller
{
    use SidebarDataController;

    // ── Get the name of the currently logged-in doctor ──
    private function actorName(): string
    {
        $userId = Session::get('user_id');
        if (!$userId) return 'Doctor';
        $user = DB::table('users')->where('user_id', $userId)->first();
        return $user ? 'Dr. ' . trim($user->firstName . ' ' . $user->lastName) : 'Doctor';
    }

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

        $actor  = $this->actorName();
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

        $patientUser = DB::table('users')->where('user_id', $appt->user_id)->first();
        $patientName = $patientUser ? trim($patientUser->firstName . ' ' . $patientUser->lastName) : 'A patient';

        $title      = 'Appointment Rescheduled';
        $patientMsg = "{$actor} rescheduled your appointment to {$request->new_date} at {$request->new_time}.";
        $staffMsg   = "{$actor} rescheduled {$patientName}'s appointment to {$request->new_date} at {$request->new_time}.";

        $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
        if ($patient) {
            NotificationHelper::send($patient->user_id, $title, $patientMsg, 'rescheduled', $apptId);
        }

        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) {
            NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'rescheduled', $apptId);
        }

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
            'cancel_reason'  => 'required|in:doctor_unavailable,doctor_emergency,patient_request,patient_noshow,other',
        ]);

        $actor  = $this->actorName();
        $apptId = (int) $request->appointment_id;

        $appt = DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->first();

        if (!$appt || $appt->status === 'cancelled') {
            return back()->with('error', 'This appointment cannot be cancelled.');
        }

        DB::table('appointments')
            ->where('appointment_id', $apptId)
            ->update([
                'status'        => 'cancelled',
                'cancel_reason' => $request->cancel_reason,
            ]);

        $reasonLabel = match($request->cancel_reason) {
            'doctor_unavailable' => 'doctor unavailability',
            'doctor_emergency'   => 'a doctor emergency',
            'patient_request'    => 'patient request',
            'patient_noshow'     => 'patient no-show',
            default              => 'an unspecified reason',
        };

        $patientUser = DB::table('users')->where('user_id', $appt->user_id)->first();
        $patientName = $patientUser ? trim($patientUser->firstName . ' ' . $patientUser->lastName) : 'A patient';

        $title      = 'Appointment Cancelled';
        $patientMsg = "{$actor} cancelled your appointment on {$appt->appointment_date} at {$appt->appointment_time} due to {$reasonLabel}.";
        $staffMsg   = "{$actor} cancelled {$patientName}'s appointment on {$appt->appointment_date} at {$appt->appointment_time}. Reason: {$reasonLabel}.";

        $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
        if ($patient) {
            NotificationHelper::send($patient->user_id, $title, $patientMsg, 'cancelled', $apptId);
        }

        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) {
            NotificationHelper::send($doctor->user_id, $title, $staffMsg, 'booking', $apptId);
        }

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) {
            NotificationHelper::send($u->user_id, $title, $staffMsg, 'booking', $apptId);
        }

        // ── Only trigger waitlist if the slot is still usable ──
        // Doctor-side reasons mean the slot is gone too
        $doctorSideReasons = ['doctor_unavailable', 'doctor_emergency'];
        if (!in_array($request->cancel_reason, $doctorSideReasons)) {
            \App\Http\Controllers\WaitlistController::notifyNext(
                $appt->appointment_date,
                $appt->appointment_time
            );
        }

        return back()->with('success', 'Appointment cancelled successfully.');
    }
}