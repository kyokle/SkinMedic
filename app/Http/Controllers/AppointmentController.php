<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function getAvailableTimes(Request $request)
    {
        if (!$request->has('date') || !$request->has('service_id')) {
            return response()->json([]);
        }

        $date       = $request->query('date');
        $serviceId  = intval($request->query('service_id'));
        $doctorId   = intval($request->query('doctor_id', 0));
        $preference = strtoupper(trim($request->query('preference', '')));

        $amTimes = ['08:00', '09:00', '10:00', '11:00'];
        $pmTimes = ['12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'];

        if ($preference === 'AM') {
            $allTimes = $amTimes;
        } elseif ($preference === 'PM') {
            $allTimes = $pmTimes;
        } else {
            $allTimes = array_merge($amTimes, $pmTimes);
        }

        if ($doctorId > 0) {
            $booked = DB::select("
                SELECT DATE_FORMAT(appointment_time, '%H:%i') AS appointment_time
                FROM appointments
                WHERE appointment_date = ?
                  AND doctor_id = ?
                  AND status NOT IN ('cancelled')
            ", [$date, $doctorId]);

            $bookedTimes = array_column($booked, 'appointment_time');
            $availableTimes = array_values(array_diff($allTimes, $bookedTimes));
        } else {
            $availableTimes = $allTimes;
        }

        return response()->json($availableTimes);
    }
}