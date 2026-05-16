<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\WaitlistController;

class StaffBookingsController extends Controller
{
    use SidebarDataController;

    // ── Get the name of the currently logged-in staff/admin ──
    private function actorName(): string
    {
        $userId = Session::get('user_id');
        if (!$userId) return 'Staff';
        $user = DB::table('users')->where('user_id', $userId)->first();
        return $user ? trim($user->firstName . ' ' . $user->lastName) : 'Staff';
    }

    // ── Auto-complete helper ──────────────────────────────────
    private function autoComplete(): void
    {
        $expired = DB::table('appointments')
            ->where('status', 'approved')
            ->whereRaw("TIMESTAMP(appointment_date, appointment_time) <= (NOW() - INTERVAL 1 HOUR)")
            ->get();

        foreach ($expired as $appt) {
            DB::table('appointments')
                ->where('appointment_id', $appt->appointment_id)
                ->update(['status' => 'completed']);

            $alreadyDeducted = DB::table('inventory_logs')
                ->where('appointment_id', $appt->appointment_id)
                ->where('type', 'OUT')
                ->exists();

            if (!$alreadyDeducted) {
                $this->deductInventory($appt->appointment_id, $appt->service_id);
            }
        }
    }

    // ── Auto-cancel helper ───────────────────────────────────
    private function autoCancel(): void
    {
        $nowManila = \Carbon\Carbon::now('Asia/Manila');

        $expired = DB::table('appointments')
            ->where('status', 'pending')
            ->where(function ($query) use ($nowManila) {
                $query->where('appointment_date', '<', $nowManila->toDateString())
                      ->orWhere(function ($q) use ($nowManila) {
                          $q->where('appointment_date', '=', $nowManila->toDateString())
                            ->where('appointment_time', '<', $nowManila->format('H:i:s'));
                      });
            })
            ->get();

        foreach ($expired as $appt) {
            DB::table('appointments')
                ->where('appointment_id', $appt->appointment_id)
                ->update([
                    'status'        => 'cancelled',
                    'cancel_reason' => 'expired_no_approval',
                    'updated_at'    => now(),
                ]);

            // Notify patient
            NotificationHelper::send(
                $appt->user_id,
                'Appointment Cancelled',
                "Your appointment on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled because it was not approved before the scheduled time.",
                'cancelled',
                $appt->appointment_id
            );

            // Notify doctor
            $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
            if ($doctor && $doctor->user_id) {
                NotificationHelper::send(
                    $doctor->user_id,
                    'Appointment Auto-Cancelled',
                    "Appointment #{$appt->appointment_id} on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled (no approval before scheduled time).",
                    'cancelled',
                    $appt->appointment_id
                );
            }

            // Notify admin/staff
            $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    'Appointment Auto-Cancelled',
                    "Appointment #{$appt->appointment_id} on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled (no approval before scheduled time).",
                    'cancelled',
                    $appt->appointment_id
                );
            }

