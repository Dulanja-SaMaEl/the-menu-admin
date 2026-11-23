<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->order_number ?: ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)) }} - MENU</title>
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
            padding: 6mm 5mm;
            background: #ffffff;
            color: #000000;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 12.5px;
            font-weight: 500;
            line-height: 1.38;
            -webkit-font-smoothing: antialiased;
            text-rendering: optimizeLegibility;
        }

        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: 700; color: #000000; }
        .black { font-weight: 900; color: #000000; }
        .muted { font-size: 11px; color: #333333; }

        .receipt-header {
            margin-bottom: 8px;
        }

        .receipt-logo {
            display: block;
            max-width: 95px;
            max-height: 55px;
            width: auto;
            height: auto;
            margin: 0 auto 5px auto;
            object-fit: contain;
            filter: contrast(160%);
        }

        .brand {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #000000;
            line-height: 1.1;
        }

        .slogan {
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #111111;
            margin-top: 3px;
        }

        .tagline {
            font-size: 10px;
            color: #444444;
            margin-top: 2px;
        }

        .sep {
            border-top: 1px dashed #222222;
            margin: 7px 0;
        }

        .double-sep {
            border-top: 2px dashed #000000;
            margin: 8px 0;
        }

        .thick-sep {
            border-top: 2px solid #000000;
            margin: 8px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            margin-bottom: 3px;
            align-items: flex-start;
        }

        .item {
            margin-bottom: 6px;
        }

        .item-name {
            font-weight: 700;
            font-size: 12.5px;
            word-break: break-word;
            line-height: 1.25;
        }

        .item-meta {
            display: flex;
            justify-content: space-between;
            gap: 6px;
            font-size: 11px;
            color: #222222;
            margin-top: 1px;
        }

        .order-badge {
            display: inline-block;
            border: 1.5px solid #000000;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 800;
            border-radius: 4px;
            margin-top: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .bag-included-notice {
            font-size: 10px;
            font-weight: 700;
            color: #111111;
            margin-top: 4px;
            letter-spacing: 0.2px;
        }

        .grand-total-box {
            border: 2px solid #000000;
            border-radius: 4px;
            padding: 6px 8px;
            margin: 8px 0;
            background: #fafafa;
        }

        .grand-total {
            font-size: 16.5px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .footer {
            margin-top: 12px;
            font-size: 11px;
            line-height: 1.4;
        }

        .toolbar {
            margin-top: 16px;
            text-align: center;
        }

        .toolbar button {
            padding: 9px 18px;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            color: #000000;
            background: #ffffff;
            border: 2px solid #000000;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .toolbar button:hover {
            background: #000000;
            color: #ffffff;
        }

        @media print {
            .toolbar {
                display: none !important;
            }
            body {
                padding: 2mm 3mm;
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

    <div class="center receipt-header">
        <!-- Brand Logo -->
        <img src="{{ asset('images/logo-clean.svg') }}" 
             alt="MENU Logo" 
             class="receipt-logo"
             onerror="this.src='{{ asset('images/logo.svg') }}'">

        <div class="brand">MENU</div>
        <div class="slogan">Crafted Flavours • Authentic Hospitality</div>
        <div class="tagline">Gourmet Dining &amp; Daily Buffet Counter</div>

        <div class="order-badge">
            @if($orderType === 'dine_in')
                Dine-In {{ $tableNum ? ('• Table ' . $tableNum) : '' }}
            @elseif($orderType === 'delivery')
                Counter Dispatch
            @else
                Takeaway Order
            @endif
        </div>

        @if($orderType === 'takeaway')
            <div class="bag-included-notice">
                * Polythene bag (Rs. 5/-) is included with your meal *
            </div>
        @endif
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
        <span>Cashier / Station:</span>
        <span>{{ $orderMeta['cashier_name'] ?? ($order->user->name ?? 'Main Counter') }}</span>
    </div>

    @if(!empty($order->customer_name) && !str_starts_with($order->customer_name, 'Table ') && $order->customer_name !== 'Walk-in Customer')
        <div class="row">
            <span>Customer:</span>
            <span class="bold">{{ $order->customer_name }}</span>
        </div>
    @endif
    @if(!empty($order->customer_phone))
        <div class="row">
            <span>Contact Phone:</span>
            <span>{{ $order->customer_phone }}</span>
        </div>
    @endif

    <div class="row">
        <span>Payment Method:</span>
        <span class="bold">{{ $order->payment_method ?: 'Cash' }}</span>
    </div>

    <div class="sep"></div>

    <div class="row bold" style="font-size: 11px; text-transform: uppercase;">
        <span>Item Description</span>
        <span>Amount (Rs.)</span>
    </div>

    <div class="sep"></div>

    @foreach($order->items as $item)
        <div class="item">
            <div class="item-name">
                {{ $item->food->name ?? 'Menu Item #' . $item->food_id }}
                @if(!empty($item->food?->portion_size))
                    <span style="font-size: 10.5px; font-weight: normal; color: #444;">({{ $item->food->portion_size }})</span>
                @endif
            </div>
            <div class="item-meta">
                <span>{{ $item->quantity }} &times; {{ number_format((float) $item->price, 2) }}</span>
                <span class="bold">{{ number_format((float) $item->price * (int) $item->quantity, 2) }}</span>
            </div>
        </div>
    @endforeach

    <div class="sep"></div>

    <div class="row">
        <span>Total Items Count:</span>
        <span class="bold">{{ (int) $order->items->sum('quantity') }}</span>
    </div>

    @if($discountAmount > 0)
        <div class="row">
            <span>Subtotal:</span>
            <span>Rs. {{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="row">
            <span>Discount Applied:</span>
            <span class="bold">- Rs. {{ number_format($discountAmount, 2) }}</span>
        </div>
    @endif

    @if($bagCount > 0)
        <div class="row">
            <span>Extra Carry Bags ({{ $bagCount }} &times; Rs. 5.00):</span>
            <span class="bold">+ Rs. {{ number_format($bagTotal, 2) }}</span>
        </div>
    @endif

    <div class="grand-total-box">
        <div class="row grand-total" style="margin-bottom: 0;">
            <span>NET TOTAL:</span>
            <span>Rs. {{ number_format((float) $order->total_price, 2) }}</span>
        </div>
    </div>

    @if($order->payment_method === 'Cash' && $cashTendered !== null)
        <div class="row">
            <span>Cash Tendered:</span>
            <span>Rs. {{ number_format($cashTendered, 2) }}</span>
        </div>
        <div class="row bold" style="font-size: 13px;">
            <span>Change Returned:</span>
            <span>Rs. {{ number_format($changeAmount ?? 0, 2) }}</span>
        </div>
        <div class="sep"></div>
    @endif

    @if(!empty($orderMeta['notes']))
        <div class="muted" style="margin: 4px 0;">
            <span class="bold">Special Instructions:</span> {{ $orderMeta['notes'] }}
        </div>
        <div class="sep"></div>
    @endif

    <div class="center footer">
        <div class="bold" style="font-size: 12px; margin-bottom: 2px;">Thank you for dining with MENU!</div>
        <div class="muted">Freshly prepared authentic flavours &amp; warm hospitality.</div>
        <div class="muted" style="margin-top: 4px; font-size: 10px; color: #666;">
            Ref: {{ $order->payment_reference ?: ('POS-' . $order->id) }} • menusrilanka.com
        </div>
    </div>

    <div class="toolbar">
        <button type="button" onclick="window.print()">🖨️ Print Receipt</button>
    </div>

    <script>
        window.addEventListener('load', function () {
            // Check if printing inside an iframe or standalone
            window.focus();
            try {
                window.print();
            } catch(e) {}
        });
    </script>
</body>
</html>
