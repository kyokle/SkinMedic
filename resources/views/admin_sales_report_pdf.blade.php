{{-- resources/views/admin_sales_report_pdf.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Report — SkinMedic</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', Arial, sans-serif; font-size: 12px; color: #1a1f16; background: #fff; padding: 32px; }
        .header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid #80a833; padding-bottom: 16px; }
        .header h1 { font-size: 22px; color: #80a833; }
        .header p  { color: #666; font-size: 11px; margin-top: 4px; }
        .section-title { font-size: 13px; font-weight: 700; color: #80a833; margin: 20px 0 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th { background: #80a833; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; }
        th.r, td.r { text-align: right; }
        td { padding: 6px 10px; border-bottom: 1px solid #f0f0ee; font-size: 11px; }
        tfoot td { background: #f5f4f0; font-weight: 700; border-top: 2px solid #80a833; }
        .grand { margin-top: 20px; border-top: 2px solid #1a1f16; padding-top: 12px; text-align: right; font-size: 15px; font-weight: 700; }
        .grand span { color: #80a833; }
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

    <div class="header">
        <h1>SkinMedic — Sales Report</h1>
        <p>Period: {{ $dateFrom }} to {{ $dateTo }} &nbsp;·&nbsp; Generated: {{ now()->format('F j, Y g:i A') }}</p>
    </div>

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

    <div class="grand">
        Grand Total: <span>₱{{ number_format($productRows->sum('total_revenue') + $serviceRows->sum('total_revenue'), 2) }}</span>
    </div>

    <div class="footer">SkinMedic · This is a system-generated report</div>

</body>
</html>
