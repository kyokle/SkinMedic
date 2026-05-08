<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use App\Helpers\NotificationHelper;
use App\Mail\WaitlistJoinedMail;
use App\Mail\WaitlistSlotAvailableMail;

class WaitlistController extends Controller
{
    // ── Patient joins waitlist ──────────────────────────────────
    public function join(Request $request)
    {
        $request->validate([
            'service_id'     => 'required|integer',
            'preferred_date' => 'required|date|after_or_equal:today',
            'preferred_time' => 'required',
        ]);

        $userId = (int) Session::get('user_id');

        // Prevent duplicate waitlist entry for same slot
        $exists = DB::table('appointment_waitlist')
            ->where('user_id',        $userId)
            ->where('service_id',     $request->service_id)
            ->where('preferred_date', $request->preferred_date)
            ->where('preferred_time', $request->preferred_time)
            ->whereIn('status', ['waiting', 'notified'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'error'   => 'You are already on the waitlist for this slot.',
            ]);
        }

        // Get next queue position for this slot
        $position = DB::table('appointment_waitlist')
            ->where('service_id',     $request->service_id)
            ->where('preferred_date', $request->preferred_date)
            ->where('preferred_time', $request->preferred_time)
            ->whereIn('status', ['waiting', 'notified'])
            ->count() + 1;

        DB::table('appointment_waitlist')->insert([
            'user_id'        => $userId,
            'service_id'     => $request->service_id,
            'preferred_date' => $request->preferred_date,
            'preferred_time' => $request->preferred_time,
            'status'         => 'waiting',
            'queue_position' => $position,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        NotificationHelper::send(
            $userId,
            'Added to Waitlist',
            'You are #' . $position . ' on the waitlist for ' .
            $request->preferred_date . ' at ' .
            \Carbon\Carbon::parse($request->preferred_time)->format('g:i A') . '.',
            'waitlist',
            null
        );

        // Send confirmation email to the patient
        $user = DB::table('users')->where('user_id', $userId)->first();
        if ($user && $user->email) {
            $service = DB::table('services')->where('service_id', $request->service_id)->first();
            Mail::to($user->email)->send(new WaitlistJoinedMail(
                firstName:     $user->firstName,
                serviceName:   $service?->name ?? 'your selected service',
                preferredDate: $request->preferred_date,
                preferredTime: $request->preferred_time,
                position:      $position,
            ));
        }

        return response()->json([
            'success'  => true,
            'position' => $position,
            'message'  => 'You are #' . $position . ' on the waitlist! We will notify you if the slot opens.',
        ]);
    }

    // ── Patient claims a slot via token link ────────────────────
    public function claim(Request $request, $token)
    {
        $userId = (int) Session::get('user_id');

        $entry = DB::table('appointment_waitlist')
            ->where('claim_token', $token)
            ->where('status', 'notified')
            ->first();

        if (!$entry) {
            return redirect()->route('patient.bookings')
                ->with('error', 'This claim link is invalid or has already been used.');
        }

        // Check 30-minute window
        if (now()->gt($entry->claim_expires_at)) {
            // Expire this entry and notify next in queue
            DB::table('appointment_waitlist')
                ->where('waitlist_id', $entry->waitlist_id)
                ->update(['status' => 'expired', 'updated_at' => now()]);

            $this->notifyNext($entry->service_id, $entry->preferred_date, $entry->preferred_time);

            return redirect()->route('patient.bookings')
                ->with('error', 'Sorry, your claim window expired. The slot has been offered to the next person.');
        }

        // Check the slot is still free
        $taken = DB::table('appointments')
            ->where('service_id',        $entry->service_id)
            ->where('appointment_date',  $entry->preferred_date)
            ->where('appointment_time',  $entry->preferred_time)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($taken) {
            DB::table('appointment_waitlist')
                ->where('waitlist_id', $entry->waitlist_id)
                ->update(['status' => 'expired', 'updated_at' => now()]);

            return redirect()->route('patient.bookings')
                ->with('error', 'Sorry, that slot was just taken. You have been moved back to the waitlist.');
        }

        // Book the appointment
        $appointmentId = DB::table('appointments')->insertGetId([
            'user_id'          => $entry->user_id,
            'service_id'       => $entry->service_id,
            'appointment_date' => $entry->preferred_date,
            'appointment_time' => $entry->preferred_time,
            'status'           => 'pending',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Mark waitlist entry as claimed
        DB::table('appointment_waitlist')
            ->where('waitlist_id', $entry->waitlist_id)
            ->update(['status' => 'claimed', 'updated_at' => now()]);

        NotificationHelper::send(
            $entry->user_id,
            'Slot Booked!',
            'Your waitlisted slot on ' . $entry->preferred_date . ' at ' .
            \Carbon\Carbon::parse($entry->preferred_time)->format('g:i A') .
            ' has been booked successfully.',
            'booking',
            $appointmentId
        );

        return redirect()->route('patient.bookings')
            ->with('success', '🎉 Slot claimed and booked successfully!');
    }

    // ── Called whenever a slot is freed ────────────────────────
    public static function notifyNext($serviceId, $date, $time)
    {
        // Find the next waiting person in queue order
        $next = DB::table('appointment_waitlist')
            ->where('service_id',     $serviceId)
            ->where('preferred_date', $date)
            ->where('preferred_time', $time)
            ->where('status', 'waiting')
            ->orderBy('queue_position')
            ->first();

        if (!$next) return; // nobody waiting

        $token   = Str::random(40);
        $expires = now()->addMinutes(30);

        DB::table('appointment_waitlist')
            ->where('waitlist_id', $next->waitlist_id)
            ->update([
                'status'           => 'notified',
                'claim_token'      => $token,
                'notified_at'      => now(),
                'claim_expires_at' => $expires,
                'updated_at'       => now(),
            ]);

        $claimUrl = route('waitlist.claim', ['token' => $token]);

        NotificationHelper::send(
            $next->user_id,
            '🔔 Your Waitlisted Slot is Available!',
            'A slot on ' . $date . ' at ' .
            \Carbon\Carbon::parse($time)->format('g:i A') .
            ' just opened up. Claim it within 30 minutes: ' . $claimUrl,
            'waitlist_available',
            null
        );

        // Send slot-available email to the next patient
        $user = DB::table('users')->where('user_id', $next->user_id)->first();
        if ($user && $user->email) {
            $service = DB::table('services')->where('service_id', $serviceId)->first();
            Mail::to($user->email)->send(new WaitlistSlotAvailableMail(
                firstName:     $user->firstName,
                serviceName:   $service?->name ?? 'your selected service',
                preferredDate: $date,
                preferredTime: $time,
                claimUrl:      $claimUrl,
                expiresAt:     $expires->format('g:i A \o\n F j, Y'),
            ));
        }
    }

    // ── Patient's own waitlist entries ──────────────────────────
    public function myWaitlist()
    {
        $userId = (int) Session::get('user_id');

        $entries = DB::table('appointment_waitlist as w')
            ->join('services as s', 'w.service_id', '=', 's.service_id')
            ->where('w.user_id', $userId)
            ->whereIn('w.status', ['waiting', 'notified'])
            ->orderBy('w.preferred_date')
            ->orderBy('w.preferred_time')
            ->select(
                'w.waitlist_id',
                's.name as service_name',
                'w.preferred_date',
                'w.preferred_time',
                'w.status',
                'w.queue_position',
                'w.claim_expires_at'
            )
            ->get();

        return response()->json(['success' => true, 'entries' => $entries]);
    }

    // ── Patient removes themselves from waitlist ────────────────
    public function leave(Request $request)
    {
        $request->validate(['waitlist_id' => 'required|integer']);

        $userId = (int) Session::get('user_id');

        DB::table('appointment_waitlist')
            ->where('waitlist_id', $request->waitlist_id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['success' => true]);
    }
}