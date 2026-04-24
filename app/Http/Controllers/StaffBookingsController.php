<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;

class StaffBookingsController extends Controller
{
    use SidebarDataController;
    public function index(Request $request)
    {
        $this->autoCompleteAppointments();

        $activeFilter = $request->get('filter', 'all');

        $bookings = DB::select("
            SELECT
                a.appointment_id,
                s.name            AS service_name,
                CONCAT(p.firstName,' ',p.lastName) AS patient_name,
                CONCAT(d.firstName,' ',d.lastName) AS doctor_name,
                a.appointment_date,
                a.appointment_time,
                a.status
            FROM appointments a
            LEFT JOIN services s ON a.service_id = s.service_id
            LEFT JOIN users    p ON a.user_id    = p.user_id
            LEFT JOIN doctor doc ON a.doctor_id = doc.doctor_id
            LEFT JOIN users d ON doc.user_id = d.user_id
            ORDER BY a.appointment_date, a.appointment_time
        ");

        return view('staff_bookings', array_merge(
            $this->sidebarData(),
            compact('bookings', 'activeFilter')
        ));
    }

    public function updateStatus(Request $request)
    {
        $id     = (int) $request->input('appointment_id');
        $status = $request->input('status');

        $row = DB::selectOne(
            "SELECT status, service_id FROM appointments WHERE appointment_id = ?",
            [$id]
        );

        if (!$row || $row->status === 'cancelled') {
            return back()->with('error', 'This booking was already cancelled.');
        }

        DB::update("UPDATE appointments SET status = ? WHERE appointment_id = ?", [$status, $id]);

        if ($status === 'completed') {
            $this->deductInventory($id, (int) $row->service_id);
        }

        return redirect()->route('staff.bookings');
    }

    private function autoCompleteAppointments(): void
    {
        $appointments = DB::select("
            SELECT appointment_id, service_id FROM appointments
            WHERE status = 'approved'
            AND TIMESTAMP(appointment_date, appointment_time) <= (NOW() - INTERVAL 1 HOUR)
        ");

        foreach ($appointments as $appt) {
            DB::update("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?", [$appt->appointment_id]);

            $existing = DB::selectOne(
                "SELECT id FROM inventory_logs WHERE appointment_id = ? AND type = 'OUT'",
                [$appt->appointment_id]
            );

            if (!$existing) {
                $this->deductInventory($appt->appointment_id, (int) $appt->service_id);
            }
        }
    }

    private function deductInventory(int $appointmentId, int $serviceId): void
    {
        $alreadyDone = DB::selectOne(
            "SELECT id FROM inventory_logs WHERE appointment_id = ? AND type = 'OUT'",
            [$appointmentId]
        );
        if ($alreadyDone) return;

        $products = DB::select(
            "SELECT product_id, quantity_used FROM service_products WHERE service_id = ?",
            [$serviceId]
        );

        foreach ($products as $prod) {
            $productId = (int) $prod->product_id;
            $needed    = (int) $prod->quantity_used;
            $remaining = $needed;

            $stocks = DB::select("
                SELECT id, quantity FROM inventory_logs
                WHERE product_id = ? AND type = 'IN' AND quantity > 0
                ORDER BY expiry_date ASC, id ASC
            ", [$productId]);

            foreach ($stocks as $stock) {
                if ($remaining <= 0) break;
                $available = (int) $stock->quantity;

                if ($available >= $remaining) {
                    DB::update("UPDATE inventory_logs SET quantity = quantity - ? WHERE id = ?", [$remaining, $stock->id]);
                    $remaining = 0;
                } else {
                    DB::update("UPDATE inventory_logs SET quantity = 0 WHERE id = ?", [$stock->id]);
                    $remaining -= $available;
                }
            }

            $deducted = $needed - $remaining;

            if ($deducted > 0) {
                DB::insert(
                    "INSERT INTO inventory_logs (product_id, quantity, type, appointment_id, created_at) VALUES (?, ?, 'OUT', ?, NOW())",
                    [$productId, $deducted, $appointmentId]
                );

                DB::update("
                    UPDATE products SET quantity = (
                        SELECT IFNULL(SUM(quantity), 0) FROM inventory_logs
                        WHERE product_id = ? AND type = 'IN' AND quantity > 0
                    ) WHERE product_id = ?
                ", [$productId, $productId]);
            }
        }
    }
}