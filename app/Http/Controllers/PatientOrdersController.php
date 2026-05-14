<?php
// app/Http/Controllers/PatientOrdersController.php
namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PatientOrdersController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────
    // GET  /patient/orders
    // ─────────────────────────────────────────
    public function index()
    {
        $userId = session('user_id');

        // Fetch all orders for this patient, newest first
        $orders = DB::table('orders')
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        // Attach items + product image to each order
        foreach ($orders as $order) {
            $order->items = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.product_id')
                ->where('order_items.order_id', $order->id)
                ->select(
                    'order_items.quantity',
                    'order_items.unit_price',
                    'order_items.subtotal',
                    'products.product_name',
                    'products.image'
                )
                ->get();
        }

        return view('patient_orders', array_merge(
            $this->sidebarData(),
            compact('orders')
        ));
    }

    // ─────────────────────────────────────────
    // PATCH  /patient/orders/{id}/cancel
    // ─────────────────────────────────────────
    public function cancel(Request $request, $id)
    {
        $userId = session('user_id');

        // Validate inputs
        $request->validate([
            'cancel_reason' => 'required|in:changed_mind,wrong_item,found_better_price,too_long,duplicate_order,other',
            'cancel_notes'  => 'nullable|string|max:500',
        ]);

        // Find the order and make sure it belongs to this patient
        $order = DB::table('orders')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$order) {
            return redirect()->route('patient.orders')
                ->with('error', 'Order not found.');
        }

        // Only pending or confirmed orders can be cancelled
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return redirect()->route('patient.orders')
                ->with('error', 'This order can no longer be cancelled.');
        }

        // Update the order
        DB::table('orders')
            ->where('id', $id)
            ->update([
                'status'        => 'cancelled',
                'cancel_reason' => $request->cancel_reason,
                'cancel_notes'  => $request->cancel_notes,
                'updated_at'    => now(),
            ]);

        return redirect()->route('patient.orders')
            ->with('success', 'Order #' . $id . ' has been cancelled.');
    }
}