<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientReviewsController extends Controller
{
    use SidebarDataController; // ✅ Add this

    public function index()
    {
        $userId = session('user_id');

        $appointments = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('reviews as r', function ($join) use ($userId) {
                $join->on('r.appointment_id', '=', 'a.appointment_id')
                     ->where('r.user_id', '=', $userId);
            })
            ->where('a.user_id', $userId)
            ->where('a.status', 'completed')
            ->whereNull('r.review_id')
            ->select('a.appointment_id', 'a.appointment_date', 's.name as service_name')
            ->orderBy('a.appointment_date', 'desc')
            ->get();

        $myReviews = DB::table('reviews as r')
            ->leftJoin('services as s', 'r.service_id', '=', 's.service_id')
            ->where('r.user_id', $userId)
            ->select('r.review_id', 'r.rating', 'r.comment', 'r.created_at', 's.name as service_name')
            ->orderBy('r.created_at', 'desc')
            ->get();

        return view('patient_reviews', array_merge( // ✅ Add sidebarData
            $this->sidebarData(),
            compact('appointments', 'myReviews')
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'appointment_id' => 'required|integer|exists:appointments,appointment_id',
            'rating'         => 'required|integer|min:1|max:5',
            'comment'        => 'required|string|min:10|max:500',
        ]);

        $userId = session('user_id');

        $appointment = DB::table('appointments')
            ->where('appointment_id', $request->appointment_id)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$appointment) {
            return response()->json(['success' => false, 'error' => 'Invalid appointment.'], 422);
        }

        $exists = DB::table('reviews')
            ->where('appointment_id', $request->appointment_id)
            ->where('user_id', $userId)
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'error' => 'You have already reviewed this appointment.'], 422);
        }

        DB::table('reviews')->insert([
            'appointment_id' => $request->appointment_id,
            'user_id'        => $userId,
            'service_id'     => $appointment->service_id,
            'rating'         => $request->rating,
            'comment'        => $request->comment,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true]);
    }
}