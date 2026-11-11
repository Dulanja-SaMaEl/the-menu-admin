<x-app-layout>
    <script>
        window.posConfig = {
            foods: @json($foods),
            categories: @json($categories),
            checkoutUrl: @json(route('pos.checkout')),
            csrfToken: @json(csrf_token()),
        };

        function posTerminal(config) {
            return {
                foods: config.foods,
                categories: config.categories,
                checkoutUrl: config.checkoutUrl,
                csrfToken: config.csrfToken,
                activeCategory: 'All',
                cart: [],
                submitting: false,

                get filteredFoods() {
                    return this.activeCategory === 'All'
                        ? this.foods
                        : this.foods.filter(food => food.category === this.activeCategory);
                },
                get cartCount() {
                    return this.cart.reduce((sum, item) => sum + item.quantity, 0);
                },
                get cartTotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },
                addToCart(food) {
                    const line = this.cart.find(item => item.id === food.id);
                    if (line) {
                        line.quantity += 1;
                    } else {
                        this.cart.push({ id: food.id, name: food.name, price: food.price, quantity: 1 });
                    }
                },
                increment(id) {
                    const line = this.cart.find(item => item.id === id);
                    if (line) {
                        line.quantity += 1;
                    }
                },
                decrement(id) {
                    const line = this.cart.find(item => item.id === id);
                    if (!line) {
                        return;
                    }
                    line.quantity -= 1;
                    if (line.quantity <= 0) {
                        this.removeItem(id);
                    }
                },
                removeItem(id) {
                    this.cart = this.cart.filter(item => item.id !== id);
                },
                clearCart() {
                    this.cart = [];
                },
                format(value) {
                    return Number(value).toLocaleString('en-LK', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
                async checkout() {
                    if (this.cart.length === 0 || this.submitting) {
                        return;
                    }

                    // Opened synchronously so the receipt tab is not caught by pop-up blockers.
                    const receiptWindow = window.open('', '_blank');
                    this.submitting = true;

                    try {
                        const response = await fetch(this.checkoutUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({
                                items: this.cart.map(item => ({ id: item.id, quantity: item.quantity })),
                            }),
                        });

                        const payload = await response.json();

                        if (!response.ok) {
                            throw new Error(payload.message || 'Unable to complete this sale.');
                        }

                        if (receiptWindow) {
                            receiptWindow.location.href = payload.receipt_url;
                        } else {
                            window.location.href = payload.receipt_url;
                        }

                        this.cart = [];
                    } catch (error) {
                        if (receiptWindow) {
                            receiptWindow.close();
                        }
                        alert(error.message);
                    } finally {
                        this.submitting = false;
                    }
                },
            };
        }
    </script>

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                <a href="{{ route('dashboard') }}" class="transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-[#0A2E2A] font-black">Point of Sale</span>
            </nav>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Point of Sale</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Tap an item to add it to the order, then checkout to print the 80mm bill.</p>
        </div>

        <a href="{{ route('bills') }}" class="shrink-0 px-6 py-3.5 bg-white text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm shadow-lg shadow-[#0A2E2A]/5 border border-gray-200 active:scale-[0.98] transition-transform">
            Bills &amp; Invoices
        </a>
    </div>

    <div x-data="posTerminal(window.posConfig)" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        {{-- LEFT: CATEGORIES + MENU GRID --}}
        <div class="lg:col-span-8 min-w-0">
            <div class="flex flex-wrap items-center gap-3 mb-6">
                <button type="button"
                        @click="activeCategory = 'All'"
                        :class="activeCategory === 'All' ? 'bg-[#0A2E2A] text-white ring-4 ring-[#C9A050]' : 'bg-white text-gray-800 ring-1 ring-gray-200'"
                        class="min-h-[64px] px-6 text-base sm:text-lg font-black rounded-2xl transition-colors duration-150 active:scale-[0.97]">
                    All Items
                </button>

                <template x-for="category in categories" :key="category">
                    <button type="button"
                            @click="activeCategory = category"
                            :class="activeCategory === category ? 'bg-[#0A2E2A] text-white ring-4 ring-[#C9A050]' : 'bg-white text-gray-800 ring-1 ring-gray-200'"
                            class="min-h-[64px] px-6 text-base sm:text-lg font-black rounded-2xl transition-colors duration-150 active:scale-[0.97]"
                            x-text="category"></button>
                </template>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">
                <template x-for="food in filteredFoods" :key="food.id">
                    <button type="button"
                            @click="addToCart(food)"
                            class="flex flex-col text-left bg-white rounded-3xl overflow-hidden ring-1 ring-gray-200 shadow-sm active:scale-[0.97] active:ring-4 active:ring-[#C9A050] transition-all duration-150 min-h-[220px]">
                        <img :src="food.image" :alt="food.name" class="w-full h-28 sm:h-32 object-cover" loading="lazy">
                        <div class="flex flex-col flex-1 p-4">
                            <p class="text-base sm:text-lg font-black text-gray-900 leading-tight" x-text="food.name"></p>
                            <p class="mt-auto pt-3 text-lg sm:text-xl font-black text-[#0A2E2A]">
                                <span class="text-xs font-bold text-gray-400">Rs.</span>
                                <span x-text="format(food.price)"></span>
                            </p>
                        </div>
                    </button>
                </template>
            </div>

            <div x-show="filteredFoods.length === 0" class="bg-white rounded-3xl ring-1 ring-gray-200 p-10 text-center">
                <p class="text-base font-black text-gray-500">No items available in this category.</p>
            </div>
        </div>

        {{-- RIGHT: PERSISTENT ORDER SUMMARY / CART --}}
        <div class="lg:col-span-4 lg:sticky lg:top-6">
            <div class="bg-white rounded-[2rem] ring-1 ring-gray-200 shadow-xl shadow-[#0A2E2A]/5 flex flex-col overflow-hidden">

                <div class="flex items-center justify-between gap-3 px-5 py-4 bg-[#0A2E2A]">
                    <div>
                        <p class="text-lg font-black text-white">Order Summary</p>
                        <p class="text-xs font-bold text-[#C9A050]">
                            <span x-text="cartCount"></span>
                            <span x-text="cartCount === 1 ? ' item' : ' items'"></span>
                        </p>
                    </div>
                    <button type="button"
                            @click="clearCart()"
                            :disabled="cart.length === 0"
                            class="min-h-[48px] px-5 rounded-2xl bg-white/10 text-white text-sm font-black disabled:opacity-40 active:scale-[0.97] transition-all">
                        Clear
                    </button>
                </div>

                <div class="max-h-[45vh] lg:max-h-[calc(100vh-450px)] overflow-y-auto divide-y divide-gray-100">
                    <template x-for="item in cart" :key="item.id">
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <p class="text-base font-black text-gray-900 leading-tight" x-text="item.name"></p>
                                <button type="button"
                                        @click="removeItem(item.id)"
                                        class="w-12 h-12 shrink-0 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center active:scale-95 transition-transform"
                                        aria-label="Remove item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>

                            <div class="mt-3 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="decrement(item.id)"
                                            class="w-14 h-14 rounded-2xl bg-gray-100 text-gray-900 text-3xl font-black leading-none flex items-center justify-center active:scale-95 transition-transform"
                                            aria-label="Decrease quantity">&minus;</button>
                                    <span class="w-14 text-center text-2xl font-black text-gray-900" x-text="item.quantity"></span>
                                    <button type="button"
                                            @click="increment(item.id)"
                                            class="w-14 h-14 rounded-2xl bg-[#C9A050] text-[#0A2E2A] text-3xl font-black leading-none flex items-center justify-center active:scale-95 transition-transform"
                                            aria-label="Increase quantity">+</button>
                                </div>
                                <p class="text-lg font-black text-[#0A2E2A] whitespace-nowrap">
                                    <span class="text-xs font-bold text-gray-400">Rs.</span>
                                    <span x-text="format(item.price * item.quantity)"></span>
                                </p>
                            </div>
                        </div>
                    </template>

                    <div x-show="cart.length === 0" class="p-10 text-center">
                        <p class="text-base font-black text-gray-400">Tap menu items to start an order.</p>
                    </div>
                </div>

                <div class="border-t-2 border-dashed border-gray-200 bg-gray-50 p-5 space-y-4">
                    <div class="flex items-end justify-between">
                        <span class="text-sm font-black text-gray-500 uppercase tracking-wider">Total</span>
                        <span class="text-3xl font-black text-[#0A2E2A] whitespace-nowrap">
                            <span class="text-base font-bold text-gray-400">Rs.</span>
                            <span x-text="format(cartTotal)"></span>
                        </span>
                    </div>

                    <button type="button"
                            @click="checkout()"
                            :disabled="cart.length === 0 || submitting"
                            class="w-full min-h-[72px] rounded-3xl bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] text-lg sm:text-xl font-black shadow-lg shadow-[#C9A050]/30 disabled:opacity-40 disabled:shadow-none active:scale-[0.98] transition-all flex items-center justify-center gap-3">
                        <svg x-show="submitting" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24" style="display: none;">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        <span x-text="submitting ? 'Saving Order…' : 'Checkout & Print Bill'"></span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
