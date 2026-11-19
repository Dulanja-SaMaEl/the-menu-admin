<x-app-layout title="Transactions & Payments">
    <div class="space-y-6" x-data="{
        slipModalOpen: false,
        activeSlip: '',
        activeOrder: null,
        deleteModalOpen: false,
        orderToDelete: null,
        customDateOpen: false,

        openSlipModal(slipUrl, orderData) {
            this.activeSlip = slipUrl;
            this.activeOrder = orderData;
            this.slipModalOpen = true;
        },
        closeSlipModal() {
            this.slipModalOpen = false;
            this.activeSlip = '';
            this.activeOrder = null;
        },
        confirmDelete(orderData) {
            this.orderToDelete = orderData;
            this.deleteModalOpen = true;
        },
        closeDeleteModal() {
            this.deleteModalOpen = false;
            this.orderToDelete = null;
        }
    }">

        <!-- Flash Notifications -->
        @if (session('success'))
            <div class="flex items-center justify-between bg-emerald-50 dark:bg-[#0E2C26] border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-200 px-6 py-4 rounded-2xl text-sm font-semibold shadow-xs">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>{{ session('success') }}</span>
                </div>
                <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-lg leading-none cursor-pointer">&times;</button>
            </div>
        @endif

        @if (session('warning'))
            <div class="flex items-center justify-between bg-amber-50 dark:bg-[#2C210E] border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200 px-6 py-4 rounded-2xl text-sm font-semibold shadow-xs">
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
                    <span class="text-[#0A2E2A] dark:text-[#D8B060] font-black">Finance & Auditing</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Transactions & Payments</h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-[#8FA8A3] mt-1 font-medium">Centralized ledger for POS terminal sales, bank slip verifications, and mobile app orders</p>
            </div>
            
            <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
                <a href="{{ route('orders') }}" class="px-5 py-3.5 bg-white dark:bg-[#071916] border border-gray-200 dark:border-[#163832] hover:border-[#C9A050] text-[#0A2E2A] dark:text-[#F5F3EF] rounded-2xl text-xs sm:text-sm font-bold flex items-center space-x-2 shadow-xs transition-all duration-300 hover:scale-[1.02] cursor-pointer">
                    <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span>View Orders</span>
                </a>
                <a href="{{ route('logs.export.orders') }}" class="px-5 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] cursor-pointer">
                    <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export Ledger</span>
                </a>
            </div>
        </div>

        <!-- =========================================================================
             CHANNEL TABS (POS vs MOBILE APP SEPARATION)
             ========================================================================= -->
        <div class="bg-white dark:bg-[#071916] p-2 rounded-2xl border border-gray-200/80 dark:border-[#163832] shadow-xs flex items-center space-x-2 overflow-x-auto no-scrollbar">
            <a href="{{ request()->fullUrlWithQuery(['channel' => 'all', 'page' => null]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 whitespace-nowrap {{ $channelFilter === 'all' ? 'bg-[#0A2E2A] dark:bg-[#123831] text-white shadow-md border border-[#C9A050]/40' : 'text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-100 dark:hover:bg-[#123831]' }}">
                <span>🏢</span>
                <span>All Transactions</span>
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $channelFilter === 'all' ? 'bg-[#C9A050] text-[#0A2E2A]' : 'bg-gray-200 dark:bg-[#163832] text-gray-700 dark:text-[#A3B8B3]' }}">{{ $stats['all_count'] }}</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['channel' => 'pos', 'page' => null]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 whitespace-nowrap {{ $channelFilter === 'pos' ? 'bg-[#0A2E2A] dark:bg-[#123831] text-white shadow-md border border-[#C9A050]/40' : 'text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-100 dark:hover:bg-[#123831]' }}">
                <span>🛒</span>
                <span>POS Counter Sales</span>
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $channelFilter === 'pos' ? 'bg-[#C9A050] text-[#0A2E2A]' : 'bg-gray-200 dark:bg-[#163832] text-gray-700 dark:text-[#A3B8B3]' }}">{{ $stats['pos_count'] }}</span>
            </a>

            <a href="{{ request()->fullUrlWithQuery(['channel' => 'app', 'page' => null]) }}" 
               class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all duration-200 flex items-center gap-2 whitespace-nowrap {{ $channelFilter === 'app' ? 'bg-[#0A2E2A] dark:bg-[#123831] text-white shadow-md border border-[#C9A050]/40' : 'text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-100 dark:hover:bg-[#123831]' }}">
                <span>📱</span>
                <span>Mobile App Sales</span>
                <span class="px-2 py-0.5 rounded-lg text-[10px] font-black {{ $channelFilter === 'app' ? 'bg-[#C9A050] text-[#0A2E2A]' : 'bg-gray-200 dark:bg-[#163832] text-gray-700 dark:text-[#A3B8B3]' }}">{{ $stats['app_count'] }}</span>
            </a>
        </div>

        <!-- Top KPI Metric Cards (Clean rounded-3xl) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
            <!-- Total Revenue -->
            <div class="bg-white/95 dark:bg-[#071916] backdrop-blur-md p-6 rounded-3xl shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#0A2E2A]/10 dark:bg-[#123831] text-[#0A2E2A] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] dark:text-[#D8B060] bg-[#0A2E2A]/10 dark:bg-[#123831] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15 dark:border-[#C9A050]/20">Revenue</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($stats['total_revenue'], 2) }}</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">
                    {{ $channelFilter === 'pos' ? 'Total POS Counter Inflow' : ($channelFilter === 'app' ? 'Total App Settled Inflow' : 'Total Settled Inflow') }}
                </p>
            </div>

            <!-- Pending Slip Verifications -->
            <div class="bg-white/95 dark:bg-[#071916] backdrop-blur-md p-6 rounded-3xl shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-50 dark:bg-amber-950/40 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center shadow-xs border border-amber-100 dark:border-amber-900/40">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-1 rounded-full border border-amber-200 dark:border-amber-800/40 {{ $stats['pending_slips_count'] > 0 ? 'animate-pulse' : '' }}">Action Required</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $stats['pending_slips_count'] }}</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Slips Awaiting Verification</p>
            </div>

            <!-- Approved Transfers -->
            <div class="bg-white/95 dark:bg-[#071916] backdrop-blur-md p-6 rounded-3xl shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100 dark:border-emerald-900/40">
                        <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-full border border-emerald-200 dark:border-emerald-800/40">Bank Transfers</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $stats['approved_transfers_count'] }}</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Rs. {{ number_format($stats['approved_transfers_amount'], 2) }} Approved</p>
            </div>

            <!-- Online Transfers -->
            <div class="bg-white/95 dark:bg-[#071916] backdrop-blur-md p-6 rounded-3xl shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 rounded-2xl flex items-center justify-center shadow-xs border border-purple-100 dark:border-purple-900/40">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/40 px-2.5 py-1 rounded-full border border-purple-200 dark:border-purple-800/40">Online & Cards</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $stats['approved_online_count'] }}</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Rs. {{ number_format($stats['approved_online_amount'], 2) }} Settled</p>
            </div>
        </div>

        <!-- Transactions Ledger Card -->
        <div class="bg-white/95 dark:bg-[#071916] backdrop-blur-md rounded-3xl shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
            <!-- Card Header & Filter Bar -->
            <div class="p-5 sm:p-7 border-b border-gray-100 dark:border-[#163832] flex flex-col gap-4 bg-white/40 dark:bg-[#071916]/40">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Payment Ledger & Verification Queue</h2>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Filter by payment method, order mode, datewise sorting, and remove test entries</p>
                    </div>
                    
                    <!-- Search Input & Sort Controls -->
                    <form method="GET" action="{{ url()->current() }}" class="flex flex-wrap items-center gap-2.5 w-full lg:w-auto">
                        <input type="hidden" name="channel" value="{{ $channelFilter }}">
                        <input type="hidden" name="order_mode" value="{{ $modeFilter }}">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <input type="hidden" name="method" value="{{ $methodFilter }}">
                        <input type="hidden" name="date" value="{{ $dateFilter }}">
                        @if($fromDate && $toDate)
                            <input type="hidden" name="from_date" value="{{ $fromDate }}">
                            <input type="hidden" name="to_date" value="{{ $toDate }}">
                        @endif

                        <div class="relative flex-1 min-w-[220px]">
                            <input type="text"
                                   name="search"
                                   value="{{ $search }}"
                                   placeholder="Search customer, ID, reference..."
                                   @input.debounce.1500ms="$el.form.submit()"
                                   class="w-full pl-10 pr-8 py-2.5 text-xs sm:text-sm font-semibold border border-gray-200 dark:border-[#163832] rounded-xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 dark:bg-[#081A17] focus:bg-white text-gray-900 dark:text-[#F5F3EF] transition-all">
                            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            @if($search)
                                <a href="{{ request()->fullUrlWithQuery(['search' => null]) }}" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 font-bold">&times;</a>
                            @endif
                        </div>

                        <!-- Date-Wise Sorting Selector -->
                        <select name="sort" onchange="this.form.submit()" 
                                class="py-2.5 px-3 bg-gray-50/80 dark:bg-[#081A17] rounded-xl border border-gray-200 dark:border-[#163832] text-xs font-bold text-gray-700 dark:text-[#F5F3EF] focus:border-[#C9A050] transition-all cursor-pointer">
                            <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>⏱️ Date: Newest First</option>
                            <option value="oldest" {{ $sort === 'oldest' ? 'selected' : '' }}>⏳ Date: Oldest First</option>
                            <option value="amount_desc" {{ $sort === 'amount_desc' ? 'selected' : '' }}>💰 Amount: High to Low</option>
                            <option value="amount_asc" {{ $sort === 'amount_asc' ? 'selected' : '' }}>💵 Amount: Low to High</option>
                        </select>
                    </form>
                </div>

                <!-- Multi-Axis Filter Bar: Order Mode, Payment Method, Status, and Date Range -->
                <div class="space-y-3 pt-3 border-t border-gray-100 dark:border-[#163832]">
                    <!-- Filter Row 1: Order Mode & Verification Status -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                        <!-- Order Mode Chips -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar py-0.5">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-1 shrink-0">Mode:</span>
                            @foreach([
                                'all'       => 'All Modes',
                                'dine_in'   => '🍽️ Dine-In',
                                'takeaway'  => '🥡 Take Away',
                                'delivery'  => '🛵 Delivery',
                            ] as $moKey => $moLabel)
                                <a href="{{ request()->fullUrlWithQuery(['order_mode' => $moKey, 'page' => null]) }}"
                                   class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $modeFilter === $moKey ? 'bg-[#0A2E2A] dark:bg-[#123831] text-[#C9A050] border border-[#C9A050]/40 shadow-xs' : 'bg-gray-100 dark:bg-[#081A17] text-gray-700 dark:text-[#8FA8A3] hover:bg-gray-200 dark:hover:bg-[#123831]' }}">
                                    {{ $moLabel }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Date Quick Presets -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar py-0.5">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-1 shrink-0">Date:</span>
                            @foreach([
                                'all'        => 'All Dates',
                                'today'      => 'Today',
                                'yesterday'  => 'Yesterday',
                                'this_week'  => 'This Week',
                                'this_month' => 'This Month',
                            ] as $dKey => $dLabel)
                                <a href="{{ request()->fullUrlWithQuery(['date' => $dKey, 'page' => null]) }}"
                                   class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $dateFilter === $dKey ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300 font-black' : 'bg-gray-100 dark:bg-[#081A17] text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-200 dark:hover:bg-[#123831]' }}">
                                    {{ $dLabel }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Filter Row 2: Payment Method & Verification Status -->
                    <div class="flex flex-wrap items-center justify-between gap-3 text-xs">
                        <!-- Payment Method Chips -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar py-0.5">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-1 shrink-0">Payment:</span>
                            @foreach([
                                'all'             => 'All Payments',
                                'cash'            => '💵 Cash',
                                'card'            => '💳 Card',
                                'online_transfer' => '⚡ QR / Online',
                                'bank_transfer'   => '🏦 Bank Transfer',
                            ] as $mKey => $mLabel)
                                <a href="{{ request()->fullUrlWithQuery(['method' => $mKey, 'page' => null]) }}"
                                   class="px-3 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $methodFilter === $mKey ? 'bg-[#C9A050] text-[#0A2E2A] font-black shadow-xs' : 'bg-gray-100 dark:bg-[#081A17] text-gray-700 dark:text-[#8FA8A3] hover:bg-gray-200 dark:hover:bg-[#123831]' }}">
                                    {{ $mLabel }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Verification Status Chips -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar py-0.5">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-1 shrink-0">Status:</span>
                            @foreach([
                                'all'      => 'All Status',
                                'pending'  => '⏳ Awaiting Slip (' . $stats['pending_slips_count'] . ')',
                                'verified' => '✓ Paid / Verified',
                                'rejected' => '✕ Rejected'
                            ] as $sKey => $sLabel)
                                <a href="{{ request()->fullUrlWithQuery(['status' => $sKey, 'page' => null]) }}"
                                   class="px-2.5 py-1 text-xs font-bold rounded-lg transition-all whitespace-nowrap {{ $statusFilter === $sKey ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#123831] font-black' : 'bg-gray-100 dark:bg-[#081A17] text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-200 dark:hover:bg-[#123831]' }}">
                                    {{ $sLabel }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1050px]">
                    <thead>
                        <tr class="bg-gray-50/80 dark:bg-[#081A17] border-b border-gray-100 dark:border-[#163832]">
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[210px]">Order Details</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[190px]">Customer</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[180px]">Payment Method</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[140px]">Amount</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[150px]">Transfer Slip</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest min-w-[150px]">Payment Status</th>
                            <th class="py-4 px-6 align-middle text-[11px] font-black text-gray-400 dark:text-[#8FA8A3] uppercase tracking-widest text-right min-w-[200px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-[#163832]">
                        @forelse ($transactions as $order)
                            @php
                                $code = '#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                                $customerName = $order->customer_name ?: ($order->user->name ?? 'Guest Customer');
                                $customerPhone = $order->customer_phone ?: ($order->user->phone ?? '');
                                $isBankTransfer = empty($order->payment_method) || stripos($order->payment_method, 'bank') !== false || !empty($order->bank_receipt_url);
                                $isCash = stripos($order->payment_method, 'cash') !== false || stripos($order->payment_method, 'cod') !== false;
                                $isCard = stripos($order->payment_method, 'card') !== false || stripos($order->payment_method, 'visa') !== false || stripos($order->payment_method, 'master') !== false;
                                $isVerified = in_array($order->payment_status, ['verified', 'paid']);
                                $isRejected = ($order->payment_status === 'rejected');
                                $isPending = !$isVerified && !$isRejected;
                                
                                $isPos = strtolower($order->channel ?? 'app') === 'pos';
                                $mode = strtolower($order->order_mode ?? 'delivery');

                                $orderModalData = [
                                    'id'        => $order->id,
                                    'code'      => $code,
                                    'customer'  => $customerName,
                                    'phone'     => $customerPhone,
                                    'amount'    => 'Rs. ' . number_format($order->total_price ?: $order->total_amount, 2),
                                    'reference' => $order->payment_reference ?? 'Not specified',
                                    'method'    => $order->payment_method ?: ($isBankTransfer ? 'Bank Transfer' : 'Online Transfer'),
                                    'channel'   => $isPos ? 'POS Counter' : 'Mobile App',
                                    'mode'      => ucfirst(str_replace('_', '-', $mode)),
                                    'status'    => $order->payment_status ?? 'pending_verification',
                                    'date'      => $order->created_at->format('M d, Y · h:i A'),
                                    'verifyUrl' => route('transactions.verify', $order->id),
                                    'rejectUrl' => route('transactions.reject', $order->id),
                                    'deleteUrl' => route('transactions.destroy', $order->id),
                                ];
                            @endphp
                            <tr id="transaction-row-{{ $order->id }}" class="border-b border-gray-100/80 dark:border-[#163832]/60 hover:bg-[#C9A050]/[0.03] transition-all duration-300 group">
                                <!-- Order Details & Badges -->
                                <td class="py-4.5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-sm font-black text-gray-900 dark:text-[#F5F3EF] group-hover:text-[#C9A050] transition-colors tracking-tight flex items-center gap-1.5" title="View tax invoice">
                                            <span>{{ $code }}</span>
                                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        <p class="text-[11px] text-gray-400 dark:text-[#8FA8A3] font-semibold">{{ $order->created_at->format('M d, Y · h:i A') }}</p>
                                        
                                        <!-- Origin Channel & Order Mode Badges -->
                                        <div class="flex items-center gap-1.5 flex-wrap mt-0.5">
                                            @if ($isPos)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold rounded-lg border border-emerald-200 dark:border-emerald-800">
                                                    🛒 POS
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 text-[10px] font-bold rounded-lg border border-blue-200 dark:border-blue-800">
                                                    📱 App
                                                </span>
                                            @endif

                                            @if ($mode === 'dine_in')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-[10px] font-bold rounded-lg border border-amber-200 dark:border-amber-800">
                                                    🍽️ Dine-In
                                                </span>
                                            @elseif ($mode === 'takeaway')
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-teal-50 dark:bg-teal-950/40 text-teal-700 dark:text-teal-300 text-[10px] font-bold rounded-lg border border-teal-200 dark:border-teal-800">
                                                    🥡 Takeaway
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-purple-50 dark:bg-purple-950/40 text-purple-700 dark:text-purple-300 text-[10px] font-bold rounded-lg border border-purple-200 dark:border-purple-800">
                                                    🛵 Delivery
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer Details -->
                                <td class="py-4.5 px-6 align-middle">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-xl bg-[#0A2E2A] text-[#C9A050] font-black text-xs flex items-center justify-center shrink-0 shadow-xs border border-[#C9A050]/20">
                                            {{ strtoupper(substr($customerName, 0, 2)) }}
                                        </div>
                                        <div class="min-w-0 flex flex-col gap-0.5">
                                            <p class="text-xs sm:text-sm font-black text-gray-900 dark:text-[#F5F3EF] truncate tracking-tight">{{ $customerName }}</p>
                                            @if ($customerPhone && $customerPhone !== 'N/A')
                                                <a href="tel:{{ $customerPhone }}" class="text-[11px] text-[#C9A050] hover:underline font-bold inline-flex items-center gap-1">
                                                    📞 {{ $customerPhone }}
                                                </a>
                                            @else
                                                <span class="text-[10px] text-gray-400 italic">No phone attached</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Payment Method & Reference -->
                                <td class="py-4.5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1.5">
                                        <div class="flex items-center gap-1.5">
                                            @if ($isCash)
                                                <span class="text-sm">💵</span>
                                                <span class="text-xs font-bold text-gray-800 dark:text-[#D1DCD9]">{{ $order->payment_method ?: 'Cash' }}</span>
                                            @elseif ($isCard)
                                                <span class="text-sm">💳</span>
                                                <span class="text-xs font-bold text-gray-800 dark:text-[#D1DCD9]">{{ $order->payment_method ?: 'Card' }}</span>
                                            @elseif ($isBankTransfer)
                                                <span class="text-sm">🏦</span>
                                                <span class="text-xs font-bold text-gray-800 dark:text-[#D1DCD9]">Bank Transfer</span>
                                            @else
                                                <span class="text-sm">⚡</span>
                                                <span class="text-xs font-bold text-gray-800 dark:text-[#D1DCD9]">{{ $order->payment_method ?: 'Online Transfer' }}</span>
                                            @endif
                                        </div>

                                        @if ($order->payment_reference)
                                            <div class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#C9A050]/10 border border-[#C9A050]/20 rounded-md text-[10px] font-mono font-bold text-[#0A2E2A] dark:text-[#D8B060] w-fit">
                                                <span>Ref:</span>
                                                <span>{{ $order->payment_reference }}</span>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-gray-400 italic">Direct Counter Settle</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="py-4.5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm sm:text-base font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($order->total_price ?: $order->total_amount, 2) }}</span>
                                        <span class="text-[10px] font-semibold text-gray-400 dark:text-[#8FA8A3]">
                                            {{ $order->items ? $order->items->sum('quantity') : 1 }} {{ \Illuminate\Support\Str::plural('item', $order->items ? $order->items->sum('quantity') : 1) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Transfer Slip Preview -->
                                <td class="py-4.5 px-6 align-middle whitespace-nowrap">
                                    @if ($order->bank_receipt_url)
                                        <div class="flex items-center space-x-2.5">
                                            <button type="button"
                                                    @click="openSlipModal('{{ $order->bank_receipt_url }}', {{ json_encode($orderModalData) }})"
                                                    class="relative group/thumb cursor-pointer overflow-hidden rounded-xl border border-gray-200 dark:border-[#163832] shadow-2xs hover:border-[#C9A050] transition-colors shrink-0">
                                                <img src="{{ $order->bank_receipt_url }}" class="w-10 h-10 object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Slip thumbnail">
                                                <div class="absolute inset-0 bg-[#0A2E2A]/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                </div>
                                            </button>
                                            <button type="button"
                                                    @click="openSlipModal('{{ $order->bank_receipt_url }}', {{ json_encode($orderModalData) }})"
                                                    class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1 cursor-pointer">
                                                <span>View Slip</span>
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400 dark:text-[#8FA8A3] italic flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-[#163832]"></span>
                                            <span>No slip attached</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Payment Status Badge -->
                                <td class="py-4.5 px-6 align-middle whitespace-nowrap">
                                    @if ($isVerified)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold rounded-full border border-emerald-200 dark:border-emerald-800 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Verified & Approved
                                        </span>
                                    @elseif ($isRejected)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs font-bold rounded-full border border-rose-200 dark:border-rose-800 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Slip Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-xs font-bold rounded-full border border-amber-200 dark:border-amber-800 shadow-2xs animate-pulse">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Awaiting Slip
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions Column with Delete Button -->
                                <td class="py-4.5 px-6 align-middle text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        @if ($isPending)
                                            <!-- Approve Form -->
                                            <form method="POST" action="{{ route('transactions.verify', $order->id) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('Verify and approve payment for Order {{ $code }}?')"
                                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-xs hover:scale-[1.02] cursor-pointer"
                                                        title="Approve Bank Transfer">
                                                    ✓ Approve
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="{{ route('transactions.reject', $order->id) }}" class="inline-block" onsubmit="return confirm('Reject transfer slip for Order {{ $code }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-all cursor-pointer"
                                                        title="Reject Transfer Slip">
                                                    ✕ Reject
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Invoice Print -->
                                        <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                                           class="p-2 text-gray-500 dark:text-[#8FA8A3] hover:text-[#0A2E2A] dark:hover:text-[#F5F3EF] hover:bg-[#C9A050]/20 transition-all bg-gray-50 dark:bg-[#081A17] border border-gray-200/80 dark:border-[#163832] rounded-xl cursor-pointer"
                                           title="Print Tax Invoice">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>

                                        <!-- Delete / Remove Test Order Action -->
                                        <button type="button" 
                                                @click="confirmDelete({{ json_encode($orderModalData) }})"
                                                class="p-2 text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-colors cursor-pointer border border-transparent hover:border-rose-200"
                                                title="Delete this order / test record">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-16 px-6 text-center align-middle">
                                    <div class="bg-gray-50 dark:bg-[#081A17] w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100 dark:border-[#163832]">
                                        <svg class="w-7 h-7 text-gray-300 dark:text-[#8FA8A3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 dark:text-[#F5F3EF] font-bold">No transaction records found</h3>
                                    <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-1">Try adjusting the channel (POS / Mobile App), payment method, or date filters</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($transactions->hasPages())
                <div class="p-5 border-t border-gray-100 dark:border-[#163832] bg-gray-50/50 dark:bg-[#081A17]">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <!-- =========================================================================
             MODAL: GLASSMORPHIC BANK SLIP PREVIEW (Teleported to body)
             ========================================================================= -->
        <template x-teleport="body">
            <div x-show="slipModalOpen"
                 x-cloak
                 class="fixed inset-0 z-[100] flex justify-center p-3 sm:p-6 overflow-y-auto bg-black/75 backdrop-blur-xs"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <div class="m-auto relative bg-white dark:bg-[#071916] rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden border border-gray-100 dark:border-[#163832]"
                     style="max-height: 90vh;"
                     @click.away="closeSlipModal()">

                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-6 sm:px-8 py-5 flex items-center justify-between border-b border-[#C9A050]/20 shrink-0">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-11 h-11 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] shadow-md shrink-0">
                                <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg sm:text-xl font-black text-white tracking-tight flex items-center gap-2">
                                    <span>Bank Transfer Receipt</span>
                                    <span class="text-xs font-bold text-[#C9A050] bg-white/10 px-2 py-0.5 rounded-lg font-mono" x-text="activeOrder?.code"></span>
                                </h3>
                                <p class="text-xs text-emerald-100 font-medium" x-text="activeOrder?.customer + ' · ' + activeOrder?.amount"></p>
                            </div>
                        </div>
                        <button type="button" @click="closeSlipModal()" class="p-2 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-8 space-y-5 overflow-y-auto flex-1 custom-scrollbar">
                        <!-- Full Slip Image Preview -->
                        <div class="rounded-2xl border border-gray-200 dark:border-[#163832] bg-gray-50/60 dark:bg-[#081A17] p-2 flex items-center justify-center overflow-hidden shadow-inner">
                            <img :src="activeSlip" class="w-full max-h-[50vh] object-contain rounded-xl hover:scale-[1.01] transition-transform" alt="Bank Transfer Slip Full Preview">
                        </div>

                        <!-- Order & Reference Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 bg-gray-50 dark:bg-[#081A17] p-4 rounded-2xl border border-gray-200/80 dark:border-[#163832] text-xs">
                            <div>
                                <span class="text-gray-400 dark:text-[#8FA8A3] block text-[10px] uppercase font-bold">Customer Name</span>
                                <span class="font-bold text-gray-900 dark:text-[#F5F3EF]" x-text="activeOrder?.customer"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 dark:text-[#8FA8A3] block text-[10px] uppercase font-bold">Contact Phone</span>
                                <span class="font-bold text-gray-900 dark:text-[#F5F3EF]" x-text="activeOrder?.phone || '—'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 dark:text-[#8FA8A3] block text-[10px] uppercase font-bold">Customer Reference</span>
                                <span class="font-mono font-bold text-[#C9A050]" x-text="activeOrder?.reference"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 dark:text-[#8FA8A3] block text-[10px] uppercase font-bold">Billed Amount</span>
                                <span class="font-black text-gray-900 dark:text-[#F5F3EF] text-sm" x-text="activeOrder?.amount"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-5 bg-gray-50 dark:bg-[#081A17] border-t border-gray-100 dark:border-[#163832] flex items-center justify-between gap-3 shrink-0 flex-wrap">
                        <div class="flex items-center space-x-2">
                            <a :href="activeSlip" target="_blank" class="px-4 py-2.5 bg-white dark:bg-[#071916] border border-gray-200 dark:border-[#163832] hover:border-[#C9A050] text-gray-800 dark:text-[#F5F3EF] rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs hover:scale-[1.02]">
                                <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                <span>Open Full Image</span>
                            </a>
                        </div>

                        <div class="flex items-center space-x-2.5">
                            <template x-if="activeOrder?.status !== 'verified' && activeOrder?.status !== 'paid'">
                                <div class="flex items-center space-x-2">
                                    <form method="POST" :action="activeOrder?.verifyUrl" class="inline">
                                        @csrf
                                        <button type="submit"
                                                onclick="return confirm('Verify and approve this payment?')"
                                                class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md shadow-emerald-600/20 hover:scale-[1.02] cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            <span>Verify & Approve</span>
                                        </button>
                                    </form>

                                    <form method="POST" :action="activeOrder?.rejectUrl" class="inline" onsubmit="return confirm('Mark this slip as rejected?');">
                                        @csrf
                                        <button type="submit"
                                                class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                            ✕ Reject Slip
                                        </button>
                                    </form>
                                </div>
                            </template>

                            <button type="button" @click="closeSlipModal()" class="px-5 py-2.5 bg-gray-200 dark:bg-[#163832] hover:bg-gray-300 dark:hover:bg-[#1E4940] text-gray-700 dark:text-[#F5F3EF] rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- =========================================================================
             MODAL: DELETE / REMOVE ORDER & TRANSACTION CONFIRMATION
             ========================================================================= -->
        <template x-teleport="body">
            <div x-show="deleteModalOpen"
                 x-cloak
                 class="fixed inset-0 z-[100] flex justify-center p-3 sm:p-6 overflow-y-auto bg-black/75 backdrop-blur-xs"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <div class="m-auto bg-white dark:bg-[#071916] rounded-3xl shadow-2xl max-w-md w-full p-6 sm:p-7 border border-red-200/80 dark:border-red-900/40 overflow-hidden"
                     @click.away="closeDeleteModal()">
                    
                    <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-950/50 text-red-600 dark:text-red-400 flex items-center justify-center text-xl font-bold mb-4 border border-red-200 dark:border-red-900/40">
                        ⚠️
                    </div>

                    <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] mb-1">
                        Permanently Remove Order?
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mb-4">
                        Are you sure you want to remove this order from records? This action cannot be undone.
                    </p>

                    <!-- Summary Card of Order to be Deleted -->
                    <div class="bg-gray-50 dark:bg-[#081A17] p-3.5 rounded-2xl border border-gray-100 dark:border-[#163832] text-xs space-y-1.5 mb-6">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Order ID</span>
                            <span class="font-mono font-black text-gray-900 dark:text-[#F5F3EF]" x-text="orderToDelete?.code"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Customer</span>
                            <span class="font-bold text-gray-900 dark:text-[#F5F3EF]" x-text="orderToDelete?.customer"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Channel / Mode</span>
                            <span class="font-bold text-[#C9A050]" x-text="(orderToDelete?.channel || '') + ' • ' + (orderToDelete?.mode || '')"></span>
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-gray-200 dark:border-[#163832]">
                            <span class="text-gray-400 font-bold uppercase text-[10px]">Total Amount</span>
                            <span class="font-black text-rose-600 dark:text-rose-400 text-sm" x-text="orderToDelete?.amount"></span>
                        </div>
                    </div>

                    <form method="POST" :action="orderToDelete?.deleteUrl" class="flex items-center justify-end space-x-3">
                        @csrf
                        @method('DELETE')
                        <button type="button" @click="closeDeleteModal()" 
                                class="px-4.5 py-2.5 rounded-xl border border-gray-200 dark:border-[#163832] text-xs font-bold text-gray-600 dark:text-[#8FA8A3] hover:bg-gray-100 dark:hover:bg-[#123831] transition-all cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-xs font-black shadow-md shadow-red-600/20 transition-all cursor-pointer">
                            Yes, Permanently Delete
                        </button>
                    </form>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
