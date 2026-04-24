<?php
// app/Http/Controllers/ReviewController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\SidebarPatientController;

class ReviewController extends Controller
{
    /**
     * Show the patient review form.
     * Only shows appointments that are completed and not yet reviewed.
     */
    public function create()
    {
        $userId = Session::get('user_id');
        if (!$userId || Session::get('role') !== 'patient') {
            return redirect('/')->with('error', 'Please log in as a patient.');
        }

        // Get completed appointments that don't have a review yet
        $appointments = DB::table('appointments')
            ->leftJoin('reviews', 'appointments.appointment_id', '=', 'reviews.appointment_id')
            ->leftJoin('services', 'appointments.service_id', '=', 'services.service_id')
            ->where('appointments.user_id', $userId)
            ->where('appointments.status', 'completed')
            ->whereNull('reviews.review_id')
            ->select(
                'appointments.appointment_id',
                'appointments.appointment_date',
                'services.name as service_name',
                'services.service_id'
            )
            ->orderBy('appointments.appointment_date', 'desc')
            ->get();

        // Already submitted reviews
        $myReviews = DB::table('reviews')
            ->leftJoin('services', 'reviews.service_id', '=', 'services.service_id')
            ->where('reviews.user_id', $userId)
            ->select('reviews.*', 'services.name as service_name')
            ->orderBy('reviews.created_at', 'desc')
            ->get();

            $sidebarData = (new SidebarPatientController)->getSidebarData();
return view('patient_reviews', array_merge(compact('appointments', 'myReviews'), $sidebarData));
    }

    /**
     * Store a new review (AJAX).
     */
    public function store(Request $request)
    {
        $userId = Session::get('user_id');
        if (!$userId || Session::get('role') !== 'patient') {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'appointment_id' => 'required|integer',
            'rating'         => 'required|integer|min:1|max:5',
            'comment'        => 'required|string|min:10|max:500',
        ]);

        // Verify the appointment belongs to this patient and is completed
        $appointment = DB::table('appointments')
            ->where('appointment_id', $request->appointment_id)
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->first();

        if (!$appointment) {
            return response()->json(['success' => false, 'error' => 'Invalid appointment.']);
        }

        // Check not already reviewed
        $existing = DB::table('reviews')
            ->where('appointment_id', $request->appointment_id)
            ->exists();

        if ($existing) {
            return response()->json(['success' => false, 'error' => 'You already reviewed this appointment.']);
        }

        DB::table('reviews')->insert([
            'user_id'        => $userId,
            'appointment_id' => $request->appointment_id,
            'service_id'     => $appointment->service_id ?? null,
            'rating'         => $request->rating,
            'comment'        => strip_tags($request->comment),
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Review submitted! Thank you.']);
    }

    /**
     * Staff/admin: list all reviews with management options.
     */
    public function index()
    {
        $role = Session::get('role');
        if (!in_array($role, ['staff', 'admin', 'doctor'])) {
            return redirect('/');
        }

        $reviews = DB::table('reviews')
            ->join('users', 'reviews.user_id', '=', 'users.user_id')
            ->leftJoin('services', 'reviews.service_id', '=', 'services.service_id')
            ->select(
                'reviews.*',
                DB::raw("CONCAT(users.firstname, ' ', users.lastname) as patient_name"),
                'services.name as service_name'
            )
            ->orderBy('reviews.created_at', 'desc')
            ->get();

        $avgRating = round($reviews->avg('rating'), 1);
        $total     = $reviews->count();

        return view('staff.reviews', compact('reviews', 'avgRating', 'total'));
    }

    /**
     * Staff/admin: delete a review (AJAX).
     */
    public function destroy(Request $request, $id)
    {
        $role = Session::get('role');
        if (!in_array($role, ['staff', 'admin', 'doctor'])) {
            return response()->json(['success' => false, 'error' => 'Unauthorized.'], 401);
        }

        $deleted = DB::table('reviews')->where('review_id', $id)->delete();

        return response()->json(['success' => (bool) $deleted]);
    }
}