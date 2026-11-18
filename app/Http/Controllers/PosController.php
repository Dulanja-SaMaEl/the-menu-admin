<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PosController extends Controller
{
    /**
     * Dedicated Touch-screen Point of Sale terminal for cashiers.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        // Fetch all sellable counter items (Buffet items, Individual Portions, Beverages, Meal Sets)
        $foods = Food::query()
            ->forPos()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('brand', 'like', '%' . $search . '%')
                      ->orWhere('type', 'like', '%' . $search . '%')
                      ->orWhere('portion_size', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Food $food) => [
                'id' => $food->id,
                'name' => $food->name,
                'price' => (float) $food->price,
                'category' => $food->type,
                'portion_size' => $food->portion_size ?: $food->servings,
                'brand' => $food->brand,
                'volume_ml' => $food->volume_ml,
                'image' => $food->image_url,
            ])
            ->values();

        // Calculate today's shift metrics for the active cashier/station
        $today = now()->toDateString();
        $shiftSales = (float) Order::query()
            ->whereDate('created_at', $today)
            ->where(function ($q) {
                $q->where('address', 'like', '%Walk-in%')
                  ->orWhere('address', 'like', '%Dine-In%')
                  ->orWhere('address', 'like', '%Counter%');
            })
            ->where('payment_status', 'paid')
            ->sum('total_price');

        $shiftOrderCount = (int) Order::query()
            ->whereDate('created_at', $today)
            ->where(function ($q) {
                $q->where('address', 'like', '%Walk-in%')
                  ->orWhere('address', 'like', '%Dine-In%')
                  ->orWhere('address', 'like', '%Counter%');
            })
            ->count();

        return view('pos.index', [
            'foods' => $foods,
            'categories' => $foods->pluck('category')->unique()->values(),
            'cashier' => $request->user(),
            'shiftSales' => $shiftSales,
            'shiftOrderCount' => $shiftOrderCount,
        ]);
    }

    /**
     * Persist a counter sale and return the thermal receipt URL and order summary.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:food,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'order_type' => ['nullable', 'string', 'in:takeaway,dine_in,delivery'],
            'table_number' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'discount_type' => ['nullable', 'string', 'in:none,percentage,fixed'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'cash_tendered' => ['nullable', 'numeric', 'min:0'],
            'bag_count' => ['nullable', 'integer', 'min:0', 'max:500'],
        ]);

        $foods = Food::whereIn('id', collect($data['items'])->pluck('id'))->get()->keyBy('id');

        $lines = [];
        $subtotal = 0.0;

        foreach ($data['items'] as $line) {
            $food = $foods->get($line['id']);
            if (! $food) {
                continue;
            }

            $quantity = (int) $line['quantity'];
            $price = (float) $food->price;
            $lineSubtotal = $price * $quantity;
            $subtotal += $lineSubtotal;

            $lines[] = [
                'food' => $food,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        if (empty($lines)) {
            return response()->json(['message' => 'There are no sellable items in this order.'], 422);
        }

        // Calculate discount
        $discountType = $data['discount_type'] ?? 'none';
        $discountValue = (float) ($data['discount_value'] ?? 0);
        $discountAmount = 0.0;

        if ($discountType === 'percentage' && $discountValue > 0) {
            $discountAmount = round($subtotal * (min(100, $discountValue) / 100), 2);
        } elseif ($discountType === 'fixed' && $discountValue > 0) {
            $discountAmount = round(min($subtotal, $discountValue), 2);
        }

        // Polythene bag charges (Rs. 5 each)
        $bagCount = max(0, (int) ($data['bag_count'] ?? 0));
        $bagUnitPrice = 5.00;
        $bagTotal = round($bagCount * $bagUnitPrice, 2);

        $finalTotal = max(0.00, round(($subtotal - $discountAmount) + $bagTotal, 2));

        $userId = $request->user()->id;
        $orderType = $data['order_type'] ?? 'takeaway';
        $tableNumber = trim((string) ($data['table_number'] ?? ''));
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $orderNotes = trim((string) ($data['notes'] ?? ''));
        $paymentMethod = $data['payment_method'] ?? 'Cash';
        $cashTendered = isset($data['cash_tendered']) ? (float) $data['cash_tendered'] : null;
        $changeAmount = ($paymentMethod === 'Cash' && $cashTendered !== null)
            ? max(0.00, round($cashTendered - $finalTotal, 2))
            : 0.00;

        $now = now();

        // Address label formatted according to order type
        if ($orderType === 'dine_in') {
            $address = 'Dine-In • Table ' . ($tableNumber ?: 'Counter');
            $resolvedCustomerName = $customerName ?: ('Table ' . ($tableNumber ?: 'Guest'));
        } elseif ($orderType === 'delivery') {
            $address = 'Counter Dispatch / Delivery';
            $resolvedCustomerName = $customerName ?: 'Delivery Customer';
        } else {
            $address = 'Walk-in Counter Sale';
            $resolvedCustomerName = $customerName ?: 'Walk-in Customer';
        }

        // Structured metadata stored in pod_notes for audit and thermal receipt
        $metadata = [
            'pos_order' => true,
            'order_type' => $orderType,
            'table_number' => $tableNumber ?: null,
            'cashier_id' => $userId,
            'cashier_name' => $request->user()->name,
            'subtotal' => $subtotal,
            'discount_type' => $discountType,
            'discount_value' => $discountValue,
            'discount_amount' => $discountAmount,
            'bag_count' => $bagCount,
            'bag_unit_price' => $bagUnitPrice,
            'bag_total' => $bagTotal,
            'cash_tendered' => $cashTendered,
            'change_amount' => $changeAmount,
            'notes' => $orderNotes ?: null,
        ];

        // Persist order inside atomic transaction
        $orderId = DB::transaction(function () use (
            $lines, $finalTotal, $userId, $paymentMethod, $resolvedCustomerName,
            $customerPhone, $address, $discountAmount, $metadata, $now
        ) {
            $nextSeq = (DB::table('orders')->max('id') ?? 0) + 1;
            $orderNumber = 'ORD-' . str_pad($nextSeq, 5, '0', STR_PAD_LEFT);

            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'customer_name' => $resolvedCustomerName,
                'customer_phone' => $customerPhone ?: 'N/A',
                'delivery_address' => $address,
                'address' => $address,
                'total_price' => $finalTotal,
                'total_amount' => $finalTotal,
                'status' => 'completed',
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'payment_reference' => Order::generateReference($userId),
                'is_subscription' => false,
                'discount_amount' => $discountAmount,
                'pod_notes' => json_encode($metadata),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Ensure exact alignment with final generated ID
            $actualOrderNumber = 'ORD-' . str_pad($orderId, 5, '0', STR_PAD_LEFT);
            if ($actualOrderNumber !== $orderNumber) {
                DB::table('orders')->where('id', $orderId)->update(['order_number' => $actualOrderNumber]);
            }

            foreach ($lines as $line) {
                DB::table('order_items')->insert([
                    'order_id' => $orderId,
                    'food_id' => $line['food']->id,
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'meal_type' => OrderController::normalizeMealType(null, $line['food']->type),
                    'scheduled_date' => $now->toDateString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            return $orderId;
        });

        $order = Order::findOrFail($orderId);

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_code' => $order->order_number ?: ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)),
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'bag_count' => $bagCount,
            'bag_total' => $bagTotal,
            'total_price' => (float) $order->total_price,
            'change_amount' => $changeAmount,
            'receipt_url' => route('pos.receipt', $order),
        ]);
    }

    /**
     * Return recent orders created at the POS for quick recall and receipt reprint.
     */
    public function recentOrders(Request $request)
    {
        $orders = Order::query()
            ->where(function ($q) {
                $q->where('address', 'like', '%Walk-in%')
                  ->orWhere('address', 'like', '%Dine-In%')
                  ->orWhere('address', 'like', '%Counter%')
                  ->orWhereNotNull('pod_notes');
            })
            ->latest('id')
            ->limit(25)
            ->with(['items.food'])
            ->get()
            ->map(function (Order $order) {
                $meta = [];
                if (!empty($order->pod_notes) && str_starts_with($order->pod_notes, '{')) {
                    $meta = json_decode($order->pod_notes, true) ?: [];
                }

                $orderType = $meta['order_type'] ?? (str_contains($order->address, 'Dine-In') ? 'dine_in' : 'takeaway');

                return [
                    'id' => $order->id,
                    'order_code' => $order->order_number ?: ('ORD-' . str_pad($order->id, 5, '0', STR_PAD_LEFT)),
                    'time' => $order->created_at->format('h:i A'),
                    'date' => $order->created_at->format('M d, Y'),
                    'customer' => $order->customer_name ?: 'Walk-in Customer',
                    'order_type' => $orderType,
                    'type_label' => ucfirst(str_replace('_', ' ', $orderType)),
                    'table_number' => $meta['table_number'] ?? null,
                    'items_count' => (int) $order->items->sum('quantity'),
                    'total' => (float) $order->total_price,
                    'payment_method' => $order->payment_method ?: 'Cash',
                    'receipt_url' => route('pos.receipt', $order),
                ];
            });

        return response()->json([
            'success' => true,
            'orders' => $orders,
        ]);
    }

    /**
     * 80mm thermal receipt for a completed counter sale.
     */
    public function receipt(Order $order)
    {
        $order->load(['items.food', 'user']);

        $meta = [];
        if (!empty($order->pod_notes) && str_starts_with($order->pod_notes, '{')) {
            $meta = json_decode($order->pod_notes, true) ?: [];
        }

        return view('pos.receipt', [
            'order' => $order,
            'meta' => $meta,
        ]);
    }
}
