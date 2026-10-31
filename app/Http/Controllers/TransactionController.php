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
        $statusFilter = $request->input('status', 'all'); // all, pending, verified, rejected
        $methodFilter = $request->input('method', 'all'); // all, bank_transfer, online_transfer
        $dateFilter = $request->input('date', 'all');     // all, today, this_week, this_month

        // Base scope: ONLY Bank Transfers and Online Transfers (strictly exclude Cash, COD, and Card)
        $applyTransferScope = function ($q) {
            $q->where(function ($sub) {
                $sub->where('payment_method', 'like', '%bank%')
                    ->orWhere('payment_method', 'like', '%online%')
                    ->orWhere('payment_method', 'like', '%transfer%')
                    ->orWhereNotNull('bank_receipt_url')
                    ->orWhereNull('payment_method');
            })->where(function ($sub) {
                $sub->whereNull('payment_method')
                    ->orWhere(function ($valid) {
                        $valid->where('payment_method', 'not like', '%cash%')
                              ->where('payment_method', 'not like', '%delivery%')
                              ->where('payment_method', 'not like', '%cod%')
                              ->where('payment_method', 'not like', '%card%')
                              ->where('payment_method', 'not like', '%visa%')
                              ->where('payment_method', 'not like', '%master%');
                    });
            });
        };

        // Overall KPI Statistics (across all valid bank & online transfers)
        $totalRevenue = Order::where($applyTransferScope)
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');
        if ($totalRevenue == 0) {
            $totalRevenue = Order::where($applyTransferScope)
                ->whereIn('status', ['completed', 'delivered', 'processing'])
                ->sum('total_price');
        }

        $pendingSlipsCount = Order::where($applyTransferScope)
            ->where(function ($sub) {
                $sub->whereNull('payment_status')
                    ->orWhereNotIn('payment_status', ['verified', 'paid', 'rejected']);
            })
            ->count();

        $approvedBankTransfersCount = Order::where($applyTransferScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url')
                  ->orWhereNull('payment_method');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->count();

        $approvedBankTransfersAmount = Order::where($applyTransferScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url')
                  ->orWhereNull('payment_method');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');

        $approvedOnlineTransfersCount = Order::where($applyTransferScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%online%')
                  ->where('payment_method', 'not like', '%bank%');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->count();

        $approvedOnlineTransfersAmount = Order::where($applyTransferScope)
            ->where(function ($q) {
                $q->where('payment_method', 'like', '%online%')
                  ->where('payment_method', 'not like', '%bank%');
            })
            ->whereIn('payment_status', ['verified', 'paid'])
            ->sum('total_price');

        $stats = [
            'total_revenue'             => $totalRevenue,
            'pending_slips_count'       => $pendingSlipsCount,
            'approved_transfers_count'  => $approvedBankTransfersCount,
            'approved_transfers_amount' => $approvedBankTransfersAmount,
            'approved_online_count'     => $approvedOnlineTransfersCount,
            'approved_online_amount'    => $approvedOnlineTransfersAmount,
        ];

        // Build Query for Transactions Table (Strictly Bank & Online Transfers)
        $query = Order::with(['user', 'items.food'])
            ->where($applyTransferScope)
            ->latest();

        // Search Filter
        if (!empty($search)) {
            $cleanSearch = ltrim($search, '#');
            $cleanSearch = preg_replace('/^(ORD|TXN)-0*/i', '', $cleanSearch);

            $query->where(function ($q) use ($search, $cleanSearch) {
                if (is_numeric($cleanSearch)) {
                    $q->orWhere('id', (int)$cleanSearch);
                }
                $q->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhere('payment_method', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
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

        // Method Filter (Strictly Bank Transfer vs Online Transfer)
        if ($methodFilter === 'bank_transfer') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%bank%')
                  ->orWhereNotNull('bank_receipt_url')
                  ->orWhereNull('payment_method');
            });
        } elseif ($methodFilter === 'online_transfer') {
            $query->where(function ($q) {
                $q->where('payment_method', 'like', '%online%');
            });
        }

        // Date Filter
        if ($dateFilter === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($dateFilter === 'this_week') {
            $query->where('created_at', '>=', now()->startOfWeek());
        } elseif ($dateFilter === 'this_month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        $transactions = $query->paginate(15)->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'stats',
            'search',
            'statusFilter',
            'methodFilter',
            'dateFilter'
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
                    'message' => "Your bank transfer for Order #{$order->id} (Ref: {$order->payment_reference}) has been verified. You can now download your official invoice.",
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
}
