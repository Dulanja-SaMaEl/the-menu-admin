<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public static function normalizeMealType(?string $mealType, ?string $foodType = null): string
    {
        $raw = strtolower(trim(($mealType ?: $foodType) ?? ''));
        if (str_contains($raw, 'break')) {
            return 'Breakfast';
        }
        if (str_contains($raw, 'din')) {
            return 'Dinner';
        }
        return 'Lunch';
    }

    /**
     * Smartly extract delivery area/direction from an address string.
     */
    public static function extractAreaFromAddress(?string $address): string
    {
        if (empty($address)) {
            return 'Unspecified';
        }

        $clean = trim($address);

        // Standard/popular delivery areas and route directions
        $knownAreas = [
            'Borella', 'Kotte', 'Rajagiriya', 'Nawala', 'Nugegoda', 'Battaramulla',
            'Malabe', 'Maharagama', 'Dehiwala', 'Mount Lavinia', 'Moratuwa',
            'Kollupitiya', 'Bambalapitiya', 'Wellawatte', 'Havelock Town', 'Kirulapone',
            'Pelawatte', 'Thalawathugoda', 'Kohuwala', 'Kottawa', 'Homagama',
            'Athurugiriya', 'Wattala', 'Ja-Ela', 'Negombo', 'Kandy', 'Galle',
            'Pannipitiya', 'Pita Kotte', 'Ethul Kotte', 'Mirihana', 'Thimbirigasyaya',
            'Cinnamon Gardens', 'Fort', 'Pettah', 'Slave Island', 'Maradana',
            'Kotahena', 'Mattakkuliya', 'Grandpass', 'Dematagoda', 'Sedawatte',
            'Kelaniya', 'Peliyagoda', 'Hendala', 'Kadawatha', 'Kiribathgoda',
            'Ragama', 'Angoda', 'Kotikawatta', 'Kolonnawa', 'Mulleriyawa',
            'Colombo 01', 'Colombo 02', 'Colombo 03', 'Colombo 04', 'Colombo 05',
            'Colombo 06', 'Colombo 07', 'Colombo 08', 'Colombo 09', 'Colombo 10',
            'Colombo 11', 'Colombo 12', 'Colombo 13', 'Colombo 14', 'Colombo 15',
            'Colombo 1', 'Colombo 2', 'Colombo 3', 'Colombo 4', 'Colombo 5',
            'Colombo 6', 'Colombo 7', 'Colombo 8', 'Colombo 9',
        ];

        foreach ($knownAreas as $known) {
            if (preg_match('/\b' . preg_quote($known, '/') . '\b/i', $clean)) {
                if (preg_match('/colombo\s*0?([1-9]|1[0-5])/i', $known, $m)) {
                    return 'Colombo ' . str_pad($m[1], 2, '0', STR_PAD_LEFT);
                }
                return $known;
            }
        }

        // If address contains commas, extract city component
        if (str_contains($clean, ',')) {
            $parts = array_map('trim', explode(',', $clean));
            $lastPart = end($parts);
            // Remove postal codes or country
            $lastPart = trim(preg_replace('/\b\d{4,6}\b/', '', $lastPart));
            if (strtolower($lastPart) === 'sri lanka' && count($parts) > 1) {
                array_pop($parts);
                $lastPart = trim(preg_replace('/\b\d{4,6}\b/', '', end($parts)));
            }
            if (!empty($lastPart) && strlen($lastPart) <= 25) {
                return ucwords(strtolower($lastPart));
            }
        }

        $words = explode(' ', $clean);
        if (count($words) <= 3 && strlen($clean) <= 25) {
            return ucwords(strtolower($clean));
        }

        return 'Central Route';
    }

    public function index(Request $request)
    {
        // API consumers (mobile app) receive JSON for the authenticated user/driver.
        if ($request->wantsJson() || $request->is('api/*')) {
            $user = $request->user();
            if ($user instanceof Driver) {
                $orders = \App\Models\Order::with(['items.food.ingredients', 'user'])
                                           ->where('driver_id', $user->id)
                                           ->orderBy('created_at', 'desc')
                                           ->get();
            } else {
                if ($user) {
                    \App\Services\SubscriptionRenewalService::checkUserRenewal($user->id);
                }
                $orders = \App\Models\Order::with('items.food.ingredients')
                                           ->where('user_id', $user?->id)
                                           ->orderBy('created_at', 'desc')
                                           ->get();
            }

            return response()->json($orders);
        }

        $search = $request->input('search');
        $statusFilter = $request->input('status', 'all');
        $scheduleFilter = $request->input('schedule', 'all');
        $viewMode = $request->input('view', 'full'); // 'full', 'meal_wise', or 'history'
        $mealFilter = $request->input('meal', 'all'); // 'all', 'breakfast', 'lunch', 'dinner'
        $areaFilter = $request->input('area', 'all'); // 'all' or specific area
        $driverStatusFilter = $request->input('driver_status', 'all'); // 'all', 'assigned', 'not_assigned'

        $todayStr = now()->toDateString();
        $tomorrowStr = now()->addDay()->toDateString();

        // Active Orders Query: STRICTLY EXCLUDES completed, delivered, and cancelled orders
        $query = \App\Models\Order::with(['user', 'driver', 'items.food'])
            ->whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->latest();

        // Search by ID, Customer Name, Email, Phone, Address, Dish, or Driver
        if ($search) {
            $cleanSearch = ltrim(trim($search), '#');
            $cleanSearch = preg_replace('/^ORD-0*/i', '', $cleanSearch);

            $query->where(function ($q) use ($search, $cleanSearch) {
                if (is_numeric($cleanSearch)) {
                    $q->orWhere('id', (int)$cleanSearch);
                }
                $q->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('dinner_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.food', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('driver', function ($q4) use ($search) {
                      $q4->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Active Status Filter (pending, processing, assigned)
        if ($statusFilter && $statusFilter !== 'all' && !in_array($statusFilter, ['completed', 'delivered', 'cancelled'])) {
            $query->where('status', $statusFilter);
        }

        // Schedule Filter
        if ($scheduleFilter === 'today') {
            $query->whereHas('items', function ($q) use ($todayStr) {
                $q->whereDate('scheduled_date', $todayStr);
            });
        } elseif ($scheduleFilter === 'tomorrow') {
            $query->whereHas('items', function ($q) use ($tomorrowStr) {
                $q->whereDate('scheduled_date', $tomorrowStr);
            });
        } elseif ($scheduleFilter === 'upcoming') {
            $query->whereHas('items', function ($q) use ($todayStr) {
                $q->whereDate('scheduled_date', '>=', $todayStr);
            });
        } elseif ($scheduleFilter === 'immediate') {
            $query->whereDoesntHave('items', function ($q) use ($todayStr) {
                $q->whereDate('scheduled_date', '>', $todayStr);
            });
        }

        $orders = $query->paginate(12)->withQueryString();

        // Counts by status
        $counts = \App\Models\Order::selectRaw('status, count(*) as total')
                                   ->groupBy('status')
                                   ->pluck('total', 'status');

        // Upcoming Alerts: Orders scheduled for Today & Tomorrow that require kitchen preparation/dispatch
        $todayUpcomingOrders = \App\Models\Order::with(['user', 'driver', 'items.food'])
            ->whereIn('status', ['pending', 'processing', 'assigned'])
            ->whereHas('items', function ($q) use ($todayStr) {
                $q->whereDate('scheduled_date', $todayStr);
            })
            ->latest()
            ->get();

        $tomorrowUpcomingOrders = \App\Models\Order::with(['user', 'driver', 'items.food'])
            ->whereIn('status', ['pending', 'processing', 'assigned'])
            ->whereHas('items', function ($q) use ($tomorrowStr) {
                $q->whereDate('scheduled_date', $tomorrowStr);
            })
            ->latest()
            ->get();

        $todayScheduledCount = \App\Models\Order::whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->whereHas('items', function ($q) use ($todayStr) {
                $q->whereDate('scheduled_date', $todayStr);
            })->count();

        $tomorrowScheduledCount = \App\Models\Order::whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->whereHas('items', function ($q) use ($tomorrowStr) {
                $q->whereDate('scheduled_date', $tomorrowStr);
            })->count();

        $upcomingScheduledCount = \App\Models\Order::whereNotIn('status', ['completed', 'delivered', 'cancelled'])
            ->whereHas('items', function ($q) use ($tomorrowStr) {
                $q->whereDate('scheduled_date', '>=', $tomorrowStr);
            })->count();

        // -------------------------------------------------------------
        // TOMORROW'S KITCHEN PREP AGGREGATION (Dedicated Chef Prep List)
        // Aggregates exact total quantities per dish and beverage for tomorrow
        // -------------------------------------------------------------
        $tomorrowDishAggregates = [];
        $tomorrowBeverageAggregates = [];
        $tomorrowMealCounts = ['breakfast' => 0, 'lunch' => 0, 'dinner' => 0, 'total' => 0];

        foreach ($tomorrowUpcomingOrders as $tOrder) {
            $isSub = (bool)$tOrder->is_subscription;
            $beverage = $tOrder->subscription_beverage ?: 'Coca Cola';

            foreach ($tOrder->items as $tItem) {
                if (!$tItem->scheduled_date || $tItem->scheduled_date->format('Y-m-d') !== $tomorrowStr) {
                    continue;
                }
                if ($tItem->is_paused) {
                    continue;
                }

                $foodId = $tItem->food_id ?: ('custom_' . md5($tItem->food->name ?? 'Dish'));
                $foodName = $tItem->food->name ?? 'Chef Special Dish';
                $category = $tItem->food->category ?? 'Main';
                $imageUrl = $tItem->food->image_url ?? null;
                $canonicalMeal = strtolower(self::normalizeMealType($tItem->meal_type, $tItem->food?->type));

                if (!isset($tomorrowDishAggregates[$foodId])) {
                    $tomorrowDishAggregates[$foodId] = [
                        'food_id' => $foodId,
                        'name' => $foodName,
                        'category' => $category,
                        'image_url' => $imageUrl,
                        'total_quantity' => 0,
                        'breakfast_qty' => 0,
                        'lunch_qty' => 0,
                        'dinner_qty' => 0,
                    ];
                }

                $tomorrowDishAggregates[$foodId]['total_quantity'] += (int)$tItem->quantity;
                if ($canonicalMeal === 'breakfast') {
                    $tomorrowDishAggregates[$foodId]['breakfast_qty'] += (int)$tItem->quantity;
                    $tomorrowMealCounts['breakfast'] += (int)$tItem->quantity;
                } elseif ($canonicalMeal === 'dinner') {
                    $tomorrowDishAggregates[$foodId]['dinner_qty'] += (int)$tItem->quantity;
                    $tomorrowMealCounts['dinner'] += (int)$tItem->quantity;
                } else {
                    $tomorrowDishAggregates[$foodId]['lunch_qty'] += (int)$tItem->quantity;
                    $tomorrowMealCounts['lunch'] += (int)$tItem->quantity;
                }
                $tomorrowMealCounts['total'] += (int)$tItem->quantity;

                // Monthly plan lunch inclusion: track complimentary beverage
                if ($isSub && $canonicalMeal === 'lunch') {
                    $bevName = $beverage;
                    if (!isset($tomorrowBeverageAggregates[$bevName])) {
                        $tomorrowBeverageAggregates[$bevName] = [
                            'name' => $bevName,
                            'total_quantity' => 0,
                        ];
                    }
                    $tomorrowBeverageAggregates[$bevName]['total_quantity'] += (int)$tItem->quantity;
                }
            }
        }

        uasort($tomorrowDishAggregates, fn($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);
        uasort($tomorrowBeverageAggregates, fn($a, $b) => $b['total_quantity'] <=> $a['total_quantity']);

        $tomorrowKitchenPrep = [
            'dishes' => array_values($tomorrowDishAggregates),
            'beverages' => array_values($tomorrowBeverageAggregates),
            'counts' => $tomorrowMealCounts,
        ];

        $drivers = \App\Models\Driver::withCount(['assignedOrders' => function ($q) {
            $q->whereIn('status', ['pending', 'processing', 'assigned']);
        }])->orderBy('name')->get();

        // -------------------------------------------------------------
        // MEAL-WISE VIEW PIPELINE: Segregating orders by Breakfast, Lunch, Dinner
        // Strictly excludes completed, delivered, and cancelled orders
        // -------------------------------------------------------------
        $mealItemsQuery = \App\Models\OrderItem::with(['order.user', 'order.driver', 'food'])
            ->whereHas('order', function ($q) use ($search, $statusFilter) {
                $q->whereNotIn('status', ['completed', 'delivered', 'cancelled']);
                if ($statusFilter && $statusFilter !== 'all' && !in_array($statusFilter, ['completed', 'delivered', 'cancelled'])) {
                    $q->where('status', $statusFilter);
                }
                if ($search) {
                    $cleanSearch = ltrim(trim($search), '#');
                    $cleanSearch = preg_replace('/^ORD-0*/i', '', $cleanSearch);

                    $q->where(function ($sub) use ($search, $cleanSearch) {
                        if (is_numeric($cleanSearch)) {
                            $sub->orWhere('id', (int)$cleanSearch);
                        }
                        $sub->orWhere('id', 'like', "%{$search}%")
                            ->orWhere('address', 'like', "%{$search}%")
                            ->orWhere('dinner_address', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($u) use ($search) {
                                $u->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%")
                                  ->orWhere('phone', 'like', "%{$search}%");
                            });
                    });
                }
            });

        if ($search) {
            $cleanSearch = ltrim(trim($search), '#');
            $cleanSearch = preg_replace('/^ORD-0*/i', '', $cleanSearch);

            $mealItemsQuery->where(function ($q) use ($search, $cleanSearch) {
                $q->whereHas('food', fn($f) => $f->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('order', function ($o) use ($search, $cleanSearch) {
                      if (is_numeric($cleanSearch)) {
                          $o->orWhere('id', (int)$cleanSearch);
                      }
                      $o->orWhere('id', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('dinner_address', 'like', "%{$search}%")
                        ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%{$search}%")
                                                         ->orWhere('email', 'like', "%{$search}%")
                                                         ->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('driver', fn($d) => $d->where('name', 'like', "%{$search}%"));
                  });
            });
        }

        if ($scheduleFilter === 'today') {
            $mealItemsQuery->whereDate('scheduled_date', $todayStr);
        } elseif ($scheduleFilter === 'tomorrow') {
            $mealItemsQuery->whereDate('scheduled_date', $tomorrowStr);
        } elseif ($scheduleFilter === 'upcoming') {
            $mealItemsQuery->whereDate('scheduled_date', '>=', $todayStr);
        } elseif ($scheduleFilter === 'immediate') {
            $mealItemsQuery->where(function ($q) use ($todayStr) {
                $q->whereNull('scheduled_date')
                  ->orWhereDate('scheduled_date', '<=', $todayStr);
            });
        }

        $allMealItems = $mealItemsQuery->orderBy('scheduled_date', 'asc')->get();

        $mealGroups = [];
        $mealCounts = [
            'total' => 0,
            'breakfast' => 0,
            'lunch' => 0,
            'dinner' => 0,
        ];

        foreach ($allMealItems as $item) {
            if (!$item->order) continue;
            $canonicalMeal = self::normalizeMealType($item->meal_type, $item->food?->type);
            $dateKey = $item->scheduled_date ? $item->scheduled_date->format('Y-m-d') : 'immediate';
            $groupKey = "{$item->order_id}_{$dateKey}_{$canonicalMeal}";

            if (!isset($mealGroups[$groupKey])) {
                $isToday = $item->scheduled_date && $item->scheduled_date->format('Y-m-d') === $todayStr;
                $isTomorrow = $item->scheduled_date && $item->scheduled_date->format('Y-m-d') === $tomorrowStr;

                $deliveryAddress = (strtolower($canonicalMeal) === 'dinner' && !empty($item->order->dinner_address))
                    ? $item->order->dinner_address
                    : ($item->order->address ?: ($item->order->delivery_address ?: 'Standard Delivery'));

                $area = self::extractAreaFromAddress($deliveryAddress);

                $mealGroups[$groupKey] = [
                    'key' => $groupKey,
                    'order' => $item->order,
                    'order_id' => $item->order->id,
                    'order_code' => '#ORD-' . str_pad($item->order->id, 4, '0', STR_PAD_LEFT),
                    'meal_type' => $canonicalMeal,
                    'scheduled_date' => $item->scheduled_date,
                    'date_key' => $dateKey,
                    'is_today' => $isToday,
                    'is_tomorrow' => $isTomorrow,
                    'customer_name' => $item->order->customer_name ?: ($item->order->user->name ?? 'Guest User'),
                    'customer_phone' => $item->order->customer_phone ?: ($item->order->user->phone ?? ''),
                    'delivery_address' => $deliveryAddress,
                    'area' => $area,
                    'has_special_dinner_address' => (strtolower($canonicalMeal) === 'dinner' && !empty($item->order->dinner_address)),
                    'driver' => $item->order->driver,
                    'driver_id' => $item->order->driver_id,
                    'driver_status' => $item->order->driver ? 'Assigned' : 'Not Assigned',
                    'status' => $item->order->status,
                    'payment_status' => $item->order->payment_status,
                    'is_subscription' => (bool)$item->order->is_subscription,
                    'subscription_beverage' => $item->order->subscription_beverage,
                    'has_beverage' => ((bool)$item->order->is_subscription && strtolower($canonicalMeal) === 'lunch'),
                    'beverage_name' => ((bool)$item->order->is_subscription && strtolower($canonicalMeal) === 'lunch') ? ($item->order->subscription_beverage ?: 'Coca Cola') : null,
                    'items' => collect(),
                    'total_quantity' => 0,
                    'total_price' => 0.0,
                ];

                $mealCounts['total']++;
                $lowerMeal = strtolower($canonicalMeal);
                if (isset($mealCounts[$lowerMeal])) {
                    $mealCounts[$lowerMeal]++;
                }
            }

            $mealGroups[$groupKey]['items']->push($item);
            $mealGroups[$groupKey]['total_quantity'] += $item->quantity;
            $mealGroups[$groupKey]['total_price'] += ($item->price * $item->quantity);
        }

        // Available areas dynamically extracted from the meal dispatches
        $availableAreas = collect($mealGroups)
            ->pluck('area')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $areaCounts = [];
        foreach ($mealGroups as $mg) {
            $a = $mg['area'];
            $areaCounts[$a] = ($areaCounts[$a] ?? 0) + 1;
        }

        // Apply filters to meal deliveries
        $filteredMealDeliveries = collect($mealGroups);

        // 1. Meal category filter (Breakfast, Lunch, Dinner)
        if ($mealFilter && $mealFilter !== 'all') {
            $filteredMealDeliveries = $filteredMealDeliveries->filter(function ($m) use ($mealFilter) {
                return strtolower($m['meal_type']) === strtolower($mealFilter);
            });
        }

        // 2. Delivery Area / Route Direction filter
        if ($areaFilter && $areaFilter !== 'all') {
            $filteredMealDeliveries = $filteredMealDeliveries->filter(function ($m) use ($areaFilter) {
                return strtolower($m['area']) === strtolower($areaFilter);
            });
        }

        // 3. Driver assignment status filter (Assigned vs Not Assigned)
        if ($driverStatusFilter && $driverStatusFilter !== 'all') {
            if ($driverStatusFilter === 'assigned') {
                $filteredMealDeliveries = $filteredMealDeliveries->filter(fn($m) => !empty($m['driver_id']));
            } elseif ($driverStatusFilter === 'not_assigned') {
                $filteredMealDeliveries = $filteredMealDeliveries->filter(fn($m) => empty($m['driver_id']));
            }
        }

        // Paginate meal deliveries
        $mealPage = (int) $request->input('meal_page', 1);
        $perMealPage = 15;
        $totalMealDeliveries = $filteredMealDeliveries->count();
        $mealPageItems = $filteredMealDeliveries->forPage($mealPage, $perMealPage)->values();

        $paginatedMealDeliveries = new \Illuminate\Pagination\LengthAwarePaginator(
            $mealPageItems,
            $totalMealDeliveries,
            $perMealPage,
            $mealPage,
            ['path' => $request->url(), 'pageName' => 'meal_page', 'query' => $request->query()]
        );

        // -------------------------------------------------------------
        // COMPLETED & HISTORY ORDERS PIPELINE: Finished Deliveries & Archive
        // -------------------------------------------------------------
        $historyStatusFilter = $request->input('history_status', 'all'); // 'all', 'delivered', 'completed', 'cancelled'

        $historyQuery = \App\Models\Order::with(['user', 'driver', 'items.food'])
            ->whereIn('status', ['completed', 'delivered', 'cancelled'])
            ->latest();

        if ($search) {
            $cleanSearch = ltrim(trim($search), '#');
            $cleanSearch = preg_replace('/^ORD-0*/i', '', $cleanSearch);

            $historyQuery->where(function ($q) use ($search, $cleanSearch) {
                if (is_numeric($cleanSearch)) {
                    $q->orWhere('id', (int)$cleanSearch);
                }
                $q->orWhere('id', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('dinner_address', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items.food', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('driver', function ($q4) use ($search) {
                      $q4->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($historyStatusFilter && $historyStatusFilter !== 'all' && in_array($historyStatusFilter, ['completed', 'delivered', 'cancelled'])) {
            $historyQuery->where('status', $historyStatusFilter);
        }

        $historyOrders = $historyQuery->paginate(12, ['*'], 'history_page')->withQueryString();
        $historyCount = \App\Models\Order::whereIn('status', ['completed', 'delivered', 'cancelled'])->count();
        $activeOrdersCount = \App\Models\Order::whereNotIn('status', ['completed', 'delivered', 'cancelled'])->count();
        $historyStats = [
            'total'     => $historyCount,
            'delivered' => \App\Models\Order::where('status', 'delivered')->count(),
            'completed' => \App\Models\Order::where('status', 'completed')->count(),
            'cancelled' => \App\Models\Order::where('status', 'cancelled')->count(),
            'revenue'   => \App\Models\Order::whereIn('status', ['completed', 'delivered'])->sum('total_price'),
        ];

        return view('orders', compact(
            'orders', 'activeOrdersCount', 'counts', 'search', 'statusFilter', 'scheduleFilter',
            'drivers', 'todayUpcomingOrders', 'tomorrowUpcomingOrders',
            'todayScheduledCount', 'tomorrowScheduledCount', 'upcomingScheduledCount',
            'tomorrowKitchenPrep',
            'viewMode', 'mealFilter', 'mealCounts', 'paginatedMealDeliveries',
            'mealGroups', 'availableAreas', 'areaCounts', 'areaFilter', 'driverStatusFilter',
            'historyOrders', 'historyCount', 'historyStats', 'historyStatusFilter'
        ));
    }

    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\OrdersExport, 'orders.xlsx');
    }

    /**
     * Assign (or unassign) a driver to an order.
     * Only drivers that are "available" (or already assigned to this order) may be assigned;
     * assigning flips the driver's status to "on_delivery", while removing the assignment
     * returns the previous driver to "available".
     */
    public function assignDriver(Request $request, Order $order)
    {
        $driverId = $request->input('driver_id') ?: null;
        $request->merge(['driver_id' => $driverId]);

        $request->validate([
            'driver_id' => ['nullable', 'exists:drivers,id'],
        ]);

        $previousDriver = $order->driver;

        if ($driverId) {
            $driver = Driver::findOrFail($driverId);

            if ($driver->status === 'offline') {
                return redirect()->back()->with('error', 'The selected driver is currently offline.');
            }

            // Check driver capacity against configured max_orders setting
            $maxOrders = $driver->max_orders ?? 5;
            $currentAssigned = Order::where('driver_id', $driver->id)
                ->whereIn('status', ['pending', 'processing', 'assigned'])
                ->where('id', '!=', $order->id)
                ->count();

            if ($maxOrders > 0 && $currentAssigned >= $maxOrders) {
                return redirect()->back()->with('error', "Driver {$driver->name} has reached maximum delivery capacity ({$maxOrders} orders). Configure higher capacity in Driver Management if needed.");
            }

            // Free up the previously assigned driver if different and has no other pending deliveries.
            if ($previousDriver && $previousDriver->id !== $driver->id) {
                $prevRemaining = Order::where('driver_id', $previousDriver->id)
                    ->whereIn('status', ['pending', 'processing', 'assigned'])
                    ->where('id', '!=', $order->id)
                    ->count();
                if ($prevRemaining === 0) {
                    $previousDriver->update(['status' => 'available']);
                    \App\Events\DriverStatusUpdated::safeDispatch($previousDriver);
                }
            }

            $driver->update(['status' => 'on_delivery']);
            $order->update([
                'driver_id' => $driver->id,
                'status' => 'assigned'
            ]);

            \App\Events\DriverStatusUpdated::safeDispatch($driver);
            \App\Events\OrderStatusUpdated::safeDispatch($order->fresh(['user', 'driver', 'items.food']));

            // Notify user of driver assignment
            if ($order->user_id) {
                try {
                    \App\Models\UserNotification::create([
                        'user_id' => $order->user_id,
                        'title' => 'Driver Assigned 🛵',
                        'message' => "Driver {$driver->name} has been assigned to your order #{$order->id}!",
                        'type' => 'driver',
                        'data' => [
                            'order_id' => $order->id,
                            'driver_name' => $driver->name,
                            'driver_phone' => $driver->phone,
                        ],
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}
            }

            $redirectUrl = url()->previous(route('orders', ['view' => 'meal_wise']));
            return redirect($redirectUrl)->with('success', "Driver {$driver->name} assigned to meal dispatch successfully.");
        }

        // Unassign: return the previous driver to available if no other orders.
        if ($previousDriver) {
            $prevRemaining = Order::where('driver_id', $previousDriver->id)
                ->whereIn('status', ['pending', 'processing', 'assigned'])
                ->where('id', '!=', $order->id)
                ->count();
            if ($prevRemaining === 0) {
                $previousDriver->update(['status' => 'available']);
                \App\Events\DriverStatusUpdated::safeDispatch($previousDriver);
            }
        }
        $order->update(['driver_id' => null]);
        \App\Events\OrderStatusUpdated::safeDispatch($order->fresh(['user', 'driver', 'items.food']));

        $redirectUrl = url()->previous(route('orders', ['view' => 'meal_wise']));
        return redirect($redirectUrl)->with('success', 'Driver assignment removed from meal dispatch.');
    }

    /**
     * Bulk assign driver to multiple orders.
     */
    public function bulkAssign(Request $request)
    {
        \Illuminate\Support\Facades\Log::info('Bulk Assign Payload: ', $request->all());

        $driverId = $request->input('bulk_driver_id') ?: $request->input('driver_id');
        $request->merge([
            'bulk_driver_id' => $driverId,
            'driver_id' => $driverId,
        ]);

        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id',
            'bulk_driver_id' => 'required|exists:drivers,id'
        ]);

        $driver = Driver::findOrFail($driverId);

        if ($driver->status === 'offline') {
            return redirect()->back()->with('error', 'The selected driver is currently offline.');
        }

        $orderIds = $request->order_ids;

        // Check capacity against max_orders
        $maxOrders = $driver->max_orders ?? 5;
        $currentAssigned = Order::where('driver_id', $driver->id)
            ->whereIn('status', ['pending', 'processing', 'assigned'])
            ->whereNotIn('id', $orderIds)
            ->count();

        if ($maxOrders > 0 && ($currentAssigned + count($orderIds)) > $maxOrders) {
            return redirect()->back()->with('error', "Cannot assign " . count($orderIds) . " orders. Driver {$driver->name} can only handle a maximum of {$maxOrders} orders at once (already handling {$currentAssigned}).");
        }

        $orders = Order::whereIn('id', $orderIds)->get();

        foreach ($orders as $order) {
            $previousDriver = $order->driver;

            // Free up the previously assigned driver if it's a different one and no remaining orders.
            if ($previousDriver && $previousDriver->id !== $driver->id) {
                $prevRemaining = Order::where('driver_id', $previousDriver->id)
                    ->whereIn('status', ['pending', 'processing', 'assigned'])
                    ->whereNotIn('id', $orderIds)
                    ->count();
                if ($prevRemaining === 0) {
                    $previousDriver->update(['status' => 'available']);
                    \App\Events\DriverStatusUpdated::safeDispatch($previousDriver);
                }
            }

            $order->update([
                'driver_id' => $driver->id,
                'status' => 'assigned'
            ]);

            \App\Events\OrderStatusUpdated::safeDispatch($order->fresh(['user', 'driver', 'items.food']));

            // Notify user of driver assignment
            if ($order->user_id) {
                try {
                    \App\Models\UserNotification::create([
                        'user_id' => $order->user_id,
                        'title' => 'Driver Assigned 🛵',
                        'message' => "Driver {$driver->name} has been assigned to your order #{$order->id}!",
                        'type' => 'driver',
                        'data' => [
                            'order_id' => $order->id,
                            'driver_name' => $driver->name,
                            'driver_phone' => $driver->phone,
                        ],
                        'is_read' => false,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        $driver->update(['status' => 'on_delivery']);
        \App\Events\DriverStatusUpdated::safeDispatch($driver);

        $redirectUrl = url()->previous(route('orders', ['view' => 'meal_wise']));
        return redirect($redirectUrl)->with('success', "Driver {$driver->name} successfully assigned to " . $orders->count() . ' meal dispatches.');
    }
    /**
     * Update order delivery address.
     */
    public function updateAddress(Request $request, Order $order)
    {
        $request->validate([
            'address' => 'required|string|max:255',
        ]);

        $order->update([
            'address' => $request->address,
        ]);

        return redirect()->back()->with('success', 'Order delivery location updated successfully.');
    }

    /**
     * API for Mobile: Return user's order schedule grouped date-wise into breakfast, lunch, and dinner.
     */
    public function calendar(Request $request)
    {
        $user = $request->user();
        if ($user) {
            \App\Services\SubscriptionRenewalService::checkUserRenewal($user->id);
        }

        $orderItems = \App\Models\OrderItem::with(['food.ingredients', 'order'])
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->get();

        $grouped = [];

        foreach ($orderItems as $item) {
            $date = $item->scheduled_date 
                ? $item->scheduled_date->format('Y-m-d') 
                : ($item->order ? $item->order->created_at->format('Y-m-d') : now()->format('Y-m-d'));
            
            $mealType = strtolower($item->meal_type ?: ($item->food ? $item->food->type : 'lunch'));
            if (!in_array($mealType, ['breakfast', 'lunch', 'dinner'])) {
                $mealType = 'lunch';
            }

            if (!isset($grouped[$date])) {
                $grouped[$date] = [
                    'date' => $date,
                    'total_spent' => 0.0,
                    'total_items' => 0,
                    'breakfast' => [],
                    'lunch' => [],
                    'dinner' => [],
                ];
            }

            $subtotal = round((float)($item->price * $item->quantity), 2);

            $itemData = [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'order_status' => $item->order ? $item->order->status : 'pending',
                'food_id' => $item->food_id,
                'food_name' => $item->food ? $item->food->name : 'Meal Item',
                'food_image' => $item->food ? $item->food->image_url : '',
                'quantity' => $item->quantity,
                'price' => (float)$item->price,
                'subtotal' => $subtotal,
                'meal_type' => $mealType,
                'scheduled_date' => $date,
            ];

            $grouped[$date][$mealType][] = $itemData;
            $grouped[$date]['total_spent'] = round($grouped[$date]['total_spent'] + $subtotal, 2);
            $grouped[$date]['total_items'] += $item->quantity;
        }

        // Sort ascending by date (oldest/completed on the left, newest on the right)
        ksort($grouped);

        return response()->json([
            'dates' => array_values($grouped),
            'summary' => [
                'total_scheduled_days' => count($grouped),
                'total_meals' => array_sum(array_column($grouped, 'total_items')),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // If items not explicitly provided, convert from active cart
        if (!$request->has('items') || empty($request->input('items'))) {
            $cart = \App\Models\Cart::with('items.food')->where('user_id', $user->id)->first();
            if ($cart && $cart->items->isNotEmpty()) {
                $subtotal = 0;
                $items = [];
                foreach ($cart->items as $cartItem) {
                    $foodPrice = $cartItem->food ? (float)$cartItem->food->price : 0.0;
                    $items[] = [
                        'food_id' => $cartItem->food_id,
                        'quantity' => $cartItem->quantity,
                        'price' => $foodPrice,
                        'meal_type' => $cartItem->meal_type,
                        'scheduled_date' => $cartItem->scheduled_date ? $cartItem->scheduled_date->format('Y-m-d') : null,
                    ];
                    $subtotal += ($foodPrice * $cartItem->quantity);
                }
                $deliveryFee = (float)($request->input('delivery_fee', $cart->delivery_fee ?? 0.0));
                $totalPrice = $subtotal + $deliveryFee;

                $request->merge([
                    'items' => $items,
                    'total_price' => $request->input('total_price', round($totalPrice, 2)),
                    'address' => $request->input('address', $request->input('delivery_address', $cart->delivery_address ?: ($user->address ?? ''))),
                ]);
            }
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.food_id' => 'required|exists:food,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric',
            'total_price' => 'required|numeric',
            'address' => 'required|string',
            'dinner_address' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'bank_receipt' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'bank_receipt_url' => 'nullable|string',
            'coupon_code' => 'nullable|string',
            'discount_amount' => 'nullable|numeric',
        ]);

        // Enforce 20 distinct days minimum for scheduled meal plans
        $scheduledDates = collect($request->items)
            ->pluck('scheduled_date')
            ->filter()
            ->unique();
        if ($scheduledDates->count() > 0 && $scheduledDates->count() < 20) {
            return response()->json([
                'message' => 'Minimum 20 days of meals required for monthly plan (currently ' . $scheduledDates->count() . ' days selected). Please select at least 20 days.',
            ], 422);
        }

        // Enforce 24-hour advance order cutoff (Breakfast: 7:00 AM, Lunch: 11:00 AM, Dinner: 6:00 PM)
        foreach ($request->items as $item) {
            if (!empty($item['scheduled_date'])) {
                $mealType = $item['meal_type'] ?? null;
                if (!$mealType && !empty($item['food_id'])) {
                    $food = \App\Models\Food::find($item['food_id']);
                    $mealType = $food?->type;
                }
                if (!\App\Services\MealCutoffService::isSlotEligible($item['scheduled_date'], $mealType)) {
                    $food = !empty($item['food_id']) ? \App\Models\Food::find($item['food_id']) : null;
                    $errMsg = \App\Services\MealCutoffService::getCutoffErrorMessage(
                        $item['scheduled_date'],
                        $mealType,
                        $food?->name
                    );
                    return response()->json([
                        'message' => $errMsg,
                        'expired_date' => $item['scheduled_date'],
                        'expired_meal_type' => $mealType,
                    ], 422);
                }
            }
        }

        // Handle Bank Receipt Upload
        $receiptUrl = $request->bank_receipt_url;
        if ($request->hasFile('bank_receipt')) {
            $path = $request->file('bank_receipt')->store('receipts', 'public');
            $receiptUrl = asset('storage/' . $path);
        }

        $paymentMethod = $request->payment_method ?: 'Bank Transfer';
        $isSubscription = in_array($paymentMethod, ['Visa / Mastercard', 'Card', 'Credit / Debit Card']);
        $paymentStatus = $isSubscription ? 'paid' : 'pending_verification';

        $reference = $request->payment_reference ?: \App\Models\Order::generateReference($user->id);

        $order = \App\Models\Order::create([
            'order_number' => 'ORD-' . strtoupper(uniqid()),
            'user_id' => $user->id,
            'customer_name' => $user->name,
            'customer_phone' => $user->phone ?? '',
            'address' => $request->address,
            'dinner_address' => $request->dinner_address,
            'total_amount' => $request->total_price,
            'total_price' => $request->total_price,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'payment_reference' => $reference,
            'bank_receipt_url' => $receiptUrl,
            'is_subscription' => $isSubscription,
            'coupon_code' => $request->coupon_code,
            'discount_amount' => $request->discount_amount ?? 0.00,
        ]);

        foreach ($request->items as $item) {
            $food = \App\Models\Food::find($item['food_id']);
            $defaultMealType = strtolower($food?->type ?? 'lunch');
            if (!in_array($defaultMealType, ['breakfast', 'lunch', 'dinner'])) {
                $defaultMealType = 'lunch';
            }

            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'food_id' => $item['food_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'meal_type' => $item['meal_type'] ?? $defaultMealType,
                'scheduled_date' => $item['scheduled_date'] ?? now()->toDateString(),
            ]);
        }

        \App\Models\Cart::where('user_id', $request->user()->id)->delete();

        // Create UserNotification for placed order
        try {
            $msg = $isSubscription
                ? "Monthly Subscription Order #{$order->id} confirmed! Card billed for Rs. " . number_format($order->total_price, 2) . "."
                : "Order #{$order->id} placed via Bank Transfer (Ref: {$reference}). Awaiting admin verification.";

            \App\Models\UserNotification::create([
                'user_id' => $user->id,
                'title' => 'Order Confirmed! 🎉',
                'message' => $msg,
                'type' => 'order',
                'data' => [
                    'order_id' => $order->id,
                    'total_price' => $order->total_price,
                    'reference' => $reference,
                    'payment_status' => $paymentStatus,
                ],
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {}

        $loadedOrder = $order->load(['items.food.ingredients', 'user', 'driver']);
        \App\Events\OrderStatusUpdated::safeDispatch($loadedOrder, 'created');

        return response()->json($loadedOrder, 201);
    }

    /**
     * Admin: Update the status of an order (web panel).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,assigned,on_delivery,completed,cancelled,delivered',
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return redirect()->back()->with('info', 'Order status is already ' . $newStatus . '.');
        }

        $order->update(['status' => $newStatus]);

        // Notify the user about the status change
        if ($order->user_id) {
            $messages = [
                'processing'  => "Your order #{$order->id} is now being prepared! 🍳",
                'completed'   => "Your order #{$order->id} has been completed! Thank you for ordering. ✅",
                'delivered'   => "Your order #{$order->id} has been delivered! Enjoy your meal! 🎉",
                'cancelled'   => "Your order #{$order->id} has been cancelled. Please contact us if you have questions.",
            ];

            try {
                \App\Models\UserNotification::create([
                    'user_id' => $order->user_id,
                    'title'   => 'Order Update 📦',
                    'message' => $messages[$newStatus] ?? "Your order #{$order->id} status changed to {$newStatus}.",
                    'type'    => 'order',
                    'data'    => ['order_id' => $order->id, 'status' => $newStatus],
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {}
        }

        if ($order->driver) {
            \App\Events\DriverStatusUpdated::safeDispatch($order->driver->fresh());
        }

        \App\Events\OrderStatusUpdated::safeDispatch($order->fresh(['user', 'driver', 'items.food']));

        $redirectUrl = url()->previous(route('orders'));
        return redirect($redirectUrl)->with('success', "Order #ORD-" . str_pad($order->id, 4, '0', STR_PAD_LEFT) . " status updated to {$newStatus}.");
    }

    /**
     * Admin: Verify bank transfer payment.
     */
    public function verifyPayment(Order $order)
    {
        $order->update(['payment_status' => 'verified']);

        if ($order->user_id) {
            try {
                \App\Models\UserNotification::create([
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
     * Admin: Reject bank transfer payment.
     */
    public function rejectPayment(Order $order)
    {
        $order->update(['payment_status' => 'rejected']);

        if ($order->user_id) {
            try {
                \App\Models\UserNotification::create([
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
     * Printable Web Invoice for Admin and Users.
     */
    public function invoice(Order $order)
    {
        $order->load(['items.food', 'user']);
        return view('orders.invoice', compact('order'));
    }

    /**
     * Prevent modifying beverage on existing orders (read-only restriction).
     */
    public function updateBeverage(Request $request, Order $order)
    {
        return redirect()->back()->with('error', "Assigned beverage cannot be modified once an order has been placed.");
    }

    /**
     * Mobile JSON Invoice Endpoint.
     */
    public function apiInvoice($id, Request $request)
    {
        $order = Order::with(['items.food', 'user'])->findOrFail($id);

        if ($request->user() && $order->user_id !== $request->user()->id && !($request->user() instanceof Driver)) {
            return response()->json(['message' => 'Unauthorized access to invoice'], 403);
        }

        return response()->json([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'invoice_number' => 'INV-' . str_pad($order->id, 5, '0', STR_PAD_LEFT),
            'date' => $order->created_at->format('Y-m-d H:i:s'),
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'address' => $order->address,
            'dinner_address' => $order->dinner_address,
            'payment_method' => $order->payment_method,
            'payment_status' => $order->payment_status,
            'payment_reference' => $order->payment_reference,
            'is_subscription' => (bool)$order->is_subscription,
            'is_verified' => in_array($order->payment_status, ['paid', 'verified']),
            'coupon_code' => $order->coupon_code,
            'discount_amount' => (float)$order->discount_amount,
            'subtotal' => (float)($order->total_amount + $order->discount_amount),
            'total_amount' => (float)$order->total_amount,
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->food?->name ?? 'Dish',
                    'quantity' => $item->quantity,
                    'price' => (float)$item->price,
                    'total' => (float)($item->price * $item->quantity),
                    'meal_type' => $item->meal_type,
                    'scheduled_date' => $item->scheduled_date,
                ];
            }),
        ]);
    }

    /**
     * Mobile API: Active subscription status, renewal countdown, and pattern info.
     */
    public function subscriptionStatus(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['has_active_subscription' => false], 401);
        }

        $status = \App\Services\SubscriptionRenewalService::getUserSubscriptionStatus($user->id);
        return response()->json($status);
    }

    /**
     * Delete/Cancel an order cleanly.
     */
    public function destroy(Order $order)
    {
        // Free up the driver if assigned
        if ($order->driver) {
            $order->driver->update(['status' => 'available']);
            \App\Events\DriverStatusUpdated::safeDispatch($order->driver);
        }

        $order->delete();
        
        return redirect()->route('orders')->with('success', 'Order has been successfully deleted.');
    }
}
