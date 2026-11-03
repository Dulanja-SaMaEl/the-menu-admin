<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Event;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display a comprehensive luxury SaaS analytics report for all core modules.
     */
    public function index(Request $request)
    {
        if (auth()->user()?->role === 'Chef') {
            abort(403, 'Unauthorized. Chefs are restricted from accessing Financial Reports & Analytics.');
        }

        $activeTab = $request->input('tab', 'overview');
        $validTabs = ['overview', 'orders', 'transactions', 'customers', 'drivers', 'events', 'meals'];
        if (!in_array($activeTab, $validTabs)) {
            $activeTab = 'overview';
        }

        $range = $request->input('range', 'all'); // all, today, 7d, 30d, this_month, custom
        $fromDate = $request->input('from');
        $toDate = $request->input('to');

        // Resolve Date Boundaries
        $startDate = null;
        $endDate = null;

        if ($range === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($range === '7d') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($range === '30d') {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($range === 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($range === 'custom') {
            if (!empty($fromDate)) {
                try {
                    $startDate = Carbon::parse($fromDate)->startOfDay();
                } catch (\Throwable $e) {
                    $startDate = null;
                }
            }
            if (!empty($toDate)) {
                try {
                    $endDate = Carbon::parse($toDate)->endOfDay();
                } catch (\Throwable $e) {
                    $endDate = null;
                }
            }
        }

        // Date Filter Helper Closure
        $applyDate = function ($query, $column = 'created_at') use ($startDate, $endDate) {
            if ($startDate && $endDate) {
                $query->whereBetween($column, [$startDate, $endDate]);
            } elseif ($startDate) {
                $query->where($column, '>=', $startDate);
            } elseif ($endDate) {
                $query->where($column, '<=', $endDate);
            }
        };

        // ==========================================
        // 1. ORDERS MODULE METRICS
        // ==========================================
        $ordersQuery = Order::query();
        $applyDate($ordersQuery, 'created_at');

        $totalOrdersCount = (clone $ordersQuery)->count();
        $completedOrdersCount = (clone $ordersQuery)->whereIn('status', ['completed', 'delivered'])->count();
        $activeOrdersCount = (clone $ordersQuery)->whereIn('status', ['pending', 'processing', 'assigned'])->count();
        $cancelledOrdersCount = (clone $ordersQuery)->where('status', 'cancelled')->count();

        $ordersRevenue = (clone $ordersQuery)
            ->whereIn('status', ['completed', 'delivered', 'processing'])
            ->sum('total_price');

        if ($ordersRevenue == 0 && $totalOrdersCount > 0) {
            $ordersRevenue = (clone $ordersQuery)
                ->whereNotIn('status', ['cancelled'])
                ->sum('total_price');
        }

        $fulfilledOrdersCount = $completedOrdersCount + $activeOrdersCount;
        $averageOrderValue = $fulfilledOrdersCount > 0 ? ($ordersRevenue / $fulfilledOrdersCount) : 0;
        $orderFulfillmentRate = $totalOrdersCount > 0 ? round(($completedOrdersCount / $totalOrdersCount) * 100, 1) : 0;

        // Meal-Type Distribution (Breakfast, Lunch, Dinner)
        $mealItemsQuery = OrderItem::whereHas('order', function ($q) use ($applyDate) {
            $applyDate($q, 'created_at');
        });

        $mealDistributionRaw = (clone $mealItemsQuery)
            ->selectRaw('meal_type, count(*) as count, sum(price * quantity) as revenue')
            ->whereNotNull('meal_type')
            ->where('meal_type', '!=', '')
            ->groupBy('meal_type')
            ->get();

        $totalMealItems = $mealDistributionRaw->sum('count');
        $mealDistribution = $mealDistributionRaw->map(function ($item) use ($totalMealItems) {
            $pct = $totalMealItems > 0 ? round(($item->count / $totalMealItems) * 100, 1) : 0;
            return [
                'type'       => ucfirst($item->meal_type),
                'count'      => (int)$item->count,
                'revenue'    => (float)($item->revenue ?: 0),
                'percentage' => $pct,
            ];
        });

        // Ensure default meal types exist if DB has no explicit rows
        if ($mealDistribution->isEmpty()) {
            $mealDistribution = collect([
                ['type' => 'Lunch', 'count' => 0, 'revenue' => 0.0, 'percentage' => 0],
                ['type' => 'Dinner', 'count' => 0, 'revenue' => 0.0, 'percentage' => 0],
                ['type' => 'Breakfast', 'count' => 0, 'revenue' => 0.0, 'percentage' => 0],
            ]);
        }

        // Recent Orders in scope
        $recentOrders = (clone $ordersQuery)
            ->with(['user', 'driver', 'items.food'])
            ->latest()
            ->take(8)
            ->get();

        $ordersStats = [
            'total_count'       => $totalOrdersCount,
            'completed_count'   => $completedOrdersCount,
            'active_count'      => $activeOrdersCount,
            'cancelled_count'   => $cancelledOrdersCount,
            'total_revenue'     => $ordersRevenue,
            'avg_order_value'   => $averageOrderValue,
            'fulfillment_rate'  => $orderFulfillmentRate,
            'meal_distribution' => $mealDistribution,
            'recent_orders'     => $recentOrders,
        ];

        // ==========================================
        // 2. TRANSACTIONS & LEDGER MODULE METRICS
        // ==========================================
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

        $txQuery = Order::where($applyTransferScope);
        $applyDate($txQuery, 'created_at');

        $totalTxVolume = (clone $txQuery)->sum('total_price');

        $verifiedTxCount = (clone $txQuery)->whereIn('payment_status', ['verified', 'paid'])->count();
        $verifiedTxVolume = (clone $txQuery)->whereIn('payment_status', ['verified', 'paid'])->sum('total_price');

        $pendingTxCount = (clone $txQuery)->where(function ($sub) {
            $sub->whereNull('payment_status')
                ->orWhereNotIn('payment_status', ['verified', 'paid', 'rejected']);
        })->count();
        $pendingTxVolume = (clone $txQuery)->where(function ($sub) {
            $sub->whereNull('payment_status')
                ->orWhereNotIn('payment_status', ['verified', 'paid', 'rejected']);
        })->sum('total_price');

        $rejectedTxCount = (clone $txQuery)->where('payment_status', 'rejected')->count();
        $rejectedTxVolume = (clone $txQuery)->where('payment_status', 'rejected')->sum('total_price');

        $totalSlipsCount = $verifiedTxCount + $pendingTxCount + $rejectedTxCount;
        $verificationRate = $totalSlipsCount > 0 ? round(($verifiedTxCount / $totalSlipsCount) * 100, 1) : 100;

        // Inferred Bank-wise Breakdown
        $allTxRecords = (clone $txQuery)->select(['id', 'payment_reference', 'payment_method', 'total_price', 'payment_status'])->get();

        $bankBuckets = [
            'Commercial Bank' => ['count' => 0, 'volume' => 0.0],
            'Sampath Bank'    => ['count' => 0, 'volume' => 0.0],
            'Hatton National' => ['count' => 0, 'volume' => 0.0],
            'Bank of Ceylon'  => ['count' => 0, 'volume' => 0.0],
            'Online Banking'  => ['count' => 0, 'volume' => 0.0],
        ];

        foreach ($allTxRecords as $tx) {
            $str = strtolower(($tx->payment_method ?? '') . ' ' . ($tx->payment_reference ?? ''));
            $amt = (float)($tx->total_price ?: 0);

            if (str_contains($str, 'commercial') || str_contains($str, 'comb')) {
                $bankBuckets['Commercial Bank']['count']++;
                $bankBuckets['Commercial Bank']['volume'] += $amt;
            } elseif (str_contains($str, 'sampath')) {
                $bankBuckets['Sampath Bank']['count']++;
                $bankBuckets['Sampath Bank']['volume'] += $amt;
            } elseif (str_contains($str, 'hatton') || str_contains($str, 'hnb')) {
                $bankBuckets['Hatton National']['count']++;
                $bankBuckets['Hatton National']['volume'] += $amt;
            } elseif (str_contains($str, 'ceylon') || str_contains($str, 'boc')) {
                $bankBuckets['Bank of Ceylon']['count']++;
                $bankBuckets['Bank of Ceylon']['volume'] += $amt;
            } else {
                $bankBuckets['Online Banking']['count']++;
                $bankBuckets['Online Banking']['volume'] += $amt;
            }
        }

        $bankBreakdown = collect($bankBuckets)->map(function ($val, $bank) use ($totalTxVolume) {
            $pct = $totalTxVolume > 0 ? round(($val['volume'] / $totalTxVolume) * 100, 1) : 0;
            return [
                'bank'       => $bank,
                'count'      => $val['count'],
                'volume'     => $val['volume'],
                'percentage' => $pct,
            ];
        })->values();

        $recentTransactions = (clone $txQuery)
            ->with(['user'])
            ->latest()
            ->take(8)
            ->get();

        $allTransactions = (clone $txQuery)
            ->with(['user'])
            ->latest()
            ->get();

        // Daily / Monthly Aggregation of Bank Transfers in Selected Range
        $dailyBankTrend = (clone $txQuery)
            ->selectRaw('DATE(created_at) as tx_date, count(*) as count, sum(total_price) as total_volume, sum(case when payment_status in ("verified", "paid") then total_price else 0 end) as verified_volume, sum(case when payment_status not in ("verified", "paid", "rejected") or payment_status is null then total_price else 0 end) as pending_volume')
            ->groupBy('tx_date')
            ->orderBy('tx_date', 'desc')
            ->take(30)
            ->get();

        $transactionsStats = [
            'total_volume'       => $totalTxVolume,
            'verified_count'     => $verifiedTxCount,
            'verified_volume'    => $verifiedTxVolume,
            'pending_count'      => $pendingTxCount,
            'pending_volume'     => $pendingTxVolume,
            'rejected_count'     => $rejectedTxCount,
            'rejected_volume'    => $rejectedTxVolume,
            'total_slips'        => $totalSlipsCount,
            'verification_rate'  => $verificationRate,
            'bank_breakdown'     => $bankBreakdown,
            'recent_transfers'   => $recentTransactions,
            'all_transfers'      => $allTransactions,
            'daily_trend'        => $dailyBankTrend,
        ];

        // ==========================================
        // 3. CUSTOMERS MODULE METRICS
        // ==========================================
        $customerBase = User::query();
        $hasCustomerRole = User::where('role', 'Customer')->exists();

        if ($hasCustomerRole) {
            $customerBase->where('role', 'Customer');
        } else {
            $adminRoles = ['Super Admin', 'System Admin', 'Chef', 'HR'];
            $customerBase->where(function ($q) use ($adminRoles) {
                $q->whereNotIn('role', $adminRoles)->orWhereNull('role');
            });
        }

        $totalCustomersCount = (clone $customerBase)->count();
        
        $newCustomersInPeriod = (clone $customerBase);
        $applyDate($newCustomersInPeriod, 'created_at');
        $newCustomersCount = $newCustomersInPeriod->count();

        // Active customers: ordered within the last 30 days
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        $activeCustomersCount = (clone $customerBase)->whereHas('orders', function ($q) use ($thirtyDaysAgo) {
            $q->where('created_at', '>=', $thirtyDaysAgo);
        })->count();

        $inactiveCustomersCount = max(0, $totalCustomersCount - $activeCustomersCount);
        $recurringCustomersCount = (clone $customerBase)->has('orders', '>=', 2)->count();
        $recurringRate = $totalCustomersCount > 0 ? round(($recurringCustomersCount / $totalCustomersCount) * 100, 1) : 0;

        $customerIds = (clone $customerBase)->pluck('id');
        $totalCustomerSpend = Order::whereIn('user_id', $customerIds)->sum('total_price');
        $averageCustomerLTV = $totalCustomersCount > 0 ? ($totalCustomerSpend / $totalCustomersCount) : 0;

        // Top Clients by Spend
        $topClients = (clone $customerBase)
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_price')
            ->orderByDesc('total_spent')
            ->take(10)
            ->get();

        // Full Customer List with Detailed Metrics
        $allCustomers = (clone $customerBase)
            ->withCount('orders')
            ->withSum('orders as total_spent', 'total_price')
            ->with(['orders' => fn($q) => $q->latest()->take(1)])
            ->orderByDesc('total_spent')
            ->get();

        $customersStats = [
            'total_count'       => $totalCustomersCount,
            'new_in_period'     => $newCustomersCount,
            'active_count'      => $activeCustomersCount,
            'inactive_count'    => $inactiveCustomersCount,
            'recurring_count'   => $recurringCustomersCount,
            'recurring_rate'    => $recurringRate,
            'total_spend'       => $totalCustomerSpend,
            'avg_ltv'           => $averageCustomerLTV,
            'top_clients'       => $topClients,
            'all_customers'     => $allCustomers,
        ];

        // ==========================================
        // 4. DRIVERS & FLEET MODULE METRICS
        // ==========================================
        $allDrivers = Driver::all();
        $totalDrivers = $allDrivers->count();
        $availableDrivers = $allDrivers->where('status', 'available')->count();
        $onDeliveryDrivers = $allDrivers->where('status', 'on_delivery')->count();
        $offlineDrivers = $allDrivers->where('status', 'offline')->count();

        $totalFleetDeliveries = (int)$allDrivers->sum('total_deliveries');
        $totalFleetMaxCapacity = (int)$allDrivers->sum('max_orders');

        // Active orders currently assigned across the fleet
        $activeAssignedOrdersCount = Order::whereIn('status', ['pending', 'processing', 'assigned'])
            ->whereNotNull('driver_id')
            ->count();

        $fleetUtilization = $totalFleetMaxCapacity > 0 
            ? round(($activeAssignedOrdersCount / $totalFleetMaxCapacity) * 100, 1) 
            : 0;

        $driversList = Driver::with(['assignedOrders' => function ($q) {
            $q->whereIn('status', ['pending', 'processing', 'assigned']);
        }])
        ->withCount([
            'assignedOrders as completed_orders_count' => function ($q) {
                $q->whereIn('status', ['completed', 'delivered']);
            }
        ])
        ->orderByDesc('total_deliveries')
        ->get();

        $driversStats = [
            'total_drivers'         => $totalDrivers,
            'available_drivers'     => $availableDrivers,
            'on_delivery_drivers'   => $onDeliveryDrivers,
            'offline_drivers'       => $offlineDrivers,
            'total_deliveries'      => $totalFleetDeliveries,
            'max_capacity'          => $totalFleetMaxCapacity,
            'active_assigned'       => $activeAssignedOrdersCount,
            'fleet_utilization'     => $fleetUtilization,
            'drivers_list'          => $driversList,
        ];

        // ==========================================
        // 5. EVENTS & BANQUET MODULE METRICS
        // ==========================================
        $eventsQuery = Event::query();
        $applyDate($eventsQuery, 'start_date');

        $totalEventsCount = (clone $eventsQuery)->count();
        $upcomingEventsCount = (clone $eventsQuery)->where('start_date', '>=', Carbon::now()->startOfDay())->count();
        $inProgressEventsCount = (clone $eventsQuery)
            ->where('start_date', '<=', Carbon::now()->endOfDay())
            ->where('end_date', '>=', Carbon::now()->startOfDay())
            ->count();
        $concludedEventsCount = (clone $eventsQuery)->where('end_date', '<', Carbon::now()->startOfDay())->count();

        $totalEventsCost = (clone $eventsQuery)->sum('total_cost');
        $averageEventCost = $totalEventsCount > 0 ? ($totalEventsCost / $totalEventsCount) : 0;

        $assignedEventsCount = (clone $eventsQuery)->whereNotNull('driver_id')->count();
        $unassignedEventsCount = (clone $eventsQuery)->whereNull('driver_id')->count();
        $courierCoverageRate = $totalEventsCount > 0 ? round(($assignedEventsCount / $totalEventsCount) * 100, 1) : 0;

        $upcomingEventsList = (clone $eventsQuery)
            ->with(['driver', 'user', 'items.food'])
            ->orderBy('start_date', 'asc')
            ->take(8)
            ->get();

        $eventsStats = [
            'total_events'       => $totalEventsCount,
            'upcoming_events'    => $upcomingEventsCount,
            'in_progress_events' => $inProgressEventsCount,
            'concluded_events'   => $concludedEventsCount,
            'total_cost'         => $totalEventsCost,
            'avg_cost'           => $averageEventCost,
            'assigned_count'     => $assignedEventsCount,
            'unassigned_count'   => $unassignedEventsCount,
            'courier_coverage'   => $courierCoverageRate,
            'upcoming_list'      => $upcomingEventsList,
        ];

        // ==========================================
        // 6. MEAL-WISE SALES & MONTHLY POPULARITY
        // ==========================================
        $allOrderItemsInPeriod = OrderItem::with(['food', 'order'])
            ->whereHas('order', function ($q) use ($applyDate) {
                $applyDate($q, 'created_at');
                $q->whereNotIn('status', ['cancelled']);
            })
            ->get();

        // 1. Group by Canonical Meal Type (Breakfast, Lunch, Dinner)
        $mealCanonicalBuckets = [
            'Breakfast' => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0],
            'Lunch'     => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0],
            'Dinner'    => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0],
        ];

        // 2. Group by 4 Luxury Category Menu Names
        $categoryBuckets = [
            'Sri Lankan Style Lunch Menu'     => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0, 'icon' => '☀️'],
            'Sri Lankan Style Dinner Menu'    => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0, 'icon' => '🌙'],
            'Sri Lankan Style Breakfast Menu' => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0, 'icon' => '🍳'],
            'English breakfast Menu'          => ['count' => 0, 'quantity' => 0, 'revenue' => 0.0, 'icon' => '🥪'],
        ];

        // 3. Dish performance tracker
        $dishTracker = [];

        foreach ($allOrderItemsInPeriod as $item) {
            $food = $item->food;
            $qty = (int)$item->quantity;
            $rev = (float)($item->price * $qty);

            $canonical = \App\Http\Controllers\OrderController::normalizeMealType($item->meal_type, $food?->type);
            if (isset($mealCanonicalBuckets[$canonical])) {
                $mealCanonicalBuckets[$canonical]['count']++;
                $mealCanonicalBuckets[$canonical]['quantity'] += $qty;
                $mealCanonicalBuckets[$canonical]['revenue'] += $rev;
            }

            $catName = $food?->type;
            if (!$catName || !isset($categoryBuckets[$catName])) {
                if ($canonical === 'Breakfast') {
                    $catName = 'Sri Lankan Style Breakfast Menu';
                } elseif ($canonical === 'Dinner') {
                    $catName = 'Sri Lankan Style Dinner Menu';
                } else {
                    $catName = 'Sri Lankan Style Lunch Menu';
                }
            }

            if (isset($categoryBuckets[$catName])) {
                $categoryBuckets[$catName]['count']++;
                $categoryBuckets[$catName]['quantity'] += $qty;
                $categoryBuckets[$catName]['revenue'] += $rev;
            }

            $dishId = $item->food_id ?: ($food?->name ?? 'Custom Dish');
            if (!isset($dishTracker[$dishId])) {
                $dishTracker[$dishId] = [
                    'id' => $dishId,
                    'name' => $food?->name ?? 'Special Dish',
                    'category' => $catName,
                    'canonical' => $canonical,
                    'image_url' => $food?->image_url ?? '',
                    'quantity' => 0,
                    'revenue' => 0.0,
                ];
            }
            $dishTracker[$dishId]['quantity'] += $qty;
            $dishTracker[$dishId]['revenue'] += $rev;
        }

        uasort($dishTracker, fn($a, $b) => $b['quantity'] <=> $a['quantity']);
        $topDishes = array_slice(array_values($dishTracker), 0, 10);

        // Calculate totals and percentages
        $totalMealQuantity = array_sum(array_column($categoryBuckets, 'quantity'));
        $totalMealRevenue = array_sum(array_column($categoryBuckets, 'revenue'));

        $categoryRanking = [];
        foreach ($categoryBuckets as $catName => $data) {
            $share = $totalMealQuantity > 0 ? round(($data['quantity'] / $totalMealQuantity) * 100, 1) : 0;
            $categoryRanking[] = [
                'name' => $catName,
                'icon' => $data['icon'],
                'quantity' => $data['quantity'],
                'revenue' => $data['revenue'],
                'share' => $share,
                'avg_price' => $data['quantity'] > 0 ? round($data['revenue'] / $data['quantity'], 2) : 0,
            ];
        }
        usort($categoryRanking, fn($a, $b) => $b['quantity'] <=> $a['quantity']);

        $highestSellingMealType = $categoryRanking[0] ?? [
            'name' => 'Sri Lankan Style Lunch Menu',
            'icon' => '☀️',
            'quantity' => 0,
            'revenue' => 0.0,
            'share' => 0,
        ];

        // 4. Monthly Historical Data (Last 6 Months Trend)
        $monthlyTrend = [];
        for ($i = 0; $i < 6; $i++) {
            $mStart = Carbon::now()->subMonths($i)->startOfMonth();
            $mEnd = Carbon::now()->subMonths($i)->endOfMonth();
            $mLabel = $mStart->format('F Y');
            $mShort = $mStart->format('M Y');

            $mItems = OrderItem::with('food')
                ->whereHas('order', function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('created_at', [$mStart, $mEnd])
                      ->whereNotIn('status', ['cancelled']);
                })
                ->get();

            $mBreakfastQty = 0;
            $mLunchQty = 0;
            $mDinnerQty = 0;
            $mEnglishQty = 0;
            $mTotalQty = 0;
            $mTotalRev = 0.0;

            foreach ($mItems as $mi) {
                $q = (int)$mi->quantity;
                $p = (float)($mi->price * $q);
                $mTotalQty += $q;
                $mTotalRev += $p;

                $type = $mi->food?->type ?: $mi->meal_type;
                $canon = \App\Http\Controllers\OrderController::normalizeMealType($mi->meal_type, $mi->food?->type);

                if (str_contains(strtolower($type), 'english')) {
                    $mEnglishQty += $q;
                } elseif ($canon === 'Breakfast') {
                    $mBreakfastQty += $q;
                } elseif ($canon === 'Dinner') {
                    $mDinnerQty += $q;
                } else {
                    $mLunchQty += $q;
                }
            }

            // Determine highest selling meal type for this month
            $mTypes = [
                'Sri Lankan Style Lunch Menu' => $mLunchQty,
                'Sri Lankan Style Dinner Menu' => $mDinnerQty,
                'Sri Lankan Style Breakfast Menu' => $mBreakfastQty,
                'English breakfast Menu' => $mEnglishQty,
            ];
            arsort($mTypes);
            $topMTypeName = array_key_first($mTypes);
            $topMQty = current($mTypes);

            $monthlyTrend[] = [
                'month' => $mLabel,
                'short_month' => $mShort,
                'total_quantity' => $mTotalQty,
                'total_revenue' => $mTotalRev,
                'top_meal_type' => $topMTypeName,
                'top_quantity' => $topMQty,
                'lunch_qty' => $mLunchQty,
                'dinner_qty' => $mDinnerQty,
                'breakfast_qty' => $mBreakfastQty,
                'english_qty' => $mEnglishQty,
            ];
        }

        $mealsStats = [
            'total_quantity' => $totalMealQuantity,
            'total_revenue' => $totalMealRevenue,
            'highest_selling' => $highestSellingMealType,
            'category_ranking' => $categoryRanking,
            'canonical_buckets' => $mealCanonicalBuckets,
            'monthly_trend' => $monthlyTrend,
            'top_dishes' => $topDishes,
        ];

        // ==========================================
        // 7. EXECUTIVE OVERVIEW CONSOLIDATION
        // ==========================================
        $executiveOverview = [
            'gross_platform_volume' => $ordersRevenue + $totalEventsCost,
            'total_orders'          => $totalOrdersCount,
            'fulfillment_rate'      => $orderFulfillmentRate,
            'verified_ledger_vol'   => $verifiedTxVolume,
            'verification_rate'     => $verificationRate,
            'total_patrons'         => $totalCustomersCount,
            'recurring_rate'        => $recurringRate,
            'active_drivers'        => $availableDrivers + $onDeliveryDrivers,
            'fleet_utilization'     => $fleetUtilization,
            'total_events'          => $totalEventsCount,
        ];

        return view('reports.index', compact(
            'activeTab',
            'range',
            'fromDate',
            'toDate',
            'startDate',
            'endDate',
            'executiveOverview',
            'ordersStats',
            'transactionsStats',
            'customersStats',
            'driversStats',
            'eventsStats',
            'mealsStats'
        ));
    }

    /**
     * Export active reports table as a clean CSV spreadsheet.
     */
    public function export(Request $request): StreamedResponse
    {
        if (auth()->user()?->role === 'Chef') {
            abort(403, 'Unauthorized.');
        }

        $tab = $request->input('tab', 'overview');
        $range = $request->input('range', 'all');
        $fromDate = $request->input('from');
        $toDate = $request->input('to');
        $filename = "menu_report_{$tab}_{$range}_" . now()->format('Ymd_His') . ".csv";

        // Date boundaries
        $startDate = null;
        $endDate = null;
        if ($range === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
        } elseif ($range === '7d') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($range === '30d') {
            $startDate = Carbon::now()->subDays(29)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        } elseif ($range === 'this_month') {
            $startDate = Carbon::now()->startOfMonth();
            $endDate = Carbon::now()->endOfMonth();
        } elseif ($range === 'custom') {
            if (!empty($fromDate)) {
                try { $startDate = Carbon::parse($fromDate)->startOfDay(); } catch (\Throwable $e) {}
            }
            if (!empty($toDate)) {
                try { $endDate = Carbon::parse($toDate)->endOfDay(); } catch (\Throwable $e) {}
            }
        }

        return response()->streamDownload(function () use ($tab, $range, $startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['The Menu Luxury Operations - Executive Intelligence Report']);
            fputcsv($handle, ['Export Timestamp', now()->toDateTimeString()]);
            fputcsv($handle, ['Module', ucfirst($tab)]);
            fputcsv($handle, ['Filter Range', $range]);
            if ($startDate && $endDate) {
                fputcsv($handle, ['Date Window', $startDate->toDateString() . ' to ' . $endDate->toDateString()]);
            }
            fputcsv($handle, []);

            if ($tab === 'orders') {
                fputcsv($handle, ['Order Number', 'Client Name', 'Phone', 'Total Price (Rs.)', 'Status', 'Payment Method', 'Date']);
                $q = Order::with('user')->latest();
                if ($startDate && $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                }
                foreach ($q->get() as $order) {
                    fputcsv($handle, [
                        $order->order_number ?: ('ORD-' . $order->id),
                        $order->customer_name ?: ($order->user->name ?? 'N/A'),
                        $order->customer_phone ?: ($order->user->phone ?? 'N/A'),
                        number_format($order->total_price ?: 0, 2, '.', ''),
                        ucfirst($order->status),
                        $order->payment_method ?: 'Bank Transfer',
                        $order->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            } elseif ($tab === 'transactions' || $tab === 'banking') {
                fputcsv($handle, ['Order Ref', 'Client Name', 'Client Phone', 'Bank / Payment Method', 'Reference Code', 'Amount (Rs.)', 'Verification Status', 'Bank Slip Attached', 'Date']);
                $txQuery = Order::where(function ($sub) {
                    $sub->where('payment_method', 'like', '%bank%')
                        ->orWhere('payment_method', 'like', '%online%')
                        ->orWhere('payment_method', 'like', '%transfer%')
                        ->orWhereNotNull('bank_receipt_url')
                        ->orWhereNull('payment_method');
                })->with('user')->latest();

                if ($startDate && $endDate) {
                    $txQuery->whereBetween('created_at', [$startDate, $endDate]);
                }

                foreach ($txQuery->get() as $tx) {
                    fputcsv($handle, [
                        $tx->order_number ?: ('ORD-' . $tx->id),
                        $tx->customer_name ?: ($tx->user->name ?? 'N/A'),
                        $tx->customer_phone ?: ($tx->user->phone ?? 'N/A'),
                        $tx->payment_method ?: 'Bank Transfer',
                        $tx->payment_reference ?: 'N/A',
                        number_format($tx->total_price ?: 0, 2, '.', ''),
                        ucfirst($tx->payment_status ?: 'Pending'),
                        $tx->bank_receipt_url ? 'Yes' : 'No',
                        $tx->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            } elseif ($tab === 'customers') {
                fputcsv($handle, ['Client ID', 'Client Name', 'Email', 'Phone', 'Primary Address', 'Dinner Address', 'Account Role', 'Total Orders', 'Total Lifetime Spend (Rs.)', 'Registered Since']);
                $customerBase = User::query();
                $hasCustomerRole = User::where('role', 'Customer')->exists();
                if ($hasCustomerRole) {
                    $customerBase->where('role', 'Customer');
                } else {
                    $adminRoles = ['Super Admin', 'System Admin', 'Chef', 'HR'];
                    $customerBase->where(function ($q) use ($adminRoles) {
                        $q->whereNotIn('role', $adminRoles)->orWhereNull('role');
                    });
                }

                $customers = $customerBase
                    ->withCount('orders')
                    ->withSum('orders as total_spent', 'total_price')
                    ->latest()
                    ->get();

                foreach ($customers as $c) {
                    fputcsv($handle, [
                        '#CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT),
                        $c->name,
                        $c->email,
                        $c->phone ?: 'N/A',
                        $c->address ?: 'N/A',
                        $c->dinner_address ?: 'N/A',
                        $c->role ?: 'Customer',
                        $c->orders_count,
                        number_format($c->total_spent ?: 0, 2, '.', ''),
                        $c->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            } elseif ($tab === 'meals') {
                fputcsv($handle, ['--- MEAL CATEGORY SALES & PERFORMANCE ---']);
                fputcsv($handle, ['Meal Category', 'Units Sold', 'Total Revenue (Rs.)', 'Average Price (Rs.)']);

                $categories = [
                    'Sri Lankan Style Lunch Menu',
                    'Sri Lankan Style Dinner Menu',
                    'Sri Lankan Style Breakfast Menu',
                    'English breakfast Menu',
                ];

                $q = OrderItem::with('food')
                    ->whereHas('order', function ($o) use ($startDate, $endDate) {
                        if ($startDate && $endDate) {
                            $o->whereBetween('created_at', [$startDate, $endDate]);
                        }
                        $o->whereNotIn('status', ['cancelled']);
                    });

                $items = $q->get();
                $catData = [];
                foreach ($categories as $cat) {
                    $catData[$cat] = ['quantity' => 0, 'revenue' => 0.0];
                }

                foreach ($items as $item) {
                    $food = $item->food;
                    $qty = (int)$item->quantity;
                    $rev = (float)($item->price * $qty);

                    $catName = $food?->type;
                    if (!$catName || !isset($catData[$catName])) {
                        $canon = \App\Http\Controllers\OrderController::normalizeMealType($item->meal_type, $food?->type);
                        if ($canon === 'Breakfast') $catName = 'Sri Lankan Style Breakfast Menu';
                        elseif ($canon === 'Dinner') $catName = 'Sri Lankan Style Dinner Menu';
                        else $catName = 'Sri Lankan Style Lunch Menu';
                    }

                    if (isset($catData[$catName])) {
                        $catData[$catName]['quantity'] += $qty;
                        $catData[$catName]['revenue'] += $rev;
                    }
                }

                foreach ($catData as $cat => $d) {
                    $avg = $d['quantity'] > 0 ? round($d['revenue'] / $d['quantity'], 2) : 0;
                    fputcsv($handle, [
                        $cat,
                        $d['quantity'],
                        number_format($d['revenue'], 2, '.', ''),
                        number_format($avg, 2, '.', ''),
                    ]);
                }

                fputcsv($handle, []);
                fputcsv($handle, ['--- MONTHLY MEAL SERVICE TELEMETRY (LAST 6 MONTHS) ---']);
                fputcsv($handle, ['Month', 'Highest Selling Meal Type', 'Total Units Sold', 'Total Revenue (Rs.)', 'Lunch Units', 'Dinner Units', 'Breakfast Units', 'English Breakfast Units']);

                for ($i = 0; $i < 6; $i++) {
                    $mStart = Carbon::now()->subMonths($i)->startOfMonth();
                    $mEnd = Carbon::now()->subMonths($i)->endOfMonth();

                    $mItems = OrderItem::with('food')
                        ->whereHas('order', function ($o) use ($mStart, $mEnd) {
                            $o->whereBetween('created_at', [$mStart, $mEnd])->whereNotIn('status', ['cancelled']);
                        })->get();

                    $mLunch = 0; $mDinner = 0; $mBreakfast = 0; $mEnglish = 0;
                    $mTotQty = 0; $mTotRev = 0.0;

                    foreach ($mItems as $mi) {
                        $qty = (int)$mi->quantity;
                        $mTotQty += $qty;
                        $mTotRev += ($mi->price * $qty);
                        $type = $mi->food?->type ?: $mi->meal_type;
                        $canon = \App\Http\Controllers\OrderController::normalizeMealType($mi->meal_type, $mi->food?->type);

                        if (str_contains(strtolower($type), 'english')) $mEnglish += $qty;
                        elseif ($canon === 'Breakfast') $mBreakfast += $qty;
                        elseif ($canon === 'Dinner') $mDinner += $qty;
                        else $mLunch += $qty;
                    }

                    $ranks = [
                        'Sri Lankan Style Lunch Menu' => $mLunch,
                        'Sri Lankan Style Dinner Menu' => $mDinner,
                        'Sri Lankan Style Breakfast Menu' => $mBreakfast,
                        'English breakfast Menu' => $mEnglish,
                    ];
                    arsort($ranks);

                    fputcsv($handle, [
                        $mStart->format('F Y'),
                        array_key_first($ranks),
                        $mTotQty,
                        number_format($mTotRev, 2, '.', ''),
                        $mLunch,
                        $mDinner,
                        $mBreakfast,
                        $mEnglish,
                    ]);
                }
            } elseif ($tab === 'drivers') {
                fputcsv($handle, ['Driver Name', 'Email', 'Phone', 'Vehicle', 'Vehicle Number', 'Status', 'Max Capacity', 'Total Deliveries']);
                $drivers = Driver::all();
                foreach ($drivers as $d) {
                    fputcsv($handle, [
                        $d->name,
                        $d->email,
                        $d->phone ?: 'N/A',
                        $d->vehicle_type ?: 'Motorcycle',
                        $d->vehicle_number ?: 'N/A',
                        ucfirst(str_replace('_', ' ', $d->status)),
                        $d->max_orders,
                        $d->total_deliveries,
                    ]);
                }
            } elseif ($tab === 'events') {
                fputcsv($handle, ['Event Name', 'Client Name', 'Start Date', 'End Date', 'Total Cost (Rs.)', 'Courier Assigned']);
                $events = Event::with(['user', 'driver'])->latest('start_date')->take(500)->get();
                foreach ($events as $ev) {
                    fputcsv($handle, [
                        $ev->name,
                        $ev->user->name ?? 'Corporate Client',
                        $ev->start_date,
                        $ev->end_date,
                        number_format($ev->total_cost, 2, '.', ''),
                        $ev->driver->name ?? 'Unassigned',
                    ]);
                }
            } else {
                fputcsv($handle, ['Executive Overview Metrics']);
                fputcsv($handle, ['Total Gross Orders Revenue', Order::whereIn('status', ['completed', 'delivered', 'processing'])->sum('total_price')]);
                fputcsv($handle, ['Total Event Revenue', Event::sum('total_cost')]);
                fputcsv($handle, ['Total Registered Customers', User::count()]);
                fputcsv($handle, ['Active Fleet Couriers', Driver::where('status', '!=', 'offline')->count()]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}

