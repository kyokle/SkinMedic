<?php
// app/Http/Controllers/StaffOrdersController.php
namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;

class StaffOrdersController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────
    // GET  /staff/orders  (also /admin/orders)
    // ─────────────────────────────────────────
    public function index(Request $request)
    {
        $activeFilter = $request->query('filter', 'all');

        // All orders with patient name
        $orders = DB::table('orders')
            ->join('users', 'orders.user_id', '=', 'users.user_id')
            ->select(
                'orders.id',
                'orders.user_id',
                'orders.total',
                'orders.note',
                'orders.status',
                'orders.payment_method',
                'orders.payment_status',
                'orders.payment_proof',
                'orders.reference',
                'orders.created_at',
                DB::raw("CONCAT(users.firstName, ' ', users.lastName) as patient_name")
            )
            ->orderByDesc('orders.created_at')
            ->get();

        // Attach items to each order
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

        return view('staff_orders', array_merge(
            $this->sidebarData(),
            compact('orders', 'activeFilter')
        ));
    }

    // ─────────────────────────────────────────
    // POST /staff/orders/update-status
    // ─────────────────────────────────────────
    public function updateStatus(Request $request)
    {
        $request->validate([
            'order_id'   => 'required|integer',
            'status'     => 'required|in:confirmed,packing,ready,completed,cancelled',
            'pay_status' => 'nullable|in:paid,unpaid',
        ]);

        $orderId   = (int) $request->input('order_id');
        $status    = $request->input('status');
        $payStatus = $request->input('pay_status');

        $order = DB::table('orders')->where('id', $orderId)->first();
        if (!$order) return back()->with('error', 'Order not found.');

        // Build update payload
        $update = [
            'status'     => $status,
            'updated_at' => now(),
        ];

        if ($payStatus) {
            $update['payment_status'] = $payStatus;
        }

        DB::table('orders')->where('id', $orderId)->update($update);

        // ── Notify patient of status change ───────────────
        $messages = [
            'confirmed' => '✅ Your order #' . $orderId . ' has been confirmed! We are preparing your items.',
            'packing'   => '📦 Your order #' . $orderId . ' is now being packed.',
            'ready'     => '🏪 Your order #' . $orderId . ' is ready for pick-up at the clinic!',
            'completed' => '✔ Your order #' . $orderId . ' has been completed. Thank you!',
            'cancelled' => '✕ Your order #' . $orderId . ' has been cancelled. Please contact us for assistance.',
        ];

        if (isset($messages[$status]) && $order->user_id) {
            NotificationHelper::send(
                $order->user_id,
                $this->notifTitle($status),
                $messages[$status]
            );
        }

        $statusLabel = ucfirst($status);
        return back()->with('success', "Order #{$orderId} has been marked as {$statusLabel}.");
    }

    private function notifTitle(string $status): string
    {
        return match($status) {
            'confirmed' => '✅ Order Confirmed',
            'packing'   => '📦 Order Being Packed',
            'ready'     => '🏪 Ready for Pick-up',
            'completed' => '✔ Order Completed',
            'cancelled' => '✕ Order Cancelled',
            default     => '🛍 Order Update',
        };
    }
}