{{-- resources/views/staff_walkin_receipt.blade.php --}}

@extends('layouts.app')

@section('title', 'Receipt #{{ $sale->sale_id }} — SkinMedic')

@push('styles')
<link rel="stylesheet" href="{{ asset('asset/css/staff_walkin.css') }}">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="walkin-wrap">

    <div class="walkin-header no-print">
        <div>
            <h2>Receipt</h2>
            <p class="walkin-sub">Sale #{{ $sale->sale_id }}</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('staff.walkin') }}" class="back-btn">← Back</a>
            <button onclick="window.print()" class="print-btn">🖨 Print Receipt</button>
            <a href="{{ route('staff.walkin') }}" class="back-btn">+ New Sale</a>
        </div>
    </div>

    <div class="receipt-card" id="printArea">

        {{-- Clinic Header --}}
        <div class="receipt-header">
            <div class="receipt-logo">SkinMedic</div>
            <p class="receipt-tagline">Walk-in Sale Receipt</p>
            <p class="receipt-meta">
                Receipt #{{ $sale->sale_id }} &nbsp;·&nbsp;
                {{ \Carbon\Carbon::parse($sale->created_at)->format('F j, Y g:i A') }}
            </p>
        </div>

        <div class="receipt-divider"></div>

        {{-- Patient & Staff --}}
        <div class="receipt-info-grid">
            <div>
                <p class="receipt-label">Patient</p>
                <p class="receipt-value">{{ $sale->patient_name }}</p>
                <p class="receipt-value dim">{{ $sale->patient_email }}</p>
            </div>
            <div>
                <p class="receipt-label">Processed by</p>
                <p class="receipt-value">{{ $sale->staff_name }}</p>
            </div>
        </div>

        <div class="receipt-divider"></div>

        {{-- Product Items --}}
        @if($productItems->isNotEmpty())
        <p class="receipt-section-label">🛍 Products</p>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($productItems as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">₱{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Prefilled service (from completed appointment — already billed, no charge) --}}
        @if($prefilledService)
        <p class="receipt-section-label" style="margin-top:16px;">💆 Appointment Service</p>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Doctor</th>
                    <th>Date & Time</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $prefilledService->service_name }}</td>
                    <td>{{ $prefilledService->doctor_name }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($prefilledService->appointment_date)->format('M j, Y') }}
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $prefilledService->appointment_time)->format('g:i A') }}
                    </td>
                    <td class="text-right" style="color:#888;font-style:italic;">
                        Billed via appointment
                    </td>
                </tr>
            </tbody>
        </table>
        @endif

        {{-- New service add-ons (these are charged in this sale) --}}
        @if($addonServices->isNotEmpty())
        <p class="receipt-section-label" style="margin-top:16px;">➕ Service Add-ons</p>
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Service</th>
                    <th>Doctor</th>
                    <th>Date & Time</th>
                    <th class="text-right">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($addonServices as $item)
                <tr>
                    <td>{{ $item->service_name }}</td>
                    <td>{{ $item->doctor_name }}</td>
                    <td>
                        {{ \Carbon\Carbon::parse($item->appointment_date)->format('M j, Y') }}
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $item->appointment_time)->format('g:i A') }}
                    </td>
                    <td class="text-right">₱{{ number_format($item->service_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        <div class="receipt-divider"></div>

        {{-- Totals --}}
        <div class="receipt-totals">
            <div class="receipt-total-row">
                <span>Subtotal</span>
                <span>₱{{ number_format($sale->subtotal, 2) }}</span>
            </div>
            <div class="receipt-total-row">
                <span>Payment Method</span>
                <span class="pay-badge {{ $sale->payment_method }}">{{ strtoupper($sale->payment_method) }}</span>
            </div>
            @if($sale->payment_method === 'cash' && $sale->amount_tendered !== null)
            <div class="receipt-total-row">
                <span>Amount Tendered</span>
                <span>₱{{ number_format($sale->amount_tendered, 2) }}</span>
            </div>
            @endif
            <div class="receipt-total-row grand">
                <span>Total</span>
                <span>₱{{ number_format($sale->total_amount, 2) }}</span>
            </div>
            @if($sale->payment_method === 'cash' && $sale->amount_tendered !== null)
            <div class="receipt-total-row change-row">
                <span>Change</span>
                <span>₱{{ number_format($change, 2) }}</span>
            </div>
            @endif
        </div>

        @if($sale->notes)
        <div class="receipt-divider"></div>
        <p class="receipt-label">Notes</p>
        <p class="receipt-value">{{ $sale->notes }}</p>
        @endif

        <div class="receipt-divider"></div>
        <p class="receipt-footer">Thank you for visiting SkinMedic! 💚</p>

    </div>{{-- /receipt-card --}}

</div>

@push('scripts')
<script>
// Auto-open print dialog if coming fresh from a sale
@if(session('success'))
    window.addEventListener('load', () => setTimeout(() => window.print(), 800));
@endif
</script>
@endpush

@endsection