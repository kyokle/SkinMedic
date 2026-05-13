<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\NotificationHelper;

class PatientProductsController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────
    // GET  /patient/products
    // ─────────────────────────────────────────
    public function index()
    {
        $products = DB::table('products')
            ->select(
                'product_id', 'product_name', 'description', 'category',
                'selling_price', 'status', 'image', 'quantity', 'reorder_level'
            )
            ->orderBy('product_name')
            ->get();

        $categories = DB::table('products')
            ->select('category')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category');

        return view('patient_products', array_merge(
            $this->sidebarData(),
            compact('products', 'categories')
        ));
    }

    // ─────────────────────────────────────────
    // POST /patient/order/place
    // ─────────────────────────────────────────
    public function placeOrder(Request $request)
    {
        //added for testing
        \Log::info('Order request data', [
    'has_file'       => $request->hasFile('payment_proof'),
    'all_files'      => array_keys($request->allFiles()),
    'payment_method' => $request->input('payment_method'),
    'reference'      => $request->input('reference'),
]);
        $request->validate([
            'items' => 'required|string',
        ]);

        $items         = json_decode($request->input('items'), true);
        $note          = $request->input('note', '');
        $paymentMethod = $request->input('payment_method', 'cash');
        $userId        = session('user_id');

        if (empty($items) || !is_array($items)) {
            return back()->with('error', 'Your cart is empty.');
        }

        // ── Create the order header ────────────────────────
        $total = collect($items)->sum(fn($i) => $i['price'] * $i['qty']);

        $orderId = DB::table('orders')->insertGetId([
            'user_id'        => $userId,
            'total'          => $total,
            'note'           => $note,
            'payment_method' => $paymentMethod,
            'status'         => 'pending',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        // ── Insert order items + deduct FIFO inventory ─────
        foreach ($items as $item) {
            $productId = (int) $item['id'];
            $qty       = (int) $item['qty'];
            $price     = (float) $item['price'];

            DB::table('order_items')->insert([
                'order_id'   => $orderId,
                'product_id' => $productId,
                'quantity'   => $qty,
                'unit_price' => $price,
                'subtotal'   => $price * $qty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // FIFO deduction from inventory_logs
            $remaining = $qty;
            $batches   = DB::table('inventory_logs')
                ->where('product_id', $productId)
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
                    $remaining = 0;
                } else {
                    DB::table('inventory_logs')
                        ->where('id', $batch->id)
                        ->update(['quantity' => 0]);
                    $remaining -= $batch->quantity;
                }
            }

            // Recalculate product total quantity
            $newQty = DB::table('inventory_logs')
                ->where('product_id', $productId)
                ->where('type', 'IN')
                ->where('quantity', '>', 0)
                ->sum('quantity');

            DB::table('products')
                ->where('product_id', $productId)
                ->update(['quantity' => $newQty]);

            // ── Notify admins/staff of low / out-of-stock ──
            $product    = DB::table('products')->where('product_id', $productId)->first();
            $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

            if ($newQty == 0) {
                foreach ($adminStaff as $u) {
                    NotificationHelper::send(
                        $u->user_id,
                        '❌ Out of Stock',
                        ($product->product_name ?? 'A product') . ' is now out of stock.',
                        'inventory'
                    );
                }
            } elseif ($newQty <= $product->reorder_level) {
                foreach ($adminStaff as $u) {
                    NotificationHelper::send(
                        $u->user_id,
                        '⚠ Low Stock Warning',
                        ($product->product_name ?? 'A product') . ' is low on stock (' . $newQty . ' units remaining).',
                        'inventory'
                    );
                }
            }
        }

        // ── Notify patient ─────────────────────────────────
        if ($userId) {
            $paymentLabel = $paymentMethod === 'gcash' ? 'GCash' : 'Cash on Pick-up';
            NotificationHelper::send(
                $userId,
                '🛍 Order Placed',
                'Your order #' . $orderId . ' has been placed! Total: ₱' . number_format($total, 2) . '. Payment: ' . $paymentLabel . '. Please pick up your order at the clinic. We will contact you to confirm your schedule.'
            );
        }

        return redirect()
            ->route('patient.products')
            ->with('success', 'Order #' . $orderId . ' placed successfully! We will contact you shortly.');
    }
}