<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;

class CancelExpiredAppointments extends Command
{
    protected $signature   = 'appointments:cancel-expired';
    protected $description = 'Auto-cancel pending appointments whose date and time have already passed.';

    public function handle()
    {
        // Fetch all pending appointments where date+time is in the past (Manila time)
        $expired = DB::table('appointments')
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->where('appointment_date', '<', now()->setTimezone('Asia/Manila')->toDateString())
                      ->orWhere(function ($q) {
                          $q->where('appointment_date', '=', now()->setTimezone('Asia/Manila')->toDateString())
                            ->where('appointment_time', '<', now()->setTimezone('Asia/Manila')->format('H:i:s'));
                      });
            })
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired pending appointments found.');
            return;
        }

        foreach ($expired as $appt) {
            // Mark as cancelled
            DB::table('appointments')
                ->where('appointment_id', $appt->appointment_id)
                ->update([
                    'status'        => 'cancelled',
                    'cancel_reason' => 'expired_no_approval',
                    'updated_at'    => now(),
                ]);

            $dateLabel = $appt->appointment_date;
            $timeLabel = $appt->appointment_time;

            // Notify the patient
            NotificationHelper::send(
                $appt->user_id,
                'Appointment Cancelled',
                "Your appointment on {$dateLabel} at {$timeLabel} was automatically cancelled because it was not approved in time.",
                'cancelled',
                $appt->appointment_id
            );

            // Notify the assigned doctor
            $doctor = DB::table('doctor')->where('doctor_id', $appt->doctor_id)->first();
            if ($doctor && $doctor->user_id) {
                NotificationHelper::send(
                    $doctor->user_id,
                    'Appointment Auto-Cancelled',
                    "An appointment scheduled on {$dateLabel} at {$timeLabel} was automatically cancelled due to no approval.",
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
                    "Appointment #{$appt->appointment_id} on {$dateLabel} at {$timeLabel} was automatically cancelled (no approval).",
                    'cancelled',
                    $appt->appointment_id
                );
            }

            $this->info("Cancelled appointment #{$appt->appointment_id} ({$dateLabel} {$timeLabel})");
        }

        $this->info("Done. {$expired->count()} appointment(s) cancelled.");
    }
}