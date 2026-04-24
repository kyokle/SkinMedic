<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\SidebarDataController;

class StaffPageController extends Controller
{
    use SidebarDataController;

    public function index()
    {
        // Guard: only staff allowed
        if (Session::get('role') !== 'staff') {
            return redirect()->route('index', ['login' => 'true']);
        }

        $today = now()->toDateString();

        /* ── Stats ── */
        $newBookings = DB::table('appointments')
            ->where('status', 'pending')
            ->count();

        $todaySessions = DB::table('appointments')
            ->where('appointment_date', $today)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        $totalServices = DB::table('services')
            ->where('status', 'available')
            ->count();

        $completedToday = DB::table('appointments')
            ->where('appointment_date', $today)
            ->where('status', 'completed')
            ->count();

        /* ── Upcoming appointments (next 7 days) ── */
        $upcoming = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->select(
                'a.appointment_date',
                'a.appointment_time',
                'a.status',
                's.name as service_name',
                DB::raw("CONCAT(u.firstName,' ',u.lastName) AS patient_name")
            )
            ->whereBetween('a.appointment_date', [$today, now()->addDays(7)->toDateString()])
            ->orderBy('a.appointment_date')
            ->orderBy('a.appointment_time')
            ->limit(10)
            ->get();

        /* ── NOTIFICATIONS: Out of Stock ── */
        $outOfStockNames = DB::table('products')
            ->where('quantity', 0)
            ->pluck('product_name')
            ->map(fn($n) => e($n));

        /* ── NOTIFICATIONS: Low Stock ── */
        $lowStock = DB::table('products')
            ->where('quantity', '<=', DB::raw('reorder_level'))
            ->where('quantity', '>', 0)
            ->orderBy('quantity')
            ->get(['product_name', 'quantity', 'reorder_level']);

        /* ── NOTIFICATIONS: Near Expiry (within 7 days) ── */
        $nearExpiryRows = DB::table('inventory_logs as l')
            ->join('products as p', 'p.product_id', '=', 'l.product_id')
            ->select(
                'p.product_name',
                'l.expiry_date',
                DB::raw('DATEDIFF(l.expiry_date, CURDATE()) AS days_left'),
                DB::raw('SUM(l.quantity) AS total_qty')
            )
            ->where('l.type', 'IN')
            ->where('l.quantity', '>', 0)
            ->whereBetween('l.expiry_date', [$today, now()->addDays(7)->toDateString()])
            ->groupBy('p.product_id', 'p.product_name', 'l.expiry_date')
            ->orderBy('l.expiry_date')
            ->get();

        $nearExpiryNames = $nearExpiryRows->pluck('product_name')->map(fn($n) => e($n));

        /* ── Collect all notifications ── */
        $notifications = [];

        foreach ($outOfStockNames as $name) {
            $notifications[] = [
                'type' => 'danger',
                'icon' => '❌',
                'msg'  => "{$name} is OUT OF STOCK — reorder immediately",
            ];
        }

        foreach ($lowStock as $r) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => '⚠️',
                'msg'  => e($r->product_name) . " is low — only {$r->quantity} left (reorder at {$r->reorder_level})",
            ];
        }

        foreach ($nearExpiryRows as $r) {
            $d     = (int) $r->days_left;
            $label = $d === 0 ? 'expires TODAY' : "expires in {$d} day" . ($d > 1 ? 's' : '');
            $notifications[] = [
                'type' => 'expiry',
                'icon' => '🕐',
                'msg'  => e($r->product_name) . " — {$label} (Qty: {$r->total_qty}, Exp: {$r->expiry_date})",
            ];
        }

        $notifCount = count($notifications);

        return view('staff_page', array_merge(
            $this->sidebarData(),
            compact(
                'newBookings',
                'todaySessions',
                'totalServices',
                'completedToday',
                'upcoming',
                'notifications',
                'notifCount',
                'outOfStockNames',
                'nearExpiryNames'
            )
        ));
    }
}