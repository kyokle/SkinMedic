<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

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
        if (!in_array(Session::get('role'), ['admin', 'staff'])) {
            return redirect()->route('index');
        }

        $this->autoComplete();

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

        return view('admin_bookings', array_merge(
            $this->sidebarData(),
            compact('bookings', 'activeFilter')
        ));
    }

    // ── POST /admin/bookings/update-status ────────────────────
    public function updateStatus(Request $request)
    {
        if (!in_array(Session::get('role'), ['admin', 'staff'])) {
            return redirect()->route('index');
        }

        $id     = (int) $request->input('appointment_id');
        $status = $request->input('status');

        $appt = DB::table('appointments')->where('appointment_id', $id)->first();

        if ($appt->status === 'cancelled') {
            return back()->with('error', 'This booking was already cancelled.');
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

        return redirect()->route('admin.bookings');
    }
}