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
}