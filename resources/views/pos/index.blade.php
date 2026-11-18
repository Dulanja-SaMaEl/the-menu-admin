<x-pos-layout>
    <script>
        window.posConfig = {
            foods: @json($foods),
            categories: @json($categories),
            checkoutUrl: @json(route('pos.checkout')),
            recentOrdersUrl: @json(route('pos.recentOrders')),
            csrfToken: @json(csrf_token()),
            shiftSales: @json($shiftSales ?? 0),
            shiftOrderCount: @json($shiftOrderCount ?? 0),
        };

        function posApp(config) {
            return {
                foods: config.foods,
                categories: config.categories,
                checkoutUrl: config.checkoutUrl,
                recentOrdersUrl: config.recentOrdersUrl,
                csrfToken: config.csrfToken,
                shiftSales: Number(config.shiftSales || 0),
                shiftOrderCount: Number(config.shiftOrderCount || 0),

                // State
                searchQuery: '',
                activeCategory: 'All',
                cart: [],
                orderType: 'takeaway', // takeaway, dine_in, delivery
                tableNumber: '',
                customerName: '',
                customerPhone: '',
                orderNotes: '',
                paymentMethod: 'Cash', // Cash, Card, Online
                discountType: 'none', // none, 5, 10, 15, 20, custom
                customDiscountAmount: 0,
                bagCount: 0,
                bagUnitPrice: 5.00,
                expandDiscount: false,
                expandBags: false,
                cashTendered: '',
                submitting: false,

                // Modals
                showRecentOrdersModal: false,
                showHeldOrdersModal: false,
                showSuccessModal: false,
                lastCompletedOrder: null,
                recentOrdersList: [],
                loadingRecentOrders: false,
                heldOrders: [],

                // Audio feedback (Web Audio API synthesised - zero external assets needed)
                audioCtx: null,

                init() {
                    // Load held orders from local storage
                    try {
                        const saved = localStorage.getItem('the_menu_pos_held_orders');
                        if (saved) {
                            this.heldOrders = JSON.parse(saved);
                        }
                    } catch (e) {
                        this.heldOrders = [];
                    }

                    // Keyboard shortcuts
                    window.addEventListener('keydown', (e) => {
                        // Focus search with '/' or F2
                        if ((e.key === '/' || e.key === 'F2') && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
                            e.preventDefault();
                            const searchEl = document.getElementById('posSearchInput');
                            if (searchEl) searchEl.focus();
                        }
                        // Hold order with F4
                        if (e.key === 'F4') {
                            e.preventDefault();
                            this.holdCurrentOrder();
                        }
                        // Checkout with F9 or Ctrl+Enter
                        if (e.key === 'F9' || (e.ctrlKey && e.key === 'Enter')) {
                            e.preventDefault();
                            this.processCheckout();
                        }
                        // Escape closes modals
                        if (e.key === 'Escape') {
                            this.showRecentOrdersModal = false;
                            this.showHeldOrdersModal = false;
                            this.showSuccessModal = false;
                        }
                    });
                },

                playTone(freq = 600, type = 'sine', duration = 0.05) {
                    try {
                        if (!this.audioCtx) {
                            this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        }
                        if (this.audioCtx.state === 'suspended') {
                            this.audioCtx.resume();
                        }
                        const osc = this.audioCtx.createOscillator();
                        const gain = this.audioCtx.createGain();
                        osc.type = type;
                        osc.frequency.value = freq;
                        gain.gain.setValueAtTime(0.08, this.audioCtx.currentTime);
                        gain.gain.exponentialRampToValueAtTime(0.001, this.audioCtx.currentTime + duration);
                        osc.connect(gain);
                        gain.connect(this.audioCtx.destination);
                        osc.start();
                        osc.stop(this.audioCtx.currentTime + duration);
                    } catch (e) {}
                },

                // Getters
                get filteredFoods() {
                    let list = this.foods;
                    if (this.activeCategory !== 'All') {
                        list = list.filter(f => f.category === this.activeCategory);
                    }
                    if (this.searchQuery.trim() !== '') {
                        const q = this.searchQuery.toLowerCase().trim();
                        list = list.filter(f => f.name.toLowerCase().includes(q) || (f.category && f.category.toLowerCase().includes(q)));
                    }
                    return list;
                },

                get cartCount() {
                    return this.cart.reduce((sum, item) => sum + item.quantity, 0);
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                },

                get discountAmount() {
                    if (this.discountType === 'none') return 0;
                    if (['5', '10', '15', '20'].includes(this.discountType)) {
                        const pct = Number(this.discountType);
                        return Math.round((this.subtotal * (pct / 100)) * 100) / 100;
                    }
                    if (this.discountType === 'custom') {
                        const val = Number(this.customDiscountAmount || 0);
                        return Math.min(this.subtotal, Math.max(0, val));
                    }
                    return 0;
                },

                get discountLabel() {
                    if (this.discountType === 'none') return '0%';
                    if (['5', '10', '15', '20'].includes(this.discountType)) return this.discountType + '%';
                    if (this.discountType === 'custom') return 'Custom';
                    return '';
                },

                get bagTotal() {
                    return this.bagCount * this.bagUnitPrice;
                },

                get grandTotal() {
                    return Math.max(0, Math.round(((this.subtotal - this.discountAmount) + this.bagTotal) * 100) / 100);
                },

                get changeAmount() {
                    if (this.paymentMethod !== 'Cash') return 0;
                    const given = Number(this.cashTendered || 0);
                    return Math.max(0, Math.round((given - this.grandTotal) * 100) / 100);
                },

                get isChangeDue() {
                    if (this.paymentMethod !== 'Cash') return false;
                    const given = Number(this.cashTendered || 0);
                    return given >= this.grandTotal && this.grandTotal > 0;
                },

                getItemCountInCart(foodId) {
                    const line = this.cart.find(i => i.id === foodId);
                    return line ? line.quantity : 0;
                },

                getCategoryCount(category) {
                    if (category === 'All') return this.foods.length;
                    return this.foods.filter(f => f.category === category).length;
                },

                // Cart Operations
                addToCart(food) {
                    const line = this.cart.find(item => item.id === food.id);
                    if (line) {
                        line.quantity += 1;
                    } else {
                        this.cart.push({
                            id: food.id,
                            name: food.name,
                            price: Number(food.price),
                            category: food.category,
                            quantity: 1
                        });
                    }
                    this.playTone(720, 'sine', 0.04);
                },

                increment(id) {
                    const line = this.cart.find(item => item.id === id);
                    if (line) {
                        line.quantity += 1;
                        this.playTone(600, 'sine', 0.03);
                    }
                },

                decrement(id) {
                    const line = this.cart.find(item => item.id === id);
                    if (!line) return;
                    line.quantity -= 1;
                    if (line.quantity <= 0) {
                        this.removeItem(id);
                    } else {
                        this.playTone(480, 'sine', 0.03);
                    }
                },

                removeItem(id) {
                    this.cart = this.cart.filter(item => item.id !== id);
                    this.playTone(350, 'sawtooth', 0.05);
                },

                clearCart(prompt = true) {
                    if (this.cart.length === 0 && this.bagCount === 0) return;
                    if (prompt && !confirm('Are you sure you want to clear the entire order?')) {
                        return;
                    }
                    this.cart = [];
                    this.discountType = 'none';
                    this.customDiscountAmount = 0;
                    this.expandDiscount = false;
                    this.expandBags = false;
                    this.bagCount = 0;
                    this.cashTendered = '';
                    this.tableNumber = '';
                    this.customerName = '';
                    this.customerPhone = '';
                    this.orderNotes = '';
                    this.playTone(300, 'sine', 0.06);
                },

                incrementBags() {
                    this.bagCount++;
                    this.playTone(650, 'sine', 0.03);
                },

                decrementBags() {
                    if (this.bagCount > 0) {
                        this.bagCount--;
                        this.playTone(450, 'sine', 0.03);
                    }
                },

                setBags(count) {
                    this.bagCount = Math.max(0, count);
                    this.playTone(600, 'sine', 0.03);
                },

                setExactCash() {
                    this.cashTendered = this.grandTotal;
                    this.playTone(800, 'sine', 0.03);
                },

                addTender(amount) {
                    const current = Number(this.cashTendered || 0);
                    this.cashTendered = current + amount;
                    this.playTone(850, 'sine', 0.03);
                },

                setTenderPreset(amount) {
                    this.cashTendered = amount;
                    this.playTone(850, 'sine', 0.03);
                },

                format(value) {
                    return Number(value || 0).toLocaleString('en-LK', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },

                // Hold & Resume Orders
                holdCurrentOrder() {
                    if (this.cart.length === 0) {
                        alert('Cannot hold an empty order. Please add items first.');
                        return;
                    }

                    const held = {
                        id: 'HOLD-' + Date.now(),
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        orderType: this.orderType,
                        tableNumber: this.tableNumber,
                        customerName: this.customerName,
                        customerPhone: this.customerPhone,
                        orderNotes: this.orderNotes,
                        cart: JSON.parse(JSON.stringify(this.cart)),
                        discountType: this.discountType,
                        customDiscountAmount: this.customDiscountAmount,
                        bagCount: this.bagCount,
                        total: this.grandTotal
                    };

                    this.heldOrders.push(held);
                    localStorage.setItem('the_menu_pos_held_orders', JSON.stringify(this.heldOrders));
                    this.clearCart(false);
                    this.playTone(900, 'sine', 0.08);
                    alert('Order held successfully! You can resume it anytime from the Held Orders menu.');
                },

                resumeHeldOrder(heldId) {
                    const idx = this.heldOrders.findIndex(h => h.id === heldId);
                    if (idx === -1) return;

                    if (this.cart.length > 0) {
                        if (!confirm('Resume this held order? The current active items will be replaced.')) {
                            return;
                        }
                    }

                    const held = this.heldOrders[idx];
                    this.cart = held.cart || [];
                    this.orderType = held.orderType || 'takeaway';
                    this.tableNumber = held.tableNumber || '';
                    this.customerName = held.customerName || '';
                    this.customerPhone = held.customerPhone || '';
                    this.orderNotes = held.orderNotes || '';
                    this.discountType = held.discountType || 'none';
                    this.customDiscountAmount = held.customDiscountAmount || 0;
                    this.bagCount = held.bagCount || 0;

                    // Remove from held
                    this.heldOrders.splice(idx, 1);
                    localStorage.setItem('the_menu_pos_held_orders', JSON.stringify(this.heldOrders));
                    this.showHeldOrdersModal = false;
                    this.playTone(1000, 'sine', 0.06);
                },

                deleteHeldOrder(heldId) {
                    if (!confirm('Are you sure you want to discard this held order?')) return;
                    this.heldOrders = this.heldOrders.filter(h => h.id !== heldId);
                    localStorage.setItem('the_menu_pos_held_orders', JSON.stringify(this.heldOrders));
                },

                // Recent Orders
                async openRecentOrders() {
                    this.showRecentOrdersModal = true;
                    this.loadingRecentOrders = true;
                    try {
                        const res = await fetch(this.recentOrdersUrl, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        this.recentOrdersList = data.orders || [];
                    } catch (e) {
                        this.recentOrdersList = [];
                    } finally {
                        this.loadingRecentOrders = false;
                    }
                },

                reprintReceipt(url) {
                    window.open(url, '_blank');
                },

                // Checkout Flow
                async processCheckout() {
                    if (this.cart.length === 0 || this.submitting) return;

                    // If Dine-in, recommend table number
                    if (this.orderType === 'dine_in' && !this.tableNumber.trim()) {
                        const t = prompt('Enter Table Number (or press Enter to proceed without table):');
                        if (t !== null) {
                            this.tableNumber = t.trim();
                        }
                    }

                    // Check cash tender warning
                    if (this.paymentMethod === 'Cash' && this.cashTendered !== '') {
                        if (Number(this.cashTendered) < this.grandTotal) {
                            if (!confirm(`Warning: Cash tendered (Rs. ${this.format(this.cashTendered)}) is less than total (Rs. ${this.format(this.grandTotal)}). Proceed anyway?`)) {
                                return;
                            }
                        }
                    }

                    // Open receipt window synchronously to bypass pop-up blockers
                    const receiptWindow = window.open('', '_blank');
                    this.submitting = true;

                    const payload = {
                        items: this.cart.map(i => ({ id: i.id, quantity: i.quantity })),
                        order_type: this.orderType,
                        table_number: this.tableNumber,
                        customer_name: this.customerName,
                        customer_phone: this.customerPhone,
                        notes: this.orderNotes,
                        payment_method: this.paymentMethod,
                        discount_type: this.discountType === 'custom' ? 'fixed' : (this.discountType === 'none' ? 'none' : 'percentage'),
                        discount_value: this.discountType === 'custom' ? Number(this.customDiscountAmount) : (this.discountType === 'none' ? 0 : Number(this.discountType)),
                        bag_count: this.bagCount,
                        cash_tendered: this.paymentMethod === 'Cash' && this.cashTendered !== '' ? Number(this.cashTendered) : null,
                    };

                    try {
                        const response = await fetch(this.checkoutUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Unable to complete sale transaction.');
                        }

                        // Direct the pre-opened popup to thermal receipt
                        if (receiptWindow) {
                            receiptWindow.location.href = data.receipt_url;
                        } else {
                            window.open(data.receipt_url, '_blank');
                        }

                        // Update shift metrics
                        this.shiftSales += data.total_price;
                        this.shiftOrderCount += 1;

                        // Set completed order for success modal
                        this.lastCompletedOrder = {
                            code: data.order_code,
                            total: data.total_price,
                            change: data.change_amount || 0,
                            paymentMethod: this.paymentMethod,
                            receiptUrl: data.receipt_url,
                        };

                        // Audio fanfare
                        this.playTone(880, 'sine', 0.08);
                        setTimeout(() => this.playTone(1320, 'sine', 0.12), 90);

                        this.showSuccessModal = true;
                        this.clearCart(false);

                    } catch (error) {
                        if (receiptWindow) {
                            receiptWindow.close();
                        }
                        alert('Checkout Error: ' + error.message);
                    } finally {
                        this.submitting = false;
                    }
                }
            };
        }
    </script>

    <!-- POS CONTAINER (100% HEIGHT SPLIT VIEW) -->
    <div x-data="posApp(window.posConfig)" class="h-full w-full flex flex-col lg:flex-row overflow-hidden select-none bg-[#081C19]">

        <!-- =========================================================================
             LEFT PANEL: PRODUCT CATALOG, SEARCH & CATEGORIES (~65% WIDTH)
             ========================================================================= -->
        <section class="flex-1 flex flex-col min-w-0 h-full border-r border-[#143B33] bg-[#0A231F] overflow-hidden">
            
            <!-- Top Controls: Search Bar & Quick Shift Stats -->
            <div class="shrink-0 p-4 border-b border-[#143B33] bg-[#061815] space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    
                    <!-- Search Input -->
                    <div class="relative flex-1 group">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-gray-400 group-focus-within:text-[#C9A050] transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input id="posSearchInput"
                               type="text"
                               x-model="searchQuery"
                               placeholder="Search menu items or categories... (Press '/' to focus)"
                               class="w-full pl-10 pr-9 py-2.5 bg-black/40 border border-white/15 focus:border-[#C9A050] rounded-2xl text-sm font-semibold text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-[#C9A050]/20 transition-all">
                        <button type="button"
                                x-show="searchQuery.length > 0"
                                @click="searchQuery = ''"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-white"
                                style="display: none;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Quick Shift Stats Pill -->
                    <div class="flex items-center space-x-2 shrink-0">
                        <div class="bg-black/30 border border-white/10 rounded-2xl px-3 py-1.5 flex items-center space-x-3 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">Today's Sales</span>
                                <span class="font-black text-[#C9A050]">Rs. <span x-text="format(shiftSales)"></span></span>
                            </div>
                            <div class="h-6 w-px bg-white/10"></div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold tracking-wider">Bills</span>
                                <span class="font-black text-white" x-text="shiftOrderCount">0</span>
                            </div>
                        </div>

                        <!-- Recent Bills Button -->
                        <button type="button"
                                @click="openRecentOrders()"
                                title="View recent POS bills & reprint receipt"
                                class="px-3 py-2 bg-[#0A2E2A] hover:bg-[#0f443e] border border-[#C9A050]/40 rounded-2xl text-xs font-bold text-white flex items-center space-x-1.5 transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span>Bills</span>
                        </button>

                        <!-- Held Orders Button with Badge -->
                        <button type="button"
                                @click="showHeldOrdersModal = true"
                                :class="heldOrders.length > 0 ? 'bg-amber-950/70 border-amber-500/60 text-amber-200' : 'bg-black/30 border-white/10 text-gray-400'"
                                class="px-3 py-2 border rounded-2xl text-xs font-bold flex items-center space-x-1.5 transition-all cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>Held (<span x-text="heldOrders.length">0</span>)</span>
                        </button>
                    </div>

                </div>

                <!-- Category Filter Pills (Horizontal Scrollable) -->
                <div class="flex items-center space-x-2 overflow-x-auto pos-scrollbar pb-1 pt-0.5">
                    <button type="button"
                            @click="activeCategory = 'All'"
                            :class="activeCategory === 'All' ? 'bg-[#C9A050] text-[#051411] font-black shadow-md shadow-[#C9A050]/20' : 'bg-white/5 hover:bg-white/10 text-gray-300 font-bold border border-white/10'"
                            class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all duration-150 cursor-pointer flex items-center space-x-1.5 shrink-0">
                        <span>All Items</span>
                        <span class="text-[10px] px-1.5 py-0.5 rounded-full"
                              :class="activeCategory === 'All' ? 'bg-[#051411]/20 text-[#051411]' : 'bg-black/40 text-gray-400'"
                              x-text="getCategoryCount('All')"></span>
                    </button>

                    <template x-for="cat in categories" :key="cat">
                        <button type="button"
                                @click="activeCategory = cat"
                                :class="activeCategory === cat ? 'bg-[#C9A050] text-[#051411] font-black shadow-md shadow-[#C9A050]/20' : 'bg-white/5 hover:bg-white/10 text-gray-300 font-bold border border-white/10'"
                                class="px-4 py-2 rounded-xl text-xs whitespace-nowrap transition-all duration-150 cursor-pointer flex items-center space-x-1.5 shrink-0">
                            <span x-text="cat"></span>
                            <span class="text-[10px] px-1.5 py-0.5 rounded-full"
                                  :class="activeCategory === cat ? 'bg-[#051411]/20 text-[#051411]' : 'bg-black/40 text-gray-400'"
                                  x-text="getCategoryCount(cat)"></span>
                        </button>
                    </template>
                </div>
            </div>

            <!-- Scrollable Items Grid -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 pos-scrollbar">
                
                <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-3.5 sm:gap-4">
                    <template x-for="food in filteredFoods" :key="food.id">
                        <div @click="addToCart(food)"
                             class="group relative bg-[#061815] hover:bg-[#09221e] active:scale-[0.98] border border-white/10 hover:border-[#C9A050]/50 rounded-2xl overflow-hidden cursor-pointer transition-all duration-150 shadow-sm flex flex-col">
                            
                            <!-- Food Photo / Graphic Thumbnail -->
                            <div class="relative w-full h-28 sm:h-32 bg-black/40 overflow-hidden">
                                <img :src="food.image" 
                                     :alt="food.name" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300 filter brightness-95" 
                                     loading="lazy"
                                     onerror="this.src='{{ asset('images/logo.svg') }}'; this.className='w-full h-full object-contain p-4 filter opacity-40';">
                                
                                <!-- Category Pill -->
                                <span class="absolute top-2 left-2 bg-black/70 backdrop-blur-sm text-[10px] font-bold text-gray-300 uppercase px-2 py-0.5 rounded-lg border border-white/10"
                                      x-text="food.category"></span>

                                <!-- In-Cart Quantity Indicator Badge -->
                                <template x-if="getItemCountInCart(food.id) > 0">
                                    <div class="absolute top-2 right-2 bg-[#C9A050] text-[#051411] font-black text-xs px-2 py-0.5 rounded-full shadow-lg border border-white/20 animate-bounce">
                                        <span x-text="getItemCountInCart(food.id)"></span> in order
                                    </div>
                                </template>
                            </div>

                            <!-- Card Body -->
                            <div class="p-3 sm:p-3.5 flex flex-col flex-1">
                                <h3 class="text-xs sm:text-sm font-bold text-white leading-tight line-clamp-2" x-text="food.name"></h3>
                                
                                <div class="mt-auto pt-2.5 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-gray-400 font-bold block">Rs.</span>
                                        <span class="text-sm sm:text-base font-black text-[#C9A050]" x-text="format(food.price)"></span>
                                    </div>

                                    <!-- Quick Add Button -->
                                    <button type="button" 
                                            class="w-8 h-8 rounded-xl bg-white/10 group-hover:bg-[#C9A050] group-hover:text-[#051411] text-white flex items-center justify-center transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Empty State -->
                <div x-show="filteredFoods.length === 0" class="py-16 text-center" style="display: none;">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-gray-400">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <p class="text-base font-bold text-white">No items found</p>
                    <p class="text-xs text-gray-400 mt-1">Try searching for a different keyword or select "All Items".</p>
                </div>

            </div>
        </section>

        <!-- =========================================================================
             RIGHT PANEL: ORDER SUMMARY, CART & TENDER CHECKOUT (~35% WIDTH)
             ========================================================================= -->
        <aside class="w-full lg:w-[420px] xl:w-[460px] 2xl:w-[500px] shrink-0 h-full flex flex-col bg-[#051411] border-t lg:border-t-0 border-[#143B33] overflow-hidden">
            
            <!-- Order Header: Mode (Dine-in / Takeaway / Delivery) -->
            <div class="shrink-0 p-3.5 bg-[#061815] border-b border-[#143B33] space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-xs font-black uppercase tracking-wider text-gray-400">Order Mode:</span>
                        <div class="inline-flex rounded-xl p-0.5 bg-black/40 border border-white/10">
                            <button type="button"
                                    @click="orderType = 'takeaway'"
                                    :class="orderType === 'takeaway' ? 'bg-[#C9A050] text-[#051411] font-black' : 'text-gray-400 hover:text-white font-bold'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition-colors cursor-pointer">
                                Takeaway
                            </button>
                            <button type="button"
                                    @click="orderType = 'dine_in'"
                                    :class="orderType === 'dine_in' ? 'bg-[#C9A050] text-[#051411] font-black' : 'text-gray-400 hover:text-white font-bold'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition-colors cursor-pointer">
                                Dine-In
                            </button>
                            <button type="button"
                                    @click="orderType = 'delivery'"
                                    :class="orderType === 'delivery' ? 'bg-[#C9A050] text-[#051411] font-black' : 'text-gray-400 hover:text-white font-bold'"
                                    class="px-2.5 py-1 rounded-lg text-xs transition-colors cursor-pointer">
                                Delivery
                            </button>
                        </div>
                    </div>

                    <!-- Cart Actions -->
                    <div class="flex items-center space-x-1.5">
                        <button type="button"
                                @click="holdCurrentOrder()"
                                :disabled="cart.length === 0"
                                title="Park this order to serve another customer"
                                class="px-2 py-1 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 text-[11px] font-bold disabled:opacity-30 cursor-pointer">
                            Hold
                        </button>
                        <button type="button"
                                @click="clearCart(true)"
                                :disabled="cart.length === 0"
                                title="Clear current cart"
                                class="px-2 py-1 rounded-lg bg-rose-950/50 hover:bg-rose-900 border border-rose-500/30 text-rose-300 text-[11px] font-bold disabled:opacity-30 cursor-pointer">
                            Clear
                        </button>
                    </div>
                </div>

                <!-- Contextual Inputs (Table number for Dine-In, or Customer name) -->
                <div x-show="orderType === 'dine_in'" class="flex items-center space-x-2 pt-1" style="display: none;">
                    <span class="text-xs font-bold text-gray-400 shrink-0">Table:</span>
                    <input type="text" 
                           x-model="tableNumber" 
                           placeholder="Table # (e.g. 04)" 
                           class="w-24 px-2.5 py-1 bg-black/40 border border-white/15 focus:border-[#C9A050] rounded-xl text-xs text-white placeholder-gray-500 focus:outline-none">
                    
                    <!-- Quick Table Selector Pills -->
                    <div class="flex items-center space-x-1 overflow-x-auto pos-scrollbar">
                        <template x-for="t in ['T1', 'T2', 'T3', 'T4', 'T5', 'T6']" :key="t">
                            <button type="button" 
                                    @click="tableNumber = t"
                                    :class="tableNumber === t ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'"
                                    class="px-2 py-1 rounded-lg text-[10px] font-bold border border-white/10 shrink-0"
                                    x-text="t"></button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Scrollable Cart Line Items -->
            <div class="flex-1 overflow-y-auto divide-y divide-white/5 p-3 space-y-2 pos-scrollbar min-h-0">
                <template x-for="item in cart" :key="item.id">
                    <div class="py-2 px-2.5 bg-white/5 hover:bg-white/[0.08] rounded-2xl transition-colors border border-white/5">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0 flex-1">
                                <p class="text-xs sm:text-sm font-bold text-white leading-snug truncate" x-text="item.name"></p>
                                <p class="text-[11px] text-gray-400 font-medium">
                                    Rs. <span x-text="format(item.price)"></span> each
                                </p>
                            </div>

                            <button type="button"
                                    @click="removeItem(item.id)"
                                    class="p-1 text-gray-500 hover:text-rose-400 transition-colors cursor-pointer"
                                    title="Remove item">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <!-- Stepper & Line Total -->
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center space-x-1 bg-black/40 border border-white/10 rounded-xl p-0.5">
                                <button type="button"
                                        @click="decrement(item.id)"
                                        class="w-6 h-6 rounded-lg bg-white/5 hover:bg-white/15 text-white font-black text-xs flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                                    &minus;
                                </button>
                                <span class="w-7 text-center text-xs font-black text-white" x-text="item.quantity"></span>
                                <button type="button"
                                        @click="increment(item.id)"
                                        class="w-6 h-6 rounded-lg bg-[#C9A050] text-[#051411] font-black text-xs flex items-center justify-center active:scale-95 transition-all cursor-pointer">
                                    +
                                </button>
                            </div>

                            <div class="text-right">
                                <span class="text-xs sm:text-sm font-black text-white">
                                    Rs. <span x-text="format(item.price * item.quantity)"></span>
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty Cart State -->
                <div x-show="cart.length === 0" class="h-full min-h-[180px] flex flex-col items-center justify-center text-center p-6 text-gray-500">
                    <svg class="w-12 h-12 mb-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <p class="text-sm font-bold text-gray-400">Order is empty</p>
                    <p class="text-xs text-gray-600 mt-1 max-w-[200px]">Tap or click any menu item from the catalog to build this bill.</p>
                </div>
            </div>

            <!-- Bottom Checkout Section: Totals, Discounts, Tender & Action -->
            <div class="shrink-0 p-3.5 sm:p-4 bg-[#061815] border-t border-[#143B33] space-y-3">
                
                <!-- Financial Breakdown -->
                <div class="space-y-1 text-xs text-gray-400">
                    <div class="flex justify-between items-center">
                        <span>Subtotal (<span x-text="cartCount"></span> items):</span>
                        <span class="text-white font-bold">Rs. <span x-text="format(subtotal)"></span></span>
                    </div>

                    <!-- Discount summary line (Visible only when discount > 0) -->
                    <div class="flex justify-between items-center text-[#C9A050] font-medium" x-show="discountAmount > 0" style="display: none;">
                        <span>Discount (<span x-text="discountLabel"></span>):</span>
                        <span class="font-bold">- Rs. <span x-text="format(discountAmount)"></span></span>
                    </div>

                    <!-- Polythene Bags Charge Line (Visible only when bagCount > 0) -->
                    <div class="flex justify-between items-center text-emerald-400 font-medium" x-show="bagCount > 0" style="display: none;">
                        <span>Polythene Bags (<span x-text="bagCount"></span> &times; Rs. 5.00):</span>
                        <span class="font-bold">+ Rs. <span x-text="format(bagTotal)"></span></span>
                    </div>
                </div>

                <!-- Option 3: Collapsible Secondary Controls Toolbar (Discount & Bags Chips) -->
                <div class="space-y-1.5">
                    <div class="grid grid-cols-2 gap-2">
                        <!-- Discount Chip Button -->
                        <button type="button"
                                @click="expandDiscount = !expandDiscount; if(expandDiscount) expandBags = false;"
                                :class="discountAmount > 0 
                                    ? 'bg-[#C9A050]/20 border-[#C9A050] text-[#C9A050] font-black shadow-sm' 
                                    : (expandDiscount ? 'bg-white/10 border-white/40 text-white font-bold' : 'bg-black/40 border-white/10 text-gray-300 hover:text-white hover:bg-white/5 font-semibold')"
                                class="flex items-center justify-between px-2.5 py-1.5 rounded-xl border text-[11px] transition-all cursor-pointer select-none">
                            <div class="flex items-center space-x-1.5 min-w-0">
                                <span class="w-4 h-4 rounded-md flex items-center justify-center text-[10px] font-black shrink-0"
                                      :class="discountAmount > 0 ? 'bg-[#C9A050] text-[#051411]' : 'bg-white/10 text-gray-300'">%</span>
                                <span class="truncate">
                                    <template x-if="discountAmount === 0"><span>Discount (0%)</span></template>
                                    <template x-if="discountAmount > 0"><span x-text="'-Rs. ' + format(discountAmount)"></span></template>
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 shrink-0 ml-1"
                                 :class="expandDiscount ? 'rotate-180 text-white' : 'text-gray-400'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <!-- Polythene Bags Chip Button -->
                        <button type="button"
                                @click="expandBags = !expandBags; if(expandBags) expandDiscount = false;"
                                :class="bagCount > 0 
                                    ? 'bg-emerald-950/70 border-emerald-500 text-emerald-300 font-black shadow-sm' 
                                    : (expandBags ? 'bg-white/10 border-white/40 text-white font-bold' : 'bg-black/40 border-white/10 text-gray-300 hover:text-white hover:bg-white/5 font-semibold')"
                                class="flex items-center justify-between px-2.5 py-1.5 rounded-xl border text-[11px] transition-all cursor-pointer select-none">
                            <div class="flex items-center space-x-1.5 min-w-0">
                                <span class="text-xs shrink-0">🛍️</span>
                                <span class="truncate">
                                    <template x-if="bagCount === 0"><span>Bags (0)</span></template>
                                    <template x-if="bagCount > 0"><span x-text="bagCount + (bagCount > 1 ? ' Bags' : ' Bag') + ' (+Rs.' + format(bagTotal) + ')'"></span></template>
                                </span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 shrink-0 ml-1"
                                 :class="expandBags ? 'rotate-180 text-white' : 'text-gray-400'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Collapsible Discount Drawer -->
                    <div x-show="expandDiscount"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
                         class="p-2.5 bg-black/50 border border-[#C9A050]/40 rounded-2xl space-y-2"
                         style="display: none;">
                        <div class="flex items-center justify-between text-[11px]">
                            <span class="font-bold text-gray-300">Select Discount:</span>
                            <button type="button" @click="expandDiscount = false" class="text-xs text-[#C9A050] hover:underline font-bold">Done ✕</button>
                        </div>
                        <div class="flex items-center space-x-1">
                            <button type="button" @click="discountType = 'none'" :class="discountType === 'none' ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1 rounded-lg text-[10px] border border-white/10 transition-colors">0%</button>
                            <button type="button" @click="discountType = '5'" :class="discountType === '5' ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1 rounded-lg text-[10px] border border-white/10 transition-colors">5%</button>
                            <button type="button" @click="discountType = '10'" :class="discountType === '10' ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1 rounded-lg text-[10px] border border-white/10 transition-colors">10%</button>
                            <button type="button" @click="discountType = '15'" :class="discountType === '15' ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1 rounded-lg text-[10px] border border-white/10 transition-colors">15%</button>
                            <button type="button" @click="discountType = 'custom'" :class="discountType === 'custom' ? 'bg-[#C9A050] text-[#051411] font-black' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1 rounded-lg text-[10px] border border-white/10 transition-colors">Custom</button>
                        </div>
                        <div x-show="discountType === 'custom'" class="pt-1">
                            <input type="number" 
                                   x-model="customDiscountAmount" 
                                   placeholder="Custom discount amount (Rs.)"
                                   class="w-full px-2.5 py-1.5 bg-black/60 border border-[#C9A050]/50 focus:border-[#C9A050] rounded-xl text-xs text-white placeholder-gray-500 focus:outline-none">
                        </div>
                    </div>

                    <!-- Collapsible Polythene Bags Drawer -->
                    <div x-show="expandBags"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
                         class="p-2.5 bg-black/50 border border-emerald-500/40 rounded-2xl space-y-2"
                         style="display: none;">
                        <div class="flex items-center justify-between text-[11px]">
                            <div class="flex items-center space-x-1.5">
                                <span class="font-bold text-gray-200">Polythene Bags</span>
                                <span class="text-[10px] text-emerald-400 font-semibold">(Rs. 5.00 each)</span>
                            </div>
                            <button type="button" @click="expandBags = false" class="text-xs text-emerald-400 hover:underline font-bold">Done ✕</button>
                        </div>
                        <div class="flex items-center space-x-2">
                            <!-- Stepper -->
                            <div class="flex items-center space-x-1 bg-black/60 border border-white/10 rounded-xl p-0.5 shrink-0">
                                <button type="button"
                                        @click="decrementBags()"
                                        :disabled="bagCount <= 0"
                                        class="w-7 h-7 rounded-lg bg-white/5 hover:bg-white/15 text-white font-black text-xs flex items-center justify-center disabled:opacity-30 active:scale-95 transition-all cursor-pointer"
                                        title="Decrease bags">
                                    &minus;
                                </button>
                                <span class="w-8 text-center text-xs font-black text-white" x-text="bagCount">0</span>
                                <button type="button"
                                        @click="incrementBags()"
                                        class="w-7 h-7 rounded-lg bg-emerald-500 hover:bg-emerald-400 text-[#051411] font-black text-xs flex items-center justify-center active:scale-95 transition-all cursor-pointer"
                                        title="Add polythene bag (+Rs. 5)">
                                    +
                                </button>
                            </div>

                            <!-- Quick Preset Count Buttons -->
                            <div class="flex items-center space-x-1 flex-1">
                                <button type="button" @click="setBags(0)" :class="bagCount === 0 ? 'bg-white/20 text-white font-bold' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg text-[10px] border border-white/10 transition-colors cursor-pointer">0</button>
                                <button type="button" @click="setBags(1)" :class="bagCount === 1 ? 'bg-emerald-500 text-[#051411] font-bold' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg text-[10px] border border-white/10 transition-colors cursor-pointer">1</button>
                                <button type="button" @click="setBags(2)" :class="bagCount === 2 ? 'bg-emerald-500 text-[#051411] font-bold' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg text-[10px] border border-white/10 transition-colors cursor-pointer">2</button>
                                <button type="button" @click="setBags(3)" :class="bagCount === 3 ? 'bg-emerald-500 text-[#051411] font-bold' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg text-[10px] border border-white/10 transition-colors cursor-pointer">3</button>
                                <button type="button" @click="setBags(5)" :class="bagCount === 5 ? 'bg-emerald-500 text-[#051411] font-bold' : 'bg-white/5 text-gray-400 hover:text-white'" class="flex-1 py-1.5 rounded-lg text-[10px] border border-white/10 transition-colors cursor-pointer">5</button>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Grand Total Line -->
                    <div class="pt-2 border-t border-white/10 flex items-baseline justify-between">
                        <span class="text-sm font-black uppercase text-white tracking-wider">Net Total</span>
                        <span class="text-2xl sm:text-3xl font-black text-[#C9A050] tracking-tight">
                            <span class="text-xs font-bold text-gray-400">Rs.</span>
                            <span x-text="format(grandTotal)"></span>
                        </span>
                    </div>

                <!-- Payment Method Tabs -->
                <div class="space-y-2 pt-1">
                    <div class="grid grid-cols-3 gap-1.5 p-1 bg-black/40 border border-white/10 rounded-2xl text-xs font-bold">
                        <button type="button" 
                                @click="paymentMethod = 'Cash'"
                                :class="paymentMethod === 'Cash' ? 'bg-[#C9A050] text-[#051411] shadow' : 'text-gray-400 hover:text-white'"
                                class="py-2 rounded-xl text-center transition-colors cursor-pointer flex items-center justify-center space-x-1">
                            <span>Cash</span>
                        </button>
                        <button type="button" 
                                @click="paymentMethod = 'Card'"
                                :class="paymentMethod === 'Card' ? 'bg-[#C9A050] text-[#051411] shadow' : 'text-gray-400 hover:text-white'"
                                class="py-2 rounded-xl text-center transition-colors cursor-pointer flex items-center justify-center space-x-1">
                            <span>Card</span>
                        </button>
                        <button type="button" 
                                @click="paymentMethod = 'Online'"
                                :class="paymentMethod === 'Online' ? 'bg-[#C9A050] text-[#051411] shadow' : 'text-gray-400 hover:text-white'"
                                class="py-2 rounded-xl text-center transition-colors cursor-pointer flex items-center justify-center space-x-1">
                            <span>QR / Online</span>
                        </button>
                    </div>

                    <!-- Cash Quick Tender & Change (Visible only on Cash) -->
                    <div x-show="paymentMethod === 'Cash'" class="space-y-2 pt-1">
                        <div class="flex items-center space-x-1.5">
                            <button type="button" @click="setExactCash()" class="px-2.5 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-white font-bold text-[11px] border border-white/10 shrink-0">Exact</button>
                            <button type="button" @click="setTenderPreset(500)" class="flex-1 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-white font-bold text-[11px] border border-white/10">500</button>
                            <button type="button" @click="setTenderPreset(1000)" class="flex-1 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-white font-bold text-[11px] border border-white/10">1,000</button>
                            <button type="button" @click="setTenderPreset(2000)" class="flex-1 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-white font-bold text-[11px] border border-white/10">2,000</button>
                            <button type="button" @click="setTenderPreset(5000)" class="flex-1 py-1.5 rounded-xl bg-white/5 hover:bg-white/15 text-white font-bold text-[11px] border border-white/10">5,000</button>
                        </div>

                        <div class="flex items-center space-x-2">
                            <div class="relative flex-1">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-xs font-bold text-gray-500">Rs.</span>
                                <input type="number"
                                       x-model="cashTendered"
                                       placeholder="Cash Given"
                                       class="w-full pl-9 pr-3 py-2 bg-black/40 border border-white/15 focus:border-[#C9A050] rounded-xl text-xs sm:text-sm font-bold text-white placeholder-gray-500 focus:outline-none">
                            </div>

                            <div class="px-3 py-2 rounded-xl text-xs font-black min-w-[130px] text-right"
                                 :class="isChangeDue ? 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-300' : 'bg-black/30 border border-white/10 text-gray-400'">
                                <span class="text-[10px] uppercase font-bold block" :class="isChangeDue ? 'text-emerald-400' : 'text-gray-500'">Change Return</span>
                                <span>Rs. <span x-text="format(changeAmount)"></span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Primary Checkout Button -->
                <button type="button"
                        @click="processCheckout()"
                        :disabled="cart.length === 0 || submitting"
                        class="w-full py-4 px-6 rounded-2xl bg-gradient-to-r from-[#C9A050] to-[#b3893c] hover:brightness-105 active:scale-[0.99] text-[#051411] text-base sm:text-lg font-black shadow-xl shadow-[#C9A050]/20 disabled:opacity-40 disabled:cursor-not-allowed transition-all flex items-center justify-center space-x-2.5 cursor-pointer">
                    <svg x-show="submitting" class="w-5 h-5 animate-spin text-[#051411]" fill="none" viewBox="0 0 24 24" style="display: none;">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span x-text="submitting ? 'Processing Sale & Bill...' : 'Checkout & Print Bill (F9)'"></span>
                    <svg x-show="!submitting" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                </button>

            </div>

        </aside>

        <!-- =========================================================================
             MODAL: RECENT ORDERS & REPRINT RECEIPT
             ========================================================================= -->
        <div x-show="showRecentOrdersModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
             @click.self="showRecentOrdersModal = false">
            <div class="w-full max-w-2xl bg-[#061815] border border-white/20 rounded-3xl p-6 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050]">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-white">Recent POS Bills</h2>
                            <p class="text-xs text-gray-400">View and reprint thermal receipts from this terminal</p>
                        </div>
                    </div>
                    <button type="button" @click="showRecentOrdersModal = false" class="p-2 text-gray-400 hover:text-white rounded-xl bg-white/5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto py-4 divide-y divide-white/10 pos-scrollbar">
                    <template x-if="loadingRecentOrders">
                        <div class="py-12 text-center text-gray-400">
                            <svg class="w-8 h-8 animate-spin mx-auto mb-2 text-[#C9A050]" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                            <p class="text-xs">Loading orders...</p>
                        </div>
                    </template>

                    <template x-for="ord in recentOrdersList" :key="ord.id">
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-white/5 px-2 rounded-2xl transition-colors">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-black text-white" x-text="ord.order_code"></span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-lg bg-black/40 text-gray-300 border border-white/10" x-text="ord.type_label"></span>
                                    <template x-if="ord.table_number">
                                        <span class="text-[10px] px-2 py-0.5 rounded-lg bg-[#0A2E2A] text-[#C9A050] font-bold" x-text="'Table ' + ord.table_number"></span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    <span x-text="ord.time"></span> • <span x-text="ord.items_count"></span> items • <span x-text="ord.payment_method"></span>
                                </p>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="text-sm font-black text-[#C9A050]">Rs. <span x-text="format(ord.total)"></span></span>
                                <button type="button"
                                        @click="reprintReceipt(ord.receipt_url)"
                                        class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-[#C9A050] hover:text-[#051411] text-white text-xs font-bold transition-all flex items-center space-x-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                    <span>Print Bill</span>
                                </button>
                            </div>
                        </div>
                    </template>

                    <template x-if="!loadingRecentOrders && recentOrdersList.length === 0">
                        <div class="py-12 text-center text-gray-400">
                            <p class="text-sm font-bold">No recent POS orders found today</p>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             MODAL: HELD ORDERS (PARKED BILLS)
             ========================================================================= -->
        <div x-show="showHeldOrdersModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fade-in"
             @click.self="showHeldOrdersModal = false">
            <div class="w-full max-w-lg bg-[#061815] border border-white/20 rounded-3xl p-6 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div class="flex items-center space-x-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-amber-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-white">Parked / Held Orders</h2>
                            <p class="text-xs text-gray-400">Resume an order to continue checkout</p>
                        </div>
                    </div>
                    <button type="button" @click="showHeldOrdersModal = false" class="p-2 text-gray-400 hover:text-white rounded-xl bg-white/5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto py-4 divide-y divide-white/10 pos-scrollbar">
                    <template x-for="held in heldOrders" :key="held.id">
                        <div class="py-3 flex items-center justify-between gap-3 hover:bg-white/5 px-2 rounded-2xl transition-colors">
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-xs font-black text-white" x-text="'Parked at ' + held.time"></span>
                                    <span class="text-[10px] px-2 py-0.5 rounded-lg bg-black/40 text-amber-300 border border-amber-500/30 uppercase" x-text="held.orderType"></span>
                                    <template x-if="held.tableNumber">
                                        <span class="text-[10px] px-2 py-0.5 rounded-lg bg-[#0A2E2A] text-[#C9A050] font-bold" x-text="'Table ' + held.tableNumber"></span>
                                    </template>
                                </div>
                                <p class="text-xs text-gray-400 mt-1">
                                    <span x-text="(held.cart ? held.cart.length : 0) + ' items'"></span> • Total: Rs. <span x-text="format(held.total)"></span>
                                </p>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button type="button"
                                        @click="resumeHeldOrder(held.id)"
                                        class="px-3 py-1.5 rounded-xl bg-[#C9A050] text-[#051411] text-xs font-black hover:brightness-110 active:scale-95 transition-all">
                                    Resume
                                </button>
                                <button type="button"
                                        @click="deleteHeldOrder(held.id)"
                                        class="p-1.5 text-gray-400 hover:text-rose-400 rounded-xl transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                        </div>
                    </template>

                    <div x-show="heldOrders.length === 0" class="py-12 text-center text-gray-500">
                        <p class="text-sm font-bold">No orders currently on hold.</p>
                        <p class="text-xs text-gray-600 mt-1">Click "Hold" in the order summary to park an order anytime.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             MODAL: SALE SUCCESS & CASH CHANGE POPUP
             ========================================================================= -->
        <div x-show="showSuccessModal" 
             x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md animate-fade-in"
             @click.self="showSuccessModal = false">
            <div class="w-full max-w-md bg-[#061815] border border-emerald-500/40 rounded-3xl p-6 sm:p-8 shadow-2xl text-center flex flex-col items-center">
                
                <!-- Success Icon -->
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/50 flex items-center justify-center text-emerald-400 mb-4 shadow-lg shadow-emerald-500/20">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <h2 class="text-2xl font-black text-white">Sale Completed!</h2>
                <p class="text-xs text-gray-300 mt-1 font-medium">
                    Order Ref: <span class="font-bold text-[#C9A050]" x-text="lastCompletedOrder?.code"></span>
                </p>

                <!-- Change Return Highlight -->
                <template x-if="lastCompletedOrder?.change > 0">
                    <div class="w-full my-5 p-4 rounded-2xl bg-emerald-950/70 border border-emerald-500/40 text-center">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 block mb-1">Change to Return</span>
                        <span class="text-3xl font-black text-white">
                            Rs. <span x-text="format(lastCompletedOrder?.change)"></span>
                        </span>
                    </div>
                </template>

                <div class="w-full mt-4 space-y-2.5">
                    <button type="button"
                            @click="showSuccessModal = false"
                            class="w-full py-3.5 px-6 rounded-2xl bg-[#C9A050] hover:bg-[#d8ae5c] text-[#051411] font-black text-sm active:scale-[0.98] transition-all cursor-pointer">
                        Start Next Order
                    </button>

                    <button type="button"
                            @click="reprintReceipt(lastCompletedOrder?.receiptUrl)"
                            class="w-full py-3 px-6 rounded-2xl bg-white/10 hover:bg-white/15 text-white font-bold text-xs border border-white/15 transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        <span>Print Receipt Again</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</x-pos-layout>
