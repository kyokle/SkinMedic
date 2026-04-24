<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;

class BookAppointmentController extends Controller
{
    // ── Show booking form ──────────────────────────────────────────
    public function show(Request $request)
{
    // Manual auth check using session
    if (!Session::has('user_id')) {
        return redirect('/')->with('error', 'Please login first.');
    }

    $serviceId = $request->query('service_id');
    $service   = DB::table('services')->where('service_id', $serviceId)->first();
    
    // Replace Auth::user() with session-based DB query
    $user = DB::table('users')->where('user_id', Session::get('user_id'))->first();

    $doctors = DB::table('doctor')
                ->join('users', 'doctor.user_id', '=', 'users.user_id')
                ->select('doctor.doctor_id', 'users.firstName', 'users.lastName')
                ->get();

    $isRegular     = (bool) $user->is_regular;
    $preferredTime = $user->preferred_time 
    ? substr($user->preferred_time, 0, 5) 
    : null;

    return view('book_appointment', compact(
        'service', 'serviceId', 'user', 'doctors', 'isRegular', 'preferredTime'
    ));
}

    // ── Store booking ──────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'service_id'       => 'required|integer',
            'doctor_id'        => 'required|integer',
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'required',
        ]);

        $user = DB::table('users')->where('user_id', Session::get('user_id'))->first();

        // Prevent double booking same slot
        $conflict = DB::table('appointments')
            ->where('doctor_id',        $request->doctor_id)
            ->where('appointment_date', $request->appointment_date)
            ->where('appointment_time', $request->appointment_time)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($conflict) {
            return back()->withErrors([
                'appointment_time' => 'That time slot is no longer available. Please choose another.'
            ])->withInput();
        }

        DB::table('appointments')->insert([
            'service_id'       => $request->service_id,
            'user_id'          => $user->user_id,
            'doctor_id'        => $request->doctor_id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'status'           => 'pending',
            'created_at'       => now(),
        ]);

        // ── Email confirmation ─────────────────────────────────────
        try {
            $service = DB::table('services')->where('service_id', $request->service_id)->first();
            $doctor  = DB::table('doctor')
                          ->join('users', 'doctor.user_id', '=', 'users.user_id')
                          ->where('doctor.doctor_id', $request->doctor_id)
                          ->select('users.firstName', 'users.lastName')
                          ->first();

            Mail::send([], [], function ($message) use ($user, $request, $service, $doctor) {
                $message->to($user->email)
                    ->subject('Appointment Confirmed – SkinMedic')
                    ->html("
                        <h2>Appointment Confirmed!</h2>
                        <p>Hi {$user->firstName},</p>
                        <p>Your appointment has been booked successfully.</p>
                        <ul>
                            <li><strong>Service:</strong> {$service->name}</li>
                            <li><strong>Doctor:</strong> Dr. {$doctor->firstName} {$doctor->lastName}</li>
                            <li><strong>Date:</strong> {$request->appointment_date}</li>
                            <li><strong>Time:</strong> {$request->appointment_time}</li>
                        </ul>
                        <p>Please arrive 10 minutes early. See you soon!</p>
                        <p>— SkinMedic Clinic</p>
                    ");
            });
        } catch (\Exception $e) {
            // Email failed silently — booking still saved
        }

        return redirect()->route('patient.home')->with('success', 'Your appointment has been booked! A confirmation email has been sent.');
    }

    // ── Get available times (AJAX) ─────────────────────────────────
    public function getAvailableTimes(Request $request)
    {
        $date       = $request->query('date');
        $doctorId   = $request->query('doctor_id');
        $preference = $request->query('preference');

        $allSlots = [
            '08:00', '09:00', '10:00', '11:00',
            '12:00', '13:00', '14:00', '15:00',
            '16:00', '17:00', '18:00', '19:00',
        ];

        if ($preference === 'AM') {
            $allSlots = array_filter($allSlots, fn($t) => (int)explode(':', $t)[0] < 12);
        } elseif ($preference === 'PM') {
            $allSlots = array_filter($allSlots, fn($t) => (int)explode(':', $t)[0] >= 12);
        }

        $booked = DB::table('appointments')
            ->where('doctor_id',        $doctorId)
            ->where('appointment_date', $date)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('appointment_time')
            ->map(fn($t) => substr($t, 0, 5))
            ->toArray();

        $available = array_values(array_filter($allSlots, fn($slot) => !in_array($slot, $booked)));

        return response()->json($available);
    }
}