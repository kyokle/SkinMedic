<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\NotificationHelper;
use App\Http\Controllers\WaitlistController;

class AdminBookingsController extends Controller
{
    use SidebarDataController;

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

            // Notify admin/admin
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

    // ── GET /admin/bookings ───────────────────────────────────
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
            ->where('role', 'doctor')
            ->orderBy('firstName')
            ->get();

        return view('admin_bookings', array_merge(
    $this->sidebarData(),
    compact('bookings', 'activeFilter', 'doctors')
    ));
    }

    // ── POST /admin/bookings/update-status ────────────────────
    public function updateStatus(Request $request)
    {
        if (!in_array(Session::get('role'), ['staff', 'admin'])) {
            return redirect()->route('index');
        }

        $id     = (int) $request->input('appointment_id');
        $status = $request->input('status');

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

                // Notify patient
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

                // Notify doctor
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

                // Notify admin/staff
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

                // Free up the slot for waitlisted patients
                WaitlistController::notifyNext(
                    $appt->appointment_date,
                    $appt->appointment_time
                );

                return back()->with('error', 'This appointment already passed without approval and has been automatically cancelled.');
            }
        }

        DB::table('appointments')
            ->where('appointment_id', $id)
            ->update(['status' => $status]);

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

        $patientMessages = [
            'approved'  => $isRescheduled ? 'Your rescheduled appointment has been approved.' : 'Your appointment has been approved.',
            'completed' => 'Your appointment has been marked as completed.',
            'cancelled' => 'Your appointment has been cancelled.',
        ];
        $patientTypes = [
            'approved'  => 'upcoming',
            'completed' => 'history',
            'cancelled' => 'cancelled',
        ];

        $staffMessages = [
            'approved'  => 'An appointment has been approved.',
            'completed' => 'An appointment has been marked as completed.',
            'cancelled' => 'An appointment has been cancelled.',
        ];
        $staffTypes = [
            'approved'  => 'booking',
            'completed' => 'booking',
            'cancelled' => 'booking',
        ];

        $title       = 'Appointment ' . ucfirst($status);
        $patientMsg  = $patientMessages[$status] ?? 'Your appointment status has been updated.';
        $patientType = $patientTypes[$status]    ?? 'upcoming';
        $staffMsg    = $staffMessages[$status]   ?? 'An appointment status has been updated.';
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

        // Notify next waitlisted patient when slot is freed
        if ($status === 'cancelled') {
            WaitlistController::notifyNext(
                $appt->appointment_date,
                $appt->appointment_time
            );
        }

        // After completing an appointment, redirect to walk-in sale
        // with patient and appointment pre-filled for billing
        if ($status === 'completed') {
            return redirect()->route('admin.walkin', [
                'from_appointment' => $id,
                'patient_id'       => $appt->user_id,
            ])->with('from_booking', 'Appointment completed — patient details pre-filled for billing.');
        }

        return redirect()->route('admin.bookings');
    }
}