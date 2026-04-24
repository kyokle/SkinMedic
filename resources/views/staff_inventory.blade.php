{{-- resources/views/staff_inventory.blade.php --}}

@extends('layouts.app')

@section('title', 'Inventory — SkinMedic')

@push('styles')
    <link rel="stylesheet" href="{{ asset('asset/css/staff_inventory.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Fraunces:wght@700&display=swap" rel="stylesheet">
@endpush

@section('content')

@include('partials.sidebar_staff')

<div class="main">

    {{-- Topbar --}}
    <div class="topbar">
        <h2>Inventory System</h2>
        <div class="date-box">
            <p>Today's Date</p>
            <strong>{{ now()->format('Y-m-d') }}</strong>
        </div>
    </div>

    {{-- Near Expiry Alert --}}
    @if($nearExpiry->isNotEmpty())
    <div class="alert-block warning">
        <b>⚠ Near Expiry (within 7 days)</b><br>
        @foreach($nearExpiry as $n)
            • {{ $n->product_name }} → Exp: <b>{{ $n->expiry_date }}</b> (Qty: {{ $n->total_qty }})<br>
        @endforeach
    </div>
    @endif

    {{-- Low / Out-of-Stock Alert --}}
    @if($lowStock->isNotEmpty() || $outOfStock->isNotEmpty())
    <div class="alert-block danger">
        <b>⚠ Low / Out-of-Stock Alert</b><br>
        @foreach($lowStock as $l)
            • {{ $l->product_name }} — only <b>{{ $l->quantity }}</b> left<br>
        @endforeach
        @foreach($outOfStock as $z)
            • {{ $z->product_name }} — <b>OUT OF STOCK</b><br>
        @endforeach
    </div>
    @endif

    {{-- Inventory Table --}}
    <div class="table-wrapper">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Total Stock</th>
                    <th>Reorder At</th>
                    <th>Status</th>
                    <th>Last Added</th>
                    <th>Current Batch Expiry</th>
                    <th>Current Batch Qty</th>
                    <th>Next Batch Expiry</th>
                    <th>Add Stock (Qty + Expiry)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $row)
                    @php
                        $expClass = 'exp-ok';
                        $daysLeft = null;
                        if (!empty($row->current_expiry)) {
                            $daysLeft = (strtotime($row->current_expiry) - strtotime(now()->toDateString())) / 86400;
                            if ($daysLeft <= 3)     $expClass = 'exp-danger';
                            elseif ($daysLeft <= 7) $expClass = 'exp-warning';
                        }
                    @endphp
                    <tr>
                        <td>{{ $row->product_id }}</td>
                        <td class="product-name">{{ $row->product_name }}</td>
                        <td>{{ $row->quantity }}</td>
                        <td>{{ $row->reorder_level }}</td>
                        <td>
                            @if($row->quantity == 0)
                                <span class="status-badge out">❌ Out of Stock</span>
                            @elseif($row->quantity <= $row->reorder_level)
                                <span class="status-badge low">⚠ Reorder Needed</span>
                            @else
                                <span class="status-badge in">✓ In Stock</span>
                            @endif
                        </td>
                        <td>{{ $row->last_added ? \Carbon\Carbon::parse($row->last_added)->format('Y-m-d H:i') : '—' }}</td>
                        <td class="{{ $expClass }}">
                            @if($row->current_expiry)
                                {{ $row->current_expiry }}
                                @if($daysLeft !== null && $daysLeft >= 0 && $daysLeft <= 7)
                                    <br><small>({{ (int) $daysLeft }} days left)</small>
                                @elseif($daysLeft !== null && $daysLeft < 0)
                                    <br><small class="expired-label">(Expired)</small>
                                @endif
                            @else
                                —
                            @endif
                        </td>
                        <td>{{ $row->current_batch_qty ?? '—' }}</td>
                        <td class="exp-next">{{ $row->next_expiry ?: '—' }}</td>
                        <td>
                            <form method="POST"
                                  action="{{ route('staff.inventory.add-stock') }}"
                                  class="add-stock-form">
                                @csrf
                                <input type="hidden" name="product_id"  value="{{ $row->product_id }}">
                                <input type="number" name="quantity"     required min="1" placeholder="Qty"
                                       class="input-qty">
                                <input type="date"   name="expiry_date" required
                                       class="input-date">
                                <button type="submit" class="add-btn">Add</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>

@endsection