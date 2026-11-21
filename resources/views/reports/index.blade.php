<x-app-layout title="Reports & Analytics">
    <div class="space-y-6" x-data="{
        activeTab: '{{ $activeTab }}',
        showCustomDate: {{ $range === 'custom' ? 'true' : 'false' }},
        switchTab(tab) {
            this.activeTab = tab;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        }
    }">

        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-500 dark:text-[#8FA8A3] mb-2 uppercase tracking-wider">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                    <svg class="w-3.5 h-3.5 mx-2 text-gray-400 dark:text-[#527971]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="text-[#0A2E2A] dark:text-[#D8B060] font-black">Executive Intelligence</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Reports & Analytics</h1>
                <p class="text-xs sm:text-sm text-gray-500 dark:text-[#8FA8A3] mt-1 font-medium">Auditing and telemetry across orders, bank transfers, client growth, fleet logistics, and catering</p>
            </div>
            
            <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
                <a href="{{ route('reports.export.excel', ['range' => $range, 'from' => $fromDate, 'to' => $toDate, 'channel' => $mealChannel ?? 'all']) }}" 
                   class="px-5 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2 shadow-lg shadow-emerald-700/25 transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export Excel (.xlsx)</span>
                </a>
                <a :href="`{{ route('reports.export') }}?tab=${activeTab}&range={{ $range }}&from={{ $fromDate }}&to={{ $toDate }}`" 
                   class="px-5 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>

        <!-- Date Range Filter Bar -->
        <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-4 sm:p-5 rounded-[2rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Quick Filter Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3] mr-1 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        <span>Period:</span>
                    </span>

                    @php
                        $ranges = [
                            'all'        => 'All Time',
                            'today'      => 'Today',
                            'yesterday'  => 'Yesterday',
                            '7d'         => 'Last 7 Days',
                            '30d'        => 'Last 30 Days',
                            'this_month' => 'This Month',
                        ];
                    @endphp

                    @foreach($ranges as $key => $label)
                        <a href="{{ route('reports.index', ['tab' => $activeTab, 'range' => $key, 'meal_channel' => $mealChannel ?? 'all']) }}"
                           class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 {{ $range === $key ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] shadow-md' : 'bg-gray-100 hover:bg-gray-200/70 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:hover:text-white dark:hover:bg-[#123831] dark:border dark:border-[#163832]' }}">
                            {{ $label }}
                        </a>
                    @endforeach

                    <button type="button" 
                            @click="showCustomDate = !showCustomDate" 
                            class="px-4 py-2 rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-1.5 {{ $range === 'custom' ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] shadow-md' : 'bg-gray-100 hover:bg-gray-200/70 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:hover:text-white dark:hover:bg-[#123831] dark:border dark:border-[#163832]' }}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                        <span>Custom Range</span>
                    </button>
                </div>

                <!-- Active Date Window Indicator -->
                <div class="text-xs font-semibold text-gray-500 dark:text-[#8FA8A3] flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>
                        @if ($startDate && $endDate)
                            Active Window: <strong class="text-gray-900 dark:text-[#F5F3EF]">{{ $startDate->format('M d, Y') }}</strong> to <strong class="text-gray-900 dark:text-[#F5F3EF]">{{ $endDate->format('M d, Y') }}</strong>
                        @elseif ($startDate)
                            Since <strong class="text-gray-900 dark:text-[#F5F3EF]">{{ $startDate->format('M d, Y') }}</strong>
                        @else
                            Showing <strong class="text-gray-900 dark:text-[#F5F3EF]">Complete Historical Data</strong>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Expandable Custom Date Form -->
            <div x-show="showCustomDate" x-transition class="mt-4 pt-4 border-t border-gray-100 dark:border-[#163832]">
                <form method="GET" action="{{ route('reports.index') }}" class="flex flex-wrap items-end gap-3">
                    <input type="hidden" name="tab" :value="activeTab">
                    <input type="hidden" name="range" value="custom">

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-[#8FA8A3] mb-1">From Date</label>
                        <input type="date" name="from" value="{{ $fromDate ?: ($startDate ? $startDate->format('Y-m-d') : '') }}" 
                               class="px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-[#163832] bg-white dark:bg-[#071916] text-gray-900 dark:text-[#F5F3EF] focus:ring-2 focus:ring-[#C9A050] focus:outline-hidden">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-[#8FA8A3] mb-1">To Date</label>
                        <input type="date" name="to" value="{{ $toDate ?: ($endDate ? $endDate->format('Y-m-d') : '') }}" 
                               class="px-3.5 py-2 text-xs rounded-xl border border-gray-200 dark:border-[#163832] bg-white dark:bg-[#071916] text-gray-900 dark:text-[#F5F3EF] focus:ring-2 focus:ring-[#C9A050] focus:outline-hidden">
                    </div>

                    <button type="submit" 
                            class="px-4 py-2 bg-[#0A2E2A] hover:bg-[#123831] dark:bg-[#C9A050] dark:hover:bg-[#d8b05f] text-[#C9A050] dark:text-[#061513] rounded-xl text-xs font-black shadow-xs transition-colors">
                        Apply Filter
                    </button>
                    @if($range === 'custom')
                        <a href="{{ route('reports.index', ['tab' => $activeTab, 'range' => 'all']) }}" 
                           class="px-3 py-2 text-xs font-bold text-gray-500 hover:text-red-500 transition-colors">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <!-- Luxury Navigation Sub-Tabs Bar (Alpine.js) -->
        <div class="flex items-center space-x-2.5 p-2 bg-gray-100/70 dark:bg-[#071916] rounded-2xl border border-gray-200/60 dark:border-[#163832] overflow-x-auto custom-scrollbar">
            <!-- 1. Overview -->
            <button type="button" @click="switchTab('overview')"
                    :data-active="activeTab === 'overview' ? 'true' : 'false'"
                    :class="activeTab === 'overview' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'overview' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                <span>Executive Overview</span>
            </button>

            <!-- 2. Orders -->
            <button type="button" @click="switchTab('orders')"
                    :data-active="activeTab === 'orders' ? 'true' : 'false'"
                    :class="activeTab === 'orders' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'orders' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <span>Orders Analysis</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'orders' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-gray-100 text-gray-600 dark:bg-[#123831] dark:text-[#D8B060] dark:border dark:border-[#1E4940]'">{{ number_format($ordersStats['total_count']) }}</span>
            </button>

            <!-- 3. Transactions -->
            <button type="button" @click="switchTab('transactions')"
                    :data-active="activeTab === 'transactions' ? 'true' : 'false'"
                    :class="activeTab === 'transactions' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'transactions' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                <span>Bank Ledger & Slips</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'transactions' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-emerald-50 text-emerald-700 dark:bg-[#123831] dark:text-[#34D399] dark:border dark:border-[#1E4940]'">{{ $transactionsStats['verification_rate'] }}%</span>
            </button>

            <!-- 4. Customers -->
            <button type="button" @click="switchTab('customers')"
                    :data-active="activeTab === 'customers' ? 'true' : 'false'"
                    :class="activeTab === 'customers' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'customers' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                <span>Customers & Retention</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'customers' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-gray-100 text-gray-600 dark:bg-[#123831] dark:text-[#D8B060] dark:border dark:border-[#1E4940]'">{{ number_format($customersStats['total_count']) }}</span>
            </button>

            <!-- 5. Drivers -->
            <button type="button" @click="switchTab('drivers')"
                    :data-active="activeTab === 'drivers' ? 'true' : 'false'"
                    :class="activeTab === 'drivers' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'drivers' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a1 1 0 001 1h1"></path></svg>
                <span>Fleet & Logistics</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'drivers' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-gray-100 text-gray-600 dark:bg-[#123831] dark:text-[#D8B060] dark:border dark:border-[#1E4940]'">{{ $driversStats['total_drivers'] }}</span>
            </button>

            <!-- 6. Events -->
            <button type="button" @click="switchTab('events')"
                    :data-active="activeTab === 'events' ? 'true' : 'false'"
                    :class="activeTab === 'events' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors" 
                     :class="activeTab === 'events' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span>Events & Catering</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'events' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-gray-100 text-gray-600 dark:bg-[#123831] dark:text-[#D8B060] dark:border dark:border-[#1E4940]'">{{ $eventsStats['total_events'] }}</span>
            </button>

            <!-- 7. Meal-wise Report -->
            <button type="button" @click="switchTab('meals')"
                    :data-active="activeTab === 'meals' ? 'true' : 'false'"
                    :class="activeTab === 'meals' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <span class="text-sm">🍽️</span>
                <span>Meal-wise Report</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'meals' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-amber-50 text-amber-800 dark:bg-[#123831] dark:text-[#F59E0B] dark:border dark:border-[#1E4940]'">Top: {{ Str::limit($mealsStats['highest_selling']['name'] ?? 'Lunch', 10) }}</span>
            </button>

            <!-- 8. POS & Buffet Counter Terminal -->
            <button type="button" @click="switchTab('pos')"
                    :data-active="activeTab === 'pos' ? 'true' : 'false'"
                    :class="activeTab === 'pos' 
                        ? 'active bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] font-black shadow-md border-[#0A2E2A] dark:border-[#C9A050]' 
                        : 'bg-white hover:bg-gray-50 text-gray-600 border-gray-200/80 dark:bg-[#081A17] dark:hover:bg-[#123831] dark:text-[#8FA8A3] dark:hover:text-[#F5F3EF] dark:border-[#163832] font-bold'"
                    class="report-subtab flex items-center space-x-2.5 px-4.5 py-2.5 rounded-xl text-xs sm:text-sm tracking-tight transition-all duration-200 whitespace-nowrap cursor-pointer border shrink-0">
                <svg class="w-4 h-4 transition-colors"
                     :class="activeTab === 'pos' ? 'text-[#C9A050] dark:text-[#061513]' : 'text-gray-400 dark:text-[#8FA8A3]'"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span>POS & Buffet Terminal</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-black transition-colors"
                      :class="activeTab === 'pos' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/25 dark:text-[#061513]' : 'bg-emerald-50 text-emerald-800 dark:bg-[#123831] dark:text-[#34D399] dark:border dark:border-[#1E4940]'">Rs. {{ number_format($posStats['total_revenue'], 0) }}</span>
            </button>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 1: EXECUTIVE OVERVIEW -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'overview'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- 5 Master KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                <!-- Total Gross Platform Revenue -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Gross Volume</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($executiveOverview['gross_platform_volume'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Orders & Banquets Volume</p>
                </div>

                <!-- Orders Count & Fulfillment -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                            <svg class="w-6 h-6 text-blue-600 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200">Fulfillment</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($executiveOverview['total_orders']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $executiveOverview['fulfillment_rate'] }}% Delivered / Closed</p>
                </div>

                <!-- Verified Bank Ledger Volume -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Verified Slips</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($executiveOverview['verified_ledger_vol'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $executiveOverview['verification_rate'] }}% Approval Accuracy</p>
                </div>

                <!-- Total Registered Customers -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-purple-50 text-purple-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] rounded-2xl flex items-center justify-center shadow-xs border border-purple-100">
                            <svg class="w-6 h-6 text-purple-600 dark:text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] px-2.5 py-1 rounded-full border border-purple-200">Retention</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($executiveOverview['total_patrons']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $executiveOverview['recurring_rate'] }}% Recurring Diners</p>
                </div>

                <!-- Active Couriers & Fleet Capacity -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                            <svg class="w-6 h-6 text-amber-600 dark:text-[#FBBF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] px-2.5 py-1 rounded-full border border-amber-200">Logistics</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $executiveOverview['active_drivers'] }} <span class="text-sm font-medium text-gray-400 dark:text-[#8FA8A3]">Drivers</span></h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $executiveOverview['fleet_utilization'] }}% Fleet Utilization</p>
                </div>
            </div>

            <!-- Module Deep Dive Cards (5 Tiles) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Orders Tile -->
                <div class="report-module-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050] dark:text-[#D8B060]">Kitchen & Delivery</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50"></span>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Orders & Meal Subscriptions</h4>
                        <p class="report-module-desc text-xs text-gray-500 dark:text-[#8FA8A3] mt-1.5 font-medium leading-relaxed">Status pipeline, average order value, meal-type distribution, and delivery fulfillment</p>
                        
                        <div class="mt-6 divide-y divide-gray-100 dark:divide-[#163832] border-t border-b border-gray-100 dark:border-[#163832]">
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Gross Orders Volume:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($ordersStats['total_revenue'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Average Order Value (AOV):
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($ordersStats['avg_order_value'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Completed Deliveries:
                                </span>
                                <span class="report-module-value font-black text-emerald-600 dark:text-emerald-400">{{ number_format($ordersStats['completed_count']) }} / {{ number_format($ordersStats['total_count']) }}</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="switchTab('orders')" class="report-module-btn mt-6 w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#0E2925] dark:hover:bg-[#16463D] text-[#0A2E2A] dark:text-[#D8B060] font-black text-xs rounded-xl border border-gray-200/80 dark:border-[#1E4940] hover:border-[#C9A050] flex items-center justify-center space-x-2 transition-all duration-200 cursor-pointer shadow-xs">
                        <span>Inspect Orders Report</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>

                <!-- Transactions Tile -->
                <div class="report-module-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050] dark:text-[#D8B060]">Finance & Auditing</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shadow-xs shadow-blue-500/50"></span>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF]">Transactions & Bank Ledger</h4>
                        <p class="report-module-desc text-xs text-gray-500 dark:text-[#8FA8A3] mt-1.5 font-medium leading-relaxed">Slip approvals, unverified transfers, bank institution breakdown, and payment security</p>
                        
                        <div class="mt-6 divide-y divide-gray-100 dark:divide-[#163832] border-t border-b border-gray-100 dark:border-[#163832]">
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Total Transfer Ledger:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($transactionsStats['total_volume'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Verified Receipts:
                                </span>
                                <span class="report-module-value font-black text-emerald-600 dark:text-emerald-400">Rs. {{ number_format($transactionsStats['verified_volume'], 2) }} ({{ $transactionsStats['verified_count'] }})</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Pending Slips:
                                </span>
                                <span class="report-module-value font-black text-amber-600 dark:text-amber-400">{{ $transactionsStats['pending_count'] }} awaiting review</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="switchTab('transactions')" class="report-module-btn mt-6 w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#0E2925] dark:hover:bg-[#16463D] text-[#0A2E2A] dark:text-[#D8B060] font-black text-xs rounded-xl border border-gray-200/80 dark:border-[#1E4940] hover:border-[#C9A050] flex items-center justify-center space-x-2 transition-all duration-200 cursor-pointer shadow-xs">
                        <span>Inspect Transactions Ledger</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>

                <!-- Customers Tile -->
                <div class="report-module-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050] dark:text-[#D8B060]">CUSTOMER RELATIONS</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-purple-500 shadow-xs shadow-purple-500/50"></span>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF]">Customer Reports</h4>
                        <p class="report-module-desc text-xs text-gray-500 dark:text-[#8FA8A3] mt-1.5 font-medium leading-relaxed">Customer acquisition rate, active vs. inactive customers, and high-value customer leaderboard</p>
                        
                        <div class="mt-6 divide-y divide-gray-100 dark:divide-[#163832] border-t border-b border-gray-100 dark:border-[#163832]">
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Total Customer Lifetime Spend:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($customersStats['total_spend'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Average Spend / Customer:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($customersStats['avg_ltv'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span>
                                    Repeat Customers:
                                </span>
                                <span class="report-module-value font-black text-purple-600 dark:text-purple-400">{{ $customersStats['recurring_count'] }} ({{ $customersStats['recurring_rate'] }}%)</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="switchTab('customers')" class="report-module-btn mt-6 w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#0E2925] dark:hover:bg-[#16463D] text-[#0A2E2A] dark:text-[#D8B060] font-black text-xs rounded-xl border border-gray-200/80 dark:border-[#1E4940] hover:border-[#C9A050] flex items-center justify-center space-x-2 transition-all duration-200 cursor-pointer shadow-xs">
                        <span>View Customer Reports</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>

                <!-- Drivers Tile -->
                <div class="report-module-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050] dark:text-[#D8B060]">Logistics & Fleet</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-xs shadow-amber-500/50"></span>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Couriers & Dispatch Fleet</h4>
                        <p class="report-module-desc text-xs text-gray-500 dark:text-[#8FA8A3] mt-1.5 font-medium leading-relaxed">Fleet availability, live dispatch status, delivery performance, and max capacity metrics</p>
                        
                        <div class="mt-6 divide-y divide-gray-100 dark:divide-[#163832] border-t border-b border-gray-100 dark:border-[#163832]">
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                    Couriers on Live Dispatch:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">{{ $driversStats['on_delivery_drivers'] }} / {{ $driversStats['total_drivers'] }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Fleet Max Order Capacity:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">{{ $driversStats['max_capacity'] }} concurrent orders</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Total Fleet Deliveries:
                                </span>
                                <span class="report-module-value font-black text-[#C9A050] dark:text-[#D8B060]">{{ number_format($driversStats['total_deliveries']) }} meals</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="switchTab('drivers')" class="report-module-btn mt-6 w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#0E2925] dark:hover:bg-[#16463D] text-[#0A2E2A] dark:text-[#D8B060] font-black text-xs rounded-xl border border-gray-200/80 dark:border-[#1E4940] hover:border-[#C9A050] flex items-center justify-center space-x-2 transition-all duration-200 cursor-pointer shadow-xs">
                        <span>Inspect Fleet Report</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>

                <!-- Events Tile -->
                <div class="report-module-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050] dark:text-[#D8B060]">Banquets & VIP</span>
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-xs shadow-emerald-500/50"></span>
                        </div>
                        <h4 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Corporate Events & Catering</h4>
                        <p class="report-module-desc text-xs text-gray-500 dark:text-[#8FA8A3] mt-1.5 font-medium leading-relaxed">Multi-day corporate banquets, assigned couriers, upcoming schedules, and revenue volume</p>
                        
                        <div class="mt-6 divide-y divide-gray-100 dark:divide-[#163832] border-t border-b border-gray-100 dark:border-[#163832]">
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Total Banquet Volume:
                                </span>
                                <span class="report-module-value font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($eventsStats['total_cost'], 2) }}</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Upcoming VIP Events:
                                </span>
                                <span class="report-module-value font-black text-emerald-600 dark:text-emerald-400">{{ $eventsStats['upcoming_events'] }} scheduled</span>
                            </div>
                            <div class="report-module-row py-3 flex justify-between items-center text-xs">
                                <span class="report-module-label text-gray-500 dark:text-[#8FA8A3] font-medium flex items-center gap-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050]"></span>
                                    Courier Assignment Rate:
                                </span>
                                <span class="report-module-value font-black text-[#C9A050] dark:text-[#D8B060]">{{ $eventsStats['courier_coverage'] }}% covered</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="switchTab('events')" class="report-module-btn mt-6 w-full py-3 px-4 bg-gray-50 hover:bg-gray-100 dark:bg-[#0E2925] dark:hover:bg-[#16463D] text-[#0A2E2A] dark:text-[#D8B060] font-black text-xs rounded-xl border border-gray-200/80 dark:border-[#1E4940] hover:border-[#C9A050] flex items-center justify-center space-x-2 transition-all duration-200 cursor-pointer shadow-xs">
                        <span>Inspect Events Report</span>
                        <svg class="w-3.5 h-3.5 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 2: ORDERS REPORT -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'orders'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Orders KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Orders -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Orders</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($ordersStats['total_count']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Total Placed Orders in Window</p>
                </div>

                <!-- Gross Orders Revenue -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Gross Inflow</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($ordersStats['total_revenue'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Avg. Rs. {{ number_format($ordersStats['avg_order_value'], 2) }} / order</p>
                </div>

                <!-- Completed Deliveries -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                            <svg class="w-6 h-6 text-blue-600 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200">Success Rate</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($ordersStats['completed_count']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $ordersStats['fulfillment_rate'] }}% Successfully Delivered</p>
                </div>

                <!-- Active / Cancelled -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                            <svg class="w-6 h-6 text-amber-600 dark:text-[#FBBF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] px-2.5 py-1 rounded-full border border-amber-200">In-Flight</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $ordersStats['active_count'] }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $ordersStats['cancelled_count'] }} Cancelled / Refunded</p>
                </div>
            </div>

            <!-- Meal Type Distribution Analysis -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-8 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                <h3 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight mb-1">Meal Service Distribution</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mb-6">Aggregate consumption volume and revenue generated across breakfast, lunch, and dinner tiers</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach($ordersStats['meal_distribution'] as $meal)
                        <div class="bg-gray-50/70 dark:bg-[#071916] p-5 rounded-2xl border border-gray-200/60 dark:border-[#163832]">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-black text-gray-900 dark:text-[#F5F3EF]">{{ $meal['type'] }} Service</span>
                                <span class="text-xs font-bold text-[#C9A050] dark:text-[#D8B060]">{{ $meal['percentage'] }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-[#0D2824] h-2.5 rounded-full overflow-hidden mb-3">
                                <div class="bg-gradient-to-r from-[#C9A050] to-[#b88f3f] h-2.5 rounded-full" style="width: {{ $meal['percentage'] }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-[#8FA8A3]">
                                <span>{{ number_format($meal['count']) }} Dishes</span>
                                <span class="font-bold text-gray-800 dark:text-[#F5F3EF]">Rs. {{ number_format($meal['revenue'], 2) }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Orders in Period Table -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Recent Orders Audit</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Telemetry on orders dispatched within this operational period</p>
                    </div>
                    <a href="{{ route('orders') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                        <span>Go to All Orders</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Order Ref</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Client</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Meal Items</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Amount</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Status</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Invoice</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($ordersStats['recent_orders'] as $order)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $order->order_number ?: ('ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT)) }}
                                        <span class="block text-[10px] text-gray-400 dark:text-[#8FA8A3] font-normal">{{ $order->created_at->format('M d, Y · h:i A') }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-800 dark:text-[#F5F3EF]">
                                        {{ $order->customer_name ?: ($order->user->name ?? 'Guest Client') }}
                                        <span class="block text-[10px] text-gray-400 dark:text-[#8FA8A3] font-normal">{{ $order->customer_phone ?: ($order->user->phone ?? '') }}</span>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $order->items->count() }} item(s)
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        Rs. {{ number_format($order->total_price ?: $order->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @php
                                            $st = strtolower($order->status);
                                            $badgeClass = match($st) {
                                                'completed', 'delivered' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
                                                'processing', 'assigned' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800',
                                                'cancelled'              => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800',
                                                default                  => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeClass }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-xs font-bold text-[#C9A050] hover:underline">
                                            View Tax PDF
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No orders recorded within this date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 3: TRANSACTIONS & BANK LEDGER -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'transactions'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Transactions KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Transfer Ledger Volume -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Ledger</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($transactionsStats['total_volume'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Total Bank Inflow in Period</p>
                </div>

                <!-- Verified Bank Slips -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Approved</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($transactionsStats['verified_volume'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $transactionsStats['verified_count'] }} Verified Slips</p>
                </div>

                <!-- Pending Slip Verifications -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                            <svg class="w-6 h-6 text-amber-600 dark:text-[#FBBF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] px-2.5 py-1 rounded-full border border-amber-200">Awaiting Review</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $transactionsStats['pending_count'] }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Rs. {{ number_format($transactionsStats['pending_volume'], 2) }} In Review</p>
                </div>

                <!-- Verification Accuracy -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-purple-50 text-purple-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] rounded-2xl flex items-center justify-center shadow-xs border border-purple-100">
                            <svg class="w-6 h-6 text-purple-600 dark:text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] px-2.5 py-1 rounded-full border border-purple-200">Audit Ratio</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $transactionsStats['verification_rate'] }}%</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $transactionsStats['rejected_count'] }} Slips Rejected</p>
                </div>
            </div>

            <!-- Bank Breakdown Grid -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-8 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                <h3 class="text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight mb-1">Financial Institution Volume Breakdown</h3>
                <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mb-6">Distribution of inbound customer deposits grouped by licensed commercial banking provider</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-5">
                    @foreach($transactionsStats['bank_breakdown'] as $bank)
                        <div class="bg-gray-50/70 dark:bg-[#071916] p-5 rounded-2xl border border-gray-200/60 dark:border-[#163832]">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-black text-gray-900 dark:text-[#F5F3EF] truncate">{{ $bank['bank'] }}</span>
                                <span class="text-[10px] font-black text-[#C9A050] dark:text-[#D8B060]">{{ $bank['percentage'] }}%</span>
                            </div>
                            <h4 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] mb-2">Rs. {{ number_format($bank['volume'], 2) }}</h4>
                            <div class="w-full bg-gray-200 dark:bg-[#0D2824] h-2 rounded-full overflow-hidden mb-2">
                                <div class="bg-[#C9A050] h-2 rounded-full" style="width: {{ $bank['percentage'] }}%"></div>
                            </div>
                            <span class="text-[10px] text-gray-500 dark:text-[#8FA8A3] font-medium">{{ $bank['count'] }} transfers</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Daily Bank Inflow & Reconciliation Telemetry -->
            @if(isset($transactionsStats['daily_trend']) && count($transactionsStats['daily_trend']) > 0)
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] p-6 sm:p-8 shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Daily Bank Deposits & Inflow Timeline</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Day-by-day telemetry of incoming bank transfers and verification breakdown</p>
                        </div>
                        <span class="text-xs font-bold text-[#C9A050] bg-[#C9A050]/10 px-3 py-1 rounded-xl">Reconciliation Track</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                    <th class="py-3 px-4 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Date</th>
                                    <th class="py-3 px-4 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Transfers</th>
                                    <th class="py-3 px-4 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Volume</th>
                                    <th class="py-3 px-4 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Verified & Approved</th>
                                    <th class="py-3 px-4 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Pending Review</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                                @foreach($transactionsStats['daily_trend'] as $d)
                                    <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                        <td class="py-3 px-4 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                            {{ \Carbon\Carbon::parse($d->tx_date)->format('M d, Y (D)') }}
                                        </td>
                                        <td class="py-3 px-4 text-xs font-bold text-gray-700 dark:text-[#8FA8A3]">
                                            {{ $d->count }} slips
                                        </td>
                                        <td class="py-3 px-4 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                            Rs. {{ number_format($d->total_volume, 2) }}
                                        </td>
                                        <td class="py-3 px-4 text-xs font-black text-emerald-600 dark:text-emerald-400">
                                            Rs. {{ number_format($d->verified_volume, 2) }}
                                        </td>
                                        <td class="py-3 px-4 text-xs font-bold text-amber-600 dark:text-amber-400">
                                            Rs. {{ number_format($d->pending_volume, 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Full Bank Transfers & Slips Ledger Table -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Full Bank Payments & Slips Ledger</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Auditing log of customer deposit slips and online banking receipts within the selected period</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('reports.export', ['tab' => 'transactions', 'range' => $range, 'from' => $fromDate, 'to' => $toDate]) }}" class="px-4 py-2 bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] rounded-xl text-xs font-black shadow-xs hover:scale-105 transition-all">
                            Export Bank Report (CSV)
                        </a>
                        <a href="{{ route('transactions.index') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                            <span>Transactions Hub</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Order Ref</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Customer & Contact</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Bank / Channel</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Reference Code</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Transfer Amount</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Status</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Slip</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @php
                                $transferList = $transactionsStats['all_transfers'] ?? $transactionsStats['recent_transfers'];
                            @endphp
                            @forelse($transferList as $tx)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $tx->order_number ?: ('#ORD-' . str_pad($tx->id, 4, '0', STR_PAD_LEFT)) }}
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="text-xs font-bold text-gray-800 dark:text-[#F5F3EF]">{{ $tx->customer_name ?: ($tx->user->name ?? 'Direct Depositor') }}</p>
                                        <p class="text-[10px] text-gray-400 dark:text-[#8FA8A3]">{{ $tx->customer_phone ?: ($tx->user->phone ?? 'No Phone') }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $tx->payment_method ?: 'Bank Transfer' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-mono font-bold text-gray-900 dark:text-[#D8B060]">
                                        {{ $tx->payment_reference ?: '—' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        Rs. {{ number_format($tx->total_price ?: $tx->total_amount, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @php
                                            $ps = strtolower($tx->payment_status ?: 'pending');
                                            $badgeClass = match($ps) {
                                                'verified', 'paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
                                                'rejected'         => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border border-rose-200 dark:border-rose-800',
                                                default            => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeClass }}">
                                            {{ ucfirst($tx->payment_status ?: 'Pending Review') }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @if(!empty($tx->bank_receipt_url))
                                            <a href="{{ $tx->bank_receipt_url }}" target="_blank" class="px-2.5 py-1 bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400 border border-sky-200 dark:border-sky-800 rounded-lg text-[10px] font-black hover:underline flex items-center gap-1 w-max">
                                                <span>📎 View Slip</span>
                                            </a>
                                        @else
                                            <span class="text-[10px] text-gray-400 dark:text-[#8FA8A3]">None</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-400 dark:text-[#8FA8A3] whitespace-nowrap">
                                        {{ $tx->created_at->format('M d, Y h:i A') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No bank transfers recorded in this date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 4: CUSTOMERS & RETENTION -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'customers'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Customers KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Registered Accounts -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Customers</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($customersStats['total_count']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ number_format($customersStats['new_in_period']) }} Joined in Window</p>
                </div>

                <!-- Active Customers (Last 30 Days) -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Active (30D)</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($customersStats['active_count']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ number_format($customersStats['inactive_count']) }} Inactive Diners</p>
                </div>

                <!-- Recurring Subscriber Rate -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                            <svg class="w-6 h-6 text-amber-600 dark:text-[#FBBF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] px-2.5 py-1 rounded-full border border-amber-200">Retention</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $customersStats['recurring_rate'] }}%</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ number_format($customersStats['recurring_count']) }} Diners Placed ≥ 2 Orders</p>
                </div>

                <!-- Customer Lifetime Value (LTV) -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                            <svg class="w-6 h-6 text-blue-600 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200">Total LTV</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($customersStats['total_spend'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Avg. Rs. {{ number_format($customersStats['avg_ltv'], 2) }} / account</p>
                </div>
            </div>

            <!-- Top Spending Customers Leaderboard -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Top High-Value Customers</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Highest lifetime meal spend ranking across registered clientele</p>
                    </div>
                    <a href="{{ route('customers.index') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                        <span>Go to Customers Directory</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Rank</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Customer Identity</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Contact</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Orders</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Cumulative Spend</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Member Since</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($customersStats['top_clients'] as $index => $client)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-400 dark:text-[#8FA8A3]">
                                        #{{ $index + 1 }}
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-[#0A2E2A] text-[#C9A050] font-black text-xs flex items-center justify-center">
                                                {{ substr($client->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $client->name }}</p>
                                                <p class="text-[10px] text-gray-400 dark:text-[#8FA8A3]">{{ $client->email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $client->phone ?: 'No Phone Recorded' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $client->orders_count }} orders
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-[#C9A050] dark:text-[#D8B060]">
                                        Rs. {{ number_format($client->total_spent ?: 0, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-400 dark:text-[#8FA8A3]">
                                        {{ $client->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No customer accounts registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Full Customer Directory & Details Report -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden"
                 x-data="{ custSearch: '' }">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Full Customer Details Report</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#C9A050]/20 dark:text-[#D8B060]">
                                {{ count($customersStats['all_customers'] ?? $customersStats['top_clients']) }} Customers
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Comprehensive audit trail of customer profiles, delivery addresses, and cumulative expenditure</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="relative">
                            <input type="text" x-model="custSearch" placeholder="Filter customers..." 
                                   class="px-3.5 py-2 pl-8 text-xs rounded-xl border border-gray-200 dark:border-[#163832] bg-white dark:bg-[#071916] text-gray-900 dark:text-[#F5F3EF] focus:ring-2 focus:ring-[#C9A050] focus:outline-hidden w-48 sm:w-60">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <a href="{{ route('reports.export', ['tab' => 'customers']) }}" class="px-4 py-2 bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] rounded-xl text-xs font-black shadow-xs hover:scale-105 transition-all flex items-center gap-1.5 shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export All Customers</span>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">ID</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Customer Name</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Phone & Email</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Primary Delivery Address</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Dinner Address</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Orders</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Lifetime Spend</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Registered</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @php
                                $custList = $customersStats['all_customers'] ?? $customersStats['top_clients'];
                            @endphp
                            @forelse($custList as $c)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors"
                                    x-show="!custSearch || '{{ strtolower(addslashes($c->name . ' ' . $c->email . ' ' . ($c->phone ?? '') . ' ' . ($c->address ?? ''))) }}'.includes(custSearch.toLowerCase())">
                                    <td class="py-4 px-6 text-xs font-mono font-bold text-gray-400 dark:text-[#8FA8A3]">
                                        #CUST-{{ str_pad($c->id, 4, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#0A2E2A] to-[#123831] text-[#C9A050] font-black text-xs flex items-center justify-center shrink-0 shadow-xs border border-[#C9A050]/20">
                                                {{ substr($c->name, 0, 1) }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF] truncate">{{ $c->name }}</p>
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[9px] font-bold bg-gray-100 dark:bg-[#123831] text-gray-600 dark:text-[#8FA8A3]">
                                                    {{ $c->role ?: 'Customer' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <p class="text-xs font-bold text-gray-800 dark:text-[#F5F3EF]">{{ $c->phone ?: '—' }}</p>
                                        <p class="text-[10px] text-gray-400 dark:text-[#8FA8A3] truncate">{{ $c->email }}</p>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3] max-w-[200px] truncate" title="{{ $c->address }}">
                                        {{ $c->address ?: 'No address specified' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3] max-w-[180px] truncate" title="{{ $c->dinner_address }}">
                                        {{ $c->dinner_address ?: 'Same as primary' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $c->orders_count }} orders
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-[#C9A050] dark:text-[#D8B060]">
                                        Rs. {{ number_format($c->total_spent ?: 0, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-400 dark:text-[#8FA8A3] whitespace-nowrap">
                                        {{ $c->created_at->format('M d, Y') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No customer accounts registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 5: DRIVERS & FLEET LOGISTICS -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'drivers'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Drivers KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Registered Couriers -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a1 1 0 001 1h1"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Fleet Size</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $driversStats['total_drivers'] }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $driversStats['available_drivers'] }} Available for Dispatch</p>
                </div>

                <!-- Couriers In-Transit -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                            <svg class="w-6 h-6 text-amber-600 dark:text-[#FBBF24]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#FBBF24] px-2.5 py-1 rounded-full border border-amber-200">In Transit</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $driversStats['on_delivery_drivers'] }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $driversStats['active_assigned'] }} Meals En Route</p>
                </div>

                <!-- Fleet Utilization Rate -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                            <svg class="w-6 h-6 text-blue-600 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200">Utilization</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $driversStats['fleet_utilization'] }}%</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Capacity: {{ $driversStats['max_capacity'] }} Max Orders</p>
                </div>

                <!-- Cumulative Lifetime Deliveries -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Delivered</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($driversStats['total_deliveries']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Completed Meal Drops</p>
                </div>
            </div>

            <!-- Fleet Dispatch Leaderboard Table -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Driver Performance & Fleet Availability</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Real-time capacity tracking, assigned deliveries, and lifetime fulfillment telemetry</p>
                    </div>
                    <a href="{{ route('drivers') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                        <span>Go to Drivers Management</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Courier</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Vehicle</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Status</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Active Orders</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Max Capacity</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Deliveries</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($driversStats['drivers_list'] as $driver)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-xl bg-[#0A2E2A] text-[#C9A050] font-black text-xs flex items-center justify-center">
                                                {{ substr($driver->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $driver->name }}</p>
                                                <p class="text-[10px] text-gray-400 dark:text-[#8FA8A3]">{{ $driver->phone }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $driver->vehicle_type ?: 'Motorcycle' }} 
                                        @if($driver->vehicle_number)
                                            <span class="block text-[10px] font-mono text-gray-400 dark:text-[#527971]">{{ $driver->vehicle_number }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @php
                                            $dst = strtolower($driver->status);
                                            $dstClass = match($dst) {
                                                'available'   => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800',
                                                'on_delivery' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border border-amber-200 dark:border-amber-800',
                                                default       => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $dstClass }}">
                                            {{ str_replace('_', ' ', ucfirst($driver->status)) }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $driver->active_orders_count }} assigned
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $driver->max_orders }} orders
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-[#C9A050] dark:text-[#D8B060]">
                                        {{ number_format($driver->total_deliveries) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No drivers registered in fleet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 6: EVENTS & CATERING BANQUETS -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'events'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Events KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total Events Scheduled -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Banquets</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($eventsStats['total_events']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Total Scheduled in Window</p>
                </div>

                <!-- Total Banquet Revenue -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                            <svg class="w-6 h-6 text-emerald-600 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200">Catering Revenue</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($eventsStats['total_cost'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Avg. Rs. {{ number_format($eventsStats['avg_cost'], 2) }} / event</p>
                </div>

                <!-- Upcoming Events -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                            <svg class="w-6 h-6 text-blue-600 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200">Upcoming</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $eventsStats['upcoming_events'] }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $eventsStats['in_progress_events'] }} Live In Progress</p>
                </div>

                <!-- Courier Assignment Coverage -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-purple-50 text-purple-600 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] rounded-2xl flex items-center justify-center shadow-xs border border-purple-100">
                            <svg class="w-6 h-6 text-purple-600 dark:text-[#A78BFA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#A78BFA] px-2.5 py-1 rounded-full border border-purple-200">Coverage</span>
                    </div>
                    <h3 class="report-kpi-value text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ $eventsStats['courier_coverage'] }}%</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">{{ $eventsStats['unassigned_count'] }} Couriers Pending</p>
                </div>
            </div>

            <!-- Upcoming Events Schedule Table -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Scheduled Banquets Schedule</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Timeline of upcoming corporate events, banquet menus, and designated dispatch couriers</p>
                    </div>
                    <a href="{{ route('events') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                        <span>Go to Events Calendar</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Event Details</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Host / Client</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Dates & Duration</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Catering Value</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Designated Courier</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($eventsStats['upcoming_list'] as $event)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $event->name }}
                                        <span class="block text-[10px] text-gray-400 dark:text-[#8FA8A3] font-normal">{{ $event->items->count() }} menu course(s)</span>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-800 dark:text-[#F5F3EF]">
                                        {{ $event->user->name ?? 'Corporate Host' }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ \Carbon\Carbon::parse($event->start_date)->format('M d, Y') }} 
                                        @if($event->end_date && $event->end_date !== $event->start_date)
                                            <span class="text-gray-400">→</span> {{ \Carbon\Carbon::parse($event->end_date)->format('M d, Y') }}
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        Rs. {{ number_format($event->total_cost, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @if($event->driver)
                                            <span class="inline-flex items-center gap-1.5 font-bold text-emerald-600 dark:text-emerald-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                <span>{{ $event->driver->name }}</span>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 font-bold text-amber-600 dark:text-amber-400">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                                <span>Unassigned</span>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @php
                                            $now = \Carbon\Carbon::now();
                                            $start = \Carbon\Carbon::parse($event->start_date)->startOfDay();
                                            $end = \Carbon\Carbon::parse($event->end_date ?: $event->start_date)->endOfDay();

                                            if ($now->lt($start)) {
                                                $statusText = 'Upcoming';
                                                $badgeStyle = 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-200 dark:border-blue-800';
                                            } elseif ($now->between($start, $end)) {
                                                $statusText = 'Live Today';
                                                $badgeStyle = 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 animate-pulse';
                                            } else {
                                                $statusText = 'Concluded';
                                                $badgeStyle = 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-200 dark:border-gray-700';
                                            }
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $badgeStyle }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No banquet events scheduled in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- TAB 7: MEAL-WISE PERFORMANCE & POPULARITY -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'meals'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- Meals Channel Selector & Excel Export Header -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-5 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3] mr-2">Meal Source:</span>
                    <a href="{{ route('reports.index', ['tab' => 'meals', 'range' => $range, 'from' => $fromDate, 'to' => $toDate, 'meal_channel' => 'all']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 {{ ($mealChannel ?? 'all') === 'all' ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] shadow-md' : 'bg-gray-100 hover:bg-gray-200/70 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:hover:bg-[#123831]' }}">
                        <span>🌐 All Channels</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($mealChannel ?? 'all') === 'all' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/20 dark:text-[#061513]' : 'bg-gray-200 dark:bg-[#163832]' }}">{{ number_format($mealsStats['total_quantity']) }}</span>
                    </a>
                    <a href="{{ route('reports.index', ['tab' => 'meals', 'range' => $range, 'from' => $fromDate, 'to' => $toDate, 'meal_channel' => 'pos']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 {{ ($mealChannel ?? 'all') === 'pos' ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] shadow-md' : 'bg-gray-100 hover:bg-gray-200/70 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:hover:bg-[#123831]' }}">
                        <span>🛒 POS Terminal Items</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($mealChannel ?? 'all') === 'pos' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/20 dark:text-[#061513]' : 'bg-gray-200 dark:bg-[#163832]' }}">{{ number_format($mealsStats['pos_total_quantity']) }}</span>
                    </a>
                    <a href="{{ route('reports.index', ['tab' => 'meals', 'range' => $range, 'from' => $fromDate, 'to' => $toDate, 'meal_channel' => 'app']) }}"
                       class="px-4 py-2 rounded-xl text-xs font-black transition-all flex items-center gap-1.5 {{ ($mealChannel ?? 'all') === 'app' ? 'bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] shadow-md' : 'bg-gray-100 hover:bg-gray-200/70 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:hover:bg-[#123831]' }}">
                        <span>📱 Mobile App Meals</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] {{ ($mealChannel ?? 'all') === 'app' ? 'bg-[#C9A050]/20 text-[#C9A050] dark:bg-[#061513]/20 dark:text-[#061513]' : 'bg-gray-200 dark:bg-[#163832]' }}">{{ number_format($mealsStats['app_total_quantity']) }}</span>
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.export.excel', ['range' => $range, 'from' => $fromDate, 'to' => $toDate, 'channel' => $mealChannel ?? 'all']) }}"
                       class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-700 hover:from-emerald-500 hover:to-teal-600 text-white rounded-xl text-xs font-black shadow-md shadow-emerald-700/25 flex items-center gap-2 transition-all hover:scale-[1.02]">
                        <svg class="w-4 h-4 text-emerald-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Download Detailed Color Excel (.xlsx)</span>
                    </a>
                </div>
            </div>

            <!-- Channel Breakdown Comparison Cards (with Gross vs Net & Discount Clarity) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div class="p-6 bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-emerald-500/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400">🛒 POS Terminal Items</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300">Counter & Retail</span>
                        </div>
                        <div class="flex items-baseline space-x-2">
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($mealsStats['pos_total_revenue'], 2) }}</p>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Gross Menu</span>
                        </div>
                        <p class="text-xs font-bold text-gray-500 dark:text-[#8FA8A3] mt-1">{{ number_format($mealsStats['pos_total_quantity']) }} items sold across Dine-In & Takeaway</p>
                    </div>

                    <!-- Gross vs Net & Discount Breakdown -->
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-[#163832] flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-400">Discounts:</span>
                            <span class="text-xs font-bold {{ ($mealsStats['pos_discounts'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                                {{ ($mealsStats['pos_discounts'] ?? 0) > 0 ? '-Rs. ' . number_format($mealsStats['pos_discounts'], 2) : 'Rs. 0.00' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-400">Net Collected:</span>
                            <span class="text-xs font-black text-emerald-700 dark:text-[#34D399]">
                                Rs. {{ number_format($mealsStats['pos_net_revenue'] ?? $mealsStats['pos_total_revenue'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-amber-500/30 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-black uppercase tracking-wider text-amber-600 dark:text-amber-400">📱 Mobile App Meals</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300">Scheduled Delivery</span>
                        </div>
                        <div class="flex items-baseline space-x-2">
                            <p class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($mealsStats['app_total_revenue'], 2) }}</p>
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Gross Menu</span>
                        </div>
                        <p class="text-xs font-bold text-gray-500 dark:text-[#8FA8A3] mt-1">{{ number_format($mealsStats['app_total_quantity']) }} portions dispatched to customers</p>
                    </div>

                    <!-- Gross vs Net & Discount Breakdown -->
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-[#163832] flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-400">Discounts:</span>
                            <span class="text-xs font-bold {{ ($mealsStats['app_discounts'] ?? 0) > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400' }}">
                                {{ ($mealsStats['app_discounts'] ?? 0) > 0 ? '-Rs. ' . number_format($mealsStats['app_discounts'], 2) : 'Rs. 0.00' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-400">Net Collected:</span>
                            <span class="text-xs font-black text-amber-700 dark:text-amber-400">
                                Rs. {{ number_format($mealsStats['app_net_revenue'] ?? $mealsStats['app_total_revenue'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-gradient-to-br from-[#0A2E2A] to-[#123831] text-white rounded-[2.5rem] shadow-xl border border-[#C9A050]/40 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2.5">
                            <span class="text-xs font-black uppercase tracking-wider text-[#C9A050]">🌟 Total Menu Volume</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-[#C9A050]/20 text-[#C9A050]">All Sources</span>
                        </div>
                        <div class="flex items-baseline space-x-2">
                            <p class="text-2xl sm:text-3xl font-black text-white">Rs. {{ number_format($mealsStats['total_revenue'], 2) }}</p>
                            <span class="text-[10px] font-bold text-[#C9A050] uppercase tracking-wider">Gross Volume</span>
                        </div>
                        <p class="text-xs font-bold text-gray-300 mt-1">{{ number_format($mealsStats['total_quantity']) }} total meals & items in window</p>
                    </div>

                    <!-- Gross vs Net & Discount Breakdown -->
                    <div class="mt-4 pt-3 border-t border-white/10 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-300">Total Discounts:</span>
                            <span class="text-xs font-bold text-amber-300">
                                {{ ($mealsStats['total_discounts'] ?? 0) > 0 ? '-Rs. ' . number_format($mealsStats['total_discounts'], 2) : 'Rs. 0.00' }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-1.5">
                            <span class="text-[11px] font-bold text-gray-300">Net Cash Inflow:</span>
                            <span class="text-xs font-black text-[#C9A050]">
                                Rs. {{ number_format($mealsStats['total_net_revenue'] ?? $mealsStats['total_revenue'], 2) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4 Luxury Category KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @php
                    $catMeta = [
                        'Sri Lankan Style Lunch Menu' => ['icon' => '☀️', 'color' => 'orange', 'title' => 'SL Lunch Menu', 'badge' => 'Mid-day Specials'],
                        'Sri Lankan Style Dinner Menu' => ['icon' => '🌙', 'color' => 'indigo', 'title' => 'SL Dinner Menu', 'badge' => 'Evening Courses'],
                        'Sri Lankan Style Breakfast Menu' => ['icon' => '🍳', 'color' => 'amber', 'title' => 'SL Breakfast Menu', 'badge' => 'Morning Specials'],
                        'English breakfast Menu' => ['icon' => '🥪', 'color' => 'emerald', 'title' => 'English Breakfast', 'badge' => 'Continental Courses'],
                    ];
                @endphp

                @foreach($mealsStats['category_ranking'] as $cr)
                    @php
                        $meta = $catMeta[$cr['name']] ?? ['icon' => '🍽️', 'color' => 'emerald', 'title' => $cr['name'], 'badge' => 'Gourmet Menu'];
                        $isLeader = ($mealsStats['highest_selling']['name'] === $cr['name']);
                    @endphp
                    <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border {{ $isLeader ? 'border-[#C9A050] dark:border-[#C9A050]' : 'border-gray-200/70 dark:border-[#163832]' }} relative overflow-hidden group">
                        @if($isLeader)
                            <div class="absolute top-0 right-0 bg-gradient-to-l from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] text-[9px] font-black uppercase tracking-widest px-3 py-1 rounded-bl-xl shadow-xs">
                                👑 #1 Best-Seller
                            </div>
                        @endif

                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-[#123831] border border-gray-200/80 dark:border-[#1E4940] flex items-center justify-center text-2xl shadow-xs">
                                {{ $cr['icon'] }}
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-wider px-2.5 py-1 rounded-full {{ $isLeader ? 'bg-[#C9A050]/20 text-[#C9A050] border border-[#C9A050]/40' : 'bg-gray-100 text-gray-600 dark:bg-[#081A17] dark:text-[#8FA8A3] dark:border dark:border-[#163832]' }}">
                                {{ $cr['share'] }}% Share
                            </span>
                        </div>

                        <h3 class="text-3xl sm:text-4xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">
                            {{ number_format($cr['quantity']) }} <span class="text-xs font-semibold text-gray-400">servings</span>
                        </h3>
                        <p class="text-xs font-black text-gray-800 dark:text-[#D8B060] mt-1">{{ $cr['name'] }}</p>
                        
                        <div class="mt-3 pt-3 border-t border-gray-100 dark:border-[#163832] flex items-center justify-between text-xs">
                            <span class="text-gray-400 dark:text-[#8FA8A3] font-medium">Gross Revenue:</span>
                            <span class="font-black text-[#C9A050]">Rs. {{ number_format($cr['revenue'], 2) }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Highest-Selling Ranking & Share Overview -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Leader Card -->
                <div class="lg:col-span-1 bg-gradient-to-br from-[#0A2E2A] to-[#123831] text-white p-7 sm:p-8 rounded-[2.5rem] shadow-2xl border border-[#C9A050]/30 flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-[#C9A050]/10 rounded-full blur-2xl"></div>

                    <div>
                        <div class="flex items-center space-x-2.5 mb-4">
                            <span class="px-3 py-1 bg-[#C9A050] text-[#0A2E2A] text-[10px] font-black rounded-full uppercase tracking-widest shadow-xs">
                                Top Performer
                            </span>
                            <span class="text-xs text-emerald-200/80 font-medium">Selected Window</span>
                        </div>
                        <div class="text-5xl mb-3">{{ $mealsStats['highest_selling']['icon'] }}</div>
                        <h4 class="text-2xl font-black tracking-tight text-white mb-2">{{ $mealsStats['highest_selling']['name'] }}</h4>
                        <p class="text-xs text-gray-300 leading-relaxed font-medium">
                            Recognized as the highest-selling meal service category across all customer orders and subscriptions.
                        </p>
                    </div>

                    <div class="mt-6 pt-5 border-t border-white/10 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Servings</p>
                            <p class="text-2xl font-black text-[#C9A050]">{{ number_format($mealsStats['highest_selling']['quantity']) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-gray-400">Total Volume</p>
                            <p class="text-2xl font-black text-[#C9A050]">Rs. {{ number_format($mealsStats['highest_selling']['revenue'], 0) }}</p>
                        </div>
                    </div>
                </div>

                <!-- Category Ranking Table -->
                <div class="lg:col-span-2 bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] p-6 sm:p-8 shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Meal Categories Popularity & Sales Ranking</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Ranked from highest to lowest volume of meals ordered by customers</p>
                        </div>
                        <span class="text-xs font-bold text-gray-400">Total: {{ number_format($mealsStats['total_quantity']) }} meals</span>
                    </div>

                    <div class="space-y-4">
                        @foreach($mealsStats['category_ranking'] as $idx => $r)
                            <div class="p-4 bg-gray-50/80 dark:bg-[#071916] rounded-2xl border border-gray-200/60 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-center space-x-3.5">
                                    <div class="w-8 h-8 rounded-xl font-black text-xs flex items-center justify-center {{ $idx === 0 ? 'bg-amber-500 text-white shadow-md' : ($idx === 1 ? 'bg-gray-300 text-gray-800' : 'bg-gray-100 text-gray-500 dark:bg-[#123831] dark:text-[#A3B8B3]') }}">
                                        #{{ $idx + 1 }}
                                    </div>
                                    <div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-base">{{ $r['icon'] }}</span>
                                            <h4 class="text-sm font-black text-gray-900 dark:text-[#F5F3EF]">{{ $r['name'] }}</h4>
                                        </div>
                                        <p class="text-[11px] text-gray-400 dark:text-[#8FA8A3] mt-0.5">Avg. Price: Rs. {{ number_format($r['avg_price'], 2) }} / meal</p>
                                    </div>
                                </div>

                                <div class="flex items-center space-x-6 sm:text-right">
                                    <div>
                                        <p class="text-sm font-black text-gray-900 dark:text-[#F5F3EF]">{{ number_format($r['quantity']) }} meals</p>
                                        <p class="text-[10px] font-bold text-gray-400 dark:text-[#8FA8A3]">{{ $r['share'] }}% share</p>
                                    </div>
                                    <div class="min-w-[100px]">
                                        <p class="text-sm font-black text-[#C9A050]">Rs. {{ number_format($r['revenue'], 2) }}</p>
                                        <div class="w-full bg-gray-200 dark:bg-[#123831] h-1.5 rounded-full overflow-hidden mt-1">
                                            <div class="bg-gradient-to-r from-[#C9A050] to-[#b88f3f] h-1.5 rounded-full" style="width: {{ $r['share'] }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Monthly Meal Service Telemetry (Last 6 Months Trend) -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-3">
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Monthly Meal Telemetry & Historical Trends</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#C9A050]/20 dark:text-[#D8B060]">
                                Past 6 Months
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Month-by-month comparative analysis of meal service distribution and revenue</p>
                    </div>

                    <a href="{{ route('reports.export', ['tab' => 'meals']) }}" class="px-4 py-2 bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] rounded-xl text-xs font-black shadow-xs hover:scale-105 transition-all flex items-center gap-1.5 shrink-0 self-start sm:self-auto">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>Export Meal Report</span>
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Month</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Highest-Selling Meal</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Servings</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Revenue</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Lunch Qty</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Dinner Qty</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Breakfast Qty</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">English Qty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($mealsStats['monthly_trend'] as $mt)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ $mt['month'] }}
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black bg-gradient-to-r from-amber-500/15 to-orange-500/15 text-amber-900 dark:text-[#F59E0B] border border-amber-300/40">
                                            <span>⭐</span>
                                            <span>{{ $mt['top_meal_type'] }}</span>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        {{ number_format($mt['total_quantity']) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-[#C9A050]">
                                        Rs. {{ number_format($mt['total_revenue'], 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-[#8FA8A3]">
                                        {{ number_format($mt['lunch_qty']) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-[#8FA8A3]">
                                        {{ number_format($mt['dinner_qty']) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-[#8FA8A3]">
                                        {{ number_format($mt['breakfast_qty']) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-700 dark:text-[#8FA8A3]">
                                        {{ number_format($mt['english_qty']) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No historical meal data available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top 10 Best-Selling Gourmet Dishes -->
            @if(isset($mealsStats['top_dishes']) && count($mealsStats['top_dishes']) > 0)
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Top-Performing Dishes by Volume</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Most frequently ordered individual culinary creations</p>
                        </div>
                        <a href="{{ route('foods.index') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                            <span>Catalog Manager</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Rank</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Dish Name</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Meal Category</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Units Ordered</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                                @foreach($mealsStats['top_dishes'] as $idx => $d)
                                    <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                        <td class="py-4 px-6 text-xs font-black text-gray-400 dark:text-[#8FA8A3]">
                                            #{{ $idx + 1 }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                @if(!empty($d['image_url']))
                                                    <img src="{{ $d['image_url'] }}" alt="{{ $d['name'] }}" class="w-10 h-10 rounded-xl object-cover shadow-xs border border-gray-200 shrink-0">
                                                @else
                                                    <div class="w-10 h-10 rounded-xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-lg font-bold shrink-0">🍽️</div>
                                                @endif
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $d['name'] }}</p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-[#123831] text-gray-700 dark:text-[#D8B060]">
                                                {{ $d['category'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                            {{ number_format($d['quantity']) }} servings
                                        </td>
                                        <td class="py-4 px-6 text-xs font-black text-[#C9A050]">
                                            Rs. {{ number_format($d['revenue'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- SEPARATED SECTION: POS Terminal Counter Items Performance -->
            @if(($mealChannel ?? 'all') === 'all' || ($mealChannel ?? 'all') === 'pos')
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-emerald-500/30 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">🛒 POS Terminal Counter Items Breakdown</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300">
                                    POS Terminal Sales
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Top-selling Dine-In and Takeaway items, beverages, and counter specials</p>
                        </div>
                        <div class="flex items-center flex-wrap gap-3">
                            <div class="text-right">
                                <div class="flex items-center justify-end space-x-2 text-xs">
                                    <span class="text-gray-500 dark:text-[#8FA8A3] font-bold">Gross Menu: <strong class="text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($mealsStats['pos_total_revenue'], 2) }}</strong></span>
                                    @if(($mealsStats['pos_discounts'] ?? 0) > 0)
                                        <span class="text-amber-600 dark:text-amber-400 font-bold">· Discounts: -Rs. {{ number_format($mealsStats['pos_discounts'], 2) }}</span>
                                    @endif
                                </div>
                                <div class="text-xs font-black text-emerald-700 dark:text-[#34D399]">
                                    Net Collected Cash: Rs. {{ number_format($mealsStats['pos_net_revenue'] ?? $mealsStats['pos_total_revenue'], 2) }}
                                </div>
                            </div>
                            <a href="{{ route('reports.export.excel', ['range' => $range, 'from' => $fromDate, 'to' => $toDate, 'channel' => 'pos']) }}" 
                               class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-black hover:bg-emerald-500 transition-all flex items-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Export POS Sheet (.xlsx)</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-[#163832] bg-emerald-50/40 dark:bg-[#071916]">
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Rank</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Item / Beverage</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Category</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Portion / Spec</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Units Sold</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Gross Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                                @forelse($mealsStats['pos_top_dishes'] as $idx => $p)
                                    <tr class="hover:bg-emerald-50/20 dark:hover:bg-[#123831]/20 transition-colors">
                                        <td class="py-4 px-6 text-xs font-black text-gray-400 dark:text-[#8FA8A3]">#{{ $idx + 1 }}</td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                @if(!empty($p['image_url']))
                                                    <img src="{{ $p['image_url'] }}" alt="{{ $p['name'] }}" class="w-10 h-10 rounded-xl object-cover shadow-xs border border-gray-200 shrink-0">
                                                @else
                                                    <div class="w-10 h-10 rounded-xl bg-emerald-800 text-emerald-200 flex items-center justify-center text-lg font-bold shrink-0">🛒</div>
                                                @endif
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $p['name'] }}</p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-[#123831] text-gray-700 dark:text-[#D8B060]">
                                                {{ $p['category'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs font-bold text-gray-600 dark:text-[#8FA8A3]">{{ $p['portion'] }}</td>
                                        <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ number_format($p['quantity']) }} sold</td>
                                        <td class="py-4 px-6 text-xs font-black text-emerald-600 dark:text-emerald-400">Rs. {{ number_format($p['revenue'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No POS terminal sales recorded in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- SEPARATED SECTION: Mobile App Scheduled Meals Performance -->
            @if(($mealChannel ?? 'all') === 'all' || ($mealChannel ?? 'all') === 'app')
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-amber-500/30 overflow-hidden">
                    <div class="p-6 sm:p-8 border-b border-gray-100 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">📱 Mobile App Scheduled Meals Breakdown</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300">
                                    App Deliveries
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Top culinary creations ordered via Customer App subscriptions and on-demand dispatches</p>
                        </div>
                        <div class="flex items-center flex-wrap gap-3">
                            <div class="text-right">
                                <div class="flex items-center justify-end space-x-2 text-xs">
                                    <span class="text-gray-500 dark:text-[#8FA8A3] font-bold">Gross Menu: <strong class="text-gray-900 dark:text-[#F5F3EF]">Rs. {{ number_format($mealsStats['app_total_revenue'], 2) }}</strong></span>
                                    @if(($mealsStats['app_discounts'] ?? 0) > 0)
                                        <span class="text-amber-600 dark:text-amber-400 font-bold">· Discounts: -Rs. {{ number_format($mealsStats['app_discounts'], 2) }}</span>
                                    @endif
                                </div>
                                <div class="text-xs font-black text-amber-700 dark:text-amber-400">
                                    Net Collected Cash: Rs. {{ number_format($mealsStats['app_net_revenue'] ?? $mealsStats['app_total_revenue'], 2) }}
                                </div>
                            </div>
                            <a href="{{ route('reports.export.excel', ['range' => $range, 'from' => $fromDate, 'to' => $toDate, 'channel' => 'app']) }}" 
                               class="px-4 py-2 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] rounded-xl text-xs font-black hover:opacity-90 transition-all flex items-center gap-1.5 shadow-sm">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                <span>Export App Sheet (.xlsx)</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-[#163832] bg-amber-50/40 dark:bg-[#071916]">
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Rank</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Dish Name</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Meal Course</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Menu Category</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Portions Ordered</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Gross Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                                @forelse($mealsStats['app_top_dishes'] as $idx => $a)
                                    <tr class="hover:bg-amber-50/20 dark:hover:bg-[#123831]/20 transition-colors">
                                        <td class="py-4 px-6 text-xs font-black text-gray-400 dark:text-[#8FA8A3]">#{{ $idx + 1 }}</td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                @if(!empty($a['image_url']))
                                                    <img src="{{ $a['image_url'] }}" alt="{{ $a['name'] }}" class="w-10 h-10 rounded-xl object-cover shadow-xs border border-gray-200 shrink-0">
                                                @else
                                                    <div class="w-10 h-10 rounded-xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-lg font-bold shrink-0">📱</div>
                                                @endif
                                                <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $a['name'] }}</p>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-[#C9A050]/15 text-[#C9A050]">
                                                {{ $a['meal_type'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">{{ $a['category'] }}</td>
                                        <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ number_format($a['quantity']) }} portions</td>
                                        <td class="py-4 px-6 text-xs font-black text-[#C9A050]">Rs. {{ number_format($a['revenue'], 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">No Mobile App meal orders recorded in this period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

        <!-- ============================================================== -->
        <!-- TAB 8: POS & BUFFET COUNTER TERMINAL -->
        <!-- ============================================================== -->
        <div x-show="activeTab === 'pos'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-2" class="space-y-6">
            <!-- 4 Master KPI Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <!-- Total POS Revenue -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-emerald-50 text-emerald-700 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-emerald-700 dark:text-[#34D399]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#34D399] px-2.5 py-1 rounded-full border border-emerald-200 dark:border-transparent">Counter Revenue</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($posStats['total_revenue'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Dine-In & Takeaway Net Total</p>
                </div>

                <!-- Total Counter Bills -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-[#0A2E2A] dark:text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#D8B060] px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Tickets Issued</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">{{ number_format($posStats['total_orders']) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Completed Counter Transactions</p>
                </div>

                <!-- Average Ticket Size -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-amber-50 text-amber-700 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#F59E0B] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-amber-700 dark:text-[#F59E0B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-amber-800 bg-amber-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#F59E0B] px-2.5 py-1 rounded-full border border-amber-200 dark:border-transparent">Average Ticket</span>
                    </div>
                    <h3 class="report-kpi-value text-2xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Rs. {{ number_format($posStats['avg_ticket'], 2) }}</h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">Mean Guest Spend per Order</p>
                </div>

                <!-- Payment Channels Split -->
                <div class="report-kpi-card bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] hover:border-[#C9A050]/40 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <div class="report-kpi-icon w-12 h-12 bg-blue-50 text-blue-700 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] rounded-2xl flex items-center justify-center shadow-xs">
                            <svg class="w-6 h-6 text-blue-700 dark:text-[#60A5FA]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                        </div>
                        <span class="report-kpi-pill text-[10px] font-black uppercase tracking-wider text-blue-800 bg-blue-50 dark:bg-[#123831] dark:border dark:border-[#1E4940] dark:text-[#60A5FA] px-2.5 py-1 rounded-full border border-blue-200 dark:border-transparent">Payment Split</span>
                    </div>
                    <h3 class="report-kpi-value text-xl font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">
                        {{ $posStats['cash_count'] }} Cash / {{ $posStats['card_count'] }} Card
                    </h3>
                    <p class="report-kpi-title text-xs text-gray-500 dark:text-[#8FA8A3] font-medium mt-1">
                        @if($posStats['online_count'] > 0) +{{ $posStats['online_count'] }} QR / Online @else 100% Counter Settled @endif
                    </p>
                </div>
            </div>

            <!-- 2 Analytical Breakdown Panels -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Payment Methods Distribution -->
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Payment Channel Breakdown</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3]">Counter receipts by settlement method</p>
                        </div>
                        <span class="text-xs font-bold text-[#C9A050]">Rs. {{ number_format($posStats['total_revenue'], 0) }} Total</span>
                    </div>

                    <div class="space-y-4">
                        @php
                            $totalPosRev = max(1, $posStats['total_revenue']);
                            $cashPct = round(($posStats['cash_revenue'] / $totalPosRev) * 100, 1);
                            $cardPct = round(($posStats['card_revenue'] / $totalPosRev) * 100, 1);
                            $onlinePct = round(($posStats['online_revenue'] / $totalPosRev) * 100, 1);
                        @endphp

                        <!-- Cash -->
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-[#F5F3EF]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                                    <span>Cash Tendered ({{ $posStats['cash_count'] }} bills)</span>
                                </span>
                                <span class="text-emerald-700 dark:text-[#34D399]">Rs. {{ number_format($posStats['cash_revenue'], 2) }} ({{ $cashPct }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 dark:bg-[#123831] rounded-full overflow-hidden">
                                <div class="h-full bg-emerald-500 rounded-full transition-all duration-500" style="width: {{ $cashPct }}%"></div>
                            </div>
                        </div>

                        <!-- Card -->
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-[#F5F3EF]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                                    <span>Credit / Debit Card ({{ $posStats['card_count'] }} bills)</span>
                                </span>
                                <span class="text-blue-700 dark:text-[#60A5FA]">Rs. {{ number_format($posStats['card_revenue'], 2) }} ({{ $cardPct }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 dark:bg-[#123831] rounded-full overflow-hidden">
                                <div class="h-full bg-blue-500 rounded-full transition-all duration-500" style="width: {{ $cardPct }}%"></div>
                            </div>
                        </div>

                        <!-- Online / QR -->
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-[#F5F3EF]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#C9A050]"></span>
                                    <span>Online Transfer / QR ({{ $posStats['online_count'] }} bills)</span>
                                </span>
                                <span class="text-[#C9A050]">Rs. {{ number_format($posStats['online_revenue'], 2) }} ({{ $onlinePct }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 dark:bg-[#123831] rounded-full overflow-hidden">
                                <div class="h-full bg-[#C9A050] rounded-full transition-all duration-500" style="width: {{ $onlinePct }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Order Type Distribution (Dine-In vs Takeaway) -->
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md p-6 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832]">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-base font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Order Type Distribution</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3]">Dine-In seating vs counter takeaway orders</p>
                        </div>
                        <span class="text-xs font-bold text-gray-500 dark:text-[#8FA8A3]">{{ $posStats['total_orders'] }} Total Tickets</span>
                    </div>

                    <div class="space-y-4">
                        @php
                            $totalTickets = max(1, $posStats['total_orders']);
                            $dineInPct = round(($posStats['dine_in_count'] / $totalTickets) * 100, 1);
                            $takeawayPct = round(($posStats['takeaway_count'] / $totalTickets) * 100, 1);
                        @endphp

                        <!-- Dine-In -->
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-[#F5F3EF]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                                    <span>Dine-In Guests ({{ $posStats['dine_in_count'] }} orders)</span>
                                </span>
                                <span class="text-amber-700 dark:text-[#F59E0B]">Rs. {{ number_format($posStats['dine_in_revenue'], 2) }} ({{ $dineInPct }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 dark:bg-[#123831] rounded-full overflow-hidden">
                                <div class="h-full bg-amber-500 rounded-full transition-all duration-500" style="width: {{ $dineInPct }}%"></div>
                            </div>
                        </div>

                        <!-- Takeaway -->
                        <div>
                            <div class="flex justify-between items-center text-xs mb-1.5 font-bold">
                                <span class="flex items-center gap-2 text-gray-700 dark:text-[#F5F3EF]">
                                    <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                                    <span>Takeaway / Parcel ({{ $posStats['takeaway_count'] }} orders)</span>
                                </span>
                                <span class="text-teal-700 dark:text-[#2DD4BF]">Rs. {{ number_format($posStats['takeaway_revenue'], 2) }} ({{ $takeawayPct }}%)</span>
                            </div>
                            <div class="w-full h-2.5 bg-gray-100 dark:bg-[#123831] rounded-full overflow-hidden">
                                <div class="h-full bg-teal-500 rounded-full transition-all duration-500" style="width: {{ $takeawayPct }}%"></div>
                            </div>
                        </div>

                        <div class="pt-2 flex items-center justify-between text-xs text-gray-500 dark:text-[#8FA8A3] border-t border-gray-100 dark:border-[#163832]">
                            <span>POS Terminal Station</span>
                            <a href="{{ route('pos.index') }}" class="font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                                <span>Open POS Terminal</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top 10 Selling Buffet & Beverage Items -->
            @if(count($posStats['top_items']) > 0)
                <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-[#163832] flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Top-Selling Buffet & Beverage Items</h3>
                            <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Highest volume dishes and refreshments ordered via POS counter</p>
                        </div>
                        <a href="{{ route('foods.index') }}" class="text-xs font-bold text-[#C9A050] hover:underline flex items-center gap-1">
                            <span>Catalog Manager</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Rank</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Item Name & Details</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Category</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Units Dispensed</th>
                                    <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Total Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                                @foreach($posStats['top_items'] as $idx => $item)
                                    <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                        <td class="py-4 px-6 text-xs font-black text-gray-400 dark:text-[#8FA8A3]">
                                            #{{ $idx + 1 }}
                                        </td>
                                        <td class="py-4 px-6">
                                            <div class="flex items-center space-x-3">
                                                @if(!empty($item['image_url']))
                                                    <img src="{{ $item['image_url'] }}" alt="{{ $item['name'] }}" class="w-10 h-10 rounded-xl object-cover shadow-xs border border-gray-200 shrink-0">
                                                @else
                                                    <div class="w-10 h-10 rounded-xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-lg font-bold shrink-0">🍲</div>
                                                @endif
                                                <div>
                                                    <p class="text-xs font-black text-gray-900 dark:text-[#F5F3EF]">{{ $item['name'] }}</p>
                                                    @if(!empty($item['portion']))
                                                        <p class="text-[10px] text-gray-400 dark:text-[#8FA8A3]">{{ $item['portion'] }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-gray-100 dark:bg-[#123831] text-gray-700 dark:text-[#D8B060]">
                                                {{ $item['category'] }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                            {{ number_format($item['quantity']) }} portions
                                        </td>
                                        <td class="py-4 px-6 text-xs font-black text-[#C9A050]">
                                            Rs. {{ number_format($item['revenue'], 2) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Recent Counter Invoices Ledger -->
            <div class="bg-white/95 dark:bg-[#0A211E]/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 dark:border-[#163832] overflow-hidden">
                <div class="p-6 border-b border-gray-100 dark:border-[#163832] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-gray-900 dark:text-[#F5F3EF] tracking-tight">Recent Counter Sales Ledger</h3>
                        <p class="text-xs text-gray-500 dark:text-[#8FA8A3] mt-0.5">Itemized transaction audit trail with 1-click in-page thermal receipt reprint</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('reports.export', ['tab' => 'pos', 'range' => $range, 'from' => $fromDate, 'to' => $toDate]) }}" class="px-4 py-2 bg-[#0A2E2A] text-[#C9A050] dark:bg-[#C9A050] dark:text-[#061513] rounded-xl text-xs font-black shadow-xs hover:scale-105 transition-all flex items-center gap-1.5 shrink-0">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            <span>Export POS CSV</span>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-[#163832] bg-gray-50/60 dark:bg-[#071916]">
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Receipt #</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Date & Time</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Guest / Desk</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Type</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Items</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Payment</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3]">Net Total</th>
                                <th class="py-4 px-6 text-[10px] font-black uppercase tracking-wider text-gray-400 dark:text-[#8FA8A3] text-right">Receipt</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/80 dark:divide-[#163832]">
                            @forelse($posStats['recent_orders'] as $order)
                                <tr class="hover:bg-[#C9A050]/[0.03] dark:hover:bg-[#C9A050]/[0.05] transition-colors">
                                    <td class="py-4 px-6 text-xs font-black text-gray-900 dark:text-[#F5F3EF]">
                                        #ORD-{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $order->created_at->format('M d, Y • h:i A') }}
                                    </td>
                                    <td class="py-4 px-6 text-xs font-bold text-gray-800 dark:text-[#F5F3EF]">
                                        {{ $order->customer_name ?: ($order->user?->name ?: 'Walk-in Guest') }}
                                        @if(stripos($order->address, 'Table') !== false)
                                            <span class="block text-[10px] text-[#C9A050] font-normal">{{ $order->address }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @if(stripos($order->address, 'Takeaway') !== false)
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-50 text-teal-700 dark:bg-[#123831] dark:text-[#2DD4BF]">Takeaway</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-800 dark:bg-[#123831] dark:text-[#F59E0B]">Dine-In</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs text-gray-600 dark:text-[#8FA8A3]">
                                        {{ $order->items ? $order->items->sum('quantity') : 0 }} item(s)
                                    </td>
                                    <td class="py-4 px-6 text-xs">
                                        @if($order->payment_method === 'Cash')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 dark:bg-[#123831] dark:text-[#34D399]">💵 Cash</span>
                                        @elseif($order->payment_method === 'Card')
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 dark:bg-[#123831] dark:text-[#60A5FA]">💳 Card</span>
                                        @else
                                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 dark:bg-[#123831] dark:text-[#C084FC]">📱 {{ $order->payment_method }}</span>
                                        @endif
                                    </td>
                                    <td class="py-4 px-6 text-xs font-black text-[#C9A050]">
                                        Rs. {{ number_format($order->total_price, 2) }}
                                    </td>
                                    <td class="py-4 px-6 text-right">
                                        <button type="button" 
                                                onclick="printReceiptInPage('{{ route('pos.receipt', $order->id) }}')"
                                                class="px-3 py-1.5 bg-gray-100 hover:bg-[#C9A050] hover:text-[#0A2E2A] text-gray-700 dark:bg-[#123831] dark:text-[#D8B060] dark:hover:bg-[#C9A050] dark:hover:text-[#061513] rounded-lg text-xs font-bold transition-all shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            <span>Receipt</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-12 text-center text-xs text-gray-400 dark:text-[#8FA8A3]">
                                        No counter sales registered in the selected period.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- In-Page Hidden Receipt Iframe for Seamless Zero-Navigation Printing -->
            <iframe id="reportReceiptFrame" style="position: absolute; width: 0; height: 0; border: none; visibility: hidden;"></iframe>
            <script>
                function printReceiptInPage(url) {
                    const frame = document.getElementById('reportReceiptFrame');
                    if (!frame) return;
                    frame.src = url;
                    frame.onload = function() {
                        setTimeout(() => {
                            try {
                                frame.contentWindow.focus();
                                frame.contentWindow.print();
                            } catch (e) {
                                console.error('In-page print error:', e);
                            }
                        }, 300);
                    };
                }
            </script>
        </div>
        </div>

    </div>
</x-app-layout>
