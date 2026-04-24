<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminInventoryController extends Controller
{
    use SidebarDataController;

    private const ALLOWED_ROLES = ['admin', 'staff'];

    /** Days ahead to flag as near-expiry */
    private const NEAR_EXPIRY_DAYS = 7;

    // ── Auth guard ────────────────────────────────────────────
    private function authorise()
    {
        if (!in_array(Session::get('role'), self::ALLOWED_ROLES)) {
            return redirect()->route('index');
        }
        return null;
    }

    // ── Expire & sync helper ──────────────────────────────────
    private function autoExpire(): void
    {
        DB::table('inventory_logs')
            ->where('type', 'IN')
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<', now()->toDateString())
            ->where('quantity', '>', 0)
            ->update(['quantity' => 0]);

        DB::statement("
            UPDATE products p
            SET quantity = (
                SELECT IFNULL(SUM(quantity), 0)
                FROM inventory_logs
                WHERE product_id = p.product_id
                  AND type = 'IN'
                  AND quantity > 0
            )
        ");
    }

    // ── Sync a single product's quantity ─────────────────────
    private function syncProductQuantity(int $productId): void
    {
        $total = DB::table('inventory_logs')
            ->where('product_id', $productId)
            ->where('type', 'IN')
            ->where('quantity', '>', 0)
            ->sum('quantity');

        DB::table('products')
            ->where('product_id', $productId)
            ->update(['quantity' => $total]);
    }

    // ── GET /admin/inventory ──────────────────────────────────
    public function index()
    {
        if ($redirect = $this->authorise()) return $redirect;

        $this->autoExpire();

        $inventoryItems = DB::table('products as p')
            ->selectRaw("
                p.*,

                (SELECT MAX(created_at)
                 FROM inventory_logs
                 WHERE product_id = p.product_id AND type = 'IN'
                ) AS last_added,

                (SELECT MIN(expiry_date)
                 FROM inventory_logs
                 WHERE product_id = p.product_id
                   AND type = 'IN' AND quantity > 0
                   AND expiry_date IS NOT NULL
                ) AS current_expiry,

                (SELECT SUM(quantity)
                 FROM inventory_logs
                 WHERE product_id = p.product_id
                   AND type = 'IN' AND quantity > 0
                   AND expiry_date = (
                       SELECT MIN(expiry_date)
                       FROM inventory_logs
                       WHERE product_id = p.product_id
                         AND type = 'IN' AND quantity > 0
                         AND expiry_date IS NOT NULL
                   )
                ) AS current_batch_qty,

                (SELECT MIN(expiry_date)
                 FROM inventory_logs
                 WHERE product_id = p.product_id
                   AND type = 'IN' AND quantity > 0
                   AND expiry_date IS NOT NULL
                   AND expiry_date > (
                       SELECT MIN(expiry_date)
                       FROM inventory_logs
                       WHERE product_id = p.product_id
                         AND type = 'IN' AND quantity > 0
                         AND expiry_date IS NOT NULL
                   )
                ) AS next_expiry
            ")
            ->orderBy('p.product_name')
            ->get();

        $lowStockItems = DB::table('products')
            ->where('quantity', '>', 0)
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->get();

        $outOfStockItems = DB::table('products')
            ->where('quantity', 0)
            ->get();

        $nearExpiryItems = DB::table('inventory_logs as l')
            ->join('products as p', 'p.product_id', '=', 'l.product_id')
            ->selectRaw('p.product_name, l.expiry_date, SUM(l.quantity) AS total_qty')
            ->where('l.type', 'IN')
            ->where('l.quantity', '>', 0)
            ->whereBetween('l.expiry_date', [
                now()->toDateString(),
                now()->addDays(self::NEAR_EXPIRY_DAYS)->toDateString(),
            ])
            ->groupBy('p.product_id', 'p.product_name', 'l.expiry_date')
            ->orderBy('l.expiry_date')
            ->get();

        return view('admin_inventory', array_merge(
            $this->sidebarData(),
            compact('inventoryItems', 'lowStockItems', 'outOfStockItems', 'nearExpiryItems')
        ));
    }

    // ── POST /admin/inventory/add-stock ───────────────────────
    public function addStock(Request $request)
    {
        if ($redirect = $this->authorise()) return $redirect;

        $productId  = (int) $request->input('product_id');
        $quantity   = (int) $request->input('quantity');
        $expiryDate = $request->input('expiry_date');

        DB::table('inventory_logs')->insert([
            'product_id'  => $productId,
            'quantity'    => $quantity,
            'type'        => 'IN',
            'expiry_date' => $expiryDate,
            'created_at'  => now(),
        ]);

        $this->syncProductQuantity($productId);

        return redirect()->route('admin.inventory');
    }
}