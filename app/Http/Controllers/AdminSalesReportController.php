<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\SidebarDataController;

class AdminSalesReportController extends Controller
{
    use SidebarDataController;

    // ─────────────────────────────────────────────────────────
    // GET /admin/reports/sales
    // ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        if (!in_array(session('role'), ['admin'])) {
            return redirect()->route('index');
        }

        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->query('date_to',   now()->toDateString());

        // ── Product & Service rows ─────────────────────────────
        $productRows = $this->getProductRows($dateFrom, $dateTo);
        $serviceRows = $this->getServiceRows($dateFrom, $dateTo);

        $productTotals = [
            'qty'     => $productRows->sum('total_qty'),
            'revenue' => $productRows->sum('total_revenue'),
        ];

        $serviceTotals = [
            'count'   => $serviceRows->sum('total_count'),
            'revenue' => $serviceRows->sum('total_revenue'),
        ];

        // ── Grand summary ─────────────────────────────────────
        $grandTotal = $productTotals['revenue'] + $serviceTotals['revenue'];

        // ── Payment method breakdown (walk-in only) ───────────
        $paymentBreakdown = DB::table('walkin_sales')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get();

        // ── Daily revenue chart data ──────────────────────────
        $dailyRevenue = DB::table('walkin_sales')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // ── Extra computed stats ──────────────────────────────
        $activeDays       = $dailyRevenue->count();
        $avgDailyRevenue  = $activeDays > 0 ? $dailyRevenue->sum() / $activeDays : 0;
        $totalTransactions = $paymentBreakdown->sum('count');

        return view('admin_sales_report', array_merge(
            $this->sidebarData(),
            compact(
                'dateFrom', 'dateTo',
                'productRows', 'productTotals',
                'serviceRows', 'serviceTotals',
                'grandTotal', 'paymentBreakdown', 'dailyRevenue',
                'activeDays', 'avgDailyRevenue', 'totalTransactions'
            )
        ));
    }

    // ─────────────────────────────────────────────────────────
    // GET /admin/reports/sales/export?format=csv|pdf
    // ─────────────────────────────────────────────────────────
    public function export(Request $request)
    {
        if (!in_array(session('role'), ['admin'])) {
            return redirect()->route('index');
        }

        $format   = $request->query('format', 'csv');
        $dateFrom = $request->query('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->query('date_to',   now()->toDateString());

        $productRows = $this->getProductRows($dateFrom, $dateTo);
        $serviceRows = $this->getServiceRows($dateFrom, $dateTo);

        $filename = "sales_report_{$dateFrom}_to_{$dateTo}";

        if ($format === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ];

            $callback = function () use ($productRows, $serviceRows, $dateFrom, $dateTo) {
                $out = fopen('php://output', 'w');

                fputcsv($out, ["SkinMedic — Sales Report"]);
                fputcsv($out, ["Period: {$dateFrom} to {$dateTo}"]);
                fputcsv($out, ["Generated: " . now()->format('Y-m-d H:i')]);
                fputcsv($out, []);

                // Products
                fputcsv($out, ["PRODUCT SALES"]);
                fputcsv($out, ["#", "Product", "Qty Sold", "Revenue (₱)"]);
                foreach ($productRows as $i => $r) {
                    fputcsv($out, [$i + 1, $r->name, $r->total_qty, number_format($r->total_revenue, 2)]);
                }
                fputcsv($out, ["", "TOTAL", $productRows->sum('total_qty'), number_format($productRows->sum('total_revenue'), 2)]);
                fputcsv($out, []);

                // Services
                fputcsv($out, ["SERVICE SALES"]);
                fputcsv($out, ["#", "Service", "Sessions", "Revenue (₱)"]);
                foreach ($serviceRows as $i => $r) {
                    fputcsv($out, [$i + 1, $r->service_name, $r->total_count, number_format($r->total_revenue, 2)]);
                }
                fputcsv($out, ["", "TOTAL", $serviceRows->sum('total_count'), number_format($serviceRows->sum('total_revenue'), 2)]);
                fputcsv($out, []);

                // Grand total
                $grand = $productRows->sum('total_revenue') + $serviceRows->sum('total_revenue');
                fputcsv($out, ["GRAND TOTAL", "", "", number_format($grand, 2)]);

                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // PDF export — pass paymentBreakdown too for the enhanced PDF view
        $paymentBreakdown = DB::table('walkin_sales')
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy('payment_method')
            ->get();

        return view('admin_sales_report_pdf', compact(
            'dateFrom', 'dateTo', 'productRows', 'serviceRows', 'paymentBreakdown'
        ));
    }

    // ── Private query helpers ─────────────────────────────────

    private function getProductRows(string $from, string $to)
    {
        $walkin = DB::table('walkin_sale_items as wi')
            ->join('walkin_sales as ws', 'ws.sale_id',   '=', 'wi.sale_id')
            ->join('products as p',      'p.product_id', '=', 'wi.product_id')
            ->select(
                'p.product_name as name',
                DB::raw('SUM(wi.quantity) as total_qty'),
                DB::raw('SUM(wi.subtotal) as total_revenue')
            )
            ->whereBetween(DB::raw('DATE(ws.created_at)'), [$from, $to])
            ->where('ws.status', 'completed')
            ->groupBy('p.product_id', 'p.product_name');

        $online = DB::table('order_items as oi')
            ->join('orders as o',   'o.order_id',   '=', 'oi.order_id')
            ->join('products as p', 'p.product_id', '=', 'oi.product_id')
            ->select(
                'p.product_name as name',
                DB::raw('SUM(oi.quantity) as total_qty'),
                DB::raw('SUM(oi.subtotal) as total_revenue')
            )
            ->whereBetween(DB::raw('DATE(o.created_at)'), [$from, $to])
            ->where('o.status', 'completed')
            ->groupBy('p.product_id', 'p.product_name');

        return collect(
            DB::table(DB::raw("({$walkin->toSql()} UNION ALL {$online->toSql()}) as combined"))
                ->mergeBindings($walkin)
                ->mergeBindings($online)
                ->select('name', DB::raw('SUM(total_qty) as total_qty'), DB::raw('SUM(total_revenue) as total_revenue'))
                ->groupBy('name')
                ->orderByDesc('total_revenue')
                ->get()
        );
    }

    private function getServiceRows(string $from, string $to)
    {
        $walkin = DB::table('walkin_sale_services as wss')
            ->join('walkin_sales as ws',  'ws.sale_id',       '=', 'wss.sale_id')
            ->join('appointments as a',   'a.appointment_id', '=', 'wss.appointment_id')
            ->join('services as sv',      'sv.service_id',    '=', 'a.service_id')
            ->select(
                'sv.name as service_name',
                DB::raw('COUNT(wss.id) as total_count'),
                DB::raw('SUM(wss.service_price) as total_revenue')
            )
            ->whereBetween(DB::raw('DATE(ws.created_at)'), [$from, $to])
            ->where('ws.status', 'completed')
            ->where('wss.is_prefilled', 1)
            ->groupBy('sv.service_id', 'sv.name');

        $appt = DB::table('appointments as a')
            ->join('services as sv', 'sv.service_id', '=', 'a.service_id')
            ->select(
                'sv.name as service_name',
                DB::raw('COUNT(a.appointment_id) as total_count'),
                DB::raw('SUM(sv.price) as total_revenue')
            )
            ->whereBetween('a.appointment_date', [$from, $to])
            ->where('a.status', 'completed')
            ->whereNotIn('a.appointment_id', function ($q) {
                $q->select('appointment_id')->from('walkin_sale_services')->where('is_prefilled', 1);
            })
            ->groupBy('sv.service_id', 'sv.name');

        return collect(
            DB::table(DB::raw("({$walkin->toSql()} UNION ALL {$appt->toSql()}) as combined"))
                ->mergeBindings($walkin)
                ->mergeBindings($appt)
                ->select('service_name', DB::raw('SUM(total_count) as total_count'), DB::raw('SUM(total_revenue) as total_revenue'))
                ->groupBy('service_name')
                ->orderByDesc('total_revenue')
                ->get()
        );
    }
}