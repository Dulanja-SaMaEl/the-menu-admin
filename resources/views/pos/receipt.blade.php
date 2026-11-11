<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</title>
    <style>
        /* Standard 80mm thermal POS printer: 80mm paper, ~72mm printable width */
        @page {
            size: 80mm auto;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        body {
            width: 80mm;
            margin: 0 auto;
            padding: 5mm 4mm;
            background: #ffffff;
            color: #000000;
            font-family: "Courier New", Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
        }

        .center { text-align: center; }
        .bold { font-weight: bold; }
        .muted { font-size: 11px; }

        .brand {
            font-size: 19px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .sep {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }

        .item {
            margin-bottom: 5px;
        }

        .item-name {
            word-break: break-word;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            font-size: 11px;
        }

        .grand-total {
            font-size: 15px;
            font-weight: bold;
        }

        .footer {
            margin-top: 10px;
            font-size: 11px;
        }

        .toolbar {
            margin-top: 14px;
            text-align: center;
        }

        .toolbar button {
            padding: 10px 18px;
            font-family: inherit;
            font-size: 13px;
            font-weight: bold;
            color: #000000;
            background: #ffffff;
            border: 1px solid #000000;
            border-radius: 4px;
            cursor: pointer;
        }

        @media print {
            .toolbar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="center">
        <div class="brand">The Menu</div>
        <div class="muted">Luxury Kitchen &amp; Catering</div>
        <div class="muted">Counter Sale Receipt</div>
    </div>

    <div class="sep"></div>

    <div class="row">
        <span>Receipt No</span>
        <span class="bold">ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</span>
    </div>
    <div class="row">
        <span>Date</span>
        <span>{{ $order->created_at->format('Y-m-d H:i') }}</span>
    </div>
    <div class="row">
        <span>Cashier</span>
        <span>{{ $order->user->name ?? 'Counter' }}</span>
    </div>
    <div class="row">
        <span>Payment</span>
        <span>{{ $order->payment_method ?: 'Cash' }}</span>
    </div>

    <div class="sep"></div>

    <div class="row bold">
        <span>Item</span>
        <span>Amount</span>
    </div>

    <div class="sep"></div>

    @foreach($order->items as $item)
        <div class="item">
            <div class="item-name">{{ $item->food->name ?? 'Item #' . $item->food_id }}</div>
            <div class="item-meta">
                <span>{{ $item->quantity }} &times; {{ number_format((float) $item->price, 2) }}</span>
                <span>{{ number_format((float) $item->price * (int) $item->quantity, 2) }}</span>
            </div>
        </div>
    @endforeach

    <div class="sep"></div>

    <div class="row">
        <span>Total Items</span>
        <span>{{ (int) $order->items->sum('quantity') }}</span>
    </div>

    <div class="sep"></div>

    <div class="row grand-total">
        <span>TOTAL</span>
        <span>Rs. {{ number_format((float) $order->total_price, 2) }}</span>
    </div>

    <div class="sep"></div>

    <div class="center footer">
        <div class="bold">Thank you for dining with us!</div>
        <div class="muted">No refunds after 24 hours. Please retain this bill.</div>
        <div class="muted">Ref: {{ $order->payment_reference ?: '—' }}</div>
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
