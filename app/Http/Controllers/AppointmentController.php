<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppointmentController extends Controller
{
    public function getAvailableTimes(Request $request)
{
    $date       = $request->query('date');
    $doctorId   = $request->query('doctor_id');
    $serviceId  = $request->query('service_id');
    $preference = $request->query('preference');

    // ── Get doctor's availability_schedule ──
    $doctor = DB::table('doctor')->where('doctor_id', $doctorId)->first();
    $schedule = $doctor->availability_schedule ?? null;

    // ── Parse free-text schedule into allowed hour range ──
    // Supports formats like: "8:00-11:00AM", "8:00AM-7:00PM", "12:00-7:00PM"
    $scheduleStart = null;
    $scheduleEnd   = null;

    if ($schedule) {
        // Normalize: remove spaces, uppercase
        $s = strtoupper(preg_replace('/\s+/', '', $schedule));

        // Try to extract two times separated by dash
        if (preg_match('/(\d{1,2}(?::\d{2})?(?:AM|PM)?)-(\d{1,2}(?::\d{2})?(?:AM|PM)?)/', $s, $m)) {
            $scheduleStart = parseScheduleTime($m[1], $m[2]);
            $scheduleEnd   = parseScheduleTime($m[2], $m[1]);
        }
    }

    $allSlots = ['08:00','09:00','10:00','11:00','12:00','13:00','14:00','15:00','16:00','17:00','18:00','19:00'];

    // ── Filter by doctor's availability schedule ──
    if ($scheduleStart !== null && $scheduleEnd !== null) {
        $allSlots = array_filter($allSlots, function($t) use ($scheduleStart, $scheduleEnd) {
            $hour = (int) explode(':', $t)[0];
            return $hour >= $scheduleStart && $hour < $scheduleEnd;
        });
    }

    // ── Filter by AM/PM preference ──
    if ($preference === 'AM') {
        $allSlots = array_filter($allSlots, fn($t) => (int)explode(':', $t)[0] < 12);
    } elseif ($preference === 'PM') {
        $allSlots = array_filter($allSlots, fn($t) => (int)explode(':', $t)[0] >= 12);
    }

    // ── Filter past slots for today ──
    $nowManila   = now()->setTimezone('Asia/Manila');
    $todayManila = $nowManila->toDateString();
    if ($date === $todayManila) {
        $currentTime = $nowManila->format('H:i');
        $allSlots = array_filter($allSlots, fn($t) => $t > $currentTime);
    }

    // ── Get booked slots ──
    $booked = DB::table('appointments')
        ->where('doctor_id',        $doctorId)
        ->where('appointment_date', $date)
        ->whereIn('status', ['pending', 'approved'])
        ->pluck('appointment_time')
        ->map(fn($t) => substr($t, 0, 5))
        ->toArray();

    // ── Build response ──
    $slots = [];
    foreach ($allSlots as $slot) {
        $taken = in_array($slot, $booked);
        $waitlistCount = 0;
        if ($taken && $serviceId) {
            $waitlistCount = DB::table('appointment_waitlist')
                ->where('service_id',     $serviceId)
                ->where('preferred_date', $date)
                ->where('preferred_time', $slot)
                ->whereIn('status', ['waiting', 'notified'])
                ->count();
        }
        $slots[] = ['time' => $slot, 'taken' => $taken, 'waitlist_count' => $waitlistCount];
    }

    return response()->json(array_values($slots));
    }
}
function parseScheduleTime(string $time, string $other = ''): int
{
    $time  = strtoupper(trim($time));
    $other = strtoupper(trim($other));

    preg_match('/(\d{1,2})(?::(\d{2}))?/', $time, $m);
    $hour = (int) $m[1];

    if (str_contains($time, 'PM') && $hour !== 12) {
        $hour += 12;
    } elseif (str_contains($time, 'AM') && $hour === 12) {
        $hour = 0;
    } elseif (!str_contains($time, 'AM') && !str_contains($time, 'PM')) {
        // No AM/PM on this part — infer from the other time or raw value
        if (str_contains($other, 'PM') && $hour < 12) $hour += 12;
        elseif ($hour <= 7) $hour += 12; // e.g. "7:00" alone → 19:00
    }

    return $hour;
}