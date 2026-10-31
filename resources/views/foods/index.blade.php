<x-app-layout>
    <div x-data="{
        openCreateModal: false,
        openEditModal: false,
        openDeleteModal: false,
        viewMode: 'table',
        currentFood: {
            id: '',
            name: '',
            type: 'Breakfast',
            price: '',
            description: '',
            prep_time: '15 min',
            calories: '450',
            difficulty: 'Easy',
            servings: '1 person',
            rating: '4.8',
            image_url: '',
            available_days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
        },
        deleteId: null,
        deleteName: '',
        deleteType: 'meal',
        imageMode: 'url',
        previewImage: null,

        openAddFood(defaultType = 'Breakfast') {
            this.currentFood = {
                id: '',
                name: '',
                type: defaultType,
                price: '',
                description: '',
                prep_time: '15 min',
                calories: '450',
                difficulty: 'Easy',
                servings: '1 person',
                rating: '4.8',
                image_url: '',
                available_days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            };
            this.previewImage = null;
            this.imageMode = 'url';
            this.openCreateModal = true;
        },

        openAddBeverage() {
            this.currentFood = {
                id: '',
                name: '',
                type: 'Beverage',
                price: '250.00',
                description: '',
                prep_time: 'Ready to Serve',
                calories: '140',
                difficulty: 'Chilled',
                servings: '330 ml can',
                rating: '4.9',
                image_url: 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80',
                available_days: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            };
            this.previewImage = this.currentFood.image_url;
            this.imageMode = 'url';
            this.openCreateModal = true;
        },

        openEdit(food) {
            this.currentFood = {
                id: food.id,
                name: food.name,
                type: food.type,
                price: food.price,
                description: food.description || '',
                prep_time: food.prep_time || (food.type === 'Beverage' ? 'Ready to Serve' : '15 min'),
                calories: food.calories || (food.type === 'Beverage' ? '140' : '450'),
                difficulty: food.difficulty || (food.type === 'Beverage' ? 'Chilled' : 'Easy'),
                servings: food.servings || (food.type === 'Beverage' ? '330 ml can' : '1 person'),
                rating: food.rating || '4.8',
                image_url: food.image_url || '',
                available_days: Array.isArray(food.available_days) ? food.available_days : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            };
            this.previewImage = food.image_url;
            this.imageMode = 'url';
            this.openEditModal = true;
        },

        confirmDelete(id, name, type = 'meal') {
            this.deleteId = id;
            this.deleteName = name;
            this.deleteType = type;
            this.openDeleteModal = true;
        },

        handleImagePreview(event) {
            const file = event.target.files[0];
            if (file) {
                this.previewImage = URL.createObjectURL(file);
            }
        }
    }">
        <!-- Session Flash Alerts -->
        @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                 class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">✓</div>
                    <div>
                        <p class="text-sm font-bold">{{ session('success') }}</p>
                    </div>
                </div>
                <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 text-lg font-bold">&times;</button>
            </div>
        @endif

        @if(session('error'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" 
                 class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-red-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">!</div>
                    <div>
                        <p class="text-sm font-bold">{{ session('error') }}</p>
                    </div>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 text-lg font-bold">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div x-data="{ show: true }" x-show="show" 
                 class="mb-6 p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-xl bg-red-500 text-white flex items-center justify-center font-bold text-sm shadow-sm">!</div>
                    <div>
                        <p class="text-sm font-bold">Please check the form for errors:</p>
                        <ul class="text-xs list-disc list-inside mt-1 text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button @click="show = false" class="text-red-500 hover:text-red-700 text-lg font-bold">&times;</button>
            </div>
        @endif

        <!-- Header Section -->
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                    <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="text-[#0A2E2A] font-black">Food & Beverages</span>
                </nav>
                <div class="flex items-center space-x-3.5 flex-wrap gap-y-2">
                    <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Culinary & Beverage Catalog</h1>
                    <span class="px-3 py-1 bg-[#0A2E2A]/10 text-[#0A2E2A] text-xs font-black rounded-xl border border-[#0A2E2A]/20">
                        {{ $totalCount }} Total Items
                    </span>
                    {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                    <span class="px-3 py-1 bg-[#C9A050]/20 text-[#0A2E2A] text-xs font-black rounded-xl border border-[#C9A050]/40">
                        🍹 {{ $beverageCount }} Master Beverages
                    </span>
                    --}}
                </div>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Curate gourmet daily menus, master beverages for monthly lunch plans, and manage pricing in LKR</p>
            </div>

            <!-- Primary Actions -->
            <div class="flex items-center flex-wrap gap-2.5">
                {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                <!-- Add Beverage Button -->
                <button type="button" @click="openAddBeverage()" 
                        class="px-5 py-3.5 bg-[#0A2E2A] hover:bg-[#0d3a35] text-[#C9A050] border border-[#C9A050]/40 font-black text-xs sm:text-sm rounded-2xl shadow-md transition-all duration-300 flex items-center space-x-2 active:scale-95 cursor-pointer">
                    <span class="text-base">🍹</span>
                    <span>Add Beverage</span>
                </button>
                --}}

                <!-- Add Dish Button -->
                <button type="button" @click="openAddFood('Breakfast')" 
                        class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black text-xs sm:text-sm rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 flex items-center space-x-2.5 active:scale-95 cursor-pointer">
                    <svg class="w-4 h-4 sm:w-5 sm:h-5 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Add New Dish</span>
                </button>
            </div>
        </div>

        <!-- Metric KPI Cards (Balanced 4-Column Grid) -->
        <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-4 mb-8">
            <div class="bg-white rounded-[2.5rem] p-5 sm:p-6 border border-gray-200/80 shadow-xs hover:border-[#C9A050]/40 transition-all duration-300 transform-gpu">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <span class="text-xs font-bold text-amber-700">Breakfast</span>
                    <span class="w-9 h-9 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-sm font-bold shadow-xs border border-amber-100">🍳</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $breakfastCount }}</p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">Morning menus</p>
            </div>

            <div class="bg-white rounded-[2.5rem] p-5 sm:p-6 border border-gray-200/80 shadow-xs hover:border-[#C9A050]/40 transition-all duration-300 transform-gpu">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <span class="text-xs font-bold text-orange-700">Lunch</span>
                    <span class="w-9 h-9 rounded-2xl bg-orange-50 text-orange-600 flex items-center justify-center text-sm font-bold shadow-xs border border-orange-100">☀️</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $lunchCount }}</p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">Mid-day specials</p>
            </div>

            <div class="bg-white rounded-[2.5rem] p-5 sm:p-6 border border-gray-200/80 shadow-xs hover:border-[#C9A050]/40 transition-all duration-300 transform-gpu">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <span class="text-xs font-bold text-indigo-700">Dinner</span>
                    <span class="w-9 h-9 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-sm font-bold shadow-xs border border-indigo-100">🌙</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $dinnerCount }}</p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">Evening courses</p>
            </div>

            {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
            <div class="bg-white rounded-[2.5rem] p-5 sm:p-6 border border-[#C9A050]/40 shadow-xs hover:border-[#C9A050] transition-all duration-300 relative overflow-hidden transform-gpu">
                <div class="absolute -right-2 -bottom-2 text-4xl opacity-10 pointer-events-none select-none">🍹</div>
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <span class="text-xs font-black text-[#0A2E2A]">Beverages</span>
                    <span class="w-9 h-9 rounded-2xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-sm font-bold shadow-xs border border-[#C9A050]/30">🍹</span>
                </div>
                <p class="beverage-kpi-count text-2xl sm:text-3xl font-black text-[#0A2E2A] tracking-tight">{{ $beverageCount }}</p>
                <p class="text-[11px] text-gray-500 font-medium mt-1">Master drink list</p>
            </div>
            --}}

            <div class="bg-white rounded-[2.5rem] p-5 sm:p-6 border border-gray-200/80 shadow-xs hover:border-[#C9A050]/40 transition-all duration-300 transform-gpu">
                <div class="flex items-center justify-between mb-2 sm:mb-3">
                    <span class="text-xs font-bold text-[#C9A050]">Avg. Price</span>
                    <span class="w-9 h-9 rounded-2xl bg-[#C9A050]/15 text-[#0A2E2A] flex items-center justify-center text-xs font-black shadow-xs border border-[#C9A050]/30">Rs</span>
                </div>
                <p class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Rs. {{ number_format($avgPrice, 0) }}</p>
                <p class="text-[11px] text-gray-400 font-medium mt-1">Across all items</p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white rounded-[2.5rem] p-4 sm:p-5 border border-gray-200/80 shadow-xs mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4 transform-gpu">
            <!-- Meal & Beverage Type Tabs -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-1 md:pb-0 custom-scrollbar">
                @php
                    $tabs = [
                        ['id' => 'all', 'label' => 'All Items', 'count' => $totalCount, 'icon' => '🍽️'],
                        ['id' => 'Breakfast', 'label' => 'Breakfast', 'count' => $breakfastCount, 'icon' => '🍳'],
                        ['id' => 'Lunch', 'label' => 'Lunch', 'count' => $lunchCount, 'icon' => '☀️'],
                        ['id' => 'Dinner', 'label' => 'Dinner', 'count' => $dinnerCount, 'icon' => '🌙'],
                        /* BEVERAGE FEATURE (DISABLED FOR LATER USE)
                        ['id' => 'Beverage', 'label' => 'Beverages', 'count' => $beverageCount, 'icon' => '🍹'],
                        */
                    ];
                @endphp

                @foreach($tabs as $tab)
                    @php 
                        $isActive = strtolower($activeType) === strtolower($tab['id']); 
                        $isBeverageTab = strtolower($tab['id']) === 'beverage';
                    @endphp
                    <a href="{{ route('foods.index', array_merge(request()->query(), ['type' => $tab['id'], 'page' => 1])) }}"
                       class="px-4.5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all duration-300 flex items-center space-x-2 whitespace-nowrap {{ $isActive ? ($isBeverageTab ? 'bg-[#0A2E2A] text-white shadow-lg border border-[#C9A050]/50' : 'bg-[#0A2E2A] text-white shadow-md border border-[#C9A050]/30') : ($isBeverageTab ? 'text-[#0A2E2A] hover:bg-[#C9A050]/15 bg-[#C9A050]/10 border border-[#C9A050]/30 font-black' : 'text-gray-600 hover:bg-gray-100/80 bg-gray-50/80 border border-transparent') }}">
                        <span>{{ $tab['icon'] }}</span>
                        <span>{{ $tab['label'] }}</span>
                        <span class="px-2 py-0.5 text-[10px] rounded-lg font-black {{ $isActive ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A]' : ($isBeverageTab ? 'bg-[#0A2E2A] text-[#C9A050]' : 'bg-gray-200 text-gray-700') }}">
                            {{ $tab['count'] }}
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- Search & Sort Controls -->
            <form method="GET" action="{{ url()->current() }}" class="flex items-center space-x-2 w-full md:w-auto">
                <input type="hidden" name="type" value="{{ $activeType }}">
                
                <div class="relative flex-1 md:w-64">
                    <input type="text" 
                           name="search" 
                           value="{{ $search }}" 
                           placeholder="{{ strtolower($activeType) === 'beverage' ? 'Search beverages...' : 'Search items...' }}" 
                           @input.debounce.1500ms="$el.form.submit()"
                           class="w-full pl-9 pr-8 py-2.5 bg-gray-50/80 hover:bg-white focus:bg-white rounded-2xl border border-gray-200 focus:border-[#C9A050] text-xs sm:text-sm font-semibold transition-all focus:ring-2 focus:ring-[#C9A050]/20">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    @if($search)
                        <a href="{{ route('foods.index', ['type' => $activeType]) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 font-bold leading-none">&times;</a>
                    @endif
                </div>

                <select name="sort" onchange="this.form.submit()" class="py-2.5 px-3.5 bg-gray-50/80 rounded-2xl border border-gray-200 text-xs sm:text-sm font-semibold text-gray-700 focus:border-[#C9A050] transition-all cursor-pointer">
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest</option>
                    <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                    <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                    <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
                </select>
            </form>
        </div>

        {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
        @if(strtolower($activeType) === 'beverage')
            <!-- ============================================================= -->
            <!-- DEDICATED BEVERAGES MANAGEMENT MODULE -->
            <!-- ============================================================= -->

            <!-- Master Beverage Banner -->
            <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0d3833] to-[#0A2E2A] text-white rounded-[2.5rem] p-6 sm:p-8 border border-[#C9A050]/40 shadow-xl shadow-[#0A2E2A]/10 mb-8 relative overflow-hidden">
                <div class="absolute -right-12 -bottom-12 w-64 h-64 bg-[#C9A050]/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-8 top-1/2 -translate-y-1/2 text-8xl opacity-10 select-none pointer-events-none">🍹</div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="max-w-2xl">
                        <div class="flex items-center space-x-2.5 mb-2.5">
                            <span class="px-3 py-1 rounded-xl text-[10px] font-black uppercase tracking-wider bg-[#C9A050]/20 text-[#C9A050] border border-[#C9A050]/30">
                                Master Beverage Library
                            </span>
                            <span class="px-3 py-1 rounded-xl text-[10px] font-bold text-emerald-200 bg-emerald-500/20 border border-emerald-400/30">
                                Monthly Plan Complimentary Drinks
                            </span>
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-white mb-2">
                            Beverage Master Management
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-300 font-medium leading-relaxed">
                            Manage the official beverage catalog including soft drinks, artisanal cold-pressed juices, and spring mineral water. These master beverages are available for retail ordering and subscription lunch package inclusions.
                        </p>

                        <div class="flex items-center space-x-2 mt-4 text-[11px] font-bold text-[#C9A050]">
                            <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>Read-Only Constraint: Once an order is placed, assigned beverages cannot be changed.</span>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <!-- View Mode Switcher -->
                        <div class="bg-black/50 p-1.5 rounded-2xl border border-white/10 flex items-center space-x-1">
                            <button type="button" @click="viewMode = 'table'" 
                                    :class="viewMode === 'table' ? 'bg-[#C9A050] text-[#0A2E2A] font-black shadow-md' : 'text-gray-300 hover:text-white'"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                                <span>Spacious Table</span>
                            </button>
                            <button type="button" @click="viewMode = 'grid'" 
                                    :class="viewMode === 'grid' ? 'bg-[#C9A050] text-[#0A2E2A] font-black shadow-md' : 'text-gray-300 hover:text-white'"
                                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                                <span>Cards View</span>
                            </button>
                        </div>

                        <button type="button" @click="openAddBeverage()" 
                                class="px-5 py-3 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black text-xs sm:text-sm rounded-2xl shadow-lg shadow-[#C9A050]/20 transition-all flex items-center space-x-2 cursor-pointer active:scale-95">
                            <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            <span>Add Master Beverage</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Spacious Luxury SaaS Table View -->
            <div x-show="viewMode === 'table'" class="bg-white rounded-[2.5rem] p-6 sm:p-8 border border-gray-200/80 shadow-xs overflow-hidden mb-8 transform-gpu">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-5 mb-6 border-b border-gray-100 gap-3">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 tracking-tight">Master Beverages Directory</h3>
                        <p class="text-xs text-gray-500 font-medium">Manage pricing in LKR, portions, temperature status, and active delivery days</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="px-3.5 py-1.5 rounded-xl text-xs font-black bg-[#0A2E2A]/5 text-[#0A2E2A] border border-[#0A2E2A]/15">
                            {{ $foods->total() }} Registered Drinks
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto custom-scrollbar">
                    <table class="w-full min-w-[950px] table-auto text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-[11px] font-black uppercase tracking-wider text-gray-400">
                                <th class="pb-4 pl-4 pr-3 min-w-[280px]">Drink & Item</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[130px]">Serving & Volume</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[120px]">Serving Style</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[100px]">Calories</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[130px]">Unit Price (LKR)</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[160px]">Active Days</th>
                                <th class="pb-4 px-3 whitespace-nowrap min-w-[90px]">Rating</th>
                                <th class="pb-4 pl-3 pr-4 text-right whitespace-nowrap min-w-[100px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-xs font-medium text-gray-700">
                            @forelse($foods as $bev)
                                <tr class="hover:bg-amber-50/20 transition-colors group">
                                    <td class="py-4 pl-4 pr-3 min-w-[280px]">
                                        <div class="flex items-center space-x-3.5">
                                            <!-- Strict 64x64px Thumbnail Wrapper & Image -->
                                            <div class="w-16 h-16 min-w-[64px] min-h-[64px] max-w-[64px] max-h-[64px] rounded-2xl overflow-hidden bg-gray-100 shadow-xs shrink-0 border border-gray-200/60 relative flex items-center justify-center" style="width: 64px; height: 64px; min-width: 64px; max-width: 64px;">
                                                <img src="{{ $bev->image_url }}" alt="{{ $bev->name }}" 
                                                     loading="lazy" decoding="async"
                                                     class="w-16 h-16 min-w-[64px] min-h-[64px] max-w-[64px] max-h-[64px] object-cover rounded-2xl shrink-0 block group-hover:scale-110 transition-transform duration-300"
                                                     style="width: 64px; height: 64px; min-width: 64px; max-width: 64px; object-fit: cover;"
                                                     onerror="this.src='https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80'">
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="text-sm font-black text-gray-900 group-hover:text-[#C9A050] transition-colors truncate max-w-[180px]" title="{{ $bev->name }}">
                                                        {{ $bev->name }}
                                                    </h4>
                                                    <span class="px-2 py-0.5 rounded-lg text-[9px] font-black bg-[#0A2E2A]/10 text-[#0A2E2A] shrink-0">
                                                        #BEV-{{ str_pad($bev->id, 3, '0', STR_PAD_LEFT) }}
                                                    </span>
                                                </div>
                                                <p class="text-[11px] text-gray-500 line-clamp-1 max-w-[200px] mt-0.5" title="{{ $bev->description }}">
                                                    {{ $bev->description ?: 'Cold refreshing specialty beverage.' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            🥤 {{ $bev->servings ?: '330 ml can' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-xs font-bold bg-cyan-50 text-cyan-800 border border-cyan-100">
                                            ❄️ {{ $bev->difficulty ?: 'Chilled' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span class="font-bold text-gray-600">
                                            {{ $bev->calories !== null ? $bev->calories . ' kcal' : '—' }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <span class="text-base font-black text-[#C9A050]">
                                            Rs. {{ number_format($bev->price, 2) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <div class="flex items-center flex-wrap gap-1 max-w-[200px]">
                                            @php
                                                $bDays = is_array($bev->available_days) ? $bev->available_days : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                            @endphp
                                            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)
                                                <span class="px-1.5 py-0.5 text-[9px] font-black rounded-md {{ in_array($d, $bDays) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-300' }}">
                                                    {{ $d }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="py-4 px-3 whitespace-nowrap">
                                        <div class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-xl bg-amber-50 text-amber-700 font-black text-xs border border-amber-200">
                                            <span class="text-amber-500">★</span>
                                            <span>{{ number_format($bev->rating ?: 4.8, 1) }}</span>
                                        </div>
                                    </td>
                                    <td class="py-4 pl-3 pr-4 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button type="button" @click="openEdit({{ json_encode($bev) }})" 
                                                    class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 bg-gray-50 rounded-xl transition-all border border-gray-200 cursor-pointer" 
                                                    title="Edit Beverage">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button type="button" @click="confirmDelete({{ $bev->id }}, '{{ addslashes($bev->name) }}', 'beverage')" 
                                                    class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 bg-gray-50 rounded-xl transition-all border border-gray-200 cursor-pointer" 
                                                    title="Delete Beverage">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-16 text-center text-gray-400">
                                        <div class="text-5xl mb-3">🍹</div>
                                        <p class="font-black text-gray-700 text-base">No beverages found</p>
                                        <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">No master beverages match your search. Click below to add a new beverage.</p>
                                        <button type="button" @click="openAddBeverage()" class="mt-4 px-5 py-2.5 bg-[#0A2E2A] text-[#C9A050] rounded-xl text-xs font-black shadow-md">
                                            + Add New Beverage
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Beverage Cards Grid (when viewMode === 'grid') -->
            <div x-show="viewMode === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
                @forelse($foods as $bev)
                    <div class="bg-white rounded-[2.5rem] p-6 border border-gray-200/80 shadow-xs hover:shadow-md hover:border-[#C9A050]/40 transition-all duration-300 flex flex-col group justify-between transform-gpu will-change-transform">
                        <div>
                            <!-- Beverage Image -->
                            <div class="relative w-full h-48 rounded-2xl overflow-hidden mb-4 bg-gray-100 shadow-xs">
                                <img src="{{ $bev->image_url }}" 
                                     alt="{{ $bev->name }}" 
                                     loading="lazy" decoding="async"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.src='https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=800&q=80'">

                                <div class="absolute top-3 left-3">
                                    <span class="px-3 py-1 text-[11px] font-black rounded-xl shadow-xs uppercase tracking-wider bg-[#0A2E2A] text-[#C9A050] border border-[#C9A050]/40">
                                        🍹 Beverage
                                    </span>
                                </div>

                                <div class="absolute top-3 right-3 px-2.5 py-1 bg-black/80 rounded-xl text-white text-[11px] font-bold flex items-center space-x-1 shadow-xs">
                                    <span class="text-amber-400">★</span>
                                    <span>{{ number_format($bev->rating ?: 4.8, 1) }}</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between mb-1">
                                <h3 class="text-base font-black text-gray-900 group-hover:text-[#C9A050] transition-colors line-clamp-1 tracking-tight" title="{{ $bev->name }}">
                                    {{ $bev->name }}
                                </h3>
                                <span class="px-2 py-0.5 rounded-lg text-[9px] font-black bg-[#0A2E2A]/10 text-[#0A2E2A]">
                                    #BEV-{{ str_pad($bev->id, 3, '0', STR_PAD_LEFT) }}
                                </span>
                            </div>

                            <p class="text-gray-500 text-xs line-clamp-2 mb-3.5 leading-relaxed font-medium">
                                {{ $bev->description ?: 'Cold refreshing specialty beverage.' }}
                            </p>

                            <div class="flex items-center space-x-2 text-[11px] font-semibold text-gray-500 mb-3.5 pb-3.5 border-b border-gray-100">
                                <span class="px-2 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold">
                                    🥤 {{ $bev->servings ?: '330 ml can' }}
                                </span>
                                <span>•</span>
                                <span class="px-2 py-0.5 rounded-lg bg-cyan-50 text-cyan-800 font-bold">
                                    ❄️ {{ $bev->difficulty ?: 'Chilled' }}
                                </span>
                                <span>•</span>
                                <span>{{ $bev->calories !== null ? $bev->calories . ' kcal' : '0 kcal' }}</span>
                            </div>

                            <!-- Available Days -->
                            <div class="flex items-center flex-wrap gap-1 mb-4">
                                @php
                                    $days = is_array($bev->available_days) ? $bev->available_days : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                @endphp
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)
                                    <span class="px-2 py-0.5 text-[9px] font-black rounded-lg {{ in_array($d, $days) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $d }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Price & Actions Bar -->
                        <div class="pt-3.5 border-t border-gray-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-black text-gray-400 block tracking-wider leading-tight">Price</span>
                                <span class="text-xl font-black text-[#C9A050]">Rs. {{ number_format($bev->price, 2) }}</span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openEdit({{ json_encode($bev) }})" 
                                        class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" 
                                        title="Edit Beverage">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button type="button" @click="confirmDelete({{ $bev->id }}, '{{ addslashes($bev->name) }}', 'beverage')" 
                                        class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" 
                                        title="Delete Beverage">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-16 text-center bg-white rounded-[2.5rem] border border-gray-200/80 shadow-xs">
                        <div class="text-5xl mb-3">🍹</div>
                        <h3 class="text-lg font-black text-gray-900 mb-1">No beverages found</h3>
                        <p class="text-xs text-gray-500 max-w-sm mx-auto mb-4 font-medium">No master beverages match your search. Add drinks to populate your master list.</p>
                        <button type="button" @click="openAddBeverage()" class="px-5 py-2.5 bg-[#0A2E2A] text-[#C9A050] rounded-xl text-xs font-black shadow-md">
                            + Add New Beverage
                        </button>
                    </div>
                @endforelse
            </div>
        @endif
        --}}
            <!-- ============================================================= -->
            <!-- STANDARD FOOD & DISHES GRID -->
            <!-- ============================================================= -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($foods as $food)
                    <div class="bg-white rounded-[2.5rem] p-6 border border-gray-200/80 shadow-xs hover:shadow-md hover:border-[#C9A050]/40 transition-all duration-300 flex flex-col group justify-between transform-gpu will-change-transform">
                        <div>
                            <!-- Dish Image with Overlay Badges -->
                            <div class="relative w-full h-48 rounded-2xl overflow-hidden mb-4 bg-gray-100 shadow-xs">
                                <img src="{{ $food->image_url }}" 
                                     alt="{{ $food->name }}" 
                                     loading="lazy" decoding="async"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=800&q=80'">

                                <!-- Meal Type Badge -->
                                <div class="absolute top-3 left-3">
                                    @php
                                        $badgeStyle = match($food->type) {
                                            'Breakfast' => 'bg-amber-600 text-white',
                                            'Lunch' => 'bg-orange-600 text-white',
                                            'Dinner' => 'bg-indigo-600 text-white',
                                            'Beverage' => 'bg-[#0A2E2A] text-[#C9A050] border border-[#C9A050]/40',
                                            default => 'bg-[#0A2E2A] text-white',
                                        };
                                    @endphp
                                    <span class="px-3 py-1 text-[11px] font-black rounded-xl shadow-xs uppercase tracking-wider {{ $badgeStyle }}">
                                        {{ $food->type === 'Beverage' ? '🍹 Beverage' : $food->type }}
                                    </span>
                                </div>

                                <!-- Rating Badge -->
                                <div class="absolute top-3 right-3 px-2.5 py-1 bg-black/80 rounded-xl text-white text-[11px] font-bold flex items-center space-x-1 shadow-xs">
                                    <span class="text-amber-400">★</span>
                                    <span>{{ number_format($food->rating ?: 4.8, 1) }}</span>
                                </div>
                            </div>

                            <!-- Dish Info -->
                            <h3 class="text-base font-black text-gray-900 group-hover:text-[#C9A050] transition-colors line-clamp-1 mb-1 tracking-tight" title="{{ $food->name }}">
                                {{ $food->name }}
                            </h3>

                            <p class="text-gray-500 text-xs line-clamp-2 mb-3.5 leading-relaxed font-medium">
                                {{ $food->description ?: 'Freshly prepared specialty dish crafted with authentic spices and premium ingredients.' }}
                            </p>

                            <!-- Cooking / Health Metadata -->
                            <div class="flex items-center space-x-3 text-[11px] font-semibold text-gray-500 mb-3.5 pb-3.5 border-b border-gray-100">
                                <span class="flex items-center space-x-1">
                                    <span>⏱️</span>
                                    <span>{{ $food->prep_time ?: '15 min' }}</span>
                                </span>
                                <span>•</span>
                                <span class="flex items-center space-x-1">
                                    <span>🔥</span>
                                    <span>{{ $food->calories ?: '450' }} kcal</span>
                                </span>
                                <span>•</span>
                                <span class="flex items-center space-x-1">
                                    <span>👥</span>
                                    <span>{{ $food->servings ?: '1 person' }}</span>
                                </span>
                            </div>

                            <!-- Available Days Mini Pills -->
                            <div class="flex items-center flex-wrap gap-1 mb-4">
                                @php
                                    $days = is_array($food->available_days) ? $food->available_days : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                                @endphp
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d)
                                    <span class="px-2 py-0.5 text-[9px] font-black rounded-lg {{ in_array($d, $days) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-400' }}">
                                        {{ $d }}
                                    </span>
                                @endforeach
                            </div>
                        </div>

                        <!-- Bottom Price & Actions Bar -->
                        <div class="pt-3.5 border-t border-gray-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-black text-gray-400 block tracking-wider leading-tight">Price</span>
                                <span class="text-xl font-black text-[#C9A050]">Rs. {{ number_format($food->price, 2) }}</span>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openEdit({{ json_encode($food) }})" 
                                        class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" 
                                        title="Edit Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </button>

                                <button type="button" @click="confirmDelete({{ $food->id }}, '{{ addslashes($food->name) }}', '{{ $food->type === 'Beverage' ? 'beverage' : 'meal' }}')" 
                                        class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 bg-gray-50 rounded-xl transition-all border border-gray-200/80 cursor-pointer" 
                                        title="Delete Item">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-[2.5rem] border border-gray-200/80 shadow-xs">
                        <div class="text-6xl mb-4">🍽️</div>
                        <h3 class="text-xl font-black text-gray-900 mb-1">No items found</h3>
                        <p class="text-sm text-gray-500 max-w-md mx-auto mb-6 font-medium">No items match your selected filter or search term. Try resetting your search or add a new meal.</p>
                        <a href="{{ route('foods.index') }}" class="px-6 py-3 bg-[#0A2E2A] text-[#C9A050] hover:bg-[#0D3833] rounded-2xl font-black text-xs transition-all shadow-md">
                            Reset Filters
                        </a>
                    </div>
                @endforelse
            </div>

        <!-- Pagination -->
        <div class="mt-8">
            {{ $foods->links() }}
        </div>

        <!-- ========================================== -->
        <!-- ADD ITEM / BEVERAGE MODAL -->
        <!-- ========================================== -->
        <div x-show="openCreateModal" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden my-8 border border-gray-100"
                 @click.away="openCreateModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                <div class="p-6 sm:p-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-2xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center font-bold text-xl border border-[#C9A050]/30 shadow-xs">
                                <span x-text="currentFood.type === 'Beverage' ? '🍹' : '🍳'"></span>
                            </div>
                            <div>
                                <h2 class="text-xl font-extrabold text-gray-900" x-text="currentFood.type === 'Beverage' ? 'Add Master Beverage' : 'Add New Dish'"></h2>
                                <p class="text-xs text-gray-400" x-text="currentFood.type === 'Beverage' ? 'Configure a drink in the master catalog for menus & subscriptions' : 'Add a delicious meal item to Menu catalog'"></p>
                            </div>
                        </div>
                        <button @click="openCreateModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Add Form -->
                    <form method="POST" action="{{ route('foods.store') }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <!-- Name & Category Type -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Beverage Name *' : 'Meal / Dish Name *'"></label>
                                <input type="text" name="name" x-model="currentFood.name" required 
                                       :placeholder="currentFood.type === 'Beverage' ? 'e.g. Coca Cola, Fresh Mango Juice' : 'e.g. Special Chicken Biriyani'" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-semibold text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Category Type *</label>
                                <select name="type" x-model="currentFood.type" required 
                                        class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-semibold text-gray-800 transition-all focus:ring-0">
                                    <option value="Breakfast">Breakfast 🍳</option>
                                    <option value="Lunch">Lunch ☀️</option>
                                    <option value="Dinner">Dinner 🌙</option>
                                    <option value="Beverage">Beverage 🍹</option>
                                </select>
                            </div>
                        </div>

                        <!-- Price & Prep / Temperature & Calories -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Price (Rs.) *</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-3 text-xs font-bold text-gray-400">Rs.</span>
                                    <input type="number" step="0.01" min="0" name="price" x-model="currentFood.price" required placeholder="250.00" 
                                           class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-bold text-[#C9A050] transition-all focus:ring-0">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Service Style' : 'Prep Time'"></label>
                                <input type="text" name="prep_time" x-model="currentFood.prep_time" 
                                       :placeholder="currentFood.type === 'Beverage' ? 'Ready to Serve' : 'e.g. 15 min'" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Calories (kcal)</label>
                                <input type="number" min="0" name="calories" x-model="currentFood.calories" placeholder="e.g. 140" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                        </div>

                        <!-- Volume/Servings & Temperature/Difficulty & Rating -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Volume / Bottle Size' : 'Servings'"></label>
                                <input type="text" name="servings" x-model="currentFood.servings" 
                                       :placeholder="currentFood.type === 'Beverage' ? 'e.g. 330 ml can, 500 ml' : 'e.g. 1 person'" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Chill / Temperature' : 'Difficulty'"></label>
                                <select name="difficulty" x-model="currentFood.difficulty" class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                                    <template x-if="currentFood.type === 'Beverage'">
                                        <optgroup label="Beverage Temperature">
                                            <option value="Chilled">Chilled ❄️</option>
                                            <option value="Cold">Cold 🧊</option>
                                            <option value="Fresh Pressed">Fresh Pressed 🍊</option>
                                            <option value="Ambient">Ambient / Room Temp 💧</option>
                                            <option value="Hot">Hot ☕</option>
                                        </optgroup>
                                    </template>
                                    <template x-if="currentFood.type !== 'Beverage'">
                                        <optgroup label="Culinary Complexity">
                                            <option value="Easy">Easy</option>
                                            <option value="Medium">Medium</option>
                                            <option value="Chef Special">Chef Special</option>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Rating (1 - 5)</label>
                                <input type="number" step="0.1" min="1" max="5" name="rating" x-model="currentFood.rating" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Beverage Notes & Description' : 'Description'"></label>
                            <textarea name="description" x-model="currentFood.description" rows="3" 
                                      :placeholder="currentFood.type === 'Beverage' ? 'Describe the drink flavour, ingredients, notes (e.g. 100% natural Valencia orange juice)...' : 'Describe the ingredients, taste, spices and specialty of this dish...'" 
                                      class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-xs sm:text-sm font-medium text-gray-800 transition-all focus:ring-0"></textarea>
                        </div>

                        <!-- Available Days Checkboxes -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Available Service Days</label>
                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                    <label class="flex items-center justify-center p-2 rounded-xl border border-gray-200 hover:border-[#C9A050] bg-gray-50/80 cursor-pointer text-xs font-bold text-gray-700 select-none">
                                        <input type="checkbox" name="available_days[]" value="{{ $day }}" 
                                               :checked="currentFood.available_days && currentFood.available_days.includes('{{ $day }}')" 
                                               class="mr-1.5 rounded text-[#C9A050] focus:ring-0">
                                        {{ $day }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Image Upload / URL Mode -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-gray-700" x-text="currentFood.type === 'Beverage' ? 'Beverage Photo' : 'Dish Photo'"></label>
                                <div class="flex items-center space-x-2 text-xs">
                                    <button type="button" @click="imageMode = 'url'" :class="imageMode === 'url' ? 'text-[#C9A050] font-bold underline' : 'text-gray-400'">Image URL</button>
                                    <span>|</span>
                                    <button type="button" @click="imageMode = 'file'" :class="imageMode === 'file' ? 'text-[#C9A050] font-bold underline' : 'text-gray-400'">Upload File</button>
                                </div>
                            </div>

                            <div x-show="imageMode === 'url'">
                                <input type="url" name="image_url" x-model="currentFood.image_url" 
                                       @input="previewImage = $event.target.value"
                                       placeholder="https://images.unsplash.com/..." 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-xs sm:text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>

                            <div x-show="imageMode === 'file'">
                                <input type="file" name="image" accept="image/*" @change="handleImagePreview($event)" 
                                       class="w-full px-3 py-2 bg-gray-50 rounded-2xl border border-gray-200 text-xs font-medium text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0A2E2A] file:text-white hover:file:bg-[#0A2E2A]/90">
                            </div>

                            <!-- Live Image Preview -->
                            <template x-if="previewImage">
                                <div class="mt-3 flex items-center space-x-3 p-2 bg-gray-50 rounded-2xl border border-gray-100">
                                    <img :src="previewImage" loading="lazy" decoding="async" class="w-16 h-16 object-cover rounded-xl shadow-xs" alt="Preview">
                                    <p class="text-[11px] text-gray-500 font-medium">Selected image preview looks ready!</p>
                                </div>
                            </template>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                            <button type="button" @click="openCreateModal = false" class="px-5 py-3 rounded-2xl border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-100 transition-all cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black text-xs sm:text-sm rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all cursor-pointer">
                                <span x-text="currentFood.type === 'Beverage' ? 'Save & Publish Beverage' : 'Save & Publish Dish'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- EDIT ITEM / BEVERAGE MODAL -->
        <!-- ========================================== -->
        <div x-show="openEditModal" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-2xl overflow-hidden my-8 border border-gray-100"
                 @click.away="openEditModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                 x-transition:enter-end="opacity-100 scale-100 translate-y-0">

                <div class="p-6 sm:p-8">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-gray-100 mb-6">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-2xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center font-bold text-xl border border-[#C9A050]/30 shadow-xs">
                                <span x-text="currentFood.type === 'Beverage' ? '🍹' : '✏️'"></span>
                            </div>
                            <div>
                                <h2 class="text-xl font-extrabold text-gray-900" x-text="currentFood.type === 'Beverage' ? 'Edit Beverage Profile' : 'Edit Dish Details'"></h2>
                                <p class="text-xs text-gray-400" x-text="currentFood.type === 'Beverage' ? 'Update drink volume, pricing in LKR, serving style or image' : 'Update meal attributes, pricing, or ingredients'"></p>
                            </div>
                        </div>
                        <button @click="openEditModal = false" class="p-2 text-gray-400 hover:text-gray-600 rounded-xl hover:bg-gray-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Edit Form -->
                    <form method="POST" :action="'/foods/' + currentFood.id" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <!-- Name & Category Type -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Beverage Name *' : 'Meal / Dish Name *'"></label>
                                <input type="text" name="name" x-model="currentFood.name" required 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-semibold text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Category Type *</label>
                                <select name="type" x-model="currentFood.type" required 
                                        class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-semibold text-gray-800 transition-all focus:ring-0">
                                    <option value="Breakfast">Breakfast 🍳</option>
                                    <option value="Lunch">Lunch ☀️</option>
                                    <option value="Dinner">Dinner 🌙</option>
                                    <option value="Beverage">Beverage 🍹</option>
                                </select>
                            </div>
                        </div>

                        <!-- Price & Prep / Service & Calories -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Price (Rs.) *</label>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-3 text-xs font-bold text-gray-400">Rs.</span>
                                    <input type="number" step="0.01" min="0" name="price" x-model="currentFood.price" required 
                                           class="w-full pl-10 pr-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-bold text-[#C9A050] transition-all focus:ring-0">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Service Style' : 'Prep Time'"></label>
                                <input type="text" name="prep_time" x-model="currentFood.prep_time" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Calories (kcal)</label>
                                <input type="number" min="0" name="calories" x-model="currentFood.calories" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                        </div>

                        <!-- Servings/Volume & Temperature/Difficulty & Rating -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Volume / Bottle Size' : 'Servings'"></label>
                                <input type="text" name="servings" x-model="currentFood.servings" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Chill / Temperature' : 'Difficulty'"></label>
                                <select name="difficulty" x-model="currentFood.difficulty" class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                                    <template x-if="currentFood.type === 'Beverage'">
                                        <optgroup label="Beverage Temperature">
                                            <option value="Chilled">Chilled ❄️</option>
                                            <option value="Cold">Cold 🧊</option>
                                            <option value="Fresh Pressed">Fresh Pressed 🍊</option>
                                            <option value="Ambient">Ambient / Room Temp 💧</option>
                                            <option value="Hot">Hot ☕</option>
                                        </optgroup>
                                    </template>
                                    <template x-if="currentFood.type !== 'Beverage'">
                                        <optgroup label="Culinary Complexity">
                                            <option value="Easy">Easy</option>
                                            <option value="Medium">Medium</option>
                                            <option value="Chef Special">Chef Special</option>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 mb-1.5">Rating (1 - 5)</label>
                                <input type="number" step="0.1" min="1" max="5" name="rating" x-model="currentFood.rating" 
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5" x-text="currentFood.type === 'Beverage' ? 'Beverage Notes & Description' : 'Description'"></label>
                            <textarea name="description" x-model="currentFood.description" rows="3" 
                                      class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-xs sm:text-sm font-medium text-gray-800 transition-all focus:ring-0"></textarea>
                        </div>

                        <!-- Available Days Checkboxes -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1.5">Available Service Days</label>
                            <div class="grid grid-cols-4 sm:grid-cols-7 gap-2">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                                    <label class="flex items-center justify-center p-2 rounded-xl border border-gray-200 hover:border-[#C9A050] bg-gray-50/80 cursor-pointer text-xs font-bold text-gray-700 select-none">
                                        <input type="checkbox" name="available_days[]" value="{{ $day }}" 
                                               :checked="currentFood.available_days && currentFood.available_days.includes('{{ $day }}')" 
                                               class="mr-1.5 rounded text-[#C9A050] focus:ring-0">
                                        {{ $day }}
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Image Upload / URL Mode -->
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-gray-700" x-text="currentFood.type === 'Beverage' ? 'Beverage Photo' : 'Dish Photo'"></label>
                                <div class="flex items-center space-x-2 text-xs">
                                    <button type="button" @click="imageMode = 'url'" :class="imageMode === 'url' ? 'text-[#C9A050] font-bold underline' : 'text-gray-400'">Image URL</button>
                                    <span>|</span>
                                    <button type="button" @click="imageMode = 'file'" :class="imageMode === 'file' ? 'text-[#C9A050] font-bold underline' : 'text-gray-400'">Upload File</button>
                                </div>
                            </div>

                            <div x-show="imageMode === 'url'">
                                <input type="url" name="image_url" x-model="currentFood.image_url" 
                                       @input="previewImage = $event.target.value"
                                       class="w-full px-4 py-3 bg-gray-50 rounded-2xl border border-gray-200 focus:border-[#C9A050] text-xs sm:text-sm font-medium text-gray-800 transition-all focus:ring-0">
                            </div>

                            <div x-show="imageMode === 'file'">
                                <input type="file" name="image" accept="image/*" @change="handleImagePreview($event)" 
                                       class="w-full px-3 py-2 bg-gray-50 rounded-2xl border border-gray-200 text-xs font-medium text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#0A2E2A] file:text-white hover:file:bg-[#0A2E2A]/90">
                            </div>

                            <!-- Live Image Preview -->
                            <template x-if="previewImage">
                                <div class="mt-3 flex items-center space-x-3 p-2 bg-gray-50 rounded-2xl border border-gray-100">
                                    <img :src="previewImage" loading="lazy" decoding="async" class="w-16 h-16 object-cover rounded-xl shadow-xs" alt="Preview">
                                    <p class="text-[11px] text-gray-500 font-medium">Selected image preview</p>
                                </div>
                            </template>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-end space-x-3">
                            <button type="button" @click="openEditModal = false" class="px-5 py-3 rounded-2xl border border-gray-200 text-gray-600 font-bold text-xs hover:bg-gray-100 transition-all cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black text-xs sm:text-sm rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all cursor-pointer">
                                <span x-text="currentFood.type === 'Beverage' ? 'Update Beverage' : 'Update Dish Profile'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- DELETE CONFIRMATION MODAL -->
        <!-- ========================================== -->
        <div x-show="openDeleteModal" 
             x-cloak 
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">

            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden p-6 sm:p-7 border border-gray-100"
                 @click.away="openDeleteModal = false">
                <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 flex items-center justify-center text-xl font-bold mb-4">
                    ⚠️
                </div>
                <h3 class="text-lg font-black text-gray-900 mb-1" x-text="'Delete ' + (deleteType === 'beverage' ? 'Beverage' : 'Meal') + '?'"></h3>
                <p class="text-xs sm:text-sm text-gray-500 mb-6">
                    Are you sure you want to remove <strong class="text-gray-800" x-text="deleteName"></strong> from the catalog? This will permanently delete this <span x-text="deleteType === 'beverage' ? 'beverage item' : 'dish and its ratings'"></span>.
                </p>

                <form method="POST" :action="'/foods/' + deleteId" class="flex items-center justify-end space-x-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="openDeleteModal = false" class="px-4 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-bold shadow-md shadow-red-500/20">
                        <span x-text="'Yes, Delete ' + (deleteType === 'beverage' ? 'Beverage' : 'Meal')"></span>
                    </button>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
