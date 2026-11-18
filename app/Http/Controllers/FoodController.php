<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class FoodController extends Controller
{
    /**
     * Display a listing of the resource (API and Web Admin).
     */
    public function index(Request $request)
    {
        $query = Food::with('ingredients');

        // Filter by item category / meal type
        if ($request->filled('type') && strtolower($request->type) !== 'all') {
            $typeInput = trim($request->type);
            $lower = strtolower($typeInput);
            $query->where(function ($q) use ($typeInput, $lower) {
                if (str_contains($lower, 'break')) {
                    $q->where('type', 'Breakfast')->orWhere('type', 'like', '%breakfast%');
                } elseif (str_contains($lower, 'lunch')) {
                    $q->where('type', 'Lunch')->orWhere('type', 'like', '%lunch%');
                } elseif (str_contains($lower, 'din')) {
                    $q->where('type', 'Dinner')->orWhere('type', 'like', '%dinner%');
                } elseif (str_contains($lower, 'bev') || str_contains($lower, 'drink')) {
                    $q->where('type', 'Beverage')->orWhere('type', 'like', '%beverage%');
                } elseif (str_contains($lower, 'rice') || str_contains($lower, 'carb')) {
                    $q->where('type', 'Rice & Carbs')->orWhere('type', 'like', '%rice%')->orWhere('type', 'like', '%carb%');
                } elseif (str_contains($lower, 'curr') || str_contains($lower, 'meat') || str_contains($lower, 'fish')) {
                    $q->where('type', 'Curries & Meats')->orWhere('type', 'like', '%curr%')->orWhere('type', 'like', '%meat%')->orWhere('type', 'like', '%fish%');
                } elseif (str_contains($lower, 'veg') || str_contains($lower, 'sambol')) {
                    $q->where('type', 'Veggies & Sambols')->orWhere('type', 'like', '%veg%')->orWhere('type', 'like', '%sambol%');
                } else {
                    $q->where('type', $typeInput)->orWhere('type', 'like', "%{$lower}%");
                }
            });
        }

        // Filter by channel (pos, app, all)
        if ($request->filled('channel') && strtolower($request->channel) !== 'all') {
            $ch = strtolower(trim($request->channel));
            $query->where(function ($q) use ($ch) {
                $q->where('channel', $ch)->orWhere('channel', 'all');
            });
        }

        // Filter by available day (Mon, Tue, Wed, Thu, Fri, Sat, Sun)
        if ($request->filled('day')) {
            $day = ucfirst(substr(trim($request->day), 0, 3));
            $query->where(function ($q) use ($day) {
                $q->whereJsonContains('available_days', $day)
                  ->orWhereNull('available_days');
            });
        }

        // Search query (Name, Description, Type, Ingredients + Typo & Keyword Tolerance)
        if ($request->filled('search')) {
            $rawSearch = trim($request->search);
            $searchKeywords = collect(preg_split('/\s+/', $rawSearch))->filter()->values();

            $query->where(function ($q) use ($rawSearch, $searchKeywords) {
                // Direct match
                $q->where('name', 'like', "%{$rawSearch}%")
                  ->orWhere('description', 'like', "%{$rawSearch}%")
                  ->orWhere('type', 'like', "%{$rawSearch}%")
                  ->orWhere('brand', 'like', "%{$rawSearch}%")
                  ->orWhere('portion_size', 'like', "%{$rawSearch}%")
                  ->orWhereHas('ingredients', function ($iq) use ($rawSearch) {
                      $iq->where('name', 'like', "%{$rawSearch}%");
                  });

                // Common phonetic / typo variations (e.g. Avacado <-> Avocado, biriyani <-> biryani, kottu <-> kothu)
                $typoMap = [
                    'avacado' => 'avocado',
                    'avocado' => 'avacado',
                    'biriyani' => 'biryani',
                    'biryani' => 'biriyani',
                    'kottu' => 'kothu',
                    'kothu' => 'kottu',
                    'omlet' => 'omelette',
                    'omelet' => 'omelette',
                    'chilli' => 'chili',
                    'chili' => 'chilli',
                    'roti' => 'rotti',
                    'rotti' => 'roti',
                ];

                foreach ($typoMap as $misspelling => $correction) {
                    if (stripos($rawSearch, $misspelling) !== false) {
                        $variant = str_ireplace($misspelling, $correction, $rawSearch);
                        $q->orWhere('name', 'like', "%{$variant}%")
                          ->orWhere('description', 'like', "%{$variant}%")
                          ->orWhereHas('ingredients', function ($iq) use ($variant) {
                              $iq->where('name', 'like', "%{$variant}%");
                          });
                    }
                }

                // If multi-word search, match items matching keywords anywhere in food/ingredients
                if ($searchKeywords->count() > 1) {
                    $q->orWhere(function ($subQ) use ($searchKeywords) {
                        foreach ($searchKeywords as $kw) {
                            $subQ->where(function ($termQ) use ($kw) {
                                $termQ->where('name', 'like', "%{$kw}%")
                                      ->orWhere('description', 'like', "%{$kw}%")
                                      ->orWhereHas('ingredients', function ($iq) use ($kw) {
                                          $iq->where('name', 'like', "%{$kw}%");
                                      });
                            });
                        }
                    });
                }
            });
        }

        // Sort
        $sort = $request->query('sort', 'newest');
        if ($sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($sort === 'name') {
            $query->orderBy('name', 'asc');
        } else {
            $query->latest('id');
        }

        // Mobile API response
        if ($request->is('api/*') || $request->wantsJson()) {
            $cacheKey = 'api_foods_' . md5($request->fullUrl());

            $cached = Cache::remember($cacheKey, 600, function () use ($query) {
                $foods = $query->get();
                $lastUpdated = Food::max('updated_at') ?? '0';
                $etag = '"' . hash('sha256', $lastUpdated . '_' . $foods->count()) . '"';

                return [
                    'foods' => $foods,
                    'etag' => $etag,
                ];
            });

            $etag = $cached['etag'];
            $clientEtag = $request->header('If-None-Match');
            if ($clientEtag && trim($clientEtag) === $etag) {
                return response('', 304, [
                    'ETag' => $etag,
                    'Cache-Control' => 'public, max-age=120',
                ]);
            }

            return response()->json($cached['foods'])
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=120');
        }

        // Web Admin View
        $activeType = $request->query('type', 'all');
        $search = $request->query('search', '');
        
        $beverageCount = Food::where('type', 'Beverage')->count();

        /* BEVERAGE FEATURE (DISABLED FOR LATER USE)
        // Auto-seed default master beverages if none exist yet
        if ($beverageCount === 0) {
            $defaultBeverages = [
                [
                    'name' => 'Coca Cola',
                    'type' => 'Beverage',
                    'price' => 250.00,
                    'description' => 'Classic chilled Coca-Cola sparkling soda (330ml). Default complimentary lunch beverage for monthly meal plan subscribers.',
                    'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => 'Ready to Serve',
                    'calories' => 140,
                    'difficulty' => 'Chilled',
                    'servings' => '330 ml can',
                    'rating' => 4.9,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                [
                    'name' => 'Sprite',
                    'type' => 'Beverage',
                    'price' => 250.00,
                    'description' => 'Crisp, refreshing lemon-lime sparkling soft drink (330ml). Complimentary lunch beverage option.',
                    'image_url' => 'https://images.unsplash.com/photo-1625772299848-391b6a87d7b3?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => 'Ready to Serve',
                    'calories' => 140,
                    'difficulty' => 'Chilled',
                    'servings' => '330 ml can',
                    'rating' => 4.8,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                [
                    'name' => 'Fanta Orange',
                    'type' => 'Beverage',
                    'price' => 250.00,
                    'description' => 'Bright, bubbly and fruity orange soda with natural fruit flavours (330ml).',
                    'image_url' => 'https://images.unsplash.com/photo-1581009146145-b5ef050c2e1e?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => 'Ready to Serve',
                    'calories' => 160,
                    'difficulty' => 'Chilled',
                    'servings' => '330 ml can',
                    'rating' => 4.7,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                [
                    'name' => 'Diet Coke',
                    'type' => 'Beverage',
                    'price' => 280.00,
                    'description' => 'Zero sugar, calorie-free iconic sparkling soft drink with clean, crisp taste (330ml).',
                    'image_url' => 'https://images.unsplash.com/photo-1554866585-cd94860890b7?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => 'Ready to Serve',
                    'calories' => 1,
                    'difficulty' => 'Chilled',
                    'servings' => '330 ml can',
                    'rating' => 4.8,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                [
                    'name' => 'Fresh Orange Juice',
                    'type' => 'Beverage',
                    'price' => 450.00,
                    'description' => '100% freshly squeezed Valencia oranges with rich vitamin C, no added sugar or preservatives.',
                    'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => '5 min',
                    'calories' => 110,
                    'difficulty' => 'Freshly Pressed',
                    'servings' => '350 ml glass',
                    'rating' => 4.9,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
                [
                    'name' => 'Mineral Water',
                    'type' => 'Beverage',
                    'price' => 150.00,
                    'description' => 'Pure natural highland spring mineral water bottled at source in Sri Lankan central highlands (500ml).',
                    'image_url' => 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?auto=format&fit=crop&w=800&q=80',
                    'prep_time' => 'Ready to Serve',
                    'calories' => 0,
                    'difficulty' => 'Chilled / Ambient',
                    'servings' => '500 ml bottle',
                    'rating' => 5.0,
                    'available_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                ],
            ];

            foreach ($defaultBeverages as $b) {
                Food::create($b);
            }

            $beverageCount = count($defaultBeverages);
        }
        */

        $totalCount = Food::count();
        $breakfastCount = Food::where(function ($q) {
            $q->where('type', 'Breakfast')->orWhere('type', 'like', '%breakfast%');
        })->count();
        $lunchCount = Food::where(function ($q) {
            $q->where('type', 'Lunch')->orWhere('type', 'like', '%lunch%');
        })->count();
        $dinnerCount = Food::where(function ($q) {
            $q->where('type', 'Dinner')->orWhere('type', 'like', '%dinner%');
        })->count();
        $beverageCount = Food::where(function ($q) {
            $q->where('type', 'Beverage')->orWhere('type', 'like', '%beverage%');
        })->count();
        $englishBreakfastCount = Food::where('type', 'like', '%english%')->orWhere('name', 'like', '%english%')->count();
        $ingredientsCount = \App\Models\Ingredient::count();
        $avgPrice = round((float) (Food::avg('price') ?: 0), 2);

        $foods = $query->paginate(12)->withQueryString();
        $beverages = Food::where('type', 'Beverage')->orWhere('type', 'like', '%beverage%')->latest('id')->paginate(12)->withQueryString();

        return view('foods.index', compact(
            'foods', 'totalCount', 'breakfastCount', 'lunchCount',
            'dinnerCount', 'englishBreakfastCount', 'ingredientsCount', 'beverageCount', 'avgPrice', 'activeType', 'search', 'sort', 'beverages'
        ));
    }

    /**
     * Download sample Excel or CSV template for buffet items & beverages.
     */
    public function downloadTemplate(Request $request)
    {
        $format = strtolower($request->query('format', 'xlsx'));
        $filename = 'the_menu_buffet_and_beverage_template.' . ($format === 'csv' ? 'csv' : 'xlsx');

        if ($format === 'csv') {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FoodItemsTemplateExport, $filename, \Maatwebsite\Excel\Excel::CSV);
        }

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\FoodItemsTemplateExport, $filename, \Maatwebsite\Excel\Excel::XLSX);
    }

    /**
     * Bulk import buffet items and beverages via Excel or CSV.
     */
    public function importItems(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        try {
            $importer = new \App\Imports\FoodItemsImport();
            \Maatwebsite\Excel\Facades\Excel::import($importer, $request->file('file'));

            $msg = "Import completed successfully! {$importer->createdCount} items added, {$importer->updatedCount} updated.";
            if ($importer->skippedCount > 0) {
                $msg .= " ({$importer->skippedCount} empty rows skipped)";
            }

            return redirect()->route('foods.index')->with('success', $msg);
        } catch (\Throwable $e) {
            return redirect()->route('foods.index')->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Dedicated entry point for the Beverages management module.
     */
    public function beverages(Request $request)
    {
        $request->merge(['type' => 'Beverage']);
        return $this->index($request);
    }

    /**
     * Store a newly created food item or beverage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'portion_size' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'volume_ml' => 'nullable|integer|min:0',
            'channel' => 'nullable|string|in:all,pos,app',
            'description' => 'nullable|string|max:1000',
            'prep_time' => 'nullable|string|max:50',
            'calories' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|string|max:50',
            'servings' => 'nullable|string|max:50',
            'rating' => 'nullable|numeric|between:0,5',
            'available_days' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_url' => 'nullable|url|max:1000',
        ]);

        // Normalize meal/item type to standard categories
        $lowerType = strtolower($validated['type']);
        if (str_contains($lowerType, 'break')) {
            $validated['type'] = 'Breakfast';
        } elseif (str_contains($lowerType, 'lunch')) {
            $validated['type'] = 'Lunch';
        } elseif (str_contains($lowerType, 'din')) {
            $validated['type'] = 'Dinner';
        } elseif (str_contains($lowerType, 'bev') || str_contains($lowerType, 'drink')) {
            $validated['type'] = 'Beverages';
        } elseif (str_contains($lowerType, 'rice') || str_contains($lowerType, 'carb') || str_contains($lowerType, 'hopper')) {
            $validated['type'] = 'Rice & Carbs';
        } elseif (str_contains($lowerType, 'curry') || str_contains($lowerType, 'meat')) {
            $validated['type'] = 'Curries & Meats';
        } elseif (str_contains($lowerType, 'sambol') || str_contains($lowerType, 'veggie')) {
            $validated['type'] = 'Veggies & Sambols';
        }

        $validated['channel'] = $validated['channel'] ?? 'all';

        // Handle local image file upload (saves to storage/app/public/foods and stores path in DB)
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foods', 'public');
            $validated['image_url'] = 'storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $validated['image_url'] = $request->input('image_url');
        } else {
            // Default placeholder based on type
            $validated['image_url'] = ($validated['type'] === 'Beverage')
                ? 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80'
                : 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80';
        }

        // Default available days to all 7 days if none selected
        if (empty($validated['available_days'])) {
            $validated['available_days'] = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        }

        // Set defaults if not provided
        if (!isset($validated['rating'])) {
            $validated['rating'] = 4.8;
        }

        if ($validated['type'] === 'Beverage') {
            $validated['prep_time'] = $validated['prep_time'] ?: 'Ready to Serve';
            $validated['difficulty'] = $validated['difficulty'] ?: 'Chilled';
            $validated['servings'] = $validated['servings'] ?: '330 ml can';
            $validated['calories'] = $validated['calories'] !== null ? $validated['calories'] : 140;
        }

        // Remove the uploaded file object from array before create
        unset($validated['image']);

        $food = Food::create($validated);

        if ($request->wantsJson()) {
            $msg = ($food->type === 'Beverage') ? 'Beverage created successfully!' : 'Meal created successfully!';
            return response()->json(['success' => true, 'message' => $msg, 'data' => $food], 201);
        }

        $redirectType = ($food->type === 'Beverage') ? 'Beverage' : $food->type;
        $label = ($food->type === 'Beverage') ? 'Beverage' : 'Meal';
        return redirect()->route('foods.index', ['type' => $redirectType])->with('success', "{$label} '{$food->name}' added successfully to the catalog!");
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $food = Food::with(['ingredients', 'ratings.user'])->findOrFail($id);

        if (request()->wantsJson() || request()->is('api/*')) {
            return response()->json($food);
        }

        return response()->json($food);
    }

    /**
     * Update the specified food item or beverage.
     */
    public function update(Request $request, $id)
    {
        $food = Food::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'portion_size' => 'nullable|string|max:100',
            'brand' => 'nullable|string|max:100',
            'volume_ml' => 'nullable|integer|min:0',
            'channel' => 'nullable|string|in:all,pos,app',
            'description' => 'nullable|string|max:1000',
            'prep_time' => 'nullable|string|max:50',
            'calories' => 'nullable|integer|min:0',
            'difficulty' => 'nullable|string|max:50',
            'servings' => 'nullable|string|max:50',
            'rating' => 'nullable|numeric|between:0,5',
            'available_days' => 'nullable|array',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp,gif|max:5120',
            'image_url' => 'nullable|url|max:1000',
        ]);

        // Normalize meal/item type to standard categories
        $lowerType = strtolower($validated['type']);
        if (str_contains($lowerType, 'break')) {
            $validated['type'] = 'Breakfast';
        } elseif (str_contains($lowerType, 'lunch')) {
            $validated['type'] = 'Lunch';
        } elseif (str_contains($lowerType, 'din')) {
            $validated['type'] = 'Dinner';
        } elseif (str_contains($lowerType, 'bev') || str_contains($lowerType, 'drink')) {
            $validated['type'] = 'Beverages';
        } elseif (str_contains($lowerType, 'rice') || str_contains($lowerType, 'carb') || str_contains($lowerType, 'hopper')) {
            $validated['type'] = 'Rice & Carbs';
        } elseif (str_contains($lowerType, 'curry') || str_contains($lowerType, 'meat')) {
            $validated['type'] = 'Curries & Meats';
        } elseif (str_contains($lowerType, 'sambol') || str_contains($lowerType, 'veggie')) {
            $validated['type'] = 'Veggies & Sambols';
        }

        $validated['channel'] = $validated['channel'] ?? 'all';

        if ($request->hasFile('image')) {
            // Delete old file if saved locally in public storage
            $rawOld = $food->getRawOriginal('image_url');
            if ($rawOld && !str_starts_with($rawOld, 'http')) {
                $cleanOld = preg_replace('#^(storage/|/storage/)#', '', $rawOld);
                if (Storage::disk('public')->exists($cleanOld)) {
                    Storage::disk('public')->delete($cleanOld);
                }
            }

            $path = $request->file('image')->store('foods', 'public');
            $validated['image_url'] = 'storage/' . $path;
        } elseif ($request->filled('image_url')) {
            $validated['image_url'] = $request->input('image_url');
        }

        if (isset($validated['available_days']) && empty($validated['available_days'])) {
            $validated['available_days'] = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"];
        }

        unset($validated['image']);

        $food->update($validated);

        if ($request->wantsJson()) {
            $msg = ($food->type === 'Beverage') ? 'Beverage updated successfully!' : 'Meal updated successfully!';
            return response()->json(['success' => true, 'message' => $msg, 'data' => $food]);
        }

        $label = ($food->type === 'Beverage') ? 'Beverage' : 'Meal';
        return redirect()->back()->with('success', "{$label} '{$food->name}' updated successfully!");
    }

    /**
     * Remove the specified food item or beverage from storage.
     */
    public function destroy(Request $request, $id)
    {
        $food = Food::findOrFail($id);
        $name = $food->name;
        $isBeverage = ($food->type === 'Beverage');

        // Cascade delete relations
        $food->ingredients()->delete();
        $food->ratings()->delete();
        $food->offers()->delete();
        
        $food->delete();

        $label = $isBeverage ? 'Beverage' : 'Meal';

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => "{$label} '{$name}' deleted successfully!"]);
        }

        return redirect()->back()->with('success', "{$label} '{$name}' deleted successfully!");
    }
}
