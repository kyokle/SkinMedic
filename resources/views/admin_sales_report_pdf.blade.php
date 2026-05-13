{{-- resources/views/admin_sales_report_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report — SkinMedic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', Arial, sans-serif; font-size: 12px; color: #1a1f16; background: #fff; padding: 32px; }

        /* Header */
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #80a833; padding-bottom: 16px; }
        .header h1 { font-size: 22px; color: #80a833; }
        .header p  { color: #666; font-size: 11px; margin-top: 4px; }

        /* Summary cards row */
        .summary-row { display: flex; gap: 10px; margin-bottom: 20px; }
        .s-card { flex: 1; border-radius: 8px; padding: 10px 14px; }
        .s-card.green  { background: #f0f7e6; border: 1px solid #c5e09b; }
        .s-card.blue   { background: #eff6ff; border: 1px solid #93c5fd; }
        .s-card.dark   { background: #1a1f16; border: 1px solid #1a1f16; }
        .s-card.amber  { background: #fffbeb; border: 1px solid #fcd34d; }
        .s-card.rose   { background: #fff1f2; border: 1px solid #fda4af; }
        .s-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; opacity: 0.65; margin-bottom: 4px; }
        .s-val   { font-size: 14px; font-weight: 700; }
        .s-card.green .s-val  { color: #4a7c10; }
        .s-card.blue  .s-val  { color: #1d4ed8; }
        .s-card.dark  .s-val  { color: #fff; }
        .s-card.dark  .s-label { color: #fff; }
        .s-card.amber .s-val  { color: #b45309; }
        .s-card.rose  .s-val  { color: #be123c; }
        .s-sub { font-size: 9px; opacity: 0.6; margin-top: 2px; }
        .s-card.dark .s-sub   { color: #fff; }

        /* Section title */
        .section-title { font-size: 13px; font-weight: 700; color: #80a833; margin: 20px 0 8px; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Tables */
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #80a833; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; }
        th.r, td.r { text-align: right; }
        td { padding: 6px 10px; border-bottom: 1px solid #f0f0ee; font-size: 11px; }
        tfoot td { background: #f5f4f0; font-weight: 700; border-top: 2px solid #80a833; }

        /* Top-performer inline bars */
        .bar-row { display: flex; align-items: center; gap: 8px; padding: 4px 0; border-bottom: 1px solid #f5f5f5; }
        .bar-row:last-child { border-bottom: none; }
        .bar-name { font-size: 10px; width: 140px; flex-shrink: 0; }
        .bar-track { flex: 1; height: 7px; background: #f0f0ee; border-radius: 3px; overflow: hidden; }
        .bar-fill  { height: 100%; background: #80a833; border-radius: 3px; }
        .bar-val   { font-size: 10px; font-weight: 700; color: #4a7c10; width: 70px; text-align: right; flex-shrink: 0; }

        /* Two-column grid */
        .two-col { display: flex; gap: 16px; margin-bottom: 20px; }
        .two-col > div { flex: 1; }
        .inner-title { font-size: 11px; font-weight: 700; color: #1a1f16; margin-bottom: 8px; }

        /* Grand total */
        .grand { margin-top: 20px; border-top: 2px solid #1a1f16; padding-top: 12px; text-align: right; font-size: 15px; font-weight: 700; }
        .grand span { color: #80a833; }

        /* Footer */
        .footer { margin-top: 32px; text-align: center; font-size: 10px; color: #aaa; }

        @media print {
            body { padding: 16px; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="text-align:center;margin-bottom:16px;">
        <button onclick="window.print()" style="padding:8px 20px;background:#80a833;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px;">🖨 Print / Save PDF</button>
    </div>

    {{-- Header --}}
    <div class="header">
        <h1>SkinMedic — Sales Report</h1>
        <p>Period: {{ $dateFrom }} to {{ $dateTo }} &nbsp;·&nbsp; Generated: {{ now()->format('F j, Y g:i A') }}</p>
    </div>

    {{-- Summary cards --}}
    <div class="summary-row">
        <div class="s-card green">
            <p class="s-label">Product Revenue</p>
            <p class="s-val">₱{{ number_format($productRows->sum('total_revenue'), 2) }}</p>
            <p class="s-sub">{{ number_format($productRows->sum('total_qty')) }} units</p>
        </div>
        <div class="s-card blue">
            <p class="s-label">Service Revenue</p>
            <p class="s-val">₱{{ number_format($serviceRows->sum('total_revenue'), 2) }}</p>
            <p class="s-sub">{{ number_format($serviceRows->sum('total_count')) }} sessions</p>
        </div>
        <div class="s-card dark">
            <p class="s-label">Grand Total</p>
            <p class="s-val">₱{{ number_format($productRows->sum('total_revenue') + $serviceRows->sum('total_revenue'), 2) }}</p>
            <p class="s-sub">{{ $dateFrom }} → {{ $dateTo }}</p>
        </div>
        @foreach($paymentBreakdown as $pm)
        <div class="s-card amber">
            <p class="s-label">{{ strtoupper($pm->payment_method) }}</p>
            <p class="s-val">₱{{ number_format($pm->total, 2) }}</p>
            <p class="s-sub">{{ $pm->count }} txns</p>
        </div>
        @endforeach
    </div>

    {{-- Product Sales --}}
    <p class="section-title">🛍 Product Sales</p>
    <table>
        <thead>
            <tr><th>#</th><th>Product</th><th class="r">Qty Sold</th><th class="r">Revenue (₱)</th></tr>
        </thead>
        <tbody>
            @foreach($productRows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->name }}</td>
                <td class="r">{{ number_format($row->total_qty) }}</td>
                <td class="r">₱{{ number_format($row->total_revenue, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="r">{{ number_format($productRows->sum('total_qty')) }}</td>
                <td class="r">₱{{ number_format($productRows->sum('total_revenue'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Service Sales --}}
    <p class="section-title">💆 Service Sales</p>
    <table>
        <thead>
            <tr><th>#</th><th>Service</th><th class="r">Sessions</th><th class="r">Revenue (₱)</th></tr>
        </thead>
        <tbody>
            @foreach($serviceRows as $i => $row)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $row->service_name }}</td>
                <td class="r">{{ number_format($row->total_count) }}</td>
                <td class="r">₱{{ number_format($row->total_revenue, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="r">{{ number_format($serviceRows->sum('total_count')) }}</td>
                <td class="r">₱{{ number_format($serviceRows->sum('total_revenue'), 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Top Performers --}}
    <p class="section-title">🏆 Top Performers</p>
    @php
        $topP = $productRows->take(5);
        $maxP = $topP->max('total_revenue') ?: 1;
        $topS = $serviceRows->take(5);
        $maxS = $topS->max('total_revenue') ?: 1;
    @endphp
    <div class="two-col">
        <div>
            <p class="inner-title">Top Products</p>
            @foreach($topP as $row)
            <div class="bar-row">
                <span class="bar-name">{{ Str::limit($row->name, 22) }}</span>
                <div class="bar-track"><div class="bar-fill" style="width:{{ round($row->total_revenue / $maxP * 100) }}%"></div></div>
                <span class="bar-val">₱{{ number_format($row->total_revenue, 0) }}</span>
            </div>
            @endforeach
        </div>
        <div>
            <p class="inner-title">Top Services</p>
            @foreach($topS as $row)
            <div class="bar-row">
                <span class="bar-name">{{ Str::limit($row->service_name, 22) }}</span>
                <div class="bar-track"><div class="bar-fill" style="width:{{ round($row->total_revenue / $maxS * 100) }}%"></div></div>
                <span class="bar-val">₱{{ number_format($row->total_revenue, 0) }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Payment Breakdown --}}
    <p class="section-title">💳 Payment Breakdown</p>
    <table>
        <thead>
            <tr><th>Method</th><th class="r">Transactions</th><th class="r">Total (₱)</th><th class="r">Avg per Txn</th><th class="r">% of Sales</th></tr>
        </thead>
        <tbody>
            @php $walkinTotal = $paymentBreakdown->sum('total'); @endphp
            @foreach($paymentBreakdown as $pm)
            <tr>
                <td>{{ ucfirst($pm->payment_method) }}</td>
                <td class="r">{{ number_format($pm->count) }}</td>
                <td class="r">₱{{ number_format($pm->total, 2) }}</td>
                <td class="r">₱{{ $pm->count > 0 ? number_format($pm->total / $pm->count, 2) : '0.00' }}</td>
                <td class="r">{{ $walkinTotal > 0 ? number_format($pm->total / $walkinTotal * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>Total</td>
                <td class="r">{{ number_format($paymentBreakdown->sum('count')) }}</td>
                <td class="r">₱{{ number_format($paymentBreakdown->sum('total'), 2) }}</td>
                <td class="r"></td>
                <td class="r">100%</td>
            </tr>
        </tfoot>
    </table>

    {{-- Grand Total --}}
    <div class="grand">
        Grand Total: <span>₱{{ number_format($productRows->sum('total_revenue') + $serviceRows->sum('total_revenue'), 2) }}</span>
    </div>

    <div class="footer">SkinMedic · This is a system-generated report</div>

</body>
</html>