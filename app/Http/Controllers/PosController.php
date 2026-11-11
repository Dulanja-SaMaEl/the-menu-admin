<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    /**
     * Touch-screen Point of Sale terminal for cashiers.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        // Mirror the Food & Menu module: beverage placeholders stay hidden while disabled.
        $foods = Food::query()
            ->where('type', '!=', 'Beverage')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->map(fn (Food $food) => [
                'id' => $food->id,
                'name' => $food->name,
                'price' => (float) $food->price,
                'category' => $food->type,
                'image' => $food->image_url,
            ])
            ->values();

        return view('pos.index', [
            'foods' => $foods,
            'categories' => $foods->pluck('category')->unique()->values(),
        ]);
    }

    /**
     * Persist a walk-in counter sale and return the thermal receipt URL.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:food,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $foods = Food::whereIn('id', collect($data['items'])->pluck('id'))->get()->keyBy('id');

        $lines = [];
        $total = 0.0;

        foreach ($data['items'] as $line) {
            $food = $foods->get($line['id']);
            if (! $food) {
                continue;
            }

            $quantity = (int) $line['quantity'];
            $price = (float) $food->price;
            $total += $price * $quantity;

            $lines[] = [
                'food' => $food,
                'quantity' => $quantity,
                'price' => $price,
            ];
        }

        if (empty($lines)) {
            return response()->json(['message' => 'There are no sellable items in this order.'], 422);
        }

        $userId = $request->user()->id;
        $paymentMethod = $data['payment_method'] ?? 'Cash';
        $now = now();

        // Written against the live orders/order_items columns so the sale flows into
        // Bills, Transactions and Reports exactly like every other order.
        $orderId = DB::transaction(function () use ($lines, $total, $userId, $paymentMethod, $now) {
            $orderId = DB::table('orders')->insertGetId([
                'user_id' => $userId,
                'total_price' => round($total, 2),
                'status' => 'completed',
                'address' => 'Walk-in Counter Sale',
                'payment_method' => $paymentMethod,
                'payment_status' => 'paid',
                'payment_reference' => Order::generateReference($userId),
                'is_subscription' => false,
                'discount_amount' => 0.00,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

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
            'order_code' => 'ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
            'total_price' => (float) $order->total_price,
            'receipt_url' => route('pos.receipt', $order),
        ]);
    }

    /**
     * 80mm thermal receipt for a completed counter sale.
     */
    public function receipt(Order $order)
    {
        $order->load(['items.food', 'user']);

        return view('pos.receipt', ['order' => $order]);
    }
}
