<?php
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
                'orders.cancel_reason',
                'orders.cancel_notes',
                'orders.created_at',
                'orders.updated_at',
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
            'order_id'      => 'required|integer',
            'status'        => 'required|in:confirmed,processing,ready_for_pickup,completed,cancelled',
            'pay_status'    => 'nullable|in:paid,unpaid',
            'cancel_reason' => 'nullable|string|max:100',
            'cancel_notes'  => 'nullable|string|max:500',
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

        if ($status === 'cancelled') {
            $update['cancel_reason'] = $request->input('cancel_reason');
            $update['cancel_notes']  = $request->input('cancel_notes') ?: null;
        }

        DB::table('orders')->where('id', $orderId)->update($update);

        // ── Notify patient of status change ───────────────
        $messages = [
            'confirmed'        => '✅ Your order #' . $orderId . ' has been confirmed! We are preparing your items.',
            'processing'       => '📦 Your order #' . $orderId . ' is now being packed.',
            'ready_for_pickup' => '🏪 Your order #' . $orderId . ' is ready for pick-up at the clinic!',
            'completed'        => '✔ Your order #' . $orderId . ' has been completed. Thank you!',
            'cancelled'        => '✕ Your order #' . $orderId . ' has been cancelled. Please contact us for assistance.',
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
            'confirmed'        => '✅ Order Confirmed',
            'processing'       => '📦 Order Being Packed',
            'ready_for_pickup' => '🏪 Ready for Pick-up',
            'completed'        => '✔ Order Completed',
            'cancelled'        => '✕ Order Cancelled',
            default            => '🛍 Order Update',
        };
    }

    // ─────────────────────────────────────────
    // POST /patient/orders/cancel
    // ─────────────────────────────────────────
    public function patientCancel(Request $request)
    {
        $request->validate([
            'order_id'      => 'required|integer',
            'cancel_reason' => 'required|string|max:100',
            'cancel_notes'  => 'nullable|string|max:500',
        ]);

        $orderId = (int) $request->input('order_id');
        $userId  = (int) \Illuminate\Support\Facades\Session::get('user_id');

        $order = DB::table('orders')
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->first();

        if (!$order || !in_array($order->status, ['pending', 'confirmed'])) {
            return back()->with('error', 'This order cannot be cancelled.');
        }

        DB::table('orders')->where('id', $orderId)->update([
            'status'        => 'cancelled',
            'cancel_reason' => $request->input('cancel_reason'),
            'cancel_notes'  => $request->input('cancel_notes') ?: null,
            'updated_at'    => now(),
        ]);

        $msg      = '✕ Your order #' . $orderId . ' has been cancelled by you.';
        $staffMsg = 'A patient has cancelled order #' . $orderId . '. Reason: ' . $request->input('cancel_reason') . '.';

        NotificationHelper::send($userId, '✕ Order Cancelled', $msg);

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();
        foreach ($adminStaff as $u) {
            NotificationHelper::send($u->user_id, '✕ Order Cancelled by Patient', $staffMsg);
        }

        return back()->with('success', 'Your order has been cancelled.');
    }
}