            // Free the slot for waitlisted patients
            WaitlistController::notifyNext(
                $appt->appointment_date,
                $appt->appointment_time
            );
        }
    }

    // ── FIFO deduction helper ─────────────────────────────────
    private function deductInventory(int $appointmentId, int $serviceId): void
    {
        $serviceProducts = DB::table('service_products')
            ->where('service_id', $serviceId)
            ->get();

        foreach ($serviceProducts as $prod) {
            $remaining = $prod->quantity_used;

            $batches = DB::table('inventory_logs')
                ->where('product_id', $prod->product_id)
                ->where('type', 'IN')
                ->where('quantity', '>', 0)
                ->orderBy('expiry_date')
                ->orderBy('id')
                ->get();

            foreach ($batches as $batch) {
                if ($remaining <= 0) break;

                if ($batch->quantity >= $remaining) {
                    DB::table('inventory_logs')
                        ->where('id', $batch->id)
                        ->decrement('quantity', $remaining);
                    $deducted  = $remaining;
                    $remaining = 0;
                } else {
                    DB::table('inventory_logs')
                        ->where('id', $batch->id)
                        ->update(['quantity' => 0]);
                    $deducted   = $batch->quantity;
                    $remaining -= $batch->quantity;
                }

                if ($deducted > 0) {
                    DB::table('inventory_logs')->insert([
                        'product_id'     => $prod->product_id,
                        'quantity'       => $deducted,
                        'type'           => 'OUT',
                        'appointment_id' => $appointmentId,
                        'created_at'     => now(),
                    ]);

                    $total = DB::table('inventory_logs')
                        ->where('product_id', $prod->product_id)
                        ->where('type', 'IN')
                        ->where('quantity', '>', 0)
                        ->sum('quantity');

                    DB::table('products')
                        ->where('product_id', $prod->product_id)
                        ->update(['quantity' => $total]);
                }
            }
        }
    }

    // ── GET /staff/bookings ───────────────────────────────────
    public function index(Request $request)
    {
        if (!in_array(Session::get('role'), ['staff', 'admin'])) {
            return redirect()->route('index');
        }

        $this->autoComplete();
        $this->autoCancel();

        $bookings = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('users as p',    'a.user_id',    '=', 'p.user_id')
            ->leftJoin('doctor as doc',  'a.doctor_id',     '=', 'doc.doctor_id')
            ->leftJoin('users as d',     'doc.user_id',     '=', 'd.user_id')
            ->selectRaw("
                a.appointment_id,
                s.name AS service_name,
                CONCAT(p.firstName,' ',p.lastName) AS patient_name,
                CONCAT(d.firstName,' ',d.lastName) AS doctor_name,
                a.appointment_date,
                a.appointment_time,
                a.status
            ")
            ->orderBy('a.appointment_date')
            ->orderBy('a.appointment_time')
            ->get();

        $activeFilter = $request->query('filter', 'all');

        $doctors = DB::table('users')
            ->join('doctor', 'doctor.user_id', '=', 'users.user_id')
            ->where('users.role', 'doctor')
            ->select('users.user_id', 'users.firstName', 'users.lastName', 'doctor.doctor_id', 'doctor.availability_schedule')
            ->orderBy('users.firstName')
            ->get();

        // ── Patients with their last completed appointment info ──
        $patients = DB::table('users as u')
            ->leftJoin(DB::raw("(
                SELECT a.user_id,
                       a.appointment_date  AS last_appt_date,
                       a.appointment_time  AS last_appt_time,
                       a.service_id        AS last_service_id,
                       a.doctor_id         AS last_doctor_id
                FROM appointments a
                INNER JOIN (
                    SELECT user_id, MAX(appointment_date) AS max_date
                    FROM appointments
                    WHERE status = 'completed'
                    GROUP BY user_id
                ) latest ON latest.user_id = a.user_id AND a.appointment_date = latest.max_date
                WHERE a.status = 'completed'
            ) AS last_appt"), 'last_appt.user_id', '=', 'u.user_id')
            ->where('u.role', 'patient')
            ->select(
                'u.user_id', 'u.firstName', 'u.lastName',
                'last_appt.last_appt_date',
                'last_appt.last_appt_time',
                'last_appt.last_service_id',
                'last_appt.last_doctor_id'
            )
            ->orderBy('u.firstName')
            ->get();

        // ── Services list for follow-up modal ──
        $services = DB::table('services')->orderBy('name')->get();

        return view('staff_bookings', array_merge(
            $this->sidebarData(),
            compact('bookings', 'activeFilter', 'doctors', 'patients', 'services')
        ));
    }

    // ── POST /staff/bookings/update-status ────────────────────
    public function updateStatus(Request $request)
    {
        if (!in_array(Session::get('role'), ['staff', 'admin'])) {
            return redirect()->route('index');
        }

        $actor        = $this->actorName();
        $id           = (int) $request->input('appointment_id');
        $status       = $request->input('status');
        $cancelReason = trim($request->input('cancel_reason', ''));

        $appt = DB::table('appointments')->where('appointment_id', $id)->first();

        if ($appt->status === 'cancelled') {
            return back()->with('error', 'This booking was already cancelled.');
        }

        // ── Guard: block approving/completing a past pending appointment ──
        if ($appt->status === 'pending' && in_array($status, ['approved', 'completed'])) {
            $apptDateTime = \Carbon\Carbon::parse(
                $appt->appointment_date . ' ' . $appt->appointment_time,
                'Asia/Manila'
            );

            if ($apptDateTime->isPast()) {
                DB::table('appointments')
                    ->where('appointment_id', $id)
                    ->update([
                        'status'        => 'cancelled',
                        'cancel_reason' => 'expired_no_approval',
                        'updated_at'    => now(),
                    ]);

                $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
                if ($patient) {
                    NotificationHelper::send(
                        $patient->user_id,
                        'Appointment Cancelled',
                        "Your appointment on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled because it was not approved before the scheduled time.",
                        'cancelled',
                        $id
                    );
                }

                $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
                if ($doctor && $doctor->user_id) {
                    NotificationHelper::send(
                        $doctor->user_id,
                        'Appointment Auto-Cancelled',
                        "Appointment #{$id} on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled (no approval before scheduled time).",
                        'cancelled',
                        $id
                    );
                }

                $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
                foreach ($adminStaff as $u) {
                    NotificationHelper::send(
                        $u->user_id,
                        'Appointment Auto-Cancelled',
                        "Appointment #{$id} on {$appt->appointment_date} at {$appt->appointment_time} was automatically cancelled (no approval before scheduled time).",
                        'cancelled',
                        $id
                    );
                }

                WaitlistController::notifyNext(
                    $appt->appointment_date,
                    $appt->appointment_time
                );

                return back()->with('error', 'This appointment already passed without approval and has been automatically cancelled.');
            }
        }

        $updatePayload = ['status' => $status, 'updated_at' => now()];
        if ($status === 'cancelled' && $cancelReason !== '') {
            $updatePayload['cancel_reason'] = $cancelReason;
        }

        DB::table('appointments')
            ->where('appointment_id', $id)
            ->update($updatePayload);

        if ($status === 'completed') {
            $alreadyDeducted = DB::table('inventory_logs')
                ->where('appointment_id', $id)
                ->where('type', 'OUT')
                ->exists();

            if (!$alreadyDeducted) {
                $this->deductInventory($id, $appt->service_id);
            }
        }

        $isRescheduled = $appt->is_rescheduled ?? false;

        if ($status === 'approved') {
            DB::table('appointments')
                ->where('appointment_id', $id)
                ->update(['is_rescheduled' => false]);
        }

        $reasonSuffix = ($status === 'cancelled' && $cancelReason !== '')
            ? " Reason: {$cancelReason}"
            : '';

        // ── Patient-facing messages (no actor name, keeps it clean) ──
        $patientMessages = [
            'approved'  => $isRescheduled
                ? "Your rescheduled appointment has been approved by {$actor}."
                : "Your appointment has been approved by {$actor}.",
            'completed' => "Your appointment has been marked as completed by {$actor}.",
            'cancelled' => "Your appointment has been cancelled by {$actor}." . $reasonSuffix,
        ];
        $patientTypes = [
            'approved'  => 'upcoming',
            'completed' => 'history',
            'cancelled' => 'cancelled',
        ];

        // ── Staff/admin/doctor messages (include actor for accountability) ──
        $staffMessages = [
            'approved'  => "{$actor} approved appointment #{$id} on {$appt->appointment_date} at {$appt->appointment_time}.",
            'completed' => "{$actor} marked appointment #{$id} on {$appt->appointment_date} at {$appt->appointment_time} as completed.",
            'cancelled' => "{$actor} cancelled appointment #{$id} on {$appt->appointment_date} at {$appt->appointment_time}." . $reasonSuffix,
        ];
        $staffTypes = [
            'approved'  => 'booking',
            'completed' => 'booking',
            'cancelled' => 'booking',
        ];

        $title       = 'Appointment ' . ucfirst($status);
        $patientMsg  = $patientMessages[$status] ?? "Your appointment status was updated by {$actor}.";
        $patientType = $patientTypes[$status]    ?? 'upcoming';
        $staffMsg    = $staffMessages[$status]   ?? "{$actor} updated appointment #{$id} status to {$status}.";
        $staffType   = $staffTypes[$status]      ?? 'booking';

        $patient = DB::table('users')->where('user_id', $appt->user_id)->first();
        if ($patient) {
            NotificationHelper::send($patient->user_id, $title, $patientMsg, $patientType, $id);
        }

        $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
        if ($doctor && $doctor->user_id) {
            NotificationHelper::send($doctor->user_id, $title, $staffMsg, $staffType, $id);
        }

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) {
            NotificationHelper::send($u->user_id, $title, $staffMsg, $staffType, $id);
        }

        if ($status === 'cancelled') {
            WaitlistController::notifyNext(
                $appt->appointment_date,
                $appt->appointment_time
            );
        }

        if ($status === 'completed') {
            return redirect()->route('staff.walkin', [
                'from_appointment' => $id,
                'patient_id'       => $appt->user_id,
            ])->with('from_booking', 'Appointment completed — patient details pre-filled for billing.');
        }

        return redirect()->route('staff.bookings');
    }

    // ── POST /staff/bookings/followup ─────────────────────────
    public function storeFollowUp(Request $request)
    {
        if (!in_array(Session::get('role'), ['staff', 'admin'])) {
            return redirect()->route('index');
        }

        $request->validate([
            'user_id'          => 'required|integer',
            'service_id'       => 'required|integer',
            'doctor_id'        => 'required|integer',
            'appointment_date' => 'required|date',
            'appointment_time' => 'required',
        ]);

        // Resolve doctor_id from doctor table (may be passed as user_id of doctor)
        $doctor = DB::table('doctor')->where('doctor_id', $request->doctor_id)->first();
        if (!$doctor) {
            // Try looking up by user_id as fallback
            $doctor = DB::table('doctor')->where('user_id', $request->doctor_id)->first();
        }

        $appointmentId = DB::table('appointments')->insertGetId([
            'user_id'          => $request->user_id,
            'service_id'       => $request->service_id,
            'doctor_id'        => $doctor ? $doctor->doctor_id : $request->doctor_id,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time,
            'status'           => 'approved',   // follow-ups are pre-approved
            'notes'            => $request->input('notes', ''),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $actor   = $this->actorName();
        $patient = DB::table('users')->where('user_id', $request->user_id)->first();

        // Notify patient
        if ($patient) {
            NotificationHelper::send(
                $patient->user_id,
                'Follow-up Appointment Scheduled',
                "A follow-up appointment has been scheduled for you on {$request->appointment_date} at {$request->appointment_time} by {$actor}.",
                'upcoming',
                $appointmentId
            );
        }

        // Notify doctor
        if ($doctor && $doctor->user_id) {
            NotificationHelper::send(
                $doctor->user_id,
                'Follow-up Appointment Scheduled',
                "{$actor} scheduled a follow-up appointment #{$appointmentId} on {$request->appointment_date} at {$request->appointment_time}.",
                'booking',
                $appointmentId
            );
        }

        return redirect()->route('staff.bookings')
            ->with('success', "Follow-up appointment scheduled for {$patient?->firstName} {$patient?->lastName} on {$request->appointment_date} at {$request->appointment_time}.");
    }
}