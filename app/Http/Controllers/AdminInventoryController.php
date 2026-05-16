<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\SidebarDataController;
use App\Helpers\NotificationHelper;

class AdminInventoryController extends Controller
{
    use SidebarDataController;

    // ── Get the name of the currently logged-in admin/staff ──
    private function actorName(): string
    {
        $userId = Session::get('user_id');
        if (!$userId) return 'Admin';
        $user = DB::table('users')->where('user_id', $userId)->first();
        return $user ? trim($user->firstName . ' ' . $user->lastName) : 'Admin';
    }

    public function index()
    {
        $this->autoRemoveExpiredStock();
        $this->checkNearExpiry();
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
        $request->validate([
            'product_id'  => 'required|integer|exists:products,product_id',
            'quantity'    => 'required|integer|min:1|max:99999',
            'expiry_date' => 'required|date|after_or_equal:today',
        ], [
            'quantity.min'               => 'Quantity must be at least 1.',
            'quantity.max'               => 'Quantity cannot exceed 99,999 per batch.',
            'expiry_date.after_or_equal' => 'Expiry date must be today or a future date.',
        ]);

        $actor      = $this->actorName();
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

        $product    = DB::table('products')->where('product_id', $productId)->first();
        $newQty     = DB::table('inventory_logs')
                        ->where('product_id', $productId)
                        ->where('type', 'IN')
                        ->where('quantity', '>', 0)
                        ->sum('quantity');

        $productName = $product->product_name ?? 'A product';
        $adminStaff  = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                'Stock Added',
                "{$actor} added {$qty} units of {$productName}. Total stock: {$newQty}.",
                'inventory'
            );
        }

        if ($newQty > 0 && $newQty <= $product->reorder_level) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '⚠ Low Stock Warning',
                    "{$productName} is still low on stock after restock by {$actor} ({$newQty} units remaining).",
                    'inventory'
                );
            }
        }

        if ($newQty == 0) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '❌ Out of Stock',
                    "{$productName} is now out of stock. Last action by {$actor}.",
                    'inventory'
                );
            }
        }

        return redirect()->route('admin.inventory')->with('success', $qty . ' units added successfully.');
    }

    public function deductStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,product_id',
            'quantity'   => 'required|integer|min:1',
            'action'     => 'required|in:deduct,set',
        ], [
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $actor   = $this->actorName();
        $product = DB::table('products')->where('product_id', $request->product_id)->first();
        if (!$product) return back()->with('error', 'Product not found.');

        if ($request->action === 'set') {
            $targetQty = (int) $request->quantity;

            if ($targetQty < 0) {
                return back()->with('error', 'Target quantity cannot be negative.');
            }

            if ($targetQty >= $product->quantity) {
                return back()->with('error', 'Target quantity must be less than the current stock (' . $product->quantity . '). Use Add Stock to increase.');
            }

            $deductQty = $product->quantity - $targetQty;
        } else {
            if ($request->quantity > $product->quantity) {
                return back()->with('error', 'Cannot deduct ' . $request->quantity . ' units; only ' . $product->quantity . ' in stock.');
            }
            $deductQty = $request->quantity;
        }

        // ── TRUE FIFO DEDUCTION ───────────────────────────────
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

        $newQty = DB::table('inventory_logs')
            ->where('product_id', $request->product_id)
            ->where('type', 'IN')
            ->where('quantity', '>', 0)
            ->sum('quantity');

        DB::table('products')
            ->where('product_id', $request->product_id)
            ->update(['quantity' => $newQty]);

        $productName = $product->product_name ?? 'A product';
        $adminStaff  = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        $actionLabel = $request->action === 'set'
            ? "{$actor} adjusted {$productName} to {$newQty} units (deducted {$deductQty})."
            : "{$actor} deducted {$deductQty} units from {$productName}. Remaining: {$newQty}.";

        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                'Stock Updated',
                $actionLabel,
                'inventory'
            );
        }

        if ($newQty > 0 && $newQty <= $product->reorder_level) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '⚠ Low Stock Warning',
                    "{$productName} is low on stock ({$newQty} units remaining) after adjustment by {$actor}.",
                    'inventory'
                );
            }
        }

        if ($newQty == 0) {
            foreach ($adminStaff as $u) {
                NotificationHelper::send(
                    $u->user_id,
                    '❌ Out of Stock',
                    "{$productName} is now out of stock after adjustment by {$actor}.",
                    'inventory'
                );
            }
        }

        return back()->with('success', 'Stock updated successfully.');
    }

    public function updateReorder(Request $request)
    {
        $request->validate([
            'product_id'    => 'required|integer|exists:products,product_id',
            'reorder_level' => 'required|integer|min:0|max:99999',
        ], [
            'reorder_level.min' => 'Reorder level cannot be negative.',
            'reorder_level.max' => 'Reorder level cannot exceed 99,999.',
        ]);

        $actor        = $this->actorName();
        $productId    = (int) $request->input('product_id');
        $reorderLevel = (int) $request->input('reorder_level');

        $product = DB::table('products')->where('product_id', $productId)->first();
        if (!$product) return back()->with('error', 'Product not found.');

        $oldLevel = $product->reorder_level;

        DB::table('products')
            ->where('product_id', $productId)
            ->update(['reorder_level' => $reorderLevel]);

        $productName = $product->product_name ?? 'A product';
        $adminStaff  = DB::table('users')->whereIn('role', ['admin', 'staff'])->get();

        foreach ($adminStaff as $u) {
            NotificationHelper::send(
                $u->user_id,
                'Reorder Level Updated',
                "{$actor} changed the reorder level of {$productName} from {$oldLevel} to {$reorderLevel} units.",
                'inventory'
            );
        }

        return back()->with('success', 'Reorder level updated successfully.');
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
                    NotificationHelper::send($u->user_id, $title, $msg, 'inventory');
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