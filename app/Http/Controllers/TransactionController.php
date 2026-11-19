<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\UserNotification;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of financial transactions and payment verifications.
     */
    public function index(Request $request)
    {
        if (auth()->user()?->role === 'Chef') {
            abort(403, 'Unauthorized. Chefs are not allowed to access Financial Transactions.');
        }

        $search = trim($request->input('search', ''));
        $channelFilter = $request->input('channel', 'all');   // all, pos, app
        $modeFilter = $request->input('order_mode', 'all');   // all, dine_in, takeaway, delivery
        $statusFilter = $request->input('status', 'all');     // all, pending, verified, rejected
        $methodFilter = $request->input('method', 'all');     // all, cash, card, bank_transfer, online_transfer
        $dateFilter = $request->input('date', 'all');         // all, today, yesterday, this_week, this_month, custom
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $sort = $request->input('sort', 'newest');            // newest, oldest, amount_desc, amount_asc

        // Base query with relationships
        $query = Order::with(['user', 'items.food']);

        // Channel filter (All, POS Terminal, Mobile App)
        if ($channelFilter === 'pos') {
            $query->where('channel', 'pos');
        } elseif ($channelFilter === 'app') {
            $query->where('channel', 'app');
        }

        // Order Mode filter (Dine-In, Takeaway, Delivery)
        if ($modeFilter !== 'all' && !empty($modeFilter)) {
            $query->where('order_mode', strtolower($modeFilter));
        }

        // Status Filter
        if ($statusFilter === 'pending') {
            $query->where(function ($q) {
                $q->whereNull('payment_status')
                  ->orWhereNotIn('payment_status', ['verified', 'paid', 'rejected']);
            });
        } elseif ($statusFilter === 'verified') {
            $query->whereIn('payment_status', ['verified', 'paid']);
        } elseif ($statusFilter === 'rejected') {
            $query->where('payment_status', 'rejected');
        }

        // Payment Method Filter
        if ($methodFilter === 'cash') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%cash%')
                  ->orWhere('payment_method', 'like', '%cod%');
            });
        } elseif ($methodFilter === 'card') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%card%')
                  ->orWhere('payment_method', 'like', '%visa%')
                  ->orWhere('payment_method', 'like', '%master%');
            });
        } elseif ($methodFilter === 'bank_transfer') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url');
            });
        } elseif ($methodFilter === 'online_transfer') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%online%')
                  ->orWhere('payment_method', 'like', '%qr%')
                  ->orWhere('payment_method', 'like', '%transfer%');
            })->where('payment_method', 'not like', '%bank%');
        }

        // Search Filter (ID, code, customer, phone, reference, method, notes)
        if (!empty($search)) {
            $cleanSearch = ltrim($search, '#');
            $cleanSearch = preg_replace('/^(ORD|TXN)-0*/i', '', $cleanSearch);

            $query->where(function ($q) use ($search, $cleanSearch) {
                if (is_numeric($cleanSearch)) {
                    $q->orWhere('id', (int)$cleanSearch);
                }
                $q->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhere('delivery_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($dateFilter === 'yesterday') {
            $query->whereDate('created_at', now()->subDay()->toDateString());
        } elseif ($dateFilter === 'this_week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        } elseif ($dateFilter === 'this_month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        } elseif ($dateFilter === 'custom' && $fromDate && $toDate) {
            $query->whereBetween('created_at', [
                $fromDate . ' 00:00:00',
                $toDate . ' 23:59:59'
            ]);
        }

        // Sorting Logic (Date-wise & Amount-wise)
        if ($sort === 'oldest') {
            $query->oldest('created_at');
        } elseif ($sort === 'amount_desc') {
            $query->orderByDesc('total_price');
        } elseif ($sort === 'amount_asc') {
            $query->orderBy('total_price');
        } else {
            // Default newest first
            $query->latest('created_at');
        }

        // Channel-Aware KPI Metrics
        $statsScope = Order::query();
        if ($channelFilter === 'pos') {
            $statsScope->where('channel', 'pos');
        } elseif ($channelFilter === 'app') {
            $statsScope->where('channel', 'app');
        }

        // Total Settled Revenue
        $totalRevenue = (clone $statsScope)
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');

        // Slips Awaiting Verification
        $pendingSlipsCount = (clone $statsScope)
            ->where(function ($sub) {
                $sub->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['verified', 'paid', 'rejected']);
            })
            ->count();

        // Bank Transfers (Approved)
        $approvedBankCount = (clone $statsScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->count();

        $approvedBankAmount = (clone $statsScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');

        // Online & Card Transfers
        $approvedOnlineCount = (clone $statsScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%online%')
                  ->orWhere('payment_method', 'like', '%qr%')
                  ->orWhere('payment_method', 'like', '%card%');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->count();

        $approvedOnlineAmount = (clone $statsScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%online%')
                  ->orWhere('payment_method', 'like', '%qr%')
                  ->orWhere('payment_method', 'like', '%card%');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');

        // Counts per channel for tabs
        $allCount = Order::count();
        $posCount = Order::where('channel', 'pos')->count();
        $appCount = Order::where('channel', 'app')->count();

        $stats = [
            'total_revenue'             => $totalRevenue,
            'pending_slips_count'       => $pendingSlipsCount,
            'approved_transfers_count'  => $approvedBankCount,
            'approved_transfers_amount' => $approvedBankAmount,
            'approved_online_count'     => $approvedOnlineCount,
            'approved_online_amount'    => $approvedOnlineAmount,
            'all_count'                 => $allCount,
            'pos_count'                 => $posCount,
            'app_count'                 => $appCount,
        ];

        $transactions = $query->paginate(15)->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'stats',
            'search',
            'channelFilter',
            'modeFilter',
            'statusFilter',
            'methodFilter',
            'dateFilter',
            'fromDate',
            'toDate',
            'sort'
        ));
    }

    /**
     * Verify bank transfer payment.
     */
    public function verifyPayment(Order $order)
    {
        $order->update(['payment_status' => 'verified']);

        if ($order->user_id) {
            try {
                UserNotification::create([
                    'user_id' => $order->user_id,
                    'title'   => 'Payment Verified! 💳✅',
                    'message' => "Your payment for Order #{$order->id} (Ref: {$order->payment_reference}) has been verified. You can now download your official invoice.",
                    'type'    => 'payment',
                    'data'    => ['order_id' => $order->id, 'payment_status' => 'verified'],
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {}
        }

        return redirect()->back()->with('success', "Payment for Order #ORD-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " has been verified successfully!");
    }

    /**
     * Reject bank transfer payment.
     */
    public function rejectPayment(Order $order)
    {
        $order->update(['payment_status' => 'rejected']);

        if ($order->user_id) {
            try {
                UserNotification::create([
                    'user_id' => $order->user_id,
                    'title'   => 'Payment Verification Failed ⚠️',
                    'message' => "We could not verify the bank transfer for Order #{$order->id}. Please contact support or re-upload your transfer slip.",
                    'type'    => 'payment',
                    'data'    => ['order_id' => $order->id, 'payment_status' => 'rejected'],
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {}
        }

        return redirect()->back()->with('warning', "Payment for Order #ORD-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " marked as rejected.");
    }

    /**
     * Permanently remove/delete an order & transaction record.
     */
    public function destroy(Order $order)
    {
        $orderCode = '#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);

        // Free up assigned driver if any
        if ($order->driver) {
            $order->driver->update(['status' => 'available']);
            try {
                \App\Events\DriverStatusUpdated::safeDispatch($order->driver);
            } catch (\Throwable $e) {}
        }

        // Delete order (cascades to order_items automatically via foreign key)
        $order->delete();

        return redirect()->back()->with('success', "Order {$orderCode} has been permanently deleted from records.");
    }
}
