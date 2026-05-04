<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;
use App\Helpers\NotificationHelper;

class AdminInventoryController extends Controller
{
    use SidebarDataController;

    public function index()
    {
        $this->autoRemoveExpiredStock();
        $this->checkNearExpiry(); // ← expiry notifications
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

        // ── Notifications ──────────────────────────────────────
        $product    = DB::table('products')->where('product_id', $productId)->first();
        $newQty     = DB::table('inventory_logs')
                        ->where('product_id', $productId)
                        ->where('type', 'IN')
                        ->where('quantity', '>', 0)
                        ->sum('quantity');

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                'Stock Added',
                ($product->product_name ?? 'A product') . ' had ' . $qty . ' units added. Total stock: ' . $newQty . '.'
            );
        }

        if ($newQty > 0 && $newQty <= $product->reorder_level) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '⚠ Low Stock Warning',
                    ($product->product_name ?? 'A product') . ' is low on stock (' . $newQty . ' units remaining).'
                );
            }
        }

        if ($newQty == 0) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '❌ Out of Stock',
                    ($product->product_name ?? 'A product') . ' is now out of stock.'
                );
            }
        }
        // ──────────────────────────────────────────────────────

        return redirect()->route('admin.inventory');
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
        $diff = $product->quantity - $request->quantity;
        if ($diff <= 0) return back()->with('error', 'Set quantity must be less than current stock.');
        $deductQty = $diff;
    } else {
        if ($request->quantity > $product->quantity) {
            return back()->with('error', 'Cannot deduct more than current stock.');
        }
        $deductQty = $request->quantity;
    }

    // ── TRUE FIFO DEDUCTION ───────────────────────────────────
    // Deduct from oldest batches first (by expiry_date then id)
    $remaining = $deductQty;

    $batches = DB::table('inventory_logs')
        ->where('product_id', $request->product_id)
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
    // ─────────────────────────────────────────────────────────

    // Recalculate total product quantity from remaining IN batches
    $newQty = DB::table('inventory_logs')
        ->where('product_id', $request->product_id)
        ->where('type', 'IN')
        ->where('quantity', '>', 0)
        ->sum('quantity');

    DB::table('products')
        ->where('product_id', $request->product_id)
        ->update(['quantity' => $newQty]);

    // ── Notifications ──────────────────────────────────────
    $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

    foreach ($adminStaff as $u) {
        NotificationHelper::send(
            $u->user_id,
            'Stock Deducted',
            ($product->product_name ?? 'A product') . ' had ' . $deductQty . ' units deducted. Remaining stock: ' . $newQty . '.',
            'inventory'
        );
    }

    if ($newQty > 0 && $newQty <= $product->reorder_level) {
        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                '⚠ Low Stock Warning',
                ($product->product_name ?? 'A product') . ' is low on stock (' . $newQty . ' units remaining).',
                'inventory'
            );
        }
    }

    if ($newQty == 0) {
        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                '❌ Out of Stock',
                ($product->product_name ?? 'A product') . ' is now out of stock.',
                'inventory'
            );
        }
    }
    // ──────────────────────────────────────────────────────

    return back()->with('success', 'Stock updated successfully.');
}

    private function checkNearExpiry(): void
    {
        $nearExpiry = DB::table('inventory_logs as l')
            ->join('products as p', 'p.product_id', '=', 'l.product_id')
            ->selectRaw('p.product_id, p.product_name, l.expiry_date, SUM(l.quantity) AS qty')
            ->where('l.type', 'IN')
            ->where('l.quantity', '>', 0)
            ->whereBetween('l.expiry_date', [
                now()->toDateString(),
                now()->addDays(30)->toDateString(),
            ])
            ->groupBy('p.product_id', 'p.product_name', 'l.expiry_date')
            ->get();

        $adminStaff = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        foreach ($nearExpiry as $item) {
            $daysLeft = now()->diffInDays($item->expiry_date);
            $title    = '⏰ Expiry Warning';
            $msg      = $item->product_name . ' is expiring in ' . $daysLeft . ' day(s) on ' . $item->expiry_date . ' (' . $item->qty . ' units remaining).';

            foreach ($adminStaff as $u) {
                $alreadyNotified = DB::table('notifications')
                    ->where('user_id', $u->user_id)
                    ->where('title', $title)
                    ->where('message', $msg)
                    ->whereDate('created_at', now()->toDateString())
                    ->exists();

                if (!$alreadyNotified) {
                    NotificationHelper::send($u->user_id, $title, $msg);
                }
            }
        }
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
            SELECT IFNULL(SUM(quantity), 0)
            FROM inventory_logs
            WHERE product_id = p.product_id
              AND type = 'IN'
              AND quantity > 0
        )
    ");
}
}