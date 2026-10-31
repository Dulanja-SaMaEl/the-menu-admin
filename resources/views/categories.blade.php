<x-app-layout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-[#0A2E2A] font-black">Categories</span>
            </nav>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Menu Categories</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Browse cuisine categories and menu assortments</p>
        </div>
        
        <div class="flex items-center space-x-2.5">
            <button class="px-5 py-3 bg-white/90 backdrop-blur-md rounded-2xl shadow-sm text-xs sm:text-sm font-bold text-gray-700 hover:bg-white transition-all border border-gray-200 cursor-pointer">
                All categories
            </button>
            <button class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] cursor-pointer">
                Add category +
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @php
            $categories = [
                ['name' => 'Seafood', 'icon' => '🦞', 'items' => 12, 'slug' => 'seafood'],
                ['name' => 'Burger', 'icon' => '🍔', 'items' => 24, 'slug' => 'burger'],
                ['name' => 'Pizza', 'icon' => '🍕', 'items' => 18, 'slug' => 'pizza'],
                ['name' => 'Pasta', 'icon' => '🍝', 'items' => 15, 'slug' => 'pasta'],
                ['name' => 'Desserts', 'icon' => '🍰', 'items' => 10, 'slug' => 'desserts'],
                ['name' => 'Drinks', 'icon' => '🥤', 'items' => 30, 'slug' => 'drinks'],
                ['name' => 'Salad', 'icon' => '🥗', 'items' => 8, 'slug' => 'salad'],
                ['name' => 'Sushi', 'icon' => '🍣', 'items' => 20, 'slug' => 'sushi'],
            ];
        @endphp

        @foreach($categories as $category)
            <a href="{{ route('category', $category['slug']) }}" class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-7 sm:p-8 flex flex-col items-center justify-center text-center shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group">
                <div class="text-5xl sm:text-6xl mb-5 transform group-hover:scale-110 transition-transform duration-300">
                    {{ $category['icon'] }}
                </div>
                <h3 class="text-lg sm:text-xl font-black text-gray-900 mb-1 group-hover:text-[#C9A050] transition-colors tracking-tight">{{ $category['name'] }}</h3>
                <p class="text-gray-400 text-xs sm:text-sm font-semibold">{{ $category['items'] }} items</p>
                <div class="mt-5 w-12 h-1.5 bg-gray-100 rounded-full group-hover:bg-gradient-to-r group-hover:from-[#C9A050] group-hover:to-[#b88f3f] transition-all"></div>
            </a>
        @endforeach
    </div>
</x-app-layout>