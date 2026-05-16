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

        // ── All products with their price, left-joined to sales data ──
        $allProducts = DB::table('products as p')
            ->leftJoin(DB::raw("(
                SELECT pr.product_name as name,
                       SUM(wi.quantity) as total_qty,
                       SUM(wi.subtotal) as total_revenue
                FROM walkin_sale_items wi
                JOIN walkin_sales ws ON ws.sale_id = wi.sale_id
                JOIN products pr ON pr.product_id = wi.product_id
                WHERE DATE(ws.created_at) BETWEEN '{$dateFrom}' AND '{$dateTo}'
                  AND ws.status = 'completed'
                GROUP BY pr.product_name
                UNION ALL
                SELECT pr.product_name,
                       SUM(oi.quantity),
                       SUM(oi.subtotal)
                FROM order_items oi
                JOIN orders o ON o.id = oi.order_id
                JOIN products pr ON pr.product_id = oi.product_id
                WHERE DATE(o.created_at) BETWEEN '{$dateFrom}' AND '{$dateTo}'
                  AND o.status = 'completed'
                GROUP BY pr.product_name
            ) as sales"), 'sales.name', '=', 'p.product_name')
            ->select(
                'p.product_name as name',
                'p.selling_price',
                DB::raw('COALESCE(SUM(sales.total_qty), 0) as total_qty'),
                DB::raw('COALESCE(SUM(sales.total_revenue), 0) as total_revenue')
            )
            ->groupBy('p.product_id', 'p.product_name', 'p.selling_price')
            ->orderByDesc('total_revenue')
            ->get();

        // ── All services with their price, left-joined to sales data ──
        $allServices = DB::table('services as sv')
            ->leftJoin(DB::raw("(
                SELECT sv2.name as service_name,
                       COUNT(wss.id) as total_count,
                       SUM(wss.service_price) as total_revenue
                FROM walkin_sale_services wss
                JOIN walkin_sales ws ON ws.sale_id = wss.sale_id
                JOIN appointments a ON a.appointment_id = wss.appointment_id
                JOIN services sv2 ON sv2.service_id = a.service_id
                WHERE DATE(ws.created_at) BETWEEN '{$dateFrom}' AND '{$dateTo}'
                  AND ws.status = 'completed'
                  AND wss.appointment_id IS NOT NULL
                GROUP BY sv2.name
                UNION ALL
                SELECT sv2.name,
                       COUNT(wss2.id),
                       SUM(wss2.service_price)
                FROM walkin_sale_services wss2
                JOIN walkin_sales ws2 ON ws2.sale_id = wss2.sale_id
                JOIN services sv2 ON sv2.service_id = wss2.service_id
                WHERE DATE(ws2.created_at) BETWEEN '{$dateFrom}' AND '{$dateTo}'
                  AND ws2.status = 'completed'
                  AND wss2.appointment_id IS NULL
                GROUP BY sv2.name
                UNION ALL
                SELECT sv2.name,
                       COUNT(a2.appointment_id),
                       SUM(sv2.price)
                FROM appointments a2
                JOIN services sv2 ON sv2.service_id = a2.service_id
                WHERE DATE(a2.updated_at) BETWEEN '{$dateFrom}' AND '{$dateTo}'
                  AND a2.status = 'completed'
                  AND a2.appointment_id NOT IN (
                      SELECT appointment_id FROM walkin_sale_services WHERE appointment_id IS NOT NULL
                  )
                GROUP BY sv2.name
            ) as sales"), 'sales.service_name', '=', 'sv.name')
            ->select(
                'sv.name as service_name',
                'sv.price',
                DB::raw('COALESCE(SUM(sales.total_count), 0) as total_count'),
                DB::raw('COALESCE(SUM(sales.total_revenue), 0) as total_revenue')
            )
            ->groupBy('sv.service_id', 'sv.name', 'sv.price')
            ->orderByDesc('total_revenue')
            ->get();

        // ── Payment method breakdown (walk-in + online combined) ──
        $walkinPayments = DB::table('walkin_sales')
            ->select(
                DB::raw('LOWER(payment_method) as payment_method'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('LOWER(payment_method)'));

        $onlinePayments = DB::table('orders')
    ->select(
        DB::raw('LOWER(payment_method) as payment_method'),
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(total) as total')
    )
    ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
    ->where('status', 'completed')
    ->whereNotNull('payment_method') // ← add this
    ->groupBy(DB::raw('LOWER(payment_method)'));

        $paymentBreakdown = DB::table(
                DB::raw("({$walkinPayments->toSql()} UNION ALL {$onlinePayments->toSql()}) as combined")
            )
            ->mergeBindings($walkinPayments)
            ->mergeBindings($onlinePayments)
            ->select(
                'payment_method',
                DB::raw('SUM(count) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // ── Grand total (all payment methods, walk-in + online) ──
        $grandTotal = $paymentBreakdown->sum('total');

        // ── Daily revenue chart (walk-in + online combined) ───
        $dailyWalkin = DB::table('walkin_sales')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'));

        $dailyOnline = DB::table('orders')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'));

        $dailyRevenue = DB::table(
                DB::raw("({$dailyWalkin->toSql()} UNION ALL {$dailyOnline->toSql()}) as combined")
            )
            ->mergeBindings($dailyWalkin)
            ->mergeBindings($dailyOnline)
            ->select('date', DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('total', 'date');

        // ── Extra computed stats ──────────────────────────────
        $activeDays        = $dailyRevenue->count();
        $avgDailyRevenue   = $activeDays > 0 ? $dailyRevenue->sum() / $activeDays : 0;
        $totalTransactions = $paymentBreakdown->sum('count');
        $walkinGrandTotal  = $grandTotal;

        return view('admin_sales_report', array_merge(
            $this->sidebarData(),
            compact(
                'dateFrom', 'dateTo',
                'productRows', 'productTotals',
                'serviceRows', 'serviceTotals',
                'grandTotal', 'walkinGrandTotal', 'paymentBreakdown', 'dailyRevenue',
                'activeDays', 'avgDailyRevenue', 'totalTransactions',
                'allProducts', 'allServices'
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

        // ── CSV Export ────────────────────────────────────────
        if ($format === 'csv') {
            $headers = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}.csv\"",
            ];

            $allProductsCsv = DB::table('products')
                ->select('product_name as name', 'selling_price')
                ->orderBy('product_name')
                ->get()
                ->map(function ($p) use ($productRows) {
                    $sold = $productRows->firstWhere('name', $p->name);
                    return (object)[
                        'name'          => $p->name,
                        'selling_price' => $p->selling_price,
                        'total_qty'     => $sold ? $sold->total_qty     : 0,
                        'total_revenue' => $sold ? $sold->total_revenue : 0,
                    ];
                });

            $allServicesCsv = DB::table('services')
                ->select('name as service_name', 'price')
                ->orderBy('name')
                ->get()
                ->map(function ($s) use ($serviceRows) {
                    $sold = $serviceRows->firstWhere('service_name', $s->service_name);
                    return (object)[
                        'service_name'  => $s->service_name,
                        'price'         => $s->price,
                        'total_count'   => $sold ? $sold->total_count   : 0,
                        'total_revenue' => $sold ? $sold->total_revenue : 0,
                    ];
                });

            $callback = function () use ($allProductsCsv, $allServicesCsv, $dateFrom, $dateTo) {
                $out = fopen('php://output', 'w');

                fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

                fputcsv($out, ["SkinMedic - Sales Report"]);
                fputcsv($out, ["Period: {$dateFrom} to {$dateTo}"]);
                fputcsv($out, ["Generated: " . now()->format('Y-m-d H:i')]);
                fputcsv($out, []);

                fputcsv($out, ["PRODUCT SALES"]);
                fputcsv($out, ["#", "Product", "Unit", "Price (PHP)", "Qty Sold", "Total Revenue (PHP)"]);
                foreach ($allProductsCsv as $i => $r) {
                    fputcsv($out, [
                        $i + 1,
                        $r->name,
                        1,
                        number_format($r->selling_price, 2),
                        $r->total_qty > 0 ? $r->total_qty : 0,
                        $r->total_revenue > 0 ? number_format($r->total_revenue, 2) : 0,
                    ]);
                }
                fputcsv($out, [
                    '', 'TOTAL', '', '',
                    $allProductsCsv->sum('total_qty'),
                    number_format($allProductsCsv->sum('total_revenue'), 2),
                ]);
                fputcsv($out, []);

                fputcsv($out, ["SERVICE SALES"]);
                fputcsv($out, ["#", "Service", "Unit", "Price (PHP)", "Total Sessions", "Total Revenue (PHP)"]);
                foreach ($allServicesCsv as $i => $r) {
                    fputcsv($out, [
                        $i + 1,
                        $r->service_name,
                        1,
                        number_format($r->price, 2),
                        $r->total_count > 0 ? $r->total_count : 0,
                        $r->total_revenue > 0 ? number_format($r->total_revenue, 2) : 0,
                    ]);
                }
                fputcsv($out, [
                    '', 'TOTAL', '', '',
                    $allServicesCsv->sum('total_count'),
                    number_format($allServicesCsv->sum('total_revenue'), 2),
                ]);
                fputcsv($out, []);

                $grand = $allProductsCsv->sum('total_revenue') + $allServicesCsv->sum('total_revenue');
                fputcsv($out, ["GRAND TOTAL", "", "", "", "", number_format($grand, 2)]);

                fclose($out);
            };

            return response()->stream($callback, 200, $headers);
        }

        // ── PDF Export ────────────────────────────────────────

        // Payment breakdown (walk-in + online combined)
        $walkinPayments = DB::table('walkin_sales')
            ->select(
                DB::raw('LOWER(payment_method) as payment_method'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_amount) as total')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('LOWER(payment_method)'));

        $onlinePayments = DB::table('orders')
    ->select(
        DB::raw('LOWER(payment_method) as payment_method'),
        DB::raw('COUNT(*) as count'),
        DB::raw('SUM(total) as total')
    )
    ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
    ->where('status', 'completed')
    ->whereNotNull('payment_method') // ← add this
    ->groupBy(DB::raw('LOWER(payment_method)'));

        $paymentBreakdown = DB::table(
                DB::raw("({$walkinPayments->toSql()} UNION ALL {$onlinePayments->toSql()}) as combined")
            )
            ->mergeBindings($walkinPayments)
            ->mergeBindings($onlinePayments)
            ->select(
                'payment_method',
                DB::raw('SUM(count) as count'),
                DB::raw('SUM(total) as total')
            )
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        // Daily breakdown (walk-in + online combined)
        $dailyWalkin = DB::table('walkin_sales')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'));

        $dailyOnline = DB::table('orders')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE(created_at)'));

        $dailyBreakdown = DB::table(
                DB::raw("({$dailyWalkin->toSql()} UNION ALL {$dailyOnline->toSql()}) as combined")
            )
            ->mergeBindings($dailyWalkin)
            ->mergeBindings($dailyOnline)
            ->select('date', DB::raw('SUM(transactions) as transactions'), DB::raw('SUM(revenue) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Weekly breakdown (walk-in + online combined)
        $weeklyWalkin = DB::table('walkin_sales')
            ->select(
                DB::raw('YEARWEEK(created_at, 1) as week_key'),
                DB::raw('MIN(DATE(created_at)) as week_start'),
                DB::raw('MAX(DATE(created_at)) as week_end'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'));

        $weeklyOnline = DB::table('orders')
            ->select(
                DB::raw('YEARWEEK(created_at, 1) as week_key'),
                DB::raw('MIN(DATE(created_at)) as week_start'),
                DB::raw('MAX(DATE(created_at)) as week_end'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'));

        $weeklyBreakdown = DB::table(
                DB::raw("({$weeklyWalkin->toSql()} UNION ALL {$weeklyOnline->toSql()}) as combined")
            )
            ->mergeBindings($weeklyWalkin)
            ->mergeBindings($weeklyOnline)
            ->select(
                'week_key',
                DB::raw('MIN(week_start) as week_start'),
                DB::raw('MAX(week_end) as week_end'),
                DB::raw('SUM(transactions) as transactions'),
                DB::raw('SUM(revenue) as revenue')
            )
            ->groupBy('week_key')
            ->orderBy('week_key')
            ->get();

        // Monthly breakdown (walk-in + online combined)
        $monthlyWalkin = DB::table('walkin_sales')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_key'),
                DB::raw('DATE_FORMAT(created_at, "%M %Y") as month_label'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), DB::raw('DATE_FORMAT(created_at, "%M %Y")'));

        $monthlyOnline = DB::table('orders')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month_key'),
                DB::raw('DATE_FORMAT(created_at, "%M %Y") as month_label'),
                DB::raw('COUNT(*) as transactions'),
                DB::raw('SUM(total) as revenue')
            )
            ->whereBetween(DB::raw('DATE(created_at)'), [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'), DB::raw('DATE_FORMAT(created_at, "%M %Y")'));

        $monthlyBreakdown = DB::table(
                DB::raw("({$monthlyWalkin->toSql()} UNION ALL {$monthlyOnline->toSql()}) as combined")
            )
            ->mergeBindings($monthlyWalkin)
            ->mergeBindings($monthlyOnline)
            ->select(
                'month_key',
                'month_label',
                DB::raw('SUM(transactions) as transactions'),
                DB::raw('SUM(revenue) as revenue')
            )
            ->groupBy('month_key', 'month_label')
            ->orderBy('month_key')
            ->get();

        // ── Detect period type for PDF layout ─────────────────
        $diffDays   = \Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo));
        $diffMonths = \Carbon\Carbon::parse($dateFrom)->diffInMonths(\Carbon\Carbon::parse($dateTo));

        if ($diffDays == 0) {
            $periodType = 'daily';
        } elseif ($diffDays <= 7) {
            $periodType = 'weekly';
        } elseif ($diffMonths < 3) {
            $periodType = 'monthly';
        } else {
            $periodType = 'yearly';
        }

        return view('admin_sales_report_pdf', compact(
            'dateFrom', 'dateTo',
            'productRows', 'serviceRows',
            'paymentBreakdown',
            'dailyBreakdown', 'weeklyBreakdown', 'monthlyBreakdown',
            'periodType'
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
            ->join('orders as o',   'o.id',         '=', 'oi.order_id')
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
        // ── Walk-in services WITH a linked appointment ──────────────
        // Removed is_prefilled restriction — count all walk-in services
        // regardless of how they were added (prefilled or manually).
        $walkinWithAppt = DB::table('walkin_sale_services as wss')
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
            ->whereNotNull('wss.appointment_id')
            ->groupBy('sv.service_id', 'sv.name');

        // ── Walk-in services WITHOUT a linked appointment ────────────
        // Walk-in services added manually (no appointment) have service_id directly on the row.
        $walkinNoAppt = DB::table('walkin_sale_services as wss')
            ->join('walkin_sales as ws', 'ws.sale_id',    '=', 'wss.sale_id')
            ->join('services as sv',     'sv.service_id', '=', 'wss.service_id')
            ->select(
                'sv.name as service_name',
                DB::raw('COUNT(wss.id) as total_count'),
                DB::raw('SUM(wss.service_price) as total_revenue')
            )
            ->whereBetween(DB::raw('DATE(ws.created_at)'), [$from, $to])
            ->where('ws.status', 'completed')
            ->whereNull('wss.appointment_id')
            ->groupBy('sv.service_id', 'sv.name');

        // ── Standalone completed appointments (not tied to any walk-in sale) ──
        // Use DATE(updated_at) so appointments marked complete today appear
        // in today's report regardless of their future appointment_date.
        $appt = DB::table('appointments as a')
            ->join('services as sv', 'sv.service_id', '=', 'a.service_id')
            ->select(
                'sv.name as service_name',
                DB::raw('COUNT(a.appointment_id) as total_count'),
                DB::raw('SUM(sv.price) as total_revenue')
            )
            ->whereBetween(DB::raw('DATE(a.updated_at)'), [$from, $to])
            ->where('a.status', 'completed')
            ->whereNotIn('a.appointment_id', function ($q) {
                $q->select('appointment_id')
                  ->from('walkin_sale_services')
                  ->whereNotNull('appointment_id');
            })
            ->groupBy('sv.service_id', 'sv.name');

        return collect(
            DB::table(DB::raw("({$walkinWithAppt->toSql()} UNION ALL {$walkinNoAppt->toSql()} UNION ALL {$appt->toSql()}) as combined"))
                ->mergeBindings($walkinWithAppt)
                ->mergeBindings($walkinNoAppt)
                ->mergeBindings($appt)
                ->select('service_name', DB::raw('SUM(total_count) as total_count'), DB::raw('SUM(total_revenue) as total_revenue'))
                ->groupBy('service_name')
                ->orderByDesc('total_revenue')
                ->get()
        );
    }
}