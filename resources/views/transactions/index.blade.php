<x-app-layout title="Transactions & Payments">
    <div class="space-y-6" x-data="{
        slipModalOpen: false,
        activeSlip: '',
        activeOrder: null,
        openSlipModal(slipUrl, orderData) {
            this.activeSlip = slipUrl;
            this.activeOrder = orderData;
            this.slipModalOpen = true;
        },
        closeSlipModal() {
            this.slipModalOpen = false;
            this.activeSlip = '';
            this.activeOrder = null;
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
                    <span class="text-[#0A2E2A] font-black">Finance & Auditing</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Transactions & Payments</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Centralized ledger for bank transfer slip verification, online transfers, and revenue reconciliation</p>
            </div>
            
            <div class="flex items-center gap-3 shrink-0 self-start sm:self-auto">
                <a href="{{ route('orders') }}" class="px-5 py-3.5 bg-white border border-gray-200 hover:border-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-bold flex items-center space-x-2 shadow-xs transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span>View Orders</span>
                </a>
                <a href="{{ route('logs.export.orders') }}" class="px-5 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02]">
                    <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <span>Export Ledger</span>
                </a>
            </div>
        </div>

        <!-- Top KPI Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Total Revenue -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] rounded-2xl flex items-center justify-center shadow-xs">
                        <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Revenue</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">Rs. {{ number_format($stats['total_revenue'], 2) }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Total Settled Inflow</p>
            </div>

            <!-- Pending Slip Verifications -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shadow-xs border border-amber-100">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-amber-700 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200 {{ $stats['pending_slips_count'] > 0 ? 'animate-pulse' : '' }}">Action Required</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $stats['pending_slips_count'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Slips Awaiting Verification</p>
            </div>

            <!-- Approved Transfers -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Bank Transfers</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $stats['approved_transfers_count'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Rs. {{ number_format($stats['approved_transfers_amount'], 2) }} Approved</p>
            </div>

            <!-- Online Transfers -->
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center shadow-xs border border-purple-100">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-200">Online Transfers</span>
                </div>
                <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight">{{ $stats['approved_online_count'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Rs. {{ number_format($stats['approved_online_amount'], 2) }} Settled</p>
            </div>
        </div>

        <!-- Transactions Ledger Card -->
        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <!-- Card Header & Filter Bar -->
            <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col gap-5 bg-white/40">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-black text-gray-900 tracking-tight">Payment Ledger & Slip Verification Queue</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Real-time payment records, uploaded customer slips, and verification tools</p>
                    </div>
                    
                    <!-- Search Input -->
                    <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-80">
                        <input type="hidden" name="status" value="{{ $statusFilter }}">
                        <input type="hidden" name="method" value="{{ $methodFilter }}">
                        <input type="hidden" name="date" value="{{ $dateFilter }}">
                        <input type="text"
                               name="search"
                               value="{{ $search }}"
                               placeholder="Search customer, ID, reference..."
                               @input.debounce.1500ms="$el.form.submit()"
                               class="w-full pl-11 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <!-- Dual Filter Bar: Verification Status & Payment Method -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-4 border-t border-gray-100/80">
                    <!-- Verification Filter Pills -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Status:</span>
                        @foreach([
                            'all'      => 'All Records',
                            'pending'  => '⏳ Awaiting Slip (' . $stats['pending_slips_count'] . ')',
                            'verified' => '✓ Verified & Paid',
                            'rejected' => '✕ Rejected'
                        ] as $sKey => $sLabel)
                            <a href="{{ request()->fullUrlWithQuery(['status' => $sKey, 'page' => null]) }}"
                               class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $statusFilter === $sKey ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/15 border border-[#0A2E2A]' : 'bg-gray-100/80 text-gray-700 hover:bg-gray-200 hover:text-gray-900' }}">
                                {{ $sLabel }}
                            </a>
                        @endforeach
                    </div>

                    <!-- Payment Method Filter Pills -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Method:</span>
                        @foreach([
                            'all'             => 'All Methods',
                            'bank_transfer'   => '🏦 Bank Transfer',
                            'online_transfer' => '⚡ Online Transfer'
                        ] as $mKey => $mLabel)
                            <a href="{{ request()->fullUrlWithQuery(['method' => $mKey, 'page' => null]) }}"
                               class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $methodFilter === $mKey ? 'bg-[#C9A050] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'bg-gray-100/80 text-gray-700 hover:bg-gray-200 hover:text-gray-900' }}">
                                {{ $mLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[200px]">Order Details</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[200px]">Customer</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[180px]">Payment Method</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Amount</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Transfer Slip</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Payment Status</th>
                            <th class="py-5 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[190px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($transactions as $order)
                            @php
                                $code = '#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                                $customerName = $order->customer_name ?: ($order->user->name ?? 'Guest Customer');
                                $customerPhone = $order->customer_phone ?: ($order->user->phone ?? '');
                                $isBankTransfer = empty($order->payment_method) || stripos($order->payment_method, 'bank') !== false || !empty($order->bank_receipt_url);
                                $isVerified = in_array($order->payment_status, ['verified', 'paid']);
                                $isRejected = ($order->payment_status === 'rejected');
                                $isPending = !$isVerified && !$isRejected;
                                
                                $orderModalData = [
                                    'id'        => $order->id,
                                    'code'      => $code,
                                    'customer'  => $customerName,
                                    'phone'     => $customerPhone,
                                    'amount'    => 'Rs. ' . number_format($order->total_price ?: $order->total_amount, 2),
                                    'reference' => $order->payment_reference ?? 'Not specified',
                                    'method'    => $isBankTransfer ? 'Bank Transfer' : 'Online Transfer',
                                    'status'    => $order->payment_status ?? 'pending_verification',
                                    'date'      => $order->created_at->format('M d, Y · h:i A'),
                                    'verifyUrl' => route('transactions.verify', $order->id),
                                    'rejectUrl' => route('transactions.reject', $order->id),
                                ];
                            @endphp
                            <tr id="transaction-row-{{ $order->id }}" class="border-b border-gray-100/80 hover:bg-[#C9A050]/[0.03] transition-all duration-300 group">
                                <!-- Order Details -->
                                <td class="py-5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-2.5">
                                        <a href="{{ route('orders.invoice', $order) }}" target="_blank" class="text-sm font-black text-gray-900 group-hover:text-[#C9A050] transition-colors tracking-tight flex items-center gap-1.5" title="View tax invoice">
                                            <span>{{ $code }}</span>
                                            <svg class="w-3.5 h-3.5 opacity-60 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                        <p class="text-[11px] text-gray-400 font-semibold">{{ $order->created_at->format('M d, Y · h:i A') }}</p>
                                        <div>
                                            @if ($order->is_subscription)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-black rounded-lg border border-purple-200">
                                                    🔄 Meal Plan
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-gray-100 text-gray-700 text-[10px] font-bold rounded-lg">
                                                    ⚡ Single Order
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer Details -->
                                <td class="py-5 px-6 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($customerName) }}&background=0A2E2A&color=C9A050&bold=true" class="w-11 h-11 rounded-2xl shadow-xs shrink-0 ring-1 ring-gray-100" alt="">
                                        <div class="min-w-0 flex flex-col gap-1">
                                            <p class="text-sm font-black text-gray-900 truncate tracking-tight">{{ $customerName }}</p>
                                            @if ($customerPhone)
                                                <a href="tel:{{ $customerPhone }}" class="text-xs text-[#C9A050] hover:text-[#b88f3f] hover:underline font-bold inline-flex items-center gap-1">
                                                    📞 {{ $customerPhone }}
                                                </a>
                                            @else
                                                <span class="text-xs text-gray-400 italic">No phone provided</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Payment Method -->
                                <td class="py-5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-2.5">
                                        <div class="flex items-center gap-2">
                                            @if ($isBankTransfer)
                                                <span class="w-7 h-7 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center font-bold text-xs shrink-0">🏦</span>
                                                <span class="text-sm font-bold text-gray-800">Bank Transfer</span>
                                            @else
                                                <span class="w-7 h-7 rounded-xl bg-purple-500/10 text-purple-600 flex items-center justify-center font-bold text-xs shrink-0">⚡</span>
                                                <span class="text-sm font-bold text-gray-800">Online Transfer</span>
                                            @endif
                                        </div>

                                        @if ($order->payment_reference)
                                            <div class="inline-flex items-center gap-1 px-2.5 py-1 bg-[#C9A050]/10 border border-[#C9A050]/20 rounded-lg text-[10px] font-mono font-bold text-[#0A2E2A] w-fit">
                                                <span>Ref:</span>
                                                <span class="text-[#C9A050]">{{ $order->payment_reference }}</span>
                                            </div>
                                        @else
                                            <span class="text-[10px] text-gray-400 font-medium italic">No customer reference</span>
                                        @endif
                                    </div>
                                </td>

                                <!-- Amount -->
                                <td class="py-5 px-6 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-base font-black text-gray-900 tracking-tight">Rs. {{ number_format($order->total_price ?: $order->total_amount, 2) }}</span>
                                        <span class="text-[11px] font-semibold text-gray-400">
                                            {{ $order->items ? $order->items->sum('quantity') : 1 }} {{ \Illuminate\Support\Str::plural('item', $order->items ? $order->items->sum('quantity') : 1) }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Transfer Slip Preview -->
                                <td class="py-5 px-6 align-middle whitespace-nowrap">
                                    @if ($order->bank_receipt_url)
                                        <div class="flex items-center space-x-3">
                                            <button type="button"
                                                    @click="openSlipModal('{{ $order->bank_receipt_url }}', {{ json_encode($orderModalData) }})"
                                                    class="relative group/thumb cursor-pointer overflow-hidden rounded-xl border border-gray-200 shadow-2xs hover:border-[#C9A050] transition-colors">
                                                <img src="{{ $order->bank_receipt_url }}" class="w-12 h-12 object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Slip thumbnail">
                                                <div class="absolute inset-0 bg-[#0A2E2A]/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white">
                                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </div>
                                            </button>
                                            <button type="button"
                                                    @click="openSlipModal('{{ $order->bank_receipt_url }}', {{ json_encode($orderModalData) }})"
                                                    class="text-xs font-bold text-[#C9A050] hover:text-[#b88f3f] hover:underline flex items-center gap-1 cursor-pointer">
                                                <span>View Slip</span>
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                            </button>
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400 italic flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span>
                                            <span>No slip attached</span>
                                        </div>
                                    @endif
                                </td>

                                <!-- Payment Status Badge -->
                                <td class="py-5 px-6 align-middle whitespace-nowrap">
                                    @if ($isVerified)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 text-emerald-700 text-xs font-bold rounded-full border border-emerald-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                            Verified & Approved
                                        </span>
                                    @elseif ($isRejected)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-50 text-rose-700 text-xs font-bold rounded-full border border-rose-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                            Slip Rejected
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-amber-700 text-xs font-bold rounded-full border border-amber-200 shadow-2xs animate-pulse">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            Awaiting Slip Verification
                                        </span>
                                    @endif
                                </td>

                                <!-- Actions -->
                                <td class="py-5 px-6 align-middle text-right whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-2">
                                        @if ($isPending)
                                            <!-- Approve Form -->
                                            <form method="POST" action="{{ route('transactions.verify', $order->id) }}" class="inline-block">
                                                @csrf
                                                <button type="submit" 
                                                        onclick="return confirm('Verify and approve payment for Order {{ $code }}?')"
                                                        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-xs hover:scale-[1.02] cursor-pointer"
                                                        title="Approve Bank Transfer">
                                                    ✓ Approve
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="{{ route('transactions.reject', $order->id) }}" class="inline-block" onsubmit="return confirm('Reject transfer slip for Order {{ $code }}?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs transition-all cursor-pointer"
                                                        title="Reject Transfer Slip">
                                                    ✕ Reject
                                                </button>
                                            </form>
                                        @else
                                            <!-- If already verified, allow quick reversal if needed -->
                                            @if ($isVerified)
                                                <form method="POST" action="{{ route('transactions.reject', $order->id) }}" class="inline-block" onsubmit="return confirm('Revert verification and mark as rejected?');">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg text-[11px] font-bold transition-colors cursor-pointer" title="Revert to Rejected">
                                                        Reject Slip
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('transactions.verify', $order->id) }}" class="inline-block" onsubmit="return confirm('Re-verify and approve payment?');">
                                                    @csrf
                                                    <button type="submit" class="px-2.5 py-1 text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg text-[11px] font-bold transition-colors cursor-pointer" title="Approve Payment">
                                                        Approve
                                                    </button>
                                                </form>
                                            @endif
                                        @endif

                                        <!-- Invoice Link -->
                                        <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                                           class="p-2 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer"
                                           title="Print Tax Invoice">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-20 px-6 text-center align-middle">
                                    <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 font-bold">No transactions found</h3>
                                    <p class="text-sm text-gray-500 mt-1">Try adjusting your filters or search terms</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($transactions->hasPages())
                <div class="p-6 border-t border-gray-100 bg-gray-50/50">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>

        <!-- Glassmorphic Slip Preview Modal -->
        <template x-teleport="body">
            <div x-show="slipModalOpen"
                 x-cloak
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">

                <!-- Backdrop with Glassmorphism -->
                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md" @click="closeSlipModal()"></div>

                <!-- Modal Window -->
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-2xl w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="slipModalOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <!-- Modal Header -->
                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] shadow-md shrink-0">
                                <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-white tracking-tight flex items-center gap-2">
                                    <span>Bank Transfer Receipt</span>
                                    <span class="text-xs font-bold text-[#C9A050] bg-white/10 px-2 py-0.5 rounded-lg font-mono" x-text="activeOrder?.code"></span>
                                </h3>
                                <p class="text-xs text-emerald-100 font-medium" x-text="activeOrder?.customer + ' · ' + activeOrder?.amount"></p>
                            </div>
                        </div>
                        <button type="button" @click="closeSlipModal()" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-8 space-y-6 max-h-[calc(85vh-160px)] overflow-y-auto">
                        <!-- Full Slip Image Preview -->
                        <div class="rounded-2xl border border-gray-200 bg-gray-50/60 p-2 flex items-center justify-center overflow-hidden shadow-inner">
                            <img :src="activeSlip" class="w-full max-h-[50vh] object-contain rounded-xl hover:scale-[1.01] transition-transform" alt="Bank Transfer Slip Full Preview">
                        </div>

                        <!-- Order & Reference Details Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-2xl border border-gray-200/80 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Customer Name</span>
                                <span class="font-bold text-gray-900" x-text="activeOrder?.customer"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Contact Phone</span>
                                <span class="font-bold text-gray-900" x-text="activeOrder?.phone || '—'"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Customer Reference</span>
                                <span class="font-mono font-bold text-[#C9A050]" x-text="activeOrder?.reference"></span>
                            </div>
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-bold">Billed Amount</span>
                                <span class="font-black text-gray-900 text-sm" x-text="activeOrder?.amount"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0 flex-wrap">
                        <div class="flex items-center space-x-2">
                            <a :href="activeSlip" target="_blank" class="px-4 py-2.5 bg-white border border-gray-200 hover:border-[#C9A050] text-gray-800 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-2xs hover:scale-[1.02]">
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

                            <button type="button" @click="closeSlipModal()" class="px-5 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-app-layout>
