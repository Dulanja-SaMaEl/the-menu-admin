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

        // Exclude Beverage items while beverage module is temporarily disabled
        $query->where('type', '!=', 'Beverage');

        // Filter by meal type (Breakfast, Lunch, Dinner)
        if ($request->filled('type') && strtolower($request->type) !== 'all') {
            $query->where('type', $request->type);
        }

        // Filter by available day (Mon, Tue, Wed, Thu, Fri, Sat, Sun)
        if ($request->filled('day')) {
            $day = ucfirst(substr(trim($request->day), 0, 3));
            $query->where(function ($q) use ($day) {
                $q->whereJsonContains('available_days', $day)
                  ->orWhereNull('available_days');
            });
        }

        // Search query (Name, Description, Type, Ingredients)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhereHas('ingredients', function ($iq) use ($search) {
                      $iq->where('name', 'like', "%{$search}%");
                  });
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

        $totalCount = Food::where('type', '!=', 'Beverage')->count();
        $breakfastCount = Food::where('type', 'Breakfast')->count();
        $lunchCount = Food::where('type', 'Lunch')->count();
        $dinnerCount = Food::where('type', 'Dinner')->count();
        $avgPrice = round((float) Food::where('type', '!=', 'Beverage')->avg('price'), 2);

        $foods = $query->paginate(12)->withQueryString();
        $beverages = Food::where('type', 'Beverage')->latest('id')->paginate(12)->withQueryString();

        return view('foods.index', compact(
            'foods', 'totalCount', 'breakfastCount', 'lunchCount',
            'dinnerCount', 'beverageCount', 'avgPrice', 'activeType', 'search', 'sort', 'beverages'
        ));
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
            'type' => 'required|in:Breakfast,Lunch,Dinner,Beverage',
            'price' => 'required|numeric|min:0',
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

        // Handle uploaded image file
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foods', 'public');
            $validated['image_url'] = asset('storage/' . $path);
        } elseif (empty($validated['image_url'])) {
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
            'type' => 'required|in:Breakfast,Lunch,Dinner,Beverage',
            'price' => 'required|numeric|min:0',
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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('foods', 'public');
            $validated['image_url'] = asset('storage/' . $path);
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
