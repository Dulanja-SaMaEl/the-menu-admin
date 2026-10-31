<x-app-layout>
    <div x-data="{ openModal: false, selectedItem: null }">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                    <a href="{{ route('categories') }}" class="hover:text-[#C9A050] transition-colors">Categories</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-[#0A2E2A] font-black capitalize">{{ $slug }}</span>
                </nav>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] capitalize tracking-tight">{{ $slug }} Menu</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Explore handcrafted culinary selections for {{ $slug }}</p>
            </div>
            
            <div class="flex items-center space-x-2">
                <a href="{{ route('categories') }}" class="px-5 py-2.5 bg-white/95 backdrop-blur-md rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70 text-gray-600 hover:text-[#0A2E2A] text-xs font-bold hover:scale-105 transition-all flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Back to Categories</span>
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            @forelse($foods as $food)
                <div 
                    @click="selectedItem = {{ json_encode(['name' => $food->name, 'price' => $food->price, 'type' => $food->type, 'image_url' => $food->image_url, 'description' => $food->description]) }}; openModal = true"
                    class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 flex flex-col items-center text-center cursor-pointer hover:shadow-2xl hover:shadow-[#0A2E2A]/10 hover:-translate-y-1.5 transition-all duration-300 group border border-gray-200/70 hover:border-[#C9A050]/40 shadow-xl shadow-[#0A2E2A]/5"
                >
                    @if ($food->image_url)
                        <div class="w-28 h-28 rounded-2xl overflow-hidden mb-4 shadow-md group-hover:scale-105 transition-transform duration-300 border border-gray-100">
                            <img src="{{ $food->image_url }}" class="w-full h-full object-cover" alt="{{ $food->name }}">
                        </div>
                    @else
                        <div class="w-28 h-28 rounded-2xl bg-gradient-to-br from-[#0A2E2A]/5 to-[#C9A050]/10 flex items-center justify-center text-5xl mb-4 group-hover:scale-110 transition-transform duration-300 border border-gray-100">
                            🍽️
                        </div>
                    @endif
                    <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1 group-hover:text-[#0A2E2A] transition-colors line-clamp-1">{{ $food->name }}</h3>
                    <span class="px-3 py-1 bg-[#0A2E2A]/5 text-[#0A2E2A] text-[10px] font-black rounded-full uppercase tracking-wider mb-3 border border-[#0A2E2A]/10">{{ $food->type }}</span>
                    <p class="text-xl sm:text-2xl font-black text-[#0A2E2A] tracking-tight">Rs. {{ number_format($food->price, 2) }}</p>
                </div>
            @empty
                <div class="col-span-full py-20 text-center bg-white/95 backdrop-blur-md rounded-[2.5rem] border border-dashed border-[#C9A050]/30 shadow-xl shadow-[#0A2E2A]/5">
                    <div class="w-16 h-16 bg-gray-50 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                    </div>
                    <h3 class="text-[#0A2E2A] font-extrabold text-lg">No dishes found in this category</h3>
                    <p class="text-sm text-gray-400 mt-1">Explore other curated meal categories</p>
                    <a href="{{ route('categories') }}" class="inline-block mt-4 px-6 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] text-xs font-black rounded-2xl shadow-lg shadow-[#C9A050]/20 hover:scale-105 transition-all">View All Categories</a>
                </div>
            @endforelse
        </div>

        <!-- Add to Order Modal -->
        <template x-teleport="body">
            <div 
                x-show="openModal" 
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm overflow-y-auto"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                style="display: none;"
            >
                <div 
                    class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all my-8 border border-gray-100"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    @click.away="openModal = false"
                >
                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] border-b border-[#C9A050]/20 px-8 py-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-white" x-text="selectedItem?.name || 'Dish Details'"></h3>
                            <p class="text-xs text-white/70 mt-0.5">Quick Culinary Overview</p>
                        </div>
                        <button type="button" @click="openModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="p-6 sm:p-8">
                        <div class="flex flex-col items-center text-center mb-6">
                            <template x-if="selectedItem?.image_url">
                                <img :src="selectedItem?.image_url" class="w-32 h-32 rounded-2xl object-cover mb-4 shadow-md border border-gray-100">
                            </template>
                            <template x-if="!selectedItem?.image_url">
                                <div class="text-6xl mb-4">🍽️</div>
                            </template>
                            <h2 class="text-2xl font-black text-[#0A2E2A]" x-text="selectedItem?.name"></h2>
                            <p class="text-gray-500 text-sm mt-1 max-w-md" x-text="selectedItem?.description || 'Authentic gourmet recipe crafted fresh.'"></p>
                            <p class="text-3xl font-black text-[#0A2E2A] mt-4" x-text="'Rs. ' + parseFloat(selectedItem?.price || 0).toFixed(2)"></p>
                        </div>

                        <div class="mt-8 flex flex-col sm:flex-row gap-3">
                            <button @click="openModal = false" class="sm:flex-1 py-3.5 rounded-2xl font-bold text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                                Close
                            </button>
                            <a :href="'{{ route('foods.index') }}'" class="sm:flex-2 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] text-sm text-center flex items-center justify-center">
                                Manage in Menu Catalog
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>