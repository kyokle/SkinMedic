<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\SidebarDataController;


class AdminInventoryController extends Controller
{
    use SidebarDataController;
    private const ALLOWED_ROLES = ['admin', 'staff'];

    public function index()
    {
        $this->autoRemoveExpiredStock();
        $this->syncProductQuantities();

        $lowStock   = collect(DB::select("SELECT * FROM products WHERE quantity > 0 AND quantity <= reorder_level"));
        $outOfStock = collect(DB::select("SELECT * FROM products WHERE quantity = 0"));

        $nearExpiry = collect(DB::select("
    SELECT p.product_name, l.expiry_date, SUM(l.quantity) AS total_qty
    FROM inventory_logs l
    JOIN products p ON p.product_id = l.product_id
    WHERE l.type = 'IN' AND l.quantity > 0
      AND l.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    GROUP BY p.product_id, p.product_name, l.expiry_date
    ORDER BY l.expiry_date ASC
"));

        $products = collect(DB::select("
            SELECT
                p.*,
                (SELECT MAX(created_at) FROM inventory_logs
                 WHERE product_id = p.product_id AND type = 'IN') AS last_added,
                (SELECT MIN(expiry_date) FROM inventory_logs
                 WHERE product_id = p.product_id AND type = 'IN'
                   AND quantity > 0 AND expiry_date IS NOT NULL) AS current_expiry,
                (SELECT SUM(quantity) FROM inventory_logs
                 WHERE product_id = p.product_id AND type = 'IN' AND quantity > 0
                   AND expiry_date = (
                       SELECT MIN(expiry_date) FROM inventory_logs
                       WHERE product_id = p.product_id AND type = 'IN'
                         AND quantity > 0 AND expiry_date IS NOT NULL
                   )) AS current_batch_qty,
                (SELECT MIN(expiry_date) FROM inventory_logs
                 WHERE product_id = p.product_id AND type = 'IN'
                   AND quantity > 0 AND expiry_date IS NOT NULL
                   AND expiry_date > (
                       SELECT MIN(expiry_date) FROM inventory_logs
                       WHERE product_id = p.product_id AND type = 'IN'
                         AND quantity > 0 AND expiry_date IS NOT NULL
                   )) AS next_expiry
            FROM products p
            ORDER BY p.product_name ASC
        "));

        return view('admin_inventory', array_merge(
            $this->sidebarData(),
            compact('products', 'lowStock', 'outOfStock', 'nearExpiry')
        ));
    }

    public function addStock(Request $request)
    {
        $productId  = (int) $request->input('product_id');
        $qty        = (int) $request->input('quantity');
        $expiryDate = $request->input('expiry_date');

        DB::insert(
            "INSERT INTO inventory_logs (product_id, quantity, type, expiry_date, created_at) VALUES (?, ?, 'IN', ?, NOW())",
            [$productId, $qty, $expiryDate]
        );

        DB::update("
            UPDATE products SET quantity = (
                SELECT IFNULL(SUM(quantity), 0) FROM inventory_logs
                WHERE product_id = ? AND type = 'IN' AND quantity > 0
            ) WHERE product_id = ?
        ", [$productId, $productId]);

        return redirect()->route('admin.inventory');
    }

    private function autoRemoveExpiredStock(): void
    {
        DB::update("
            UPDATE inventory_logs SET quantity = 0
            WHERE type = 'IN' AND expiry_date IS NOT NULL
              AND expiry_date < CURDATE() AND quantity > 0
        ");
    }

    private function syncProductQuantities(): void
    {
        DB::update("
            UPDATE products p SET quantity = (
                SELECT IFNULL(SUM(CASE WHEN type = 'IN' THEN quantity ELSE -quantity END), 0)
                FROM inventory_logs
                WHERE product_id = p.product_id AND quantity > 0
            )
        ");
    }

    public function deductStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
            'action'     => 'required|in:deduct,set',
        ]);

        $product = DB::table('products')->where('product_id', $request->product_id)->first();

        if (!$product) return back()->with('error', 'Product not found.');

        if ($request->action === 'set') {
            // Calculate the difference needed to reach the desired quantity
            $diff = $product->quantity - $request->quantity;
            if ($diff <= 0) return back()->with('error', 'Set quantity must be less than current stock.');
            $deductQty = $diff;
        } else {
            if ($request->quantity > $product->quantity) {
                return back()->with('error', 'Cannot deduct more than current stock.');
            }
            $deductQty = $request->quantity;
        }

        // Insert an OUT log entry so syncProductQuantities() reflects the change on next load
        DB::insert(
            "INSERT INTO inventory_logs (product_id, quantity, type, created_at) VALUES (?, ?, 'OUT', NOW())",
            [$request->product_id, $deductQty]
        );

        // Immediately sync this product's quantity in the products table
        DB::update("
            UPDATE products SET quantity = (
                SELECT IFNULL(SUM(CASE WHEN type = 'IN' THEN quantity ELSE -quantity END), 0)
                FROM inventory_logs
                WHERE product_id = ? AND quantity > 0
            ) WHERE product_id = ?
        ", [$request->product_id, $request->product_id]);

        return back()->with('success', 'Stock updated successfully.');
    }
}