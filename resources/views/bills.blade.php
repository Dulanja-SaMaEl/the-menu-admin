<x-app-layout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                <span class="text-[#0A2E2A] font-black">Invoices & POS</span>
            </nav>
            <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Billing & Invoices</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Total Completed Invoicing Volume: <strong class="text-[#0A2E2A] font-black">Rs. {{ number_format($totalBilled, 2) }}</strong></p>
        </div>
        
        <div class="flex items-center space-x-2 shrink-0">
            <a href="{{ route('orders') }}" class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] flex items-center space-x-2.5">
                <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>Orders Management</span>
            </a>
        </div>
    </div>

    @php
        $selectedFirstOrder = $bills->first();
    @endphp

    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8 min-h-[600px] lg:h-[calc(100vh-210px)]" 
         x-data="{ 
            selectedOrder: {{ $selectedFirstOrder ? json_encode([
                'id' => $selectedFirstOrder->id,
                'code' => '#ORD-' . str_pad($selectedFirstOrder->id, 4, '0', STR_PAD_LEFT),
                'customer' => $selectedFirstOrder->customer_name ?: ($selectedFirstOrder->user->name ?? 'Guest User'),
                'phone' => $selectedFirstOrder->customer_phone ?: ($selectedFirstOrder->user->phone ?? '—'),
                'address' => $selectedFirstOrder->delivery_address ?: ($selectedFirstOrder->address ?: 'Standard Delivery'),
                'total' => $selectedFirstOrder->total_price,
                'status' => $selectedFirstOrder->status,
                'payment' => $selectedFirstOrder->payment_method ?? 'Cash on Delivery',
                'created_at' => $selectedFirstOrder->created_at->format('M d, Y · h:i A'),
                'items' => $selectedFirstOrder->items->map(fn($i) => [
                    'name' => $i->food->name ?? 'Dish',
                    'meal_type' => $i->meal_type ?? 'Meal',
                    'date' => $i->scheduled_date ? $i->scheduled_date->format('M d') : 'Standard',
                    'qty' => $i->quantity,
                    'price' => $i->price,
                    'subtotal' => $i->price * $i->quantity
                ])
            ]) : 'null' }}
         }">
        <!-- Bills List -->
        <div class="w-full lg:w-1/2 bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 sm:p-8 flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden min-h-[460px] lg:min-h-0">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-3 border-b border-gray-100">
                <div class="flex space-x-2 overflow-x-auto no-scrollbar pb-1">
                    <a href="{{ route('bills', ['status' => 'all']) }}" 
                       class="px-4 py-2 text-xs sm:text-sm font-bold rounded-2xl transition-all {{ $status === 'all' ? 'bg-[#0A2E2A] text-white shadow-md' : 'text-gray-500 hover:text-gray-900 bg-gray-50' }}">
                        All ({{ $bills->total() }})
                    </a>
                    <a href="{{ route('bills', ['status' => 'paid']) }}" 
                       class="px-4 py-2 text-xs sm:text-sm font-black rounded-2xl transition-all {{ $status === 'paid' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] shadow-md' : 'text-gray-500 hover:text-gray-900 bg-gray-50' }}">
                        Paid / Done
                    </a>
                    <a href="{{ route('bills', ['status' => 'pending']) }}" 
                       class="px-4 py-2 text-xs sm:text-sm font-bold rounded-2xl transition-all {{ $status === 'pending' ? 'bg-amber-500 text-white shadow-md' : 'text-gray-500 hover:text-gray-900 bg-gray-50' }}">
                        Pending
                    </a>
                </div>
                <div class="text-xs font-semibold text-gray-500 shrink-0">
                    Showing latest invoices
                </div>
            </div>

            <div class="flex-grow overflow-y-auto space-y-3 pr-1 custom-scrollbar">
                @forelse($bills as $b)
                    @php
                        $bCode = '#ORD-' . str_pad($b->id, 4, '0', STR_PAD_LEFT);
                        $bCustomer = $b->customer_name ?: ($b->user->name ?? 'Guest User');
                        $bItemsJson = json_encode([
                            'id' => $b->id,
                            'code' => $bCode,
                            'customer' => $bCustomer,
                            'phone' => $b->customer_phone ?: ($b->user->phone ?? '—'),
                            'address' => $b->delivery_address ?: ($b->address ?: 'Standard Delivery'),
                            'total' => $b->total_price,
                            'status' => $b->status,
                            'payment' => $b->payment_method ?? 'Cash on Delivery',
                            'created_at' => $b->created_at->format('M d, Y · h:i A'),
                            'items' => $b->items->map(fn($i) => [
                                'name' => $i->food->name ?? 'Dish',
                                'meal_type' => $i->meal_type ?? 'Meal',
                                'date' => $i->scheduled_date ? $i->scheduled_date->format('M d') : 'Standard',
                                'qty' => $i->quantity,
                                'price' => $i->price,
                                'subtotal' => $i->price * $i->quantity
                            ])
                        ]);
                    @endphp
                    <div @click="selectedOrder = {{ $bItemsJson }}" 
                         :class="selectedOrder && selectedOrder.id === {{ $b->id }} ? 'border-[#C9A050] bg-[#C9A050]/10 shadow-md ring-1 ring-[#C9A050]' : 'border-gray-200/80 hover:border-[#C9A050]/40 hover:bg-[#C9A050]/[0.02]'"
                         class="p-4 sm:p-5 rounded-2xl border transition-all duration-300 cursor-pointer group bg-white">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2.5">
                                <h3 class="text-sm sm:text-base font-black text-gray-900 group-hover:text-[#C9A050] transition-colors">{{ $bCode }}</h3>
                                <x-status-badge :status="$b->status" />
                            </div>
                            <span class="text-sm sm:text-base font-black text-[#0A2E2A]">Rs. {{ number_format($b->total_price, 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-gray-500 font-medium">
                            <span class="truncate max-w-[200px]">👤 {{ $bCustomer }}</span>
                            <span>{{ $b->created_at->format('M d · h:i A') }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-16 text-center text-gray-400">
                        <p class="font-bold">No bills found in this status</p>
                    </div>
                @endforelse
            </div>

            @if ($bills->hasPages())
                <div class="pt-4 border-t border-gray-100">
                    {{ $bills->links() }}
                </div>
            @endif
        </div>

        <!-- Bill Invoice Breakdown Panel -->
        <div class="w-full lg:w-1/2 bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 sm:p-8 flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden min-h-[460px] lg:min-h-0">
            <template x-if="selectedOrder">
                <div class="flex flex-col h-full">
                    <div class="flex items-center justify-between mb-6 pb-3 border-b border-gray-100">
                        <div class="flex items-center space-x-2 text-xs sm:text-sm font-semibold text-gray-900">
                            <span class="text-[#C9A050] font-black tracking-wider uppercase">INVOICE BREAKDOWN</span>
                            <span class="text-gray-300">·</span>
                            <span x-text="selectedOrder.code" class="font-bold"></span>
                        </div>
                        <button onclick="window.print()" class="text-xs font-bold text-gray-500 hover:text-[#0A2E2A] flex items-center transition-colors cursor-pointer">
                            🖨️ Print Receipt
                        </button>
                    </div>

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight" x-text="selectedOrder.code"></h2>
                            <p class="text-xs text-gray-400 mt-0.5" x-text="'Issued: ' + selectedOrder.created_at"></p>
                        </div>
                        <span class="px-3.5 py-1 text-xs font-black rounded-full uppercase bg-[#0A2E2A] text-[#C9A050] border border-[#C9A050]/30" x-text="selectedOrder.status"></span>
                    </div>

                    <!-- Customer Meta -->
                    <div class="grid grid-cols-2 gap-3 mb-4 bg-gray-50/90 p-4 rounded-2xl border border-gray-200/80 text-xs">
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Customer</p>
                            <p class="font-bold text-gray-900" x-text="selectedOrder.customer"></p>
                            <p class="text-gray-500" x-text="'📞 ' + selectedOrder.phone"></p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-wider">Payment Method</p>
                            <p class="font-bold text-gray-900" x-text="selectedOrder.payment"></p>
                            <p class="text-gray-500 truncate" x-text="'📍 ' + selectedOrder.address"></p>
                        </div>
                    </div>

                    <h3 class="text-xs font-black text-gray-400 uppercase tracking-wider mb-2">Itemized Breakdown</h3>
                    <div class="flex-grow overflow-y-auto space-y-2 mb-4 pr-1 custom-scrollbar">
                        <template x-for="(item, idx) in selectedOrder.items" :key="idx">
                            <div class="flex items-center justify-between p-3.5 rounded-2xl bg-gray-50/80 hover:bg-[#C9A050]/5 border border-transparent hover:border-[#C9A050]/20 transition-all duration-300">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate" x-text="item.name"></p>
                                    <p class="text-[11px] text-gray-500 font-medium" x-text="item.meal_type + ' (' + item.date + ') · Qty: ' + item.qty + ' × Rs. ' + Number(item.price).toFixed(2)"></p>
                                </div>
                                <span class="text-sm font-bold text-gray-900 ml-4 shrink-0" x-text="'Rs. ' + Number(item.subtotal).toFixed(2)"></span>
                            </div>
                        </template>
                    </div>

                    <div class="pt-4 border-t border-gray-100 mt-auto">
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-base font-bold text-gray-600">Total Invoiced</span>
                            <span class="text-2xl sm:text-3xl font-black text-[#0A2E2A]" x-text="'Rs. ' + Number(selectedOrder.total).toFixed(2)"></span>
                        </div>
                        <a :href="'/orders?search=' + selectedOrder.code" 
                           class="w-full py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl font-black text-sm flex items-center justify-center shadow-lg shadow-[#C9A050]/25 hover:shadow-xl transition-all duration-300 hover:scale-[1.01]">
                            Manage Order in Orders Panel →
                        </a>
                    </div>
                </div>
            </template>
            <template x-if="!selectedOrder">
                <div class="flex flex-col items-center justify-center h-full text-center text-gray-400">
                    <p class="font-bold">Select a bill from the left list to view invoice details.</p>
                </div>
            </template>
        </div>
    </div>
</x-app-layout>