<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ScheduleSessionsController extends Controller
{
    /**
     * Show all scheduled sessions for the logged-in user.
     * GET /schedule-sessions
     */
    public function index()
    {
        $userId = (int) Session::get('user_id');

        $appointments = DB::table('appointments as a')
            ->join('services as s', 'a.service_id', '=', 's.service_id')
            ->where('a.user_id', $userId)
            ->orderByDesc('a.appointment_date')
            ->orderByDesc('a.appointment_time')
            ->select(
                'a.appointment_id',
                's.name as service_name',
                'a.appointment_date',
                'a.appointment_time',
                'a.status'
            )
            ->get();

        return view('schedule_sessions', compact('appointments'));
    }

    /**
     * Delete an appointment belonging to the logged-in user.
     * POST /schedule-sessions/delete
     */
    public function delete(Request $request)
    {
        $userId        = (int) Session::get('user_id');
        $appointmentId = (int) $request->input('appointment_id');

        DB::table('appointments')
            ->where('appointment_id', $appointmentId)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true]);
    }
}