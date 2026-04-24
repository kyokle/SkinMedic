<?php

namespace App\Http\Controllers;

use App\Http\Controllers\SidebarDataController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AdminPageController extends Controller
{
    use SidebarDataController;
    public function index()
    {
        if (!in_array(Session::get('role'), ['admin', 'staff', 'doctor'])) {
            return redirect()->route('index');
        }

        $today = now()->toDateString();

        // ── Stat Cards ──
        $totalBookings  = DB::table('appointments')->count();
        $todayBooks     = DB::table('appointments')->where('appointment_date', $today)->count();
        $pendingBooks   = DB::table('appointments')->where('status', 'pending')->count();
        $totalPatients  = DB::table('users')->where('role', 'patient')->count();
        $totalDoctors   = DB::table('users')->where('role', 'doctor')->count();
        $totalRevenue   = DB::table('appointments as a')
            ->join('services as s', 'a.service_id', '=', 's.service_id')
            ->where('a.status', 'completed')
            ->sum('s.price');

        // ── Stock Alerts ──
        $lowStock   = DB::table('products')
            ->where('quantity', '<=', DB::raw('reorder_level'))
            ->where('quantity', '>', 0)
            ->count();
        $outOfStock = DB::table('products')->where('quantity', 0)->count();

        // ── Line Chart: bookings last 7 days ──
        $lineData = DB::table('appointments')
            ->selectRaw('DATE(appointment_date) as day, COUNT(*) as cnt')
            ->whereBetween('appointment_date', [now()->subDays(6)->toDateString(), $today])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('cnt', 'day');

        $lineLabels = [];
        $lineValues = [];
        for ($i = 6; $i >= 0; $i--) {
            $date         = now()->subDays($i)->toDateString();
            $lineLabels[] = now()->subDays($i)->format('M d');
            $lineValues[] = $lineData[$date] ?? 0;
        }

        // ── Donut Chart: booking status breakdown ──
        $statusData  = DB::table('appointments')
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $donutLabels = $statusData->keys()->toArray();
        $donutData   = $statusData->values()->toArray();
        $donutColors = ['#80a833', '#f59e0b', '#3b82f6', '#ef4444'];

        // ── Bar Chart: most popular services ──
        $svcData2  = DB::table('appointments as a')
            ->join('services as s', 'a.service_id', '=', 's.service_id')
            ->selectRaw('s.name, COUNT(*) as cnt')
            ->groupBy('s.name')
            ->orderByDesc('cnt')
            ->limit(6)
            ->pluck('cnt', 'name');

        $svcLabels = $svcData2->keys()->toArray();
        $svcData   = $svcData2->values()->toArray();

        // ── Stock Chart ──
        $stockData  = DB::table('products')
            ->orderBy('quantity')
            ->limit(8)
            ->pluck('quantity', 'product_name');

        $stockNames = $stockData->keys()->toArray();
        $stockQty   = $stockData->values()->toArray();

        // ── Recent Bookings ──
        $recent = DB::table('appointments as a')
            ->leftJoin('services as s', 'a.service_id', '=', 's.service_id')
            ->leftJoin('users as u', 'a.user_id', '=', 'u.user_id')
            ->selectRaw("a.appointment_id, CONCAT(u.firstName,' ',u.lastName) AS patient, s.name AS service, a.appointment_date, a.status")
            ->orderByDesc('a.appointment_id')
            ->limit(8)
            ->get();

        // ── Near Expiry ──
        $nearExp = DB::table('inventory_logs as l')
            ->join('products as p', 'p.product_id', '=', 'l.product_id')
            ->selectRaw('p.product_name, l.expiry_date, SUM(l.quantity) as qty')
            ->where('l.type', 'IN')
            ->where('l.quantity', '>', 0)
            ->whereBetween('l.expiry_date', [$today, now()->addDays(14)->toDateString()])
            ->groupBy('p.product_id', 'p.product_name', 'l.expiry_date')
            ->orderBy('l.expiry_date')
            ->get();

        return view('admin_page', array_merge(
            $this->sidebarData(),
            compact(
                'totalBookings', 'todayBooks', 'pendingBooks',
                'totalPatients', 'totalDoctors', 'totalRevenue',
                'lowStock', 'outOfStock',
                'lineLabels', 'lineValues',
                'donutLabels', 'donutData', 'donutColors',
                'svcLabels', 'svcData',
                'stockNames', 'stockQty',
                'recent', 'nearExp'
            )
        ));
    }
}