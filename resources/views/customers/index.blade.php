<x-app-layout title="Customers Management">
    <div class="space-y-6" x-data="{
        detailModalOpen: false,
        activeCustomer: null,
        openDetailModal(customerData) {
            this.activeCustomer = customerData;
            this.detailModalOpen = true;
        },
        closeDetailModal() {
            this.detailModalOpen = false;
            this.activeCustomer = null;
        }
    }">

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 text-emerald-800 px-6 py-4 rounded-2xl text-sm font-semibold shadow-xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        @if (session('warning'))
            <div class="flex items-center justify-between bg-amber-50 border border-amber-200 text-amber-800 px-6 py-4 rounded-2xl text-sm font-semibold shadow-xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>{{ session('warning') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-amber-500 hover:text-amber-700 text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                    <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="text-[#0A2E2A] font-black">Client Relations</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Customers Management</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Directory of registered clientele, delivery coordinates, purchase volumes, and loyalty metrics</p>
            </div>
            
            <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
                <a href="{{ route('orders') }}" class="px-5 py-3.5 bg-white border border-gray-200 hover:border-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-bold flex items-center space-x-2 shadow-xs transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span>View Orders</span>
                </a>
                <a href="{{ route('transactions.index') }}" class="px-5 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                    <span>View Transactions</span>
                </a>
            </div>
        </div>

        <!-- KPI Metric Cards (rounded-[2.5rem]) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Registered Customers -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] rounded-2xl flex items-center justify-center shadow-xs">
                        <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Patrons</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ number_format($stats['total_customers']) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Total Registered Accounts</p>
            </div>

            <!-- Active Diners -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Active</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ number_format($stats['active_customers']) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Patrons With Meal Orders</p>
            </div>

            <!-- Recurring Diners -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200">Loyalty</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ number_format($stats['recurring_customers']) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Multi-Order Recurring Diners</p>
            </div>

            <!-- Total Customer Spend Volume -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">Total LTV</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Rs. {{ number_format($stats['total_customer_spend'], 2) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Avg. Rs. {{ number_format($stats['avg_spend_per_customer'], 2) }} / client</p>
            </div>
        </div>

        <!-- Customers Directory Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <!-- Header & Filter Bar -->
            <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col gap-5 bg-white/40">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 tracking-tight">Registered Clients Directory</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Profile identities, verified contact numbers, saved addresses, and meal history</p>
                    </div>
                    
                    <!-- Search Input -->
                    <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-80">
                        <input type="hidden" name="filter" value="{{ $filter }}">
                        @if ($roleFilter)
                            <input type="hidden" name="role" value="{{ $roleFilter }}">
                        @endif
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search customer, phone, email..."
                               @input.debounce.1500ms="$el.form.submit()"
                               class="w-full pl-11 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <!-- Segment Filter Pills -->
                <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-100/80">
                    <a href="{{ route('customers.index', array_merge(request()->query(), ['filter' => 'all'])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $filter === 'all' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80' }}">
                        All Patrons ({{ $stats['total_customers'] }})
                    </a>

                    <a href="{{ route('customers.index', array_merge(request()->query(), ['filter' => 'active'])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $filter === 'active' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80' }}">
                        Active Diners ({{ $stats['active_customers'] }})
                    </a>

                    <a href="{{ route('customers.index', array_merge(request()->query(), ['filter' => 'recurring'])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $filter === 'recurring' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80' }}">
                        Recurring / VIP ({{ $stats['recurring_customers'] }})
                    </a>

                    <a href="{{ route('customers.index', array_merge(request()->query(), ['filter' => 'new'])) }}"
                       class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $filter === 'new' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/20' : 'bg-gray-100 text-gray-600 hover:bg-gray-200/80' }}">
                        ✨ Joined Recently
                    </a>

                    @if ($search || $filter !== 'all' || $roleFilter)
                        <a href="{{ route('customers.index') }}" class="ml-auto text-xs font-bold text-gray-400 hover:text-red-600 transition-colors inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            <span>Reset Filters</span>
                        </a>
                    @endif
                </div>
            </div>

            <!-- Spacious Luxury Table (min-w-[1000px], py-6 px-6) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[260px]">Customer</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Contact</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[240px]">Addresses</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[160px]">Orders & Spend</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Latest Order</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[140px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($customers as $customer)
                            @php
                                $customerName = $customer->name;
                                $customerPhone = $customer->phone;
                                $customerEmail = $customer->email;
                                $latestOrder = $customer->orders->first();
                                $totalSpent = $customer->total_spent ?? 0;
                                $isVip = $customer->orders_count >= 3;

                                $modalData = [
                                    'id'            => $customer->id,
                                    'name'          => $customerName,
                                    'email'         => $customerEmail,
                                    'phone'         => $customerPhone ?: 'Not provided',
                                    'role'          => $customer->role ?? 'Customer',
                                    'address'       => $customer->address ?: 'Not provided',
                                    'dinner_address'=> $customer->dinner_address ?: 'Not provided',
                                    'orders_count'  => $customer->orders_count,
                                    'total_spent'   => 'Rs. ' . number_format($totalSpent, 2),
                                    'joined_date'   => $customer->created_at ? $customer->created_at->format('M d, Y') : '—',
                                    'latest_order'  => $latestOrder ? ('#ORD-' . str_pad($latestOrder->id, 4, '0', STR_PAD_LEFT) . ' (' . ucfirst($latestOrder->status) . ')') : 'No orders yet',
                                ];
                            @endphp
                            <tr id="customer-row-{{ $customer->id }}" class="border-b border-gray-100/80 hover:bg-[#C9A050]/[0.03] transition-all duration-300 group">
                                <!-- Customer Profile -->
                                <td class="py-6 px-6 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($customerName) }}&background=0A2E2A&color=C9A050&bold=true" 
                                             class="w-12 h-12 rounded-2xl shadow-xs shrink-0 ring-1 ring-gray-100 group-hover:ring-[#C9A050]/40 transition-all duration-200" 
                                             alt="{{ $customerName }}">
                                        <div class="flex flex-col gap-1 min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-sm font-black text-gray-900 truncate tracking-tight group-hover:text-[#C9A050] transition-colors">{{ $customerName }}</p>
                                                @if ($isVip)
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                                        ★ VIP
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="text-xs text-gray-400 truncate">{{ $customerEmail }}</p>
                                            <span class="text-[10px] text-gray-400 font-semibold tracking-wide">
                                                Joined {{ $customer->created_at ? $customer->created_at->format('M d, Y') : '—' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact Information -->
                                <td class="py-6 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        @if ($customerPhone)
                                            <a href="tel:{{ $customerPhone }}" class="text-xs text-[#C9A050] hover:text-[#b88f3f] hover:underline font-bold inline-flex items-center gap-1.5">
                                                <span>📞</span>
                                                <span>{{ $customerPhone }}</span>
                                            </a>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No phone provided</span>
                                        @endif
                                        <div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-black bg-gray-100 text-gray-600 border border-gray-200/80">
                                                {{ $customer->role ?? 'Customer' }}
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Delivery Coordinates -->
                                <td class="py-6 px-6 align-middle">
                                    <div class="flex flex-col gap-2 max-w-xs">
                                        @if ($customer->address)
                                            <div class="flex items-start gap-1.5">
                                                <span class="text-xs shrink-0 mt-0.5">📍</span>
                                                <span class="text-xs text-gray-800 font-medium line-clamp-1" title="{{ $customer->address }}">
                                                    {{ $customer->address }}
                                                </span>
                                            </div>
                                        @endif

                                        @if ($customer->dinner_address)
                                            <div class="flex items-start gap-1.5">
                                                <span class="text-xs shrink-0 mt-0.5">🌙</span>
                                                <span class="text-xs text-gray-600 font-medium line-clamp-1" title="{{ $customer->dinner_address }}">
                                                    {{ $customer->dinner_address }}
                                                </span>
                                            </div>
                                        @endif

                                        @if (!$customer->address && !$customer->dinner_address)
                                            <span class="text-xs text-gray-400 italic">No delivery address saved</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Orders & Spend Volume -->
                                <td class="py-6 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gray-50 border border-gray-200/80 shadow-2xs w-fit">
                                            <span class="text-xs font-black text-gray-900">{{ $customer->orders_count }}</span>
                                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">{{ $customer->orders_count === 1 ? 'order' : 'orders' }}</span>
                                        </div>
                                        <p class="text-xs font-black text-[#0A2E2A] tracking-tight">
                                            Rs. {{ number_format($totalSpent, 2) }}
                                        </p>
                                    </div>
                                </td>

                                <!-- Latest Order Activity -->
                                <td class="py-6 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        @if ($latestOrder)
                                            @php
                                                $orderCode = '#ORD-' . str_pad($latestOrder->id, 4, '0', STR_PAD_LEFT);
                                                $statusColor = match($latestOrder->status) {
                                                    'completed', 'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'processing'             => 'bg-blue-50 text-blue-700 border-blue-200',
                                                    'cancelled'              => 'bg-red-50 text-red-700 border-red-200',
                                                    default                  => 'bg-amber-50 text-amber-700 border-amber-200',
                                                };
                                            @endphp
                                            <a href="{{ route('orders.invoice', $latestOrder) }}" target="_blank" class="text-xs font-black text-gray-900 hover:text-[#C9A050] transition-colors flex items-center gap-1">
                                                <span>{{ $orderCode }}</span>
                                                <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                            </a>
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider border {{ $statusColor }}">
                                                    {{ $latestOrder->status }}
                                                </span>
                                                <span class="text-[10px] text-gray-400 font-semibold">
                                                    {{ $latestOrder->created_at ? $latestOrder->created_at->format('M d') : '' }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">No orders placed yet</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-6 px-6 align-middle text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Quick Info Modal -->
                                        <button type="button" 
                                                @click="openDetailModal({{ json_encode($modalData) }})" 
                                                class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 hover:-translate-y-0.5 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer shadow-xs" 
                                                title="View Customer Dossier">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>

                                        <!-- Filter Customer Orders -->
                                        <a href="{{ route('orders') }}?search={{ urlencode($customerName) }}" 
                                           class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 hover:-translate-y-0.5 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer shadow-xs" 
                                           title="Filter Orders for this Customer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                        </a>

                                        <!-- Delete Account Form -->
                                        <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Are you sure you want to remove customer account: {{ addslashes($customerName) }}?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 hover:-translate-y-0.5 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer shadow-xs" 
                                                    title="Remove Customer Account">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-24 px-6 text-center align-middle">
                                    <div class="bg-gray-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100 shadow-inner">
                                        <svg class="w-10 h-10 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 font-extrabold text-lg">No customer records found</h3>
                                    <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">Try adjusting your search criteria or segment filters to discover registered patrons.</p>
                                    @if ($search || $filter !== 'all')
                                        <a href="{{ route('customers.index') }}" class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-gray-100 hover:bg-[#C9A050]/20 text-[#0A2E2A] text-xs font-bold rounded-xl transition-all">
                                            Clear All Filters
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Bar -->
            @if ($customers->hasPages())
                <div class="p-6 sm:p-8 border-t border-gray-100 bg-white/40">
                    {{ $customers->links() }}
                </div>
            @endif
        </div>

        <!-- Customer Dossier Modal -->
        <template x-teleport="body">
            <div x-show="detailModalOpen"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md" @click="closeDetailModal()"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="detailModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] shadow-md shrink-0">
                                <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-black text-white tracking-tight" x-text="activeCustomer?.name"></h3>
                                <p class="text-xs text-[#C9A050] font-semibold tracking-wider uppercase">Customer Dossier</p>
                            </div>
                        </div>
                        <button type="button" @click="closeDetailModal()" class="w-9 h-9 rounded-xl bg-white/10 hover:bg-white/20 text-gray-300 hover:text-white flex items-center justify-center transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <div class="p-8 space-y-6 max-h-[75vh] overflow-y-auto">
                        <!-- Key Stats Row -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Spend</span>
                                <span class="text-lg font-black text-[#0A2E2A]" x-text="activeCustomer?.total_spent"></span>
                            </div>
                            <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Orders</span>
                                <span class="text-lg font-black text-gray-900" x-text="activeCustomer?.orders_count + ' orders'"></span>
                            </div>
                        </div>

                        <!-- Details List -->
                        <div class="space-y-4">
                            <div class="pb-3 border-b border-gray-100">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Email Address</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.email"></span>
                            </div>

                            <div class="pb-3 border-b border-gray-100">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Phone Contact</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.phone"></span>
                            </div>

                            <div class="pb-3 border-b border-gray-100">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Default Address (Lunch)</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.address"></span>
                            </div>

                            <div class="pb-3 border-b border-gray-100">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Dinner Address</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.dinner_address"></span>
                            </div>

                            <div class="pb-3 border-b border-gray-100">
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Latest Order</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.latest_order"></span>
                            </div>

                            <div>
                                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">Registration Date</span>
                                <span class="text-sm font-semibold text-gray-900" x-text="activeCustomer?.joined_date"></span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="pt-4 flex items-center justify-end gap-3 border-t border-gray-100">
                            <button type="button" @click="closeDetailModal()" class="px-5 py-2.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 transition-all cursor-pointer">
                                Close
                            </button>
                            <a :href="'{{ route('orders') }}?search=' + encodeURIComponent(activeCustomer?.name || '')" class="px-5 py-2.5 rounded-xl bg-[#0A2E2A] text-[#C9A050] text-xs font-bold hover:bg-[#0D3833] transition-all shadow-md">
                                View Order History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
