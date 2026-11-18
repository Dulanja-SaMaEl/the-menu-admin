<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->order_number ?: ('ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT)) }}</title>
    <style>
        /* Standard 80mm thermal POS printer: 80mm paper, ~72mm printable width */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 4mm;
            background: #ffffff;
            color: #000000;
            font-family: "Courier New", Courier, monospace, -apple-system, BlinkMacSystemFont;
            font-size: 12px;
            line-height: 1.35;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .muted { font-size: 11px; }

        .brand {
            font-size: 18px;
            font-weight: 900;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sep {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }

        .double-sep {
            border-top: 2px dashed #000000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 2px;
        }

        .item {
            margin-bottom: 5px;
        }

        .item-name {
            font-weight: bold;
            word-break: break-word;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            font-size: 11px;
        }

        .grand-total {
            font-size: 16px;
            font-weight: 900;
        }

        .order-badge {
            display: inline-block;
            border: 1px solid #000000;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 10px;
            font-size: 11px;
        }

        .toolbar {
            margin-top: 16px;
            text-align: center;
        }

        .toolbar button {
            padding: 10px 18px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 6px;
            cursor: pointer;
        }

        .toolbar button:hover {
            background: #000000;
            color: #ffffff;
        }

        @media print {
            .toolbar {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    @php
        $orderMeta = $meta ?? [];
        $orderType = $orderMeta['order_type'] ?? (str_contains($order->address, 'Dine-In') ? 'dine_in' : 'takeaway');
        $tableNum = $orderMeta['table_number'] ?? null;
        $subtotal = (float) ($orderMeta['subtotal'] ?? $order->items->sum(fn($i) => (float)$i->price * (int)$i->quantity));
        $discountAmount = (float) ($order->discount_amount ?: ($orderMeta['discount_amount'] ?? 0));
        $bagCount = (int) ($orderMeta['bag_count'] ?? 0);
        $bagTotal = (float) ($orderMeta['bag_total'] ?? ($bagCount * 5.00));
        $cashTendered = isset($orderMeta['cash_tendered']) ? (float)$orderMeta['cash_tendered'] : null;
        $changeAmount = isset($orderMeta['change_amount']) ? (float)$orderMeta['change_amount'] : null;
    @endphp

    <div class="center">
        <div class="brand">The Menu</div>
        <div class="muted">Luxury Culinary &amp; Catering</div>
        <div class="muted">POS Sales Terminal Receipt</div>
        <div class="order-badge">
            @if($orderType === 'dine_in')
                Dine-In {{ $tableNum ? ('• Table ' . $tableNum) : '' }}
            @elseif($orderType === 'delivery')
                Counter Dispatch
            @else
                Takeaway Order
            @endif
        </div>
    </div>

    <div class="sep"></div>

    <div class="row">
        <span>Receipt No:</span>
        <span class="bold">{{ $order->order_number ?: ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }}</span>
    </div>
    <div class="row">
        <span>Date &amp; Time:</span>
        <span>{{ $order->created_at->format('Y-m-d H:i') }}</span>
    </div>
    <div class="row">
        <span>Cashier:</span>
        <span>{{ $orderMeta['cashier_name'] ?? ($order->user->name ?? 'POS Counter') }}</span>
    </div>

    @if(!empty($order->customer_name) && !str_starts_with($order->customer_name, 'Table ') && $order->customer_name !== 'Walk-in Customer')
        <div class="row">
            <span>Customer:</span>
            <span>{{ $order->customer_name }}</span>
        </div>
    @endif
    @if(!empty($order->customer_phone))
        <div class="row">
            <span>Phone:</span>
            <span>{{ $order->customer_phone }}</span>
        </div>
    @endif

    <div class="row">
        <span>Payment:</span>
        <span class="bold">{{ $order->payment_method ?: 'Cash' }}</span>
    </div>

    <div class="sep"></div>

    <div class="row bold">
        <span>Item Description</span>
        <span>Amount (Rs.)</span>
    </div>

    <div class="sep"></div>

    @foreach($order->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->food->name ?? 'Menu Item #' . $item->food_id }}</div>
            <div class="item-meta">
                <span>{{ $item->quantity }} &times; {{ number_format((float) $item->price, 2) }}</span>
                <span class="bold">{{ number_format((float) $item->price * (int) $item->quantity, 2) }}</span>
            </div>
        </div>
    @endforeach

    <div class="sep"></div>

    <div class="row">
        <span>Total Items:</span>
        <span>{{ (int) $order->items->sum('quantity') }}</span>
    </div>

    @if($discountAmount > 0)
        <div class="row">
            <span>Subtotal:</span>
            <span>Rs. {{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="row">
            <span>Discount:</span>
            <span class="bold">- Rs. {{ number_format($discountAmount, 2) }}</span>
        </div>
    @endif

    @if($bagCount > 0)
        <div class="row">
            <span>Polythene Bags ({{ $bagCount }} &times; Rs. 5.00):</span>
            <span class="bold">+ Rs. {{ number_format($bagTotal, 2) }}</span>
        </div>
    @endif

    <div class="double-sep"></div>

    <div class="row grand-total">
        <span>NET TOTAL:</span>
        <span>Rs. {{ number_format((float) $order->total_price, 2) }}</span>
    </div>

    @if($order->payment_method === 'Cash' && $cashTendered !== null)
        <div class="sep"></div>
        <div class="row">
            <span>Cash Tendered:</span>
            <span>Rs. {{ number_format($cashTendered, 2) }}</span>
        </div>
        <div class="row bold">
            <span>Change Returned:</span>
            <span>Rs. {{ number_format($changeAmount ?? 0, 2) }}</span>
        </div>
    @endif

    @if(!empty($orderMeta['notes']))
        <div class="sep"></div>
        <div class="muted">
            <span class="bold">Note:</span> {{ $orderMeta['notes'] }}
        </div>
    @endif

    <div class="sep"></div>

    <div class="center footer">
        <div class="bold">Thank you for dining with us!</div>
        <div class="muted">Freshly prepared with culinary excellence.</div>
        <div class="muted">Ref: {{ $order->payment_reference ?: 'POS-' . $order->id }}</div>
    </div>

    <div class="toolbar">
        <button type="button" onclick="window.print()">Print Receipt</button>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
