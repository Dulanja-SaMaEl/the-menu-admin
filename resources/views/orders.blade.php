<x-app-layout>
    @php
        $statusStyles = [
            'pending'    => ['label' => 'Pending',    'class' => 'bg-amber-50 text-amber-600 border-amber-200'],
            'processing' => ['label' => 'Processing', 'class' => 'bg-blue-50 text-blue-600 border-blue-200'],
            'completed'  => ['label' => 'Completed',  'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
            'delivered'  => ['label' => 'Delivered',  'class' => 'bg-emerald-50 text-emerald-600 border-emerald-200'],
            'cancelled'  => ['label' => 'Cancelled',  'class' => 'bg-rose-50 text-rose-600 border-rose-200'],
        ];
        $totalAll = $counts->sum();
        $pendingCount = $counts->get('pending', 0);
        $processingCount = $counts->get('processing', 0);
        $completedCount = $counts->get('completed', 0) + $counts->get('delivered', 0);
        $todayStr = now()->toDateString();
        $tomorrowStr = now()->addDay()->toDateString();

        // Calculate meal servings totals for Today & Tomorrow
        $todayMealsTotal = isset($todayUpcomingOrders) ? $todayUpcomingOrders->flatMap(function($order) use ($todayStr) {
            return $order->items ? $order->items->filter(function($i) use ($todayStr) {
                return $i->scheduled_date && \Carbon\Carbon::parse($i->scheduled_date)->format('Y-m-d') === $todayStr;
            }) : collect();
        })->sum('quantity') : 0;

        $tomorrowMealsTotal = isset($tomorrowUpcomingOrders) ? $tomorrowUpcomingOrders->flatMap(function($order) use ($tomorrowStr) {
            return $order->items ? $order->items->filter(function($i) use ($tomorrowStr) {
                return $i->scheduled_date && \Carbon\Carbon::parse($i->scheduled_date)->format('Y-m-d') === $tomorrowStr;
            }) : collect();
        })->sum('quantity') : 0;
    @endphp

    <div class="space-y-6" x-data="{ 
        viewMode: '{{ $viewMode }}',
        activeMealFilter: '{{ $mealFilter }}',
        showAssignModal: false, 
        selected: null, 
        assignOrderId: null, 
        assignDriverId: null,
        bulkDriverId: '',
        showEditAddressModal: false,
        editAddressOrderId: null,
        editAddressValue: '',
        showAlertDetails: true,
        selectedOrders: [],
        selectAll: false,
        showKitchenPrepModal: false,
        showWhatsAppModal: false,
        whatsAppManifestText: '',
        whatsAppRecipientPhone: '',
        whatsappPhone: '',
        whatsAppRecipientName: '',
        whatsAppCopied: false,
        openWhatsAppManifest(text, phone = '', name = '') {
            this.whatsAppManifestText = text;
            this.whatsAppRecipientPhone = phone || '';
            this.whatsappPhone = phone || '';
            this.whatsAppRecipientName = name || '';
            this.showWhatsAppModal = true;
            this.whatsAppCopied = false;
        },
        copyWhatsAppManifest() {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(this.whatsAppManifestText);
            } else {
                const textArea = document.createElement('textarea');
                textArea.value = this.whatsAppManifestText;
                textArea.style.position = 'fixed';
                textArea.style.left = '-999999px';
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.error('Failed to copy text', err);
                }
                document.body.removeChild(textArea);
            }
            this.whatsAppCopied = true;
            setTimeout(() => { this.whatsAppCopied = false; }, 3000);
        },
        sendToWhatsApp() {
            const encoded = encodeURIComponent(this.whatsAppManifestText);
            const rawPhone = this.whatsAppRecipientPhone || this.whatsappPhone || '';
            let cleanPhone = rawPhone ? rawPhone.replace(/[^0-9]/g, '') : '';
            if (cleanPhone.startsWith('0') && cleanPhone.length === 10) {
                cleanPhone = '94' + cleanPhone.substring(1);
            }
            const url = cleanPhone 
                ? ('https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + encoded) 
                : ('https://api.whatsapp.com/send?text=' + encoded);
            window.open(url, '_blank');
        },
        switchView(mode) {
            this.viewMode = mode;
            this.selectedOrders = [];
            this.selectAll = false;
            const url = new URL(window.location);
            url.searchParams.set('view', mode);
            window.history.replaceState({}, '', url);
        },
        toggleAll() {
            if (this.selectAll && this.viewMode === 'meal_wise') {
                this.selectedOrders = {{ collect($paginatedMealDeliveries->items())->pluck('order_id')->unique()->values()->toJson() }};
            } else {
                this.selectedOrders = [];
            }
            this.$nextTick(() => {
                document.querySelectorAll('.order-checkbox').forEach(cb => {
                    cb.checked = this.selectAll;
                });
            });
        },
        submitBulkAssign() {
            window.submitBulkAssign();
        }
    }">
        <!-- Assign Driver Modal (Per Meal Dispatch) -->
        <template x-teleport="body">
            <div x-show="showAssignModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md transition-opacity" @click="showAssignModal = false"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showAssignModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <form method="POST" :action="'/orders/' + assignOrderId + '/assign-driver'">
                        @csrf
                        <input type="hidden" name="view" value="meal_wise">
                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] text-xl shadow-md shrink-0">
                                    🛵
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white tracking-tight">Assign Delivery Driver</h3>
                                    <p class="text-xs text-emerald-100/70 mt-0.5">Dispatch for Order <span class="font-bold text-[#C9A050]" x-text="'#ORD-' + String(assignOrderId).padStart(4, '0')"></span></p>
                                </div>
                            </div>
                            <button type="button" @click="showAssignModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white/80 hover:text-white rounded-xl transition-all duration-200 cursor-pointer" title="Close modal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="p-6 sm:p-8 bg-gray-50/40">
                            <p class="text-[11px] font-black text-gray-400 uppercase tracking-widest mb-3.5">Select Fleet Driver</p>
                            <div class="space-y-3 max-h-[340px] overflow-y-auto pr-2 custom-scrollbar">
                                <!-- Unassign option -->
                                <label class="group relative flex items-center p-4 border-2 rounded-2xl cursor-pointer transition-all duration-300"
                                       :class="assignDriverId == null ? 'border-[#C9A050] bg-[#C9A050]/10 shadow-sm' : 'border-gray-200/80 bg-white hover:border-[#C9A050]/40 hover:bg-[#C9A050]/5 hover:shadow-xs'">
                                    <input type="radio" name="driver_id" value="" :checked="assignDriverId == null" class="w-5 h-5 text-[#C9A050] border-gray-300 focus:ring-[#C9A050]">
                                    <div class="ml-4 flex items-center space-x-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 group-hover:bg-gray-200 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-black text-gray-900 tracking-tight">Unassigned</p>
                                            <p class="text-xs text-gray-500 font-medium">Order will have no allocated driver</p>
                                        </div>
                                    </div>
                                </label>

                                @foreach ($drivers as $driver)
                                    @php
                                        $driverColor = [
                                            'available'   => 'text-emerald-700 bg-emerald-50 border-emerald-200',
                                            'on_delivery' => 'text-blue-700 bg-blue-50 border-blue-200',
                                            'offline'     => 'text-gray-500 bg-gray-100 border-gray-200',
                                        ][$driver->status] ?? 'text-gray-500 bg-gray-100 border-gray-200';
                                    @endphp
                                    <label class="group relative flex items-center p-4 border-2 rounded-2xl cursor-pointer transition-all duration-300"
                                           :class="assignDriverId == {{ $driver->id }} ? 'border-[#C9A050] bg-[#C9A050]/10 shadow-sm' : 'border-gray-200/80 bg-white hover:border-[#C9A050]/40 hover:bg-[#C9A050]/5 hover:shadow-xs'">
                                        <input type="radio" name="driver_id" value="{{ $driver->id }}" :checked="assignDriverId == {{ $driver->id }}" class="w-5 h-5 text-[#C9A050] border-gray-300 focus:ring-[#C9A050]">
                                        <div class="ml-4 flex items-center justify-between flex-1">
                                            <div class="flex items-center space-x-3.5">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($driver->name) }}&background=0A2E2A&color=C9A050&bold=true" class="w-10 h-10 rounded-xl shadow-xs" alt="">
                                                <div>
                                                    <p class="text-sm font-black text-gray-900 tracking-tight">{{ $driver->name }}</p>
                                                    <p class="text-xs text-gray-500 font-medium">
                                                        {{ $driver->phone ?? 'No phone' }} · {{ $driver->vehicle_type ?? 'Motorbike' }} · 
                                                        <span class="font-bold text-[#0A2E2A]">Cap: {{ $driver->assigned_orders_count ?? 0 }}/{{ $driver->max_orders ?? 5 }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @if(($driver->assigned_orders_count ?? 0) >= ($driver->max_orders ?? 5))
                                                    <span class="text-[9px] font-black px-2.5 py-1 rounded-full border border-rose-200 bg-rose-50 text-rose-700 uppercase tracking-wider">
                                                        At Capacity
                                                    </span>
                                                @endif
                                                <span class="text-[10px] font-black px-2.5 py-1 rounded-full border {{ $driverColor }} uppercase tracking-wider">
                                                    {{ str_replace('_', ' ', $driver->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div class="flex items-center space-x-3 mt-6 pt-4 border-t border-gray-200/80">
                                <button type="button" @click="showAssignModal = false" class="flex-1 px-5 py-3 rounded-2xl font-bold text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 transition-colors duration-200 cursor-pointer">Cancel</button>
                                <button type="submit" class="flex-1 px-5 py-3 rounded-2xl font-black text-sm bg-[#C9A050] hover:bg-[#b88f3f] text-[#0A2E2A] shadow-lg shadow-[#C9A050]/25 hover:shadow-xl hover:scale-[1.01] transition-all duration-300 cursor-pointer">Confirm Driver</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Edit Address Modal -->
        <template x-teleport="body">
            <div x-show="showEditAddressModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">
                 <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md transition-opacity" @click="showEditAddressModal = false"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-md w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showEditAddressModal">
                    <form method="POST" :action="'/orders/' + editAddressOrderId + '/update-address'">
                        @csrf
                        @method('PATCH')
                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                            <div class="flex items-center space-x-3.5">
                                <div class="w-11 h-11 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] text-xl shadow-md shrink-0">
                                    📍
                                </div>
                                <div>
                                    <h3 class="text-lg font-black text-white tracking-tight">Edit Delivery Location</h3>
                                    <p class="text-xs text-emerald-100/70 mt-0.5">Order <span class="font-bold text-[#C9A050]" x-text="'#ORD-' + String(editAddressOrderId).padStart(4, '0')"></span></p>
                                </div>
                            </div>
                            <button type="button" @click="showEditAddressModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white/80 hover:text-white rounded-xl transition-all duration-200 cursor-pointer" title="Close modal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                        <div class="p-6 sm:p-8 bg-gray-50/40">
                            <label class="block text-[11px] font-black text-gray-400 uppercase tracking-widest mb-2.5">New Delivery Address / Area</label>
                            <input type="text" name="address" x-model="editAddressValue" class="w-full px-4 py-3.5 rounded-2xl border border-gray-200 bg-white focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] text-sm text-gray-900 transition-all duration-200 shadow-inner" placeholder="Enter new address..." required>
                            
                            <div class="flex items-center space-x-3 mt-6 pt-4 border-t border-gray-200/80">
                                <button type="button" @click="showEditAddressModal = false" class="flex-1 px-5 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl font-bold text-sm transition-colors duration-200 cursor-pointer">Cancel</button>
                                <button type="submit" class="flex-1 px-5 py-3 rounded-2xl font-black text-sm bg-[#C9A050] hover:bg-[#b88f3f] text-[#0A2E2A] shadow-lg shadow-[#C9A050]/25 hover:shadow-xl hover:scale-[1.01] transition-all duration-300 cursor-pointer">Update Address</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Chef's Kitchen Prep List Modal (Tomorrow's Scheduled Deliveries) -->
        <template x-teleport="body">
            <div x-show="showKitchenPrepModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showKitchenPrepModal = false"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl max-w-4xl w-full overflow-hidden transform transition-all my-8 max-h-[90vh] flex flex-col border border-gray-100"
                     x-show="showKitchenPrepModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between shrink-0 border-b border-[#C9A050]/30">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] shadow-md shadow-black/20">
                                <span class="text-2xl">👨‍🍳</span>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black tracking-wider uppercase bg-[#C9A050] text-[#0A2E2A]">
                                        Chef's Schedule
                                    </span>
                                    <span class="text-xs text-gray-300 font-bold">
                                        {{ now()->addDay()->format('l, F j, Y') }}
                                    </span>
                                </div>
                                <h3 class="text-xl font-black text-white tracking-tight mt-1">Tomorrow's Kitchen Prep Manifest</h3>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2.5">
                            <button type="button" 
                                    onclick="window.print()" 
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white/10 hover:bg-white/20 text-[#C9A050] hover:text-white rounded-xl text-xs font-bold transition-colors border border-white/10 cursor-pointer"
                                    title="Print Prep Slip">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                <span class="hidden sm:inline">Print Slip</span>
                            </button>
                            <button type="button" @click="showKitchenPrepModal = false" class="p-2.5 bg-white/10 text-white/70 hover:text-white rounded-xl transition-colors cursor-pointer" title="Close modal">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Modal Body (Scrollable) -->
                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/50">
                        <!-- Quick Metric Stat Pills -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3.5 bg-white rounded-2xl border border-gray-200 shadow-2xs">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block">Total Servings</span>
                                <span class="text-2xl font-black text-[#0A2E2A]">{{ $tomorrowKitchenPrep['counts']['total'] ?? 0 }}</span>
                            </div>
                            <div class="p-3.5 bg-amber-50/60 rounded-2xl border border-amber-200/80 shadow-2xs">
                                <span class="text-[10px] font-bold text-amber-800 uppercase tracking-wider block">☀️ Breakfast</span>
                                <span class="text-2xl font-black text-amber-900">{{ $tomorrowKitchenPrep['counts']['breakfast'] ?? 0 }}</span>
                            </div>
                            <div class="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-200/80 shadow-2xs">
                                <span class="text-[10px] font-bold text-blue-800 uppercase tracking-wider block">🌤️ Lunch</span>
                                <span class="text-2xl font-black text-blue-900">{{ $tomorrowKitchenPrep['counts']['lunch'] ?? 0 }}</span>
                            </div>
                            <div class="p-3.5 bg-purple-50/60 rounded-2xl border border-purple-200/80 shadow-2xs">
                                <span class="text-[10px] font-bold text-purple-800 uppercase tracking-wider block">🌙 Dinner</span>
                                <span class="text-2xl font-black text-purple-900">{{ $tomorrowKitchenPrep['counts']['dinner'] ?? 0 }}</span>
                            </div>
                            {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                            <div class="p-3.5 bg-gradient-to-br from-pink-50 to-purple-50 rounded-2xl border border-purple-300 shadow-2xs col-span-2 sm:col-span-1">
                                <span class="text-[10px] font-bold text-purple-800 uppercase tracking-wider block">🍹 Beverages</span>
                                <span class="text-2xl font-black text-purple-900">{{ collect($tomorrowKitchenPrep['beverages'] ?? [])->sum('total_quantity') }}</span>
                            </div>
                            --}}
                        </div>

                        {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                        <!-- SECTION 1: Beverages for Monthly Plan Lunches (Critical Driver/Kitchen Feature) -->
                        <div class="bg-gradient-to-br from-purple-900 via-indigo-900 to-[#0A2E2A] rounded-3xl p-6 text-white shadow-lg border border-purple-400/30">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-xl bg-purple-400/20 border border-purple-300/40 flex items-center justify-center text-xl">
                                        🍹
                                    </div>
                                    <div>
                                        <h4 class="text-base font-black text-white tracking-tight">Monthly Plan Complimentary Beverages</h4>
                                        <p class="text-xs text-purple-200/80 font-medium">Included specifically with tomorrow's Lunch deliveries</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-[#C9A050] text-[#0A2E2A] self-start sm:self-auto">
                                    Pack with Lunch Only
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($tomorrowKitchenPrep['beverages'] ?? [] as $bev)
                                    <div class="flex items-center justify-between p-3.5 bg-white/10 backdrop-blur-md rounded-2xl border border-white/15">
                                        <div class="flex items-center space-x-3 min-w-0">
                                            <span class="text-2xl">🥤</span>
                                            <div class="min-w-0">
                                                <p class="text-sm font-black text-white truncate">{{ $bev['name'] }}</p>
                                                <p class="text-[11px] text-purple-200 font-semibold">Lunch Package Beverage</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-xl text-base font-black bg-[#C9A050] text-[#0A2E2A] shrink-0">
                                            {{ $bev['total_quantity'] }}x
                                        </span>
                                    </div>
                                @empty
                                    <div class="col-span-full p-4 rounded-2xl bg-white/5 border border-white/10 text-center text-xs text-purple-200">
                                        No complimentary beverages scheduled for tomorrow's lunch packages yet.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                        --}}

                        <!-- SECTION 2: Aggregated Dish Quantities for Tomorrow -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-black text-gray-900 tracking-tight uppercase">Aggregated Dish Production Requirements</h4>
                                    <p class="text-xs text-gray-400">Total batch quantities the kitchen must prepare and package for tomorrow</p>
                                </div>
                                <span class="text-xs font-bold text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                    {{ count($tomorrowKitchenPrep['dishes'] ?? []) }} unique {{ \Illuminate\Support\Str::plural('recipe', count($tomorrowKitchenPrep['dishes'] ?? [])) }}
                                </span>
                            </div>

                            <div class="space-y-2.5">
                                @forelse($tomorrowKitchenPrep['dishes'] ?? [] as $dish)
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between p-4 bg-white hover:bg-amber-50/40 rounded-2xl border border-gray-200 shadow-2xs gap-4 transition-all">
                                        <div class="flex items-center space-x-3.5 min-w-0">
                                            @if(!empty($dish['image_url']))
                                                <img src="{{ $dish['image_url'] }}" class="w-14 h-14 rounded-xl object-cover shadow-xs border border-gray-100 shrink-0" alt="">
                                            @else
                                                <div class="w-14 h-14 rounded-xl bg-amber-50 border border-amber-200 flex items-center justify-center text-2xl shrink-0">🍲</div>
                                            @endif
                                            <div class="min-w-0">
                                                <div class="flex items-center space-x-2">
                                                    <h5 class="text-sm font-black text-gray-900 truncate">{{ $dish['name'] }}</h5>
                                                    @if(!empty($dish['category']))
                                                        <span class="px-2 py-0.5 text-[9px] font-black uppercase tracking-wider rounded-md bg-gray-100 text-gray-600">
                                                            {{ $dish['category'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center space-x-2 mt-1.5 flex-wrap gap-y-1">
                                                    @if($dish['breakfast_qty'] > 0)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                                            <span>☀️ Breakfast:</span> <span class="font-black">{{ $dish['breakfast_qty'] }}</span>
                                                        </span>
                                                    @endif
                                                    @if($dish['lunch_qty'] > 0)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200">
                                                            <span>🌤️ Lunch:</span> <span class="font-black">{{ $dish['lunch_qty'] }}</span>
                                                        </span>
                                                    @endif
                                                    @if($dish['dinner_qty'] > 0)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-50 text-purple-800 border border-purple-200">
                                                            <span>🌙 Dinner:</span> <span class="font-black">{{ $dish['dinner_qty'] }}</span>
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-3 self-end sm:self-center shrink-0">
                                            <div class="text-right">
                                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider block">Total Quantity</span>
                                                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-xl text-lg font-black bg-[#0A2E2A] text-[#C9A050] border border-[#0A2E2A] shadow-xs">
                                                    <span>{{ $dish['total_quantity'] }}x</span>
                                                    <span class="text-xs uppercase font-bold text-white/80">Servings</span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 text-center text-gray-400 bg-white rounded-2xl border border-gray-200">
                                        <span class="text-3xl block mb-2">🍽️</span>
                                        <p class="text-sm font-bold text-gray-700">No scheduled dishes found for tomorrow.</p>
                                        <p class="text-xs text-gray-400 mt-1">Dishes scheduled by active customers and recurring subscriptions will appear here automatically.</p>
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-8 py-4 bg-white border-t border-gray-100 flex items-center justify-between shrink-0">
                        <span class="text-xs font-semibold text-gray-500">
                            Chef Prep Report · Generated {{ now()->format('h:i A') }}
                        </span>
                        <div class="flex items-center space-x-2.5">
                            <button type="button" @click="showKitchenPrepModal = false" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-xs transition-colors cursor-pointer">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Driver WhatsApp Delivery Manifest Modal -->
        <template x-teleport="body">
            <div x-show="showWhatsAppModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <!-- Backdrop -->
                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md transition-opacity" @click="showWhatsAppModal = false"></div>

                <!-- Modal Content -->
                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-2xl w-full overflow-hidden transform transition-all my-8 max-h-[90vh] flex flex-col border border-gray-100"
                     x-show="showWhatsAppModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <!-- Header -->
                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#075E54] to-[#0A2E2A] px-8 py-6 flex items-center justify-between shrink-0 text-white border-b border-[#C9A050]/20">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] text-2xl shadow-md shrink-0">
                                <svg class="w-7 h-7 text-[#0A2E2A]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black tracking-tight">Driver WhatsApp Delivery Manifest</h3>
                                <p class="text-xs text-emerald-100 font-medium">Clean, pre-formatted dispatch text block ready for driver sharing</p>
                            </div>
                        </div>
                        <button type="button" @click="showWhatsAppModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors cursor-pointer" title="Close modal">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 sm:p-8 space-y-5 overflow-y-auto custom-scrollbar flex-1 bg-gray-50/40">
                        <!-- Recipient Phone Input -->
                        <div class="bg-white p-5 rounded-2xl border border-gray-200/80 shadow-xs">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-2.5">
                                <label class="text-[11px] font-black text-gray-700 uppercase tracking-widest flex items-center gap-2">
                                    <span>Direct WhatsApp Dispatch Number</span>
                                    <template x-if="whatsAppRecipientName">
                                        <span class="text-[11px] font-black text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded-full border border-emerald-200 shadow-2xs">
                                            🛵 <span x-text="whatsAppRecipientName"></span>
                                        </span>
                                    </template>
                                </label>
                                <span class="text-[11px] font-semibold text-gray-400">Auto-filled with assigned driver's phone</span>
                            </div>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">📞</span>
                                <input type="text" 
                                       x-model="whatsAppRecipientPhone" 
                                       @input="whatsappPhone = whatsAppRecipientPhone"
                                       placeholder="e.g. 077 123 4567 or +94 77 123 4567" 
                                       class="w-full pl-10 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner">
                            </div>
                        </div>

                        <!-- Manifest Textarea -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[11px] font-black text-gray-500 uppercase tracking-widest">Formatted Delivery Manifest (Editable)</label>
                                <span class="text-[11px] font-bold text-gray-400">Customer · Address · Phone · Dish & Beverage</span>
                            </div>
                            <textarea x-model="whatsAppManifestText" 
                                      rows="11" 
                                      class="w-full p-4.5 text-xs font-mono bg-white border border-gray-200 rounded-2xl shadow-inner text-gray-800 leading-relaxed focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] resize-none custom-scrollbar transition-all"></textarea>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <div class="px-8 py-5 bg-white border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
                        <button type="button" 
                                @click="copyWhatsAppManifest()" 
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-[#0A2E2A] hover:bg-[#0D3833] text-white font-bold rounded-xl text-xs shadow-md shadow-[#0A2E2A]/15 hover:shadow-lg transition-all duration-300 cursor-pointer">
                            <template x-if="!whatsAppCopied">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                    <span>Copy Manifest</span>
                                </span>
                            </template>
                            <template x-if="whatsAppCopied">
                                <span class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    <span class="text-[#C9A050] font-black">Copied to Clipboard! ✅</span>
                                </span>
                            </template>
                        </button>

                        <div class="flex items-center space-x-3 w-full sm:w-auto">
                            <button type="button" 
                                    @click="showWhatsAppModal = false" 
                                    class="flex-1 sm:flex-initial px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl text-xs transition-colors duration-200 cursor-pointer">
                                Close
                            </button>
                            <button type="button" 
                                    @click="sendToWhatsApp()" 
                                    class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-xl text-xs shadow-lg shadow-[#C9A050]/25 hover:shadow-xl transition-all duration-300 hover:scale-[1.02] cursor-pointer">
                                <svg class="w-4 h-4 text-[#0A2E2A]" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>Share to WhatsApp</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
            <div>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Orders Management</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Real-time order tracking, kitchen prep schedules, and driver dispatch</p>
            </div>
            <div class="flex items-center space-x-2.5 shrink-0">
                <a href="{{ request()->fullUrlWithQuery(['schedule' => 'today']) }}" 
                   class="px-5 py-3 rounded-2xl text-xs sm:text-sm font-black transition-all flex items-center space-x-2 border shadow-md {{ $scheduleFilter === 'today' ? 'bg-[#0A2E2A] text-[#C9A050] border-[#0A2E2A] shadow-[#0A2E2A]/20 scale-[1.02]' : 'bg-white text-gray-800 border-gray-200 hover:border-[#C9A050]/50 hover:bg-gray-50' }}">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-ping"></span>
                    <span>Today's Schedule ({{ $todayScheduledCount }})</span>
                </a>
            </div>
        </div>

        <!-- PRIMARY VIEW MODE TOGGLE: Active Full Orders vs Meal-wise vs Completed & History -->
        <div class="bg-white rounded-[2.5rem] p-3 shadow-xl shadow-[#0A2E2A]/5 border border-gray-100">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-3">
                <!-- Tab 1: Active Full Orders -->
                <button type="button" 
                        id="toggle-view-full"
                        @click="switchView('full')" 
                        :class="viewMode === 'full' 
                            ? 'bg-gradient-to-r from-[#C9A050] to-[#d8b05f] text-[#0A2E2A] shadow-xl shadow-[#C9A050]/25 font-black ring-2 ring-[#C9A050]/60 border-transparent' 
                            : 'bg-gray-50/80 hover:bg-white text-gray-900 font-bold border border-gray-100 hover:border-[#C9A050]/40 hover:shadow-md'"
                        class="group flex items-center justify-between px-5 py-4.5 rounded-2xl transition-all duration-300 text-left cursor-pointer">
                    <div class="flex items-center space-x-3.5 flex-1">
                        <div :class="viewMode === 'full' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md' : 'bg-gray-200 text-gray-800 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050] group-hover:shadow-md'"
                             class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h3 :class="viewMode === 'full' ? 'text-[#0A2E2A] group-hover:text-black' : 'text-gray-900 group-hover:text-black'"
                                    class="text-base font-black tracking-tight leading-snug group-hover:text-black truncate">
                                    Active Full Orders
                                </h3>
                                <span :class="viewMode === 'full' ? 'bg-[#0A2E2A] text-[#C9A050]' : 'bg-gray-200 text-gray-900 border border-gray-300 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050]'"
                                      class="text-[10px] font-black px-2.5 py-0.5 rounded-full whitespace-nowrap transition-colors">
                                    {{ $activeOrdersCount }} Ongoing
                                </span>
                            </div>
                            <p :class="viewMode === 'full' ? 'text-[#0A2E2A]/90 group-hover:text-black' : 'text-gray-500 group-hover:text-gray-900'"
                               class="text-xs mt-0.5 font-medium leading-normal group-hover:text-gray-900 truncate">
                                Ongoing customer purchases, prep & status
                            </p>
                        </div>
                    </div>
                    <div :class="viewMode === 'full' ? 'opacity-100 text-[#0A2E2A]' : 'opacity-40 text-gray-700 group-hover:opacity-100 group-hover:text-[#0A2E2A]'"
                         class="hidden xl:flex items-center transition-all group-hover:translate-x-1 shrink-0 ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </button>

                <!-- Tab 2: Meal-wise View -->
                <button type="button" 
                        id="toggle-view-meal-wise"
                        @click="switchView('meal_wise')" 
                        :class="viewMode === 'meal_wise' 
                            ? 'bg-gradient-to-r from-[#C9A050] to-[#d8b05f] text-[#0A2E2A] shadow-xl shadow-[#C9A050]/25 font-black ring-2 ring-[#C9A050]/60 border-transparent' 
                            : 'bg-gray-50/80 hover:bg-white text-gray-900 font-bold border border-gray-100 hover:border-[#C9A050]/40 hover:shadow-md'"
                        class="group flex items-center justify-between px-5 py-4.5 rounded-2xl transition-all duration-300 text-left cursor-pointer">
                    <div class="flex items-center space-x-3.5 flex-1">
                        <div :class="viewMode === 'meal_wise' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md' : 'bg-amber-100 text-amber-900 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050] group-hover:shadow-md'"
                             class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h3 :class="viewMode === 'meal_wise' ? 'text-[#0A2E2A] group-hover:text-black' : 'text-gray-900 group-hover:text-black'"
                                    class="text-base font-black tracking-tight leading-snug group-hover:text-black truncate">
                                    Meal-wise View
                                </h3>
                                <span :class="viewMode === 'meal_wise' ? 'bg-[#0A2E2A] text-[#C9A050]' : 'bg-amber-100 text-amber-900 border border-amber-300 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050]'"
                                      class="text-[10px] font-black px-2.5 py-0.5 rounded-full whitespace-nowrap transition-colors">
                                    {{ $mealCounts['total'] }} Dispatches
                                </span>
                            </div>
                            <p :class="viewMode === 'meal_wise' ? 'text-[#0A2E2A]/90 group-hover:text-black' : 'text-gray-500 group-hover:text-gray-900'"
                               class="text-xs mt-0.5 font-medium leading-normal group-hover:text-gray-900 truncate">
                                Segregated meals for driver dispatch
                            </p>
                        </div>
                    </div>
                    <div :class="viewMode === 'meal_wise' ? 'opacity-100 text-[#0A2E2A]' : 'opacity-40 text-gray-700 group-hover:opacity-100 group-hover:text-[#0A2E2A]'"
                         class="hidden xl:flex items-center transition-all group-hover:translate-x-1 shrink-0 ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </button>

                <!-- Tab 3: Completed & History -->
                <button type="button" 
                        id="toggle-view-history"
                        @click="switchView('history')" 
                        :class="viewMode === 'history' 
                            ? 'bg-gradient-to-r from-[#C9A050] to-[#d8b05f] text-[#0A2E2A] shadow-xl shadow-[#C9A050]/25 font-black ring-2 ring-[#C9A050]/60 border-transparent' 
                            : 'bg-gray-50/80 hover:bg-white text-gray-900 font-bold border border-gray-100 hover:border-[#C9A050]/40 hover:shadow-md'"
                        class="group flex items-center justify-between px-5 py-4.5 rounded-2xl transition-all duration-300 text-left cursor-pointer">
                    <div class="flex items-center space-x-3.5 flex-1">
                        <div :class="viewMode === 'history' ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md' : 'bg-emerald-100 text-emerald-900 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050] group-hover:shadow-md'"
                             class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-all duration-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <h3 :class="viewMode === 'history' ? 'text-[#0A2E2A] group-hover:text-black' : 'text-gray-900 group-hover:text-black'"
                                    class="text-base font-black tracking-tight leading-snug group-hover:text-black truncate">
                                    Completed & History
                                </h3>
                                <span :class="viewMode === 'history' ? 'bg-[#0A2E2A] text-[#C9A050]' : 'bg-emerald-100 text-emerald-900 border border-emerald-300 group-hover:bg-[#0A2E2A] group-hover:text-[#C9A050]'"
                                      class="text-[10px] font-black px-2.5 py-0.5 rounded-full whitespace-nowrap transition-colors">
                                    {{ $historyCount }} Finished
                                </span>
                            </div>
                            <p :class="viewMode === 'history' ? 'text-[#0A2E2A]/90 group-hover:text-black' : 'text-gray-500 group-hover:text-gray-900'"
                               class="text-xs mt-0.5 font-medium leading-normal group-hover:text-gray-900 truncate">
                                Delivered orders & past delivery history
                            </p>
                        </div>
                    </div>
                    <div :class="viewMode === 'history' ? 'opacity-100 text-[#0A2E2A]' : 'opacity-40 text-gray-700 group-hover:opacity-100 group-hover:text-[#0A2E2A]'"
                         class="hidden xl:flex items-center transition-all group-hover:translate-x-1 shrink-0 ml-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                    </div>
                </button>
            </div>
        </div>

        <!-- ============================================================== -->
        <!-- VIEW 1: FULL ORDERS VIEW                                       -->
        <!-- ============================================================== -->
        <div x-show="viewMode === 'full'" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-1" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

        <!-- UPCOMING ORDERS NOTIFICATION & ALERT BANNERS (Proactive Kitchen & Delivery Alert) -->
        @if ($todayUpcomingOrders->isNotEmpty() || $tomorrowUpcomingOrders->isNotEmpty())
            <div class="space-y-4">
                @if ($todayUpcomingOrders->isNotEmpty())
                    <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-white rounded-3xl p-5 sm:p-6 border border-amber-300 shadow-sm relative overflow-hidden">
                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-black text-lg shadow-md shadow-amber-500/30 shrink-0">
                                    ⚡
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">Orders Scheduled for Delivery Today!</h3>
                                        <span class="px-2 py-0.5 bg-amber-500 text-white text-[10px] font-black rounded-full uppercase tracking-wider animate-pulse">
                                            {{ $todayUpcomingOrders->count() }} Orders Today
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-0.5">Kitchen staff should prioritize preparing these scheduled meals immediately.</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 shrink-0">
                                <a href="{{ route('orders', ['schedule' => 'today']) }}" class="px-4 py-2 text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 rounded-xl transition-colors">
                                    View All Today →
                                </a>
                            </div>
                        </div>

                        <!-- Mini cards for today's orders -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach ($todayUpcomingOrders->take(3) as $tOrder)
                                @php
                                    $todayMeals = $tOrder->items->filter(fn($i) => $i->scheduled_date && $i->scheduled_date->format('Y-m-d') === $todayStr);
                                    $mealsList = $todayMeals->map(fn($i) => ucfirst($i->meal_type ?? 'meal') . ': ' . ($i->food->name ?? 'Dish') . ' ×' . $i->quantity)->join(' · ');
                                @endphp
                                <div class="bg-white/90 backdrop-blur-xs rounded-2xl p-4 border border-amber-200 shadow-xs flex flex-col justify-between hover:shadow-md transition-shadow">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <span class="text-xs font-black text-gray-900">#ORD-{{ str_pad($tOrder->id, 4, '0', STR_PAD_LEFT) }}</span>
                                            <p class="text-xs font-bold text-gray-800 mt-0.5">{{ $tOrder->customer_name ?: ($tOrder->user->name ?? 'Customer') }}</p>
                                            <a href="tel:{{ $tOrder->customer_phone ?: ($tOrder->user->phone ?? '') }}" class="text-[11px] text-amber-700 hover:underline font-medium flex items-center mt-0.5">
                                                📞 {{ $tOrder->customer_phone ?: ($tOrder->user->phone ?? 'No phone') }}
                                            </a>
                                        </div>
                                        <x-status-badge :status="$tOrder->status" />
                                    </div>
                                    <div class="bg-amber-50/70 rounded-xl p-2.5 border border-amber-100/80 mb-3 text-[11px] text-gray-700">
                                        <span class="font-bold text-amber-900 block mb-0.5">Scheduled for Today:</span>
                                        <span class="line-clamp-2">{{ $mealsList ?: 'All ordered items' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                                        @if(auth()->user()?->role !== 'Chef')
                                            <span class="text-xs font-black text-gray-900">Rs. {{ number_format($tOrder->total_price, 2) }}</span>
                                        @else
                                            <span class="text-xs font-bold text-gray-500">{{ $tOrder->items->sum('quantity') }} items</span>
                                        @endif
                                        <button type="button" id="today-alert-details-btn-{{ $tOrder->id }}" @click="selected = {{ $tOrder->id }}" class="text-xs font-bold text-[#C9A050] hover:text-[#B38E46] cursor-pointer">
                                            Open Details →
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($tomorrowUpcomingOrders->isNotEmpty())
                    <div class="bg-gradient-to-r from-blue-500/10 via-blue-500/5 to-white rounded-3xl p-5 sm:p-6 border border-blue-200 shadow-sm">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-lg shadow-md shadow-blue-500/30 shrink-0">
                                    📅
                                </div>
                                <div>
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-base font-bold text-gray-900">Upcoming Orders for Tomorrow ({{ now()->addDay()->format('M d, D') }})</h3>
                                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-full uppercase">
                                            {{ $tomorrowUpcomingOrders->count() }} Orders Tomorrow
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-0.5">Prepare ingredients and plan scheduled kitchen prep in advance for seamless tomorrow dispatch.</p>
                                </div>
                            </div>
                            <a href="{{ route('orders', ['schedule' => 'tomorrow']) }}" class="px-4 py-2 text-xs font-bold text-blue-800 bg-blue-50 hover:bg-blue-100 rounded-xl transition-colors shrink-0">
                                View Tomorrow's Schedule →
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- EXECUTIVE SUMMARY STAT CARDS (Strictly 2 Luxury Cards: Today's Dispatch & Tomorrow's Kitchen Prep) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 sm:gap-6">
            <!-- Card 1: Today's Dispatch -->
            <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#0A2E2A] via-[#0D3833] to-[#061D1A] p-7 sm:p-8 text-white border-2 border-[#C9A050]/40 shadow-xl shadow-[#0A2E2A]/15 hover:border-[#C9A050] transition-all duration-300 group">
                <!-- Background Ambient Glow -->
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-[#C9A050]/15 rounded-full blur-3xl pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
                <div class="absolute -left-10 -top-10 w-40 h-40 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col justify-between h-full space-y-6">
                    <!-- Top Bar: Icon, Pill & Live Pulse -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-14 h-14 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] shadow-md shadow-black/20 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase bg-[#C9A050]/20 text-[#C9A050] border border-[#C9A050]/30 shadow-xs">
                                        <span class="w-2 h-2 rounded-full bg-rose-500 animate-ping"></span>
                                        <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                                        Live Dispatch
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-300 mt-1 uppercase tracking-wider">Scheduled for Today</h4>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-400 bg-white/5 border border-white/10 px-3 py-1 rounded-full">
                            {{ now()->format('M d, D') }}
                        </span>
                    </div>

                    <!-- Main Metric Block -->
                    <div>
                        <div class="flex items-baseline space-x-3">
                            <h3 class="text-5xl sm:text-6xl font-black text-[#C9A050] tracking-tight leading-none" id="today-dispatch-count">
                                {{ $todayScheduledCount }}
                            </h3>
                            <span class="text-lg sm:text-xl font-bold text-white/90">
                                {{ \Illuminate\Support\Str::plural('Order', $todayScheduledCount) }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <p class="text-base sm:text-lg font-black text-white tracking-tight">Today's Dispatch</p>
                            @if($todayMealsTotal > 0)
                                <span class="text-xs font-bold text-gray-300 bg-white/10 px-2.5 py-0.5 rounded-md border border-white/10">
                                    {{ $todayMealsTotal }} meal servings
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-300/80 mt-1 font-medium leading-relaxed">
                            Active deliveries and kitchen handovers requiring packaging and driver dispatch today.
                        </p>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-4 border-t border-white/10 flex items-center justify-between gap-3">
                        <span class="text-xs font-medium text-gray-300 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                            <span>Immediate kitchen priority</span>
                        </span>
                        <a href="{{ request()->fullUrlWithQuery(['schedule' => 'today', 'page' => null]) }}" 
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-[#C9A050] hover:bg-[#d8b05f] text-[#0A2E2A] transition-all shadow-md shadow-[#C9A050]/20 hover:scale-[1.02] cursor-pointer">
                            <span>Filter Today's Dispatch</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Card 2: Tomorrow's Kitchen Prep -->
            <div class="relative overflow-hidden rounded-[2rem] bg-gradient-to-br from-[#0A2E2A] via-[#0D3833] to-[#061D1A] p-7 sm:p-8 text-white border-2 border-[#C9A050]/40 shadow-xl shadow-[#0A2E2A]/15 hover:border-[#C9A050] transition-all duration-300 group">
                <!-- Background Ambient Glow -->
                <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-amber-500/15 rounded-full blur-3xl pointer-events-none group-hover:scale-125 transition-transform duration-700"></div>
                <div class="absolute -left-10 -top-10 w-40 h-40 bg-[#C9A050]/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col justify-between h-full space-y-6">
                    <!-- Top Bar: Icon, Pill & Schedule Date -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-14 h-14 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] shadow-md shadow-black/20 group-hover:scale-105 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4a4 4 0 00-3.8 2.76A4.5 4.5 0 004.5 11c0 2.22 1.6 4.07 3.73 4.43A2 2 0 0010 17h4a2 2 0 001.77-1.57C17.9 15.07 19.5 13.22 19.5 11a4.5 4.5 0 00-3.7-4.24A4 4 0 0012 4z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17v3a1 1 0 001 1h8a1 1 0 001-1v-3"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black tracking-wider uppercase bg-[#C9A050]/20 text-[#C9A050] border border-[#C9A050]/30 shadow-xs">
                                        <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                                        Kitchen Prep
                                    </span>
                                </div>
                                <h4 class="text-xs font-bold text-gray-300 mt-1 uppercase tracking-wider">Scheduled for Tomorrow</h4>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-400 bg-white/5 border border-white/10 px-3 py-1 rounded-full">
                            {{ now()->addDay()->format('M d, D') }}
                        </span>
                    </div>

                    <!-- Main Metric Block -->
                    <div>
                        <div class="flex items-baseline space-x-3">
                            <h3 class="text-5xl sm:text-6xl font-black text-[#C9A050] tracking-tight leading-none" id="tomorrow-prep-count">
                                {{ $tomorrowScheduledCount }}
                            </h3>
                            <span class="text-lg sm:text-xl font-bold text-white/90">
                                {{ \Illuminate\Support\Str::plural('Order', $tomorrowScheduledCount) }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-2 mt-2">
                            <p class="text-base sm:text-lg font-black text-white tracking-tight">Tomorrow's Kitchen Prep</p>
                            @if($tomorrowMealsTotal > 0)
                                <span class="text-xs font-bold text-gray-300 bg-white/10 px-2.5 py-0.5 rounded-md border border-white/10">
                                    {{ $tomorrowMealsTotal }} meal servings
                                </span>
                            @endif
                        </div>
                        <p class="text-xs text-gray-300/80 mt-1 font-medium leading-relaxed">
                            Advance meal preparations and ingredient staging planned for tomorrow morning dispatch.
                        </p>
                    </div>

                    <!-- Footer Action -->
                    <div class="pt-4 border-t border-white/10 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-medium text-gray-300 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                            <span>Proactive inventory & prep</span>
                        </span>
                        <div class="flex items-center space-x-2">
                            <a href="{{ request()->fullUrlWithQuery(['schedule' => 'tomorrow', 'page' => null]) }}" 
                               class="inline-flex items-center gap-1 px-3 py-2 rounded-xl text-xs font-bold bg-white/10 hover:bg-white/20 text-white transition-all border border-white/15 cursor-pointer"
                               title="Filter orders view to tomorrow only">
                                <span>Filter</span>
                            </a>
                            <button type="button" 
                                    id="open-kitchen-prep-modal-btn"
                                    @click="showKitchenPrepModal = true" 
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-black bg-[#C9A050] hover:bg-[#d8b05f] text-[#0A2E2A] transition-all shadow-md shadow-[#C9A050]/20 hover:scale-[1.02] cursor-pointer">
                                <span>👨‍🍳 Chef's Prep List</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table & Filter Card -->
        <div class="bg-white rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-100 overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center space-x-2.5">
                            <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">Active Orders Directory</h2>
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-[#0A2E2A]/5 text-[#0A2E2A] border border-[#0A2E2A]/10">
                                Live Pipeline
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Customer identities, delivery schedules, payment verification and status updates</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                        @if(auth()->user()?->role !== 'Chef')
                            <!-- Export Button -->
                            <a href="{{ route('orders.export') }}" class="flex items-center justify-center space-x-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl px-5 py-3 text-xs sm:text-sm font-black transition-all duration-300 shadow-md shadow-emerald-600/20 hover:scale-[1.02] shrink-0 w-full sm:w-auto">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                <span>Export to Excel</span>
                            </a>
                        @endif

                        <!-- Search Form -->
                        <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-80">
                            <input type="hidden" name="status" value="{{ $statusFilter }}">
                            <input type="hidden" name="schedule" value="{{ $scheduleFilter }}">
                            <input
                                type="text"
                                name="search"
                                value="{{ $search }}"
                                placeholder="Search customer, ID, phone, dish..."
                                @input.debounce.1500ms="$el.form.submit()"
                                class="w-full pl-11 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner"
                            >
                            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </form>
                    </div>
                </div>

                <!-- Dual Filter Bar: Status & Schedule -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-3 border-t border-gray-100">
                    <!-- Schedule Filter Pills -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Schedule:</span>
                        @foreach ([
                            'all'       => 'All Dates',
                            'today'     => '🔴 Today (' . $todayScheduledCount . ')',
                            'tomorrow'  => '🟠 Tomorrow',
                            'upcoming'  => '📅 Future Days',
                            'immediate' => '⚡ Immediate Orders'
                        ] as $sKey => $sLabel)
                            <a href="{{ request()->fullUrlWithQuery(['schedule' => $sKey, 'page' => null]) }}"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $scheduleFilter === $sKey ? 'bg-[#0A2E2A] text-[#C9A050] shadow-md shadow-[#0A2E2A]/15 border border-[#0A2E2A]' : 'bg-gray-100/80 text-gray-700 hover:bg-gray-200 hover:text-gray-900' }}">
                                {{ $sLabel }}
                            </a>
                        @endforeach
                    </div>

                    <!-- Status Filter Pills (Active Orders Only) -->
                    <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Status:</span>
                        @foreach (['all' => 'All Active', 'pending' => 'Pending', 'processing' => 'Processing', 'assigned' => 'Assigned'] as $key => $label)
                            <a href="{{ request()->fullUrlWithQuery(['status' => $key, 'page' => null]) }}"
                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition-all whitespace-nowrap {{ $statusFilter === $key ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'bg-gray-100/80 text-gray-700 hover:bg-gray-200 hover:text-gray-900' }}">
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Orders Table (Raw Order Details & Master Status) -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1240px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Order</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[270px]">Customer & Contact</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[340px]">Target Schedule & Meals</th>
                            @if(auth()->user()?->role !== 'Chef')
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Total & Payment</th>
                            @endif
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Status</th>
                            @if(auth()->user()?->role !== 'Chef')
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[140px]">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($orders as $order)
                            @php
                                $code = '#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT);
                                $scheduledDates = $order->items->pluck('scheduled_date')->filter()->unique();
                                $mealTypes = $order->items->pluck('meal_type')->filter()->unique();
                                
                                $isScheduledToday = $scheduledDates->contains(function($d) use ($todayStr) {
                                    return \Carbon\Carbon::parse($d)->toDateString() === $todayStr;
                                });
                                $isScheduledTomorrow = $scheduledDates->contains(function($d) use ($tomorrowStr) {
                                    return \Carbon\Carbon::parse($d)->toDateString() === $tomorrowStr;
                                });

                                $customerName = $order->customer_name ?: ($order->user->name ?? 'Guest User');
                                $customerPhone = $order->customer_phone ?: ($order->user->phone ?? '');
                                $deliveryAddress = $order->address ?: 'Standard Delivery';
                            @endphp
                            <tr id="order-row-{{ $order->id }}" class="border-b border-gray-100/80 hover:bg-[#C9A050]/[0.03] transition-all duration-300 group {{ $isScheduledToday ? 'bg-amber-50/25' : '' }}">
                                <!-- Order Code & Timing -->
                                <td class="px-6 py-5 align-middle whitespace-nowrap">
                                    <div class="flex flex-col gap-2">
                                        <button type="button" id="order-code-btn-{{ $order->id }}" @click="selected = {{ $order->id }}" class="text-sm font-black text-gray-900 group-hover:text-[#C9A050] transition-colors text-left cursor-pointer tracking-tight" title="Click to view details">
                                            {{ $code }}
                                        </button>
                                        <p class="text-[11px] text-gray-400 font-semibold">{{ $order->created_at->format('M d, Y · h:i A') }}</p>
                                        <div>
                                            @if ($scheduledDates->isNotEmpty())
                                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-50 text-purple-700 text-[10px] font-black rounded-xl border border-purple-200 shadow-2xs">
                                                    📅 Meal Plan ({{ $scheduledDates->count() }} {{ \Illuminate\Support\Str::plural('day', $scheduledDates->count()) }})
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-3 py-1 bg-gray-100 text-gray-700 text-[10px] font-bold rounded-xl">
                                                    ⚡ Immediate Order
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- Customer Details -->
                                <td class="px-6 py-5 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($customerName) }}&background=0A2E2A&color=C9A050&bold=true" class="w-11 h-11 rounded-2xl shadow-xs shrink-0 border border-gray-100" alt="">
                                        <div class="min-w-0 flex-1 flex flex-col gap-2">
                                            <div>
                                                <p class="text-sm font-black text-gray-900 truncate tracking-tight">{{ $customerName }}</p>
                                                @if ($customerPhone)
                                                    <a href="tel:{{ $customerPhone }}" class="text-xs text-[#C9A050] hover:text-[#b88f3f] hover:underline font-bold inline-flex items-center mt-0.5">
                                                        📞 {{ $customerPhone }}
                                                    </a>
                                                @else
                                                    <span class="text-xs text-gray-400 italic block mt-0.5">No phone</span>
                                                @endif
                                            </div>
                                            
                                            <div class="flex flex-col gap-1.5">
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-[11px] font-medium bg-gray-50 text-gray-700 border border-gray-200/80 max-w-full" title="{{ $deliveryAddress }}">
                                                        <span class="shrink-0 mr-1.5">📍</span>
                                                        <span class="truncate">{{ \Illuminate\Support\Str::limit($deliveryAddress, 32) }}</span>
                                                    </span>
                                                    <button @click="editAddressOrderId = {{ $order->id }}; editAddressValue = '{{ addslashes($deliveryAddress) }}'; showEditAddressModal = true;" class="text-gray-400 hover:text-[#C9A050] transition-colors p-1.5 rounded-xl hover:bg-gray-100 shrink-0 cursor-pointer" title="Edit Address">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                    </button>
                                                </div>
                                                @if ($order->dinner_address)
                                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-[10px] font-black bg-purple-50 text-purple-700 border border-purple-200 w-fit max-w-full" title="Dinner: {{ $order->dinner_address }}">
                                                        <span class="shrink-0 mr-1.5">🌙</span>
                                                        <span class="truncate">Dinner: {{ \Illuminate\Support\Str::limit($order->dinner_address, 26) }}</span>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Target Schedule & Meals Breakdown -->
                                <td class="px-6 py-5 align-middle">
                                    <div class="flex flex-col gap-2.5 min-w-[320px]">
                                        <!-- Schedule Status Pill -->
                                        <div>
                                            @if ($isScheduledToday)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-rose-100 text-rose-800 border border-rose-300 shadow-2xs animate-pulse">
                                                    <span>🔴</span> Today ({{ now()->format('M d') }})
                                                </span>
                                            @elseif ($isScheduledTomorrow)
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200 shadow-2xs">
                                                    <span>🟠</span> Tomorrow ({{ now()->addDay()->format('M d') }})
                                                </span>
                                            @elseif ($scheduledDates->isNotEmpty())
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-50 text-purple-700 border border-purple-200 shadow-2xs">
                                                    <span>📅</span> {{ \Carbon\Carbon::parse($scheduledDates->first())->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-700 border border-gray-200">
                                                    <span>⚡</span> Immediate Delivery
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Meals List with Meal Type Badges & Dishes -->
                                        <div class="flex flex-col gap-2">
                                            @forelse ($order->items->take(3) as $item)
                                                @php
                                                    $mType = strtolower($item->meal_type ?? '');
                                                @endphp
                                                <div class="flex items-center justify-between gap-3 text-xs bg-gray-50 hover:bg-white px-3.5 py-2.5 rounded-xl border border-gray-200/70 shadow-2xs transition-colors">
                                                    <div class="flex items-center space-x-2.5 min-w-0">
                                                        @if ($mType === 'breakfast')
                                                            <span class="px-2 py-0.5 bg-amber-100 text-amber-900 text-[10px] font-black rounded-md border border-amber-300 shrink-0 uppercase tracking-wider">B'fast</span>
                                                        @elseif ($mType === 'lunch')
                                                            <span class="px-2 py-0.5 bg-sky-100 text-sky-900 text-[10px] font-black rounded-md border border-sky-300 shrink-0 uppercase tracking-wider">Lunch</span>
                                                        @elseif ($mType === 'dinner')
                                                            <span class="px-2 py-0.5 bg-purple-100 text-purple-900 text-[10px] font-black rounded-md border border-purple-300 shrink-0 uppercase tracking-wider">Dinner</span>
                                                        @else
                                                            <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-[10px] font-bold rounded-md shrink-0 uppercase tracking-wider">Meal</span>
                                                        @endif
                                                        <span class="font-bold text-gray-900 truncate" title="{{ $item->food->name ?? 'Dish' }}">{{ $item->food->name ?? 'Dish' }}</span>
                                                    </div>
                                                    <span class="text-xs font-black text-[#0A2E2A] bg-white px-2 py-0.5 rounded-lg border border-gray-200 shadow-2xs shrink-0">×{{ $item->quantity }}</span>
                                                </div>
                                            @empty
                                                <p class="text-xs text-gray-400 italic">No meals configured</p>
                                            @endforelse

                                            @if ($order->items->count() > 3)
                                                <button type="button" id="more-dishes-btn-{{ $order->id }}" @click="selected = {{ $order->id }}" class="text-xs text-[#C9A050] hover:text-[#b88f3f] font-black inline-flex items-center gap-1 text-left transition-colors cursor-pointer">
                                                    <span>+{{ $order->items->count() - 3 }} more dishes in this order</span>
                                                    <span>&rarr;</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                @if(auth()->user()?->role !== 'Chef')
                                    <!-- Total & Payment -->
                                    <td class="px-6 py-5 align-middle whitespace-nowrap">
                                        <div class="flex flex-col gap-2">
                                            <span class="text-base font-black text-gray-900 block tracking-tight">Rs. {{ number_format($order->total_price, 2) }}</span>
                                            <p class="text-xs text-gray-500 font-semibold">{{ ucfirst($order->payment_method ?? 'Bank Transfer') }}</p>
                                            
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                @if ($order->payment_status === 'verified' || $order->payment_status === 'paid')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-black rounded-lg border border-emerald-200">
                                                        <span>✓</span> Verified
                                                    </span>
                                                @elseif ($order->payment_status === 'rejected')
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-rose-50 text-rose-700 text-[10px] font-black rounded-lg border border-rose-200">
                                                        <span>✕</span> Rejected
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-black rounded-lg border border-amber-200 animate-pulse">
                                                        <span>⏳</span> Slip Pending
                                                    </span>
                                                @endif

                                                @if ($order->is_subscription)
                                                    <span class="inline-flex items-center px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-black rounded-lg border border-purple-200">
                                                        🔄 Plan
                                                    </span>
                                                @endif
                                            </div>

                                            @if ($order->payment_reference)
                                                <p class="text-[10px] font-mono text-gray-400 truncate max-w-[150px]" title="{{ $order->payment_reference }}">
                                                    Ref: {{ $order->payment_reference }}
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                @endif

                                <!-- Status: Assigned or Not Assigned ONLY -->
                                <td class="px-6 py-5 align-middle min-w-[140px]" id="order-status-badge-{{ $order->id }}">
                                    @php
                                        $isAssigned = !empty($order->driver_id) || $order->status === 'assigned';
                                    @endphp
                                    @if ($isAssigned)
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            Assigned
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                            Not Assigned
                                        </span>
                                    @endif
                                </td>

                                @if(auth()->user()?->role !== 'Chef')
                                    <!-- Actions (View Details & Invoice only - Delete removed) -->
                                    <td class="px-6 py-5 text-right align-middle whitespace-nowrap min-w-[140px]">
                                        @php
                                            $assignedOrderDriver = $order->driver;
                                            if (!$assignedOrderDriver && !empty($order->driver_id)) {
                                                $assignedOrderDriver = $drivers->firstWhere('id', $order->driver_id);
                                            }
                                            $orderDriverPhone = $assignedOrderDriver ? ($assignedOrderDriver->phone ?? '') : '';
                                            $orderDriverName = $assignedOrderDriver ? ($assignedOrderDriver->name ?? '') : '';
                                            $orderCode = $order->order_number ?: ('#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
                                            $cName = $order->customer_name ?: ($order->user->name ?? 'Guest User');
                                            $cPhone = $order->customer_phone ?: ($order->user->phone ?? 'N/A');
                                            $cAddress = $order->delivery_address ?: ($order->address ?: 'Standard Delivery');
                                            
                                            $orderDishes = [];
                                            if ($order->items) {
                                                foreach ($order->items as $it) {
                                                    $orderDishes[] = $it->quantity . 'x ' . ($it->food->name ?? 'Dish');
                                                }
                                            }
                                            $orderDishesStr = !empty($orderDishes) ? implode(', ', $orderDishes) : 'Dishes as scheduled';
                                            $orderManifestText = "🛵 *DELIVERY DISPATCH — THE MENU*\n"
                                                . "📋 *Order:* " . $orderCode . "\n"
                                                . "👤 *Customer:* " . $cName . "\n"
                                                . "📍 *Address:* " . $cAddress . "\n"
                                                . "📞 *Phone:* " . $cPhone . "\n"
                                                . "📦 *Items:* " . $orderDishesStr
                                                . ($assignedOrderDriver ? ("\n🛵 *Assigned Driver:* " . $assignedOrderDriver->name . ($assignedOrderDriver->phone ? ' (' . $assignedOrderDriver->phone . ')' : '')) : "");
                                        @endphp

                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- WhatsApp Share Stop Snippet Button -->
                                            <button type="button"
                                                    id="order-whatsapp-btn-{{ $order->id }}"
                                                    @click="openWhatsAppManifest({{ json_encode($orderManifestText) }}, {{ json_encode($orderDriverPhone) }}, {{ json_encode($orderDriverName) }})" 
                                                    class="p-2.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-xl transition-all duration-300 border border-emerald-200/80 shadow-2xs hover:shadow-md hover:scale-105 cursor-pointer group" 
                                                    title="Share Order Dispatch to WhatsApp">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            </button>
                                            <!-- One-Click Deliver / Move to History Button -->
                                            <form method="POST" action="{{ route('orders.updateStatus', $order->id) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="delivered">
                                                <button type="submit" 
                                                        id="mark-delivered-btn-{{ $order->id }}"
                                                        onclick="return confirm('Mark Order #{{ $order->id }} as Delivered? It will immediately move to Completed/History.')"
                                                        class="p-2.5 text-emerald-700 bg-emerald-50 hover:bg-emerald-600 hover:text-white rounded-xl transition-all duration-300 border border-emerald-200/80 shadow-2xs hover:shadow-md hover:scale-105 cursor-pointer group" 
                                                        title="Mark Delivered (Move to History)">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                </button>
                                            </form>
                                            <a href="{{ route('orders.invoice', $order) }}" target="_blank" 
                                               id="invoice-btn-{{ $order->id }}"
                                               class="p-2.5 text-gray-500 bg-gray-50 hover:bg-[#0A2E2A] hover:text-[#C9A050] rounded-xl transition-all duration-300 border border-gray-200/80 shadow-2xs hover:shadow-md hover:scale-105" 
                                               title="Print / View Tax Invoice">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            </a>
                                            <button type="button"
                                                    id="view-details-btn-{{ $order->id }}"
                                                    @click="selected = {{ $order->id }}" 
                                                    class="p-2.5 text-gray-500 bg-gray-50 hover:bg-[#0A2E2A] hover:text-[#C9A050] rounded-xl transition-all duration-300 border border-gray-200/80 shadow-2xs hover:shadow-md hover:scale-105 cursor-pointer" 
                                                    title="View Complete Order Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        </div>

                                        <!-- Full Order Details Modal Component (Rendered directly inside loop) -->
                                        <template x-teleport="body">
                                            @php
                                                $vCode = $order->order_number ?: ('#ORD-' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
                                                $cName = $order->customer_name ?: ($order->user->name ?? 'Guest User');
                                                $cPhone = $order->customer_phone ?: ($order->user->phone ?? '—');
                                                $cEmail = $order->customer_email ?: ($order->user->email ?? 'Guest Checkout');
                                                $cAddress = $order->address ?: ($order->delivery_address ?: 'Standard Delivery');

                                                // Group order items by scheduled_date
                                                $groupedItems = $order->items ? $order->items->groupBy(function($item) {
                                                    return $item->scheduled_date ? \Carbon\Carbon::parse($item->scheduled_date)->format('Y-m-d') : 'Immediate Delivery';
                                                }) : collect();
                                            @endphp
                                            <div id="modal-{{ $order->id }}"
                                                 x-show="selected === {{ $order->id }}"
                                                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-200"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0"
                                                 style="display: none;">

                                                <!-- Backdrop -->
                                                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md transition-opacity" @click="selected = null"></div>

                                                <!-- Modal Window -->
                                                <div id="modal-window-{{ $order->id }}"
                                                     class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-3xl w-full overflow-hidden transform transition-all my-8 max-h-[90vh] flex flex-col border border-gray-100"
                                                     x-show="selected === {{ $order->id }}"
                                                     x-transition:enter="transition ease-out duration-300"
                                                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                     x-transition:leave="transition ease-in duration-200"
                                                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                                                    <!-- Modal Header -->
                                                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between shrink-0 border-b border-[#C9A050]/20">
                                                        <div class="flex items-center space-x-3.5">
                                                            <div class="w-11 h-11 rounded-2xl bg-[#C9A050]/20 border border-[#C9A050]/40 flex items-center justify-center text-[#C9A050] text-xl shadow-md shrink-0">
                                                                📋
                                                            </div>
                                                            <div>
                                                                <p class="text-xs text-emerald-100/70 font-semibold uppercase tracking-wider">Order Details & Schedule</p>
                                                                <h3 class="text-xl font-black text-white tracking-tight">{{ $vCode }}</h3>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center space-x-3">
                                                            <div id="modal-order-status-badge-{{ $order->id }}">
                                                                <x-status-badge :status="in_array($order->status, ['assigned', 'processing']) ? 'processing' : $order->status" />
                                                            </div>
                                                            <button type="button" id="modal-close-btn-{{ $order->id }}" @click="selected = null" class="p-2.5 bg-white/10 hover:bg-white/20 text-white/80 hover:text-white rounded-xl transition-colors cursor-pointer" title="Close modal">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Modal Body (Scrollable) -->
                                                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                                                        <!-- Customer & Delivery Information Card -->
                                                        <div class="bg-gray-50/80 rounded-3xl p-5 border border-gray-100">
                                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer Information</h4>
                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                <div class="flex items-center space-x-3.5">
                                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($cName) }}&background=0A2E2A&color=C9A050&bold=true" class="w-12 h-12 rounded-2xl shadow-xs" alt="">
                                                                    <div>
                                                                        <p class="text-base font-bold text-gray-900">{{ $cName }}</p>
                                                                        <p class="text-xs text-gray-500">{{ $cEmail }}</p>
                                                                        @if ($cPhone && $cPhone !== '—')
                                                                            <a href="tel:{{ $cPhone }}" class="inline-flex items-center text-xs font-bold text-[#C9A050] hover:underline mt-0.5">
                                                                                📞 {{ $cPhone }}
                                                                            </a>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="space-y-1.5 text-xs text-gray-600 bg-white p-3.5 rounded-2xl border border-gray-100">
                                                                    <p><span class="font-bold text-gray-900">Delivery Address:</span></p>
                                                                    <p class="text-gray-700 leading-relaxed font-medium">📍 {{ $cAddress }}</p>
                                                                    @if ($order->dinner_address)
                                                                        <p class="pt-1.5 text-[11px] text-purple-700 font-bold border-t border-purple-100">
                                                                            🌙 Dinner Address: {{ $order->dinner_address }}
                                                                        </p>
                                                                    @endif
                                                                    <p class="pt-1 text-[11px] text-gray-400 border-t border-gray-100">
                                                                        Placed on: {{ $order->created_at ? $order->created_at->format('M d, Y · h:i A') : '—' }}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                                                        @if($order->is_subscription)
                                                            <!-- Monthly Plan Complimentary Beverage Card (Read-Only) -->
                                                            <div class="bg-gradient-to-r from-[#0A2E2A]/5 via-amber-50/40 to-[#C9A050]/10 rounded-3xl p-5 border border-[#C9A050]/30 shadow-2xs">
                                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                                    <div class="flex items-center space-x-3.5">
                                                                        <div class="w-11 h-11 rounded-2xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-xl shadow-xs border border-[#C9A050]/30">
                                                                            🍹
                                                                        </div>
                                                                        <div>
                                                                            <div class="flex items-center space-x-2">
                                                                                <h4 class="text-xs font-black text-[#0A2E2A] uppercase tracking-wider">Monthly Plan Complimentary Beverage</h4>
                                                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-[#C9A050]/20 text-[#0A2E2A] border border-[#C9A050]/30">Lunch Deliveries</span>
                                                                            </div>
                                                                            <p class="text-xs text-gray-500 font-medium mt-0.5">
                                                                                Assigned during plan onboarding &middot; Order beverage is non-editable
                                                                            </p>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Read-Only Dark Green & Gold Beverage Badge -->
                                                                    <div class="flex items-center space-x-2 sm:self-center">
                                                                        <div class="inline-flex items-center space-x-2.5 px-4 py-2 rounded-2xl bg-[#0A2E2A] text-white border border-[#C9A050]/40 shadow-xs">
                                                                            <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                                                                            <span class="text-xs font-black tracking-wide text-[#C9A050]">{{ $order->subscription_beverage ?: 'Coca Cola' }}</span>
                                                                            <span class="text-[9px] uppercase font-bold text-gray-300 bg-white/10 px-2 py-0.5 rounded-lg border border-white/10 tracking-wider">Read-Only</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        --}}

                                                        <!-- Complete Scheduled Dishes List with Quantities -->
                                                        <div>
                                                            <div class="flex items-center justify-between mb-3">
                                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Complete Scheduled Dishes List</h4>
                                                                <span class="text-xs font-bold text-[#C9A050]">{{ $order->items ? $order->items->sum('quantity') : 0 }} Total Servings ({{ $order->items ? $order->items->count() : 0 }} Dishes)</span>
                                                            </div>

                                                            <div class="space-y-4">
                                                                @forelse ($groupedItems as $dateStr => $itemsForDate)
                                                                    @php
                                                                        $dateLabel = $dateStr === 'Immediate Delivery' 
                                                                            ? 'Immediate Delivery' 
                                                                            : \Carbon\Carbon::parse($dateStr)->format('l, F d, Y');
                                                                        $isDateToday = ($dateStr === $todayStr);
                                                                        $isDateTomorrow = ($dateStr === $tomorrowStr);
                                                                    @endphp
                                                                    <div class="rounded-2xl border {{ $isDateToday ? 'border-amber-300 bg-amber-50/20' : 'border-gray-200 bg-white' }} overflow-hidden shadow-2xs">
                                                                        <!-- Date Header -->
                                                                        <div class="px-5 py-3 {{ $isDateToday ? 'bg-amber-100/60' : 'bg-gray-50' }} border-b border-gray-200/80 flex items-center justify-between">
                                                                            <div class="flex items-center space-x-2">
                                                                                <span class="text-sm font-bold text-gray-900">📅 {{ $dateLabel }}</span>
                                                                                @if ($isDateToday)
                                                                                    <span class="px-2 py-0.5 bg-amber-500 text-white text-[9px] font-black rounded-full uppercase animate-pulse">TODAY</span>
                                                                                @elseif ($isDateTomorrow)
                                                                                    <span class="px-2 py-0.5 bg-blue-600 text-white text-[9px] font-black rounded-full uppercase">TOMORROW</span>
                                                                                @endif
                                                                            </div>
                                                                            <span class="text-xs font-bold text-gray-500">{{ $itemsForDate->sum('quantity') }} servings · {{ $itemsForDate->count() }} {{ \Illuminate\Support\Str::plural('dish', $itemsForDate->count()) }}</span>
                                                                        </div>

                                                                        <!-- Items for this specific Date -->
                                                                        <div class="divide-y divide-gray-100">
                                                                            @foreach ($itemsForDate as $item)
                                                                                <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                                                    <div class="flex items-center space-x-3.5">
                                                                                        @if ($item->food && $item->food->image_url)
                                                                                            <img src="{{ $item->food->image_url }}" class="w-12 h-12 rounded-xl object-cover shadow-xs shrink-0" alt="">
                                                                                        @else
                                                                                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 font-bold shrink-0">🍽️</div>
                                                                                        @endif
                                                                                        <div>
                                                                                            <div class="flex items-center space-x-2">
                                                                                                <p class="text-sm font-bold text-gray-900">{{ $item->food->name ?? 'Dish' }}</p>
                                                                                                @if ($item->meal_type)
                                                                                                    <span class="px-2 py-0.5 text-[9px] font-black rounded-md uppercase tracking-wider
                                                                                                        @if(strtolower($item->meal_type) === 'breakfast') bg-amber-100 text-amber-800
                                                                                                        @elseif(strtolower($item->meal_type) === 'lunch') bg-blue-100 text-blue-800
                                                                                                        @else bg-purple-100 text-purple-800 @endif">
                                                                                                        {{ $item->meal_type }}
                                                                                                    </span>
                                                                                                @endif
                                                                                                @if ($item->is_paused)
                                                                                                    <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-bold rounded">
                                                                                                        ⏸️ Paused
                                                                                                    </span>
                                                                                                @endif
                                                                                            </div>
                                                                                            @if ($item->swappedFromFood)
                                                                                                <p class="text-[10px] text-gray-400 mt-0.5">Swapped from: {{ $item->swappedFromFood->name }}</p>
                                                                                            @endif
                                                                                            <p class="text-xs text-gray-600 mt-1">
                                                                                                <span class="font-bold text-gray-900 bg-gray-100 px-1.5 py-0.5 rounded text-[11px]">Qty: {{ $item->quantity }}</span>
                                                                                                <span class="text-gray-400 mx-1">×</span>
                                                                                                Rs. {{ number_format($item->price, 2) }}
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>
                                                                                    <span class="text-sm font-black text-gray-900">
                                                                                        Rs. {{ number_format($item->price * $item->quantity, 2) }}
                                                                                    </span>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @empty
                                                                    <div class="p-6 text-center text-gray-400 text-xs bg-gray-50 rounded-2xl border border-gray-100">
                                                                        No scheduled dishes registered for this order.
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <!-- Financial Summary Breakdown -->
                                                        <div class="bg-[#0A2E2A]/5 rounded-3xl p-5 border border-[#0A2E2A]/10 space-y-2">
                                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Invoice Amounts & Breakdown</h4>
                                                            <div class="flex items-center justify-between text-xs text-gray-600">
                                                                <span>Subtotal:</span>
                                                                <span class="font-bold text-gray-900">Rs. {{ number_format(($order->total_price ?? 0) + ($order->discount_amount ?? 0) + ($order->paused_credit_amount ?? 0), 2) }}</span>
                                                            </div>
                                                            @if ($order->coupon_code && $order->discount_amount > 0)
                                                                <div class="flex items-center justify-between text-xs text-emerald-700 font-semibold">
                                                                    <span>Coupon Discount ({{ $order->coupon_code }}):</span>
                                                                    <span>- Rs. {{ number_format($order->discount_amount, 2) }}</span>
                                                                </div>
                                                            @endif
                                                            @if ($order->paused_credit_amount > 0)
                                                                <div class="flex items-center justify-between text-xs text-purple-700 font-semibold">
                                                                    <span>Paused Days Credit:</span>
                                                                    <span>- Rs. {{ number_format($order->paused_credit_amount, 2) }}</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex items-center justify-between text-base font-black text-gray-900 pt-2 border-t border-[#0A2E2A]/10">
                                                                <span>Total Invoiced:</span>
                                                                <span class="text-xl font-black text-[#0A2E2A]">Rs. {{ number_format($order->total_price ?? $order->total_amount, 2) }}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Payment & Bank Slip Verification Card -->
                                                        <div class="bg-gray-50/90 rounded-3xl p-5 border border-gray-200/80 space-y-4">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Payment & Verification</h4>
                                                                    <p class="text-xs text-gray-500 mt-0.5">Manage bank transfer slip and payment status</p>
                                                                </div>
                                                                @if ($order->payment_status === 'verified' || $order->payment_status === 'paid')
                                                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200 flex items-center gap-1">
                                                                        ✓ Payment Verified
                                                                    </span>
                                                                @elseif ($order->payment_status === 'rejected')
                                                                    <span class="px-2.5 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-lg border border-rose-200 flex items-center gap-1">
                                                                        ✕ Verification Rejected
                                                                    </span>
                                                                @else
                                                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-lg border border-amber-200 flex items-center gap-1 animate-pulse">
                                                                        ⏳ Awaiting Slip Verification
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                                                <div class="space-y-2 bg-white p-4 rounded-2xl border border-gray-100">
                                                                    <div>
                                                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Payment Method</span>
                                                                        <span class="font-bold text-gray-900 text-sm">{{ ucfirst($order->payment_method ?? 'Direct Bank Transfer') }}</span>
                                                                    </div>
                                                                    @if ($order->payment_reference)
                                                                        <div>
                                                                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Customer Reference</span>
                                                                            <span class="font-mono font-bold text-[#C9A050] text-sm">{{ $order->payment_reference }}</span>
                                                                        </div>
                                                                    @endif
                                                                    @if ($order->is_subscription)
                                                                        <div class="pt-1">
                                                                            <span class="inline-flex items-center px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded border border-purple-200">
                                                                                🔄 Recurring Monthly Plan
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col justify-between">
                                                                    <div>
                                                                        <span class="text-gray-400 block text-[10px] uppercase font-bold mb-1">Transfer Receipt Slip</span>
                                                                        @if ($order->bank_receipt_url)
                                                                            <a href="{{ $order->bank_receipt_url }}" target="_blank" class="inline-flex items-center gap-1 text-[#C9A050] hover:underline font-bold text-xs mb-2">
                                                                                📄 View Full Uploaded Slip &rarr;
                                                                            </a>
                                                                            <div class="mt-1">
                                                                                <a href="{{ $order->bank_receipt_url }}" target="_blank">
                                                                                    <img src="{{ $order->bank_receipt_url }}" class="h-28 w-auto max-w-full rounded-xl border border-gray-200 object-cover shadow-xs hover:opacity-90 transition-opacity" alt="Bank Slip">
                                                                                </a>
                                                                            </div>
                                                                        @else
                                                                            <p class="text-gray-400 italic text-xs py-2">No bank transfer slip attached.</p>
                                                                        @endif
                                                                    </div>

                                                                    <!-- Verification Actions -->
                                                                    <div class="flex items-center space-x-2 pt-3 mt-3 border-t border-gray-100">
                                                                        <form method="POST" action="{{ route('orders.verifyPayment', $order->id) }}" class="inline-block">
                                                                            @csrf
                                                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-colors shadow-xs cursor-pointer">
                                                                                ✓ Verify & Approve
                                                                            </button>
                                                                        </form>
                                                                        <form method="POST" action="{{ route('orders.rejectPayment', $order->id) }}" class="inline-block" onsubmit="return confirm('Mark this transfer slip as rejected?');">
                                                                            @csrf
                                                                            <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs transition-colors cursor-pointer">
                                                                                ✕ Reject Slip
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <!-- Modal Footer -->
                                                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0 flex-wrap">
                                                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                            <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                                                            <span>Driver dispatches are allocated per meal inside the <strong class="text-gray-900 font-bold">Meal-wise View</strong></span>
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <button type="button" 
                                                                    id="modal-whatsapp-btn-{{ $order->id }}"
                                                                    @click="openWhatsAppManifest({{ json_encode($orderManifestText) }}, {{ json_encode($orderDriverPhone) }}, {{ json_encode($orderDriverName) }})"
                                                                    class="px-4 py-2.5 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 shadow-xs cursor-pointer hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                                WhatsApp
                                                            </button>
                                                            <form method="POST" action="{{ route('orders.updateStatus', $order->id) }}" class="inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="delivered">
                                                                <button type="submit" 
                                                                        onclick="return confirm('Mark Order #{{ $order->id }} as Delivered? It will immediately leave the active views and move to Completed/History.')"
                                                                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md shadow-emerald-600/20 hover:scale-[1.02] cursor-pointer">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                                    Mark Delivered & Complete
                                                                </button>
                                                            </form>
                                                            <a href="{{ route('orders.invoice', $order) }}" target="_blank"
                                                               id="modal-invoice-btn-{{ $order->id }}"
                                                               class="px-5 py-2.5 bg-[#0A2E2A] text-white hover:bg-black hover:text-[#C9A050] rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-md shadow-[#0A2E2A]/15 hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                                Print Tax Invoice
                                                            </a>
                                                            <button type="button" id="modal-footer-close-btn-{{ $order->id }}" @click="selected = null" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-colors cursor-pointer">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()?->role === 'Chef' ? 4 : 6 }}" class="px-6 py-20 text-center">
                                    <div class="w-20 h-20 rounded-3xl bg-gray-50 flex items-center justify-center mx-auto mb-4 text-gray-300 border border-gray-100 shadow-inner">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 font-black text-lg tracking-tight">No orders matching your criteria</h3>
                                    <p class="text-xs sm:text-sm text-gray-400 mt-1">Try resetting your schedule or status filter to see all records</p>
                                    <a href="{{ route('orders') }}" class="inline-block mt-4 px-5 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] text-xs font-black rounded-xl shadow-md hover:scale-105 transition-all">Clear Filters</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="p-6 border-t border-gray-100 bg-gray-50/30">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
        </div>
        <!-- END VIEW 1: FULL ORDERS VIEW -->

        <!-- ============================================================== -->
        <!-- VIEW 2: MEAL-WISE VIEW (Breakfast, Lunch, Dinner Segregation) -->
        <!-- ============================================================== -->
        <div x-show="viewMode === 'meal_wise'" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-1" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6"
             style="display: none;">

            <!-- Meal Logistics Route Overview Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 sm:gap-6">
                <!-- All Routes Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100/80 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:shadow-[#0A2E2A]/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-[#0A2E2A] to-[#0D3833] text-[#C9A050] rounded-2xl shadow-md border border-[#C9A050]/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-[#0A2E2A] bg-[#0A2E2A]/5 border border-[#0A2E2A]/10 px-3 py-1 rounded-full uppercase tracking-wider">All Routes</span>
                    </div>
                    <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ $mealCounts['total'] }}</h3>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Total Meal Dispatches</p>
                </div>

                <!-- Breakfast Deliveries Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-amber-200/60 shadow-xl shadow-amber-500/5 hover:shadow-2xl hover:shadow-amber-500/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-amber-500/10 text-amber-600 rounded-2xl text-xl font-black border border-amber-500/20 shadow-xs">
                            ☀️
                        </div>
                        <span class="text-[10px] font-black text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1 rounded-full uppercase tracking-wider">07:00 - 09:00 AM</span>
                    </div>
                    <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ $mealCounts['breakfast'] }}</h3>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Breakfast Deliveries</p>
                </div>

                <!-- Lunch Deliveries Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-sky-200/60 shadow-xl shadow-sky-500/5 hover:shadow-2xl hover:shadow-sky-500/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-sky-500/10 text-sky-600 rounded-2xl text-xl font-black border border-sky-500/20 shadow-xs">
                            🌤️
                        </div>
                        <span class="text-[10px] font-black text-sky-800 bg-sky-50 border border-sky-200 px-3 py-1 rounded-full uppercase tracking-wider">11:30 AM - 01:30 PM</span>
                    </div>
                    <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ $mealCounts['lunch'] }}</h3>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Lunch Deliveries</p>
                </div>

                <!-- Dinner Deliveries Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-purple-200/60 shadow-xl shadow-purple-500/5 hover:shadow-2xl hover:shadow-purple-500/10 transition-all duration-300 relative overflow-hidden group">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-500/10 text-purple-600 rounded-2xl text-xl font-black border border-purple-500/20 shadow-xs">
                            🌙
                        </div>
                        <span class="text-[10px] font-black text-purple-800 bg-purple-50 border border-purple-200 px-3 py-1 rounded-full uppercase tracking-wider">06:30 - 08:30 PM</span>
                    </div>
                    <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">{{ $mealCounts['dinner'] }}</h3>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Dinner Deliveries</p>
                </div>
            </div>

            <!-- Meal-wise Table & Filter Card -->
            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-100 overflow-hidden">
                <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col space-y-6">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center space-x-3">
                                <h2 class="text-xl sm:text-2xl font-black text-gray-900 tracking-tight">Meal-wise Deliveries</h2>
                                <span class="px-3 py-1 bg-gradient-to-r from-[#C9A050]/20 to-[#C9A050]/10 text-[#0A2E2A] text-[10px] font-black rounded-full uppercase tracking-widest border border-[#C9A050]/30 shadow-xs">
                                    Driver Routing Mode
                                </span>
                            </div>
                            <p class="text-xs font-medium text-gray-400 mt-1">Dispatches segregated by Breakfast, Lunch & Dinner for dedicated driver allocation</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-center space-y-3 sm:space-y-0 sm:space-x-3 w-full sm:w-auto">
                            @if(auth()->user()?->role !== 'Chef')
                                <!-- Export Button -->
                                <a href="{{ route('orders.export') }}" class="flex items-center justify-center space-x-2 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white rounded-2xl px-5 py-2.5 text-xs sm:text-sm font-bold transition-all duration-300 shadow-md shadow-emerald-600/20 hover:shadow-lg shrink-0 w-full sm:w-auto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                    <span>Export to Excel</span>
                                </a>
                            @endif

                            <!-- Search Form -->
                            <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-80">
                                <input type="hidden" name="view" value="meal_wise">
                                <input type="hidden" name="status" value="{{ $statusFilter }}">
                                <input type="hidden" name="schedule" value="{{ $scheduleFilter }}">
                                <input type="hidden" name="meal" value="{{ $mealFilter }}">
                                <input type="hidden" name="area" value="{{ $areaFilter }}">
                                <input type="hidden" name="driver_status" value="{{ $driverStatusFilter }}">
                                <input
                                    type="text"
                                    name="search"
                                    value="{{ $search }}"
                                    placeholder="Search customer, ID, phone, dish..."
                                    @input.debounce.1500ms="$el.form.submit()"
                                    class="w-full pl-11 pr-4 py-2.5 bg-gray-50/80 border border-gray-200 rounded-2xl text-xs sm:text-sm focus:outline-none focus:bg-white focus:border-[#C9A050] focus:ring-2 focus:ring-[#C9A050]/20 transition-all duration-300 shadow-inner"
                                >
                                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </form>
                        </div>
                    </div>

                    <!-- Dual Filter Bar: Meal Category & Schedule -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-4 border-t border-gray-100">
                        <!-- Meal Category Filter Pills -->
                        <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar pb-1">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Meal Type:</span>
                            @foreach ([
                                'all'       => '🌟 All Meals (' . $mealCounts['total'] . ')',
                                'breakfast' => '☀️ Breakfast (' . $mealCounts['breakfast'] . ')',
                                'lunch'     => '🌤️ Lunch (' . $mealCounts['lunch'] . ')',
                                'dinner'    => '🌙 Dinner (' . $mealCounts['dinner'] . ')'
                            ] as $mKey => $mLabel)
                                <a href="{{ request()->fullUrlWithQuery(['meal' => $mKey, 'view' => 'meal_wise', 'meal_page' => null]) }}"
                                    class="px-4 py-2 text-xs font-black rounded-2xl transition-all duration-300 whitespace-nowrap {{ $mealFilter === $mKey ? 'bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] text-[#C9A050] border border-[#C9A050]/30 shadow-md shadow-[#0A2E2A]/15' : 'bg-gray-100/80 text-gray-600 hover:bg-gray-200 hover:text-gray-900' }}">
                                    {{ $mLabel }}
                                </a>
                            @endforeach
                        </div>

                        <!-- Schedule Filter Pills for Meal View -->
                        <div class="flex items-center space-x-1.5 overflow-x-auto no-scrollbar pb-1">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Schedule:</span>
                            @foreach ([
                                'all'       => 'All Dates',
                                'today'     => '🔴 Today (' . $todayScheduledCount . ')',
                                'tomorrow'  => '🟠 Tomorrow',
                                'upcoming'  => '📅 Future Days',
                                'immediate' => '⚡ Immediate'
                            ] as $sKey => $sLabel)
                                <a href="{{ request()->fullUrlWithQuery(['schedule' => $sKey, 'view' => 'meal_wise', 'meal_page' => null]) }}"
                                    class="px-3.5 py-2 text-xs font-bold rounded-2xl transition-all duration-300 whitespace-nowrap {{ $scheduleFilter === $sKey ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] shadow-md shadow-[#C9A050]/20 font-black' : 'bg-gray-100/80 text-gray-600 hover:bg-gray-200 hover:text-gray-900' }}">
                                    {{ $sLabel }}
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <!-- Dispatch Routing & Allocation Filters: Delivery Area / Direction & Driver Status -->
                    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pt-4 border-t border-gray-100">
                        <!-- Route / Delivery Area Filter -->
                        <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                            <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest shrink-0 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                Route / Area:
                            </span>

                            <form method="GET" action="{{ route('orders') }}" class="inline-flex items-center">
                                <input type="hidden" name="view" value="meal_wise">
                                <input type="hidden" name="status" value="{{ $statusFilter }}">
                                <input type="hidden" name="schedule" value="{{ $scheduleFilter }}">
                                <input type="hidden" name="meal" value="{{ $mealFilter }}">
                                <input type="hidden" name="driver_status" value="{{ $driverStatusFilter }}">
                                <input type="hidden" name="search" value="{{ $search }}">
                                <select name="area" onchange="this.form.submit()"
                                    class="text-xs font-bold border border-gray-200 rounded-2xl px-4 py-2 bg-gray-50/70 text-gray-800 hover:border-[#C9A050] focus:ring-2 focus:ring-[#C9A050]/20 focus:bg-white transition-all cursor-pointer shadow-xs">
                                    <option value="all" {{ ($areaFilter === 'all' || empty($areaFilter)) ? 'selected' : '' }}>🗺️ All Delivery Routes ({{ count($mealGroups ?? []) }})</option>
                                    @foreach($availableAreas as $areaName)
                                        <option value="{{ $areaName }}" {{ $areaFilter === $areaName ? 'selected' : '' }}>
                                            📍 {{ $areaName }} ({{ $areaCounts[$areaName] ?? 0 }})
                                        </option>
                                    @endforeach
                                </select>
                            </form>

                            <!-- Quick Area Filter Chips (Top Areas) -->
                            <div class="hidden sm:flex items-center space-x-1.5">
                                @foreach(array_slice($availableAreas, 0, 4) as $topArea)
                                    <a href="{{ request()->fullUrlWithQuery(['area' => $topArea, 'view' => 'meal_wise', 'meal_page' => null]) }}"
                                       class="px-3 py-1.5 text-[11px] font-bold rounded-xl transition-all duration-300 {{ $areaFilter === $topArea ? 'bg-[#0A2E2A] text-[#C9A050] shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $topArea }} ({{ $areaCounts[$topArea] ?? 0 }})
                                    </a>
                                @endforeach
                            </div>

                            @if(!empty($areaFilter) && $areaFilter !== 'all')
                                <a href="{{ request()->fullUrlWithQuery(['area' => 'all', 'view' => 'meal_wise', 'meal_page' => null]) }}" 
                                   class="text-[11px] font-black text-rose-600 hover:underline flex items-center gap-1 ml-1.5">
                                    <span>✕</span> Clear Route
                                </a>
                            @endif
                        </div>

                        @php
                            $manifestStopsCount = $paginatedMealDeliveries->count();
                            $routeManifestText = "🛵 *MENU — DRIVER DELIVERY MANIFEST*\n"
                                . "📍 *Route / Area:* " . ($areaFilter && $areaFilter !== 'all' ? $areaFilter : 'All Delivery Routes') . "\n"
                                . "📅 *Schedule:* " . ucfirst($scheduleFilter ?: 'Active Orders') . "\n"
                                . "🍽️ *Meal Type:* " . ucfirst($mealFilter ?: 'All Meals') . "\n"
                                . "📦 *Total Deliveries:* {$manifestStopsCount} stop(s)\n"
                                . "========================================\n\n";

                            foreach ($paginatedMealDeliveries as $idx => $m) {
                                $stopNum = $idx + 1;
                                $dLines = [];
                                foreach ($m['items'] as $it) {
                                    $dLines[] = $it->quantity . 'x ' . ($it->food->name ?? 'Dish');
                                }
                                $dishesSummary = implode(', ', $dLines);
                                $bevText = ""; {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE) !empty($m['has_beverage']) ? ("\n🍹 *Beverage:* + " . ($m['beverage_name'] ?? 'Coca Cola') . " (Monthly Plan)") : "" --}}
                                $driverTag = !empty($m['driver']) ? ("\n🛵 *Driver:* " . $m['driver']->name) : "";

                                $routeManifestText .= "*STOP #{$stopNum}*\n"
                                    . "👤 *Customer:* " . $m['customer_name'] . "\n"
                                    . "📍 *Address:* " . $m['delivery_address'] . "\n"
                                    . "📞 *Phone:* " . ($m['customer_phone'] ?: 'N/A') . "\n"
                                    . "🍽️ *Meal:* " . ucfirst($m['meal_type']) . "\n"
                                    . "📦 *Dishes:* " . $dishesSummary . $bevText . $driverTag . "\n"
                                    . "----------------------------------------\n\n";
                            }
                            $routeManifestText .= "✨ *Menu Kitchen & Dispatch Management*";
                        @endphp

                        <!-- Driver Allocation Filter Pills & WhatsApp Route Export -->
                        <div class="flex items-center space-x-3 overflow-x-auto no-scrollbar pb-1">
                            <div class="flex items-center space-x-1.5 shrink-0">
                                <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-1 shrink-0">Driver:</span>
                                @foreach ([
                                    'all'          => 'All (' . count($mealGroups ?? []) . ')',
                                    'assigned'     => '✅ Assigned',
                                    'not_assigned' => '⚠️ Not Assigned'
                                ] as $dKey => $dLabel)
                                    <a href="{{ request()->fullUrlWithQuery(['driver_status' => $dKey, 'view' => 'meal_wise', 'meal_page' => null]) }}"
                                        class="px-3 py-1.5 text-xs font-bold rounded-2xl transition-all duration-300 whitespace-nowrap {{ $driverStatusFilter === $dKey ? 'bg-[#0A2E2A] text-[#C9A050] border border-[#0A2E2A] shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                        {{ $dLabel }}
                                    </a>
                                @endforeach
                            </div>

                            <!-- WhatsApp Route Manifest Export Button -->
                            <button type="button" 
                                    id="whatsapp-route-manifest-btn"
                                    @click="openWhatsAppManifest({{ json_encode($routeManifestText) }}, '', '')"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-xs font-black bg-gradient-to-r from-[#25D366] to-[#1ebc57] hover:from-[#20ba59] hover:to-[#17a34a] text-white shadow-md shadow-emerald-500/20 transition-all duration-300 hover:scale-[1.02] cursor-pointer shrink-0"
                                    title="Export & Share WhatsApp manifest for currently visible delivery stops">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span>WhatsApp Manifest</span>
                                <span class="bg-black/20 px-2 py-0.5 rounded-full text-[10px]">{{ $manifestStopsCount }} stops</span>
                            </button>
                        </div>
                    </div>
                </div>

                @if(auth()->user()?->role !== 'Chef')
                    <!-- Bulk Assign Actions for Meals -->
                    <div x-show="selectedOrders.length > 0" style="display: none;" class="px-6 sm:px-8 py-3.5 bg-gradient-to-r from-amber-50 via-amber-50/60 to-white border-b border-amber-200/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 transition-all">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-black text-amber-950">
                                <span class="bg-amber-200 text-amber-950 px-2.5 py-0.5 rounded-full text-xs font-black" x-text="selectedOrders.length"></span> meal(s) selected
                            </span>
                            @if(!empty($areaFilter) && $areaFilter !== 'all')
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-xl bg-amber-100 text-amber-900 border border-amber-300">
                                    Route: {{ $areaFilter }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center space-x-2 w-full sm:w-auto">
                            <select id="bulkDriverSelectMeal" name="bulk_driver_id" class="text-xs font-bold border border-amber-300 rounded-xl px-3.5 py-2 bg-white text-gray-700 hover:border-[#C9A050] focus:outline-none focus:ring-2 focus:ring-[#C9A050]/20 w-full sm:w-auto shadow-xs" required>
                                <option value="" disabled selected>Select Driver to Assign...</option>
                                @foreach ($drivers as $driver)
                                    @php
                                        $activeCount = $driver->assigned_orders_count ?? 0;
                                        $maxCap = $driver->max_orders ?? 5;
                                        $isFull = $activeCount >= $maxCap;
                                    @endphp
                                    <option value="{{ $driver->id }}" {{ $isFull ? 'class=text-red-500 font-bold' : '' }}>
                                        {{ $driver->name }} (Cap: {{ $activeCount }}/{{ $maxCap }}) {{ $isFull ? '⚠️ [FULL]' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" @click="submitBulkAssignFromMeal()" class="px-4 py-2 text-xs font-black bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#b88f3f] hover:to-[#a77e2e] text-[#0A2E2A] rounded-xl shadow-md transition-all duration-300 hover:scale-[1.02] shrink-0 cursor-pointer">
                                Bulk Assign
                            </button>
                        </div>
                    </div>
                @endif

                <!-- Meal Deliveries Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1240px]">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                @if(auth()->user()?->role !== 'Chef')
                                    <th class="px-5 py-4.5 w-12 text-center">
                                        <input type="checkbox" x-model="selectAll" @change="toggleAll" class="w-4 h-4 text-[#C9A050] border-gray-300 rounded-lg focus:ring-[#C9A050] transition-colors cursor-pointer">
                                    </th>
                                @endif
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[180px]">Meal & Route Window</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[200px]">Order & Customer</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[280px]">Dishes in Package</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[240px]">Delivery Route & Address</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Driver Assignment</th>
                                @if(auth()->user()?->role !== 'Chef')
                                    <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[140px]">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70">
                            @forelse ($paginatedMealDeliveries as $meal)
                                <tr class="hover:bg-[#C9A050]/[0.03] transition-colors duration-200 border-b border-gray-100/70 {{ $meal['is_today'] ? 'bg-amber-50/25' : '' }}">
                                    @if(auth()->user()?->role !== 'Chef')
                                        <td class="px-5 py-5 w-12 text-center align-middle">
                                            <input type="checkbox" name="order_ids[]" value="{{ $meal['order_id'] }}" x-model="selectedOrders" class="order-checkbox w-4 h-4 text-[#C9A050] border-gray-300 rounded-lg focus:ring-[#C9A050] transition-colors cursor-pointer">
                                        </td>
                                    @endif

                                    <!-- Meal Type & Window -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center space-x-2">
                                                @if(strtolower($meal['meal_type']) === 'breakfast')
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-black bg-amber-50 text-amber-900 border border-amber-200/80 shadow-xs">
                                                        <span>☀️</span>
                                                        <span>Breakfast</span>
                                                    </span>
                                                @elseif(strtolower($meal['meal_type']) === 'lunch')
                                                    <div class="flex flex-col gap-1.5">
                                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-black bg-sky-50 text-sky-900 border border-sky-200/80 shadow-xs">
                                                            <span>🌤️</span>
                                                            <span>Lunch</span>
                                                        </span>
                                                        {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                                                        @if(!empty($meal['has_beverage']))
                                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-50 text-purple-900 border border-purple-200 shadow-2xs">
                                                                <span>🍹</span>
                                                                <span>+ {{ $meal['beverage_name'] ?? 'Coca Cola' }}</span>
                                                            </span>
                                                        @endif
                                                        --}}
                                                    </div>
                                                @else
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-2xl text-xs font-black bg-purple-50 text-purple-900 border border-purple-200/80 shadow-xs">
                                                        <span>🌙</span>
                                                        <span>Dinner</span>
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex flex-col gap-1">
                                                @if($meal['is_today'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs animate-pulse w-fit">
                                                        🔴 TODAY ({{ now()->format('M d') }})
                                                    </span>
                                                @elseif($meal['is_tomorrow'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs w-fit">
                                                        🟠 TOMORROW ({{ now()->addDay()->format('M d') }})
                                                    </span>
                                                @elseif($meal['scheduled_date'])
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-700 border border-gray-200 w-fit">
                                                        📅 {{ $meal['scheduled_date']->format('M d, Y') }}
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-50 text-amber-800 border border-amber-200 w-fit">
                                                        ⚡ Immediate Dispatch
                                                    </span>
                                                @endif
                                                <p class="text-[10px] text-gray-400 font-semibold tracking-wide">
                                                    @if(strtolower($meal['meal_type']) === 'breakfast') Window: 07:00 – 09:00 AM
                                                    @elseif(strtolower($meal['meal_type']) === 'lunch') Window: 11:30 AM – 01:30 PM
                                                    @else Window: 06:30 – 08:30 PM @endif
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Order Reference & Customer Details -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center space-x-2">
                                                <button type="button" id="meal-order-code-btn-{{ $meal['key'] }}" @click="selected = 'meal-{{ $meal['key'] }}'" class="text-sm font-black text-gray-900 hover:text-[#C9A050] transition-colors duration-200 text-left cursor-pointer tracking-tight" title="Click to view full order details">
                                                    {{ $meal['order_code'] }}
                                                </button>
                                                @if($meal['order']->is_subscription)
                                                    <span class="px-2 py-0.5 bg-purple-50 text-purple-700 text-[9px] font-black rounded-md border border-purple-200 uppercase tracking-wider">
                                                        Plan
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex items-center space-x-3">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($meal['customer_name']) }}&background=0A2E2A&color=C9A050&bold=true" class="w-9 h-9 rounded-2xl shrink-0 shadow-xs border border-gray-100" alt="">
                                                <div class="min-w-0">
                                                    <p class="text-xs font-black text-gray-900 truncate">{{ $meal['customer_name'] }}</p>
                                                    @if($meal['customer_phone'])
                                                        <a href="tel:{{ $meal['customer_phone'] }}" class="text-[11px] text-[#C9A050] hover:underline font-bold flex items-center mt-0.5">
                                                            📞 {{ $meal['customer_phone'] }}
                                                        </a>
                                                    @else
                                                        <span class="text-[10px] text-gray-400 italic">No phone</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Dishes in this Meal Package -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2 max-w-[280px]">
                                            @foreach($meal['items'] as $mItem)
                                                <div class="flex items-center space-x-3 bg-gray-50/70 p-2 rounded-xl border border-gray-100">
                                                    @if($mItem->food && $mItem->food->image_url)
                                                        <img src="{{ $mItem->food->image_url }}" class="w-9 h-9 rounded-xl object-cover border border-gray-100 shadow-xs shrink-0" alt="">
                                                    @else
                                                        <div class="w-9 h-9 rounded-xl bg-gray-100 text-gray-400 flex items-center justify-center text-xs font-bold shrink-0">🍽️</div>
                                                    @endif
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-xs font-bold text-gray-900 truncate">{{ $mItem->food->name ?? 'Dish' }}</p>
                                                        <p class="text-[10px] text-gray-500 font-medium">
                                                            <span class="font-black text-gray-800">Qty: {{ $mItem->quantity }}</span>
                                                            @if(auth()->user()?->role !== 'Chef')
                                                                · <span class="text-gray-400">Rs. {{ number_format($mItem->price * $mItem->quantity, 2) }}</span>
                                                            @endif
                                                        </p>
                                                    </div>
                                                </div>
                                            @endforeach

                                            {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                                            @if(!empty($meal['has_beverage']))
                                                <!-- Prominent Beverage Inclusion Badge for Monthly Plan Lunches -->
                                                <div>
                                                    <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-2xl text-xs font-black bg-gradient-to-r from-purple-50 via-pink-50 to-amber-50 text-purple-950 border border-purple-200 shadow-xs">
                                                        <span class="text-base">🍹</span>
                                                        <span>+ {{ $meal['beverage_name'] ?? 'Coca Cola' }}</span>
                                                        <span class="text-[9px] uppercase tracking-wider bg-purple-200/80 text-purple-900 px-2 py-0.5 rounded-md font-black">Monthly Plan</span>
                                                    </span>
                                                </div>
                                            @endif
                                            --}}

                                            <div class="pt-1.5 text-[11px] text-gray-400 font-semibold border-t border-gray-100 flex items-center justify-between">
                                                <span>Total items:</span>
                                                <span class="font-black text-gray-900">{{ $meal['total_quantity'] }} {{ \Illuminate\Support\Str::plural('dish', $meal['total_quantity']) }}</span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Delivery Route & Destination -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex items-center space-x-1.5">
                                                <span class="inline-flex items-center gap-1 px-3 py-1 rounded-xl text-[10px] font-black bg-[#0A2E2A]/5 text-[#0A2E2A] border border-[#0A2E2A]/15">
                                                    📍 {{ $meal['area'] ?? 'Colombo' }}
                                                </span>
                                            </div>
                                            @if($meal['has_special_dinner_address'])
                                                <div class="p-3 rounded-2xl bg-purple-50/80 border border-purple-200/80">
                                                    <span class="text-[10px] font-black text-purple-800 uppercase tracking-wider block">🌙 Dinner Location:</span>
                                                    <p class="text-xs font-bold text-purple-950 mt-1 leading-snug">{{ $meal['delivery_address'] }}</p>
                                                </div>
                                            @else
                                                <div class="flex items-center space-x-1.5">
                                                    <span class="text-xs font-medium text-gray-700 leading-snug" title="{{ $meal['delivery_address'] }}">
                                                        {{ \Illuminate\Support\Str::limit($meal['delivery_address'], 35) }}
                                                    </span>
                                                    <button @click="editAddressOrderId = {{ $meal['order_id'] }}; editAddressValue = '{{ addslashes($meal['delivery_address']) }}'; showEditAddressModal = true;" class="text-gray-400 hover:text-[#C9A050] transition-colors p-1 rounded-lg hover:bg-gray-100 cursor-pointer" title="Edit Address">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Driver Assignment Status (Strictly "Assigned" or "Not Assigned") -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2.5">
                                            @if ($meal['driver'])
                                                <!-- Strictly Assigned Badge -->
                                                <div>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                        Assigned
                                                    </span>
                                                </div>

                                                <!-- Driver Information -->
                                                <div class="flex items-center space-x-2.5">
                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($meal['driver']->name) }}&background=0A2E2A&color=C9A050" class="w-8 h-8 rounded-xl shrink-0 shadow-2xs" alt="">
                                                    <div class="min-w-0">
                                                        <span class="text-xs font-black text-gray-900 block leading-tight truncate">{{ $meal['driver']->name }}</span>
                                                        @if($meal['driver']->phone)
                                                            <a href="tel:{{ $meal['driver']->phone }}" class="text-[10px] text-gray-500 hover:text-[#C9A050] font-medium block mt-0.5">
                                                                📞 {{ $meal['driver']->phone }}
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if(auth()->user()?->role !== 'Chef')
                                                    <button @click="showAssignModal = true; assignOrderId = {{ $meal['order_id'] }}; assignDriverId = {{ $meal['driver']->id }}" 
                                                            class="text-[10px] font-black text-[#C9A050] hover:underline block cursor-pointer">
                                                        Change Driver
                                                    </button>
                                                @endif
                                            @else
                                                <!-- Strictly Not Assigned Badge -->
                                                <div>
                                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-amber-50 text-amber-800 border border-amber-200/80 shadow-2xs">
                                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                                        Not Assigned
                                                    </span>
                                                </div>

                                                @if(auth()->user()?->role !== 'Chef')
                                                    <div>
                                                        <button @click="showAssignModal = true; assignOrderId = {{ $meal['order_id'] }}; assignDriverId = null" 
                                                                class="px-3 py-1.5 text-xs font-black bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#b88f3f] hover:to-[#a77e2e] text-[#0A2E2A] rounded-xl shadow-xs hover:shadow-md transition-all duration-300 flex items-center space-x-1.5 cursor-pointer">
                                                            <span>🛵</span>
                                                            <span>Assign Driver</span>
                                                        </button>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Actions -->
                                    @if(auth()->user()?->role !== 'Chef')
                                        <td class="px-6 py-5 text-right align-middle whitespace-nowrap min-w-[140px]">
                                            @php
                                                $rowDishes = [];
                                                foreach ($meal['items'] as $it) {
                                                    $rowDishes[] = $it->quantity . 'x ' . ($it->food->name ?? 'Dish');
                                                }
                                                $rowDishesStr = implode(', ', $rowDishes);
                                                $rowBevStr = ""; {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE) !empty($meal['has_beverage']) ? ("\n🍹 *Beverage:* + " . ($meal['beverage_name'] ?? 'Coca Cola') . " (Monthly Plan)") : "" --}}
                                                $assignedMealDriver = $meal['driver'] ?? ($meal['order']->driver ?? null);
                                                if (!$assignedMealDriver && !empty($meal['driver_id'])) {
                                                    $assignedMealDriver = $drivers->firstWhere('id', $meal['driver_id']);
                                                }
                                                if (!$assignedMealDriver && !empty($meal['order']->driver_id)) {
                                                    $assignedMealDriver = $drivers->firstWhere('id', $meal['order']->driver_id);
                                                }
                                                $rowDriverPhone = $assignedMealDriver ? ($assignedMealDriver->phone ?? '') : '';
                                                $rowDriverName = $assignedMealDriver ? ($assignedMealDriver->name ?? '') : '';
                                                $rowDriverStr = $assignedMealDriver ? ("\n🛵 *Assigned Driver:* " . $assignedMealDriver->name . ($assignedMealDriver->phone ? ' (' . $assignedMealDriver->phone . ')' : '')) : "";

                                                $singleStopManifest = "🛵 *DELIVERY DISPATCH — THE MENU*\n"
                                                    . "👤 *Customer:* " . $meal['customer_name'] . "\n"
                                                    . "📍 *Address:* " . $meal['delivery_address'] . "\n"
                                                    . "📞 *Phone:* " . ($meal['customer_phone'] ?: 'N/A') . "\n"
                                                    . "🍽️ *Meal:* " . ucfirst($meal['meal_type']) . "\n"
                                                    . "📦 *Dishes:* " . $rowDishesStr . $rowBevStr . $rowDriverStr;
                                            @endphp

                                            <div class="flex items-center justify-end space-x-2">
                                                <!-- WhatsApp Share Stop Snippet Button -->
                                                <button type="button"
                                                        id="meal-whatsapp-btn-{{ $meal['key'] }}"
                                                        @click="openWhatsAppManifest({{ json_encode($singleStopManifest) }}, {{ json_encode($rowDriverPhone) }}, {{ json_encode($rowDriverName) }})" 
                                                        class="p-2.5 text-emerald-600 hover:text-white hover:bg-emerald-600 rounded-2xl transition-all duration-300 border border-emerald-200/80 bg-emerald-50/60 shadow-xs hover:shadow-md cursor-pointer hover:scale-105" 
                                                        title="Share this stop to WhatsApp / Copy Driver Snippet">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                </button>

                                                <!-- One-Click Deliver / Move to History Button -->
                                                <form method="POST" action="{{ route('orders.updateStatus', $meal['order_id']) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="delivered">
                                                    <button type="submit" 
                                                            id="meal-mark-delivered-btn-{{ $meal['key'] }}"
                                                            onclick="return confirm('Mark Order #{{ $meal['order_id'] }} as Delivered? This will immediately move it to Completed/History.')"
                                                            class="p-2.5 text-emerald-700 hover:text-white hover:bg-emerald-600 rounded-2xl transition-all duration-300 border border-emerald-200/80 bg-emerald-50/60 shadow-xs hover:shadow-md cursor-pointer hover:scale-105" 
                                                            title="Mark Delivered (Move to History)">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                    </button>
                                                </form>
                                                <a href="{{ route('orders.invoice', $meal['order_id']) }}" target="_blank" 
                                                   id="meal-invoice-btn-{{ $meal['key'] }}"
                                                   class="p-2.5 text-gray-500 hover:text-[#0A2E2A] hover:bg-gray-100 rounded-2xl transition-all duration-300 border border-gray-200/80 shadow-xs hover:scale-105" 
                                                   title="Print Tax Invoice">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                </a>
                                                <button type="button"
                                                        id="meal-view-details-btn-{{ $meal['key'] }}"
                                                        @click="selected = 'meal-{{ $meal['key'] }}'" 
                                                        class="p-2.5 text-gray-500 hover:text-[#C9A050] hover:bg-[#C9A050]/10 rounded-2xl transition-all duration-300 border border-gray-200/80 shadow-xs hover:scale-105 cursor-pointer" 
                                                        title="View full order details">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                </button>
                                                <button type="button"
                                                        id="meal-assign-driver-btn-{{ $meal['key'] }}"
                                                        @click="showAssignModal = true; assignOrderId = {{ $meal['order_id'] }}; assignDriverId = {{ $meal['driver_id'] ?? 'null' }}" 
                                                        class="px-3.5 py-1.5 text-xs font-black bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#b88f3f] hover:to-[#a77e2e] text-[#0A2E2A] rounded-2xl shadow-xs hover:shadow-md transition-all duration-300 cursor-pointer">
                                                    Driver
                                                </button>
                                            </div>

                                            <!-- Meal Delivery Order Details Modal Component (Rendered directly inside loop) -->
                                            <template x-teleport="body">
                                                @php
                                                    $mOrder = $meal['order'];
                                                    $mModalId = 'meal-' . $meal['key'];
                                                    $vCode = $mOrder->order_number ?: ('#ORD-' . str_pad($mOrder->id, 4, '0', STR_PAD_LEFT));
                                                    $cName = $mOrder->customer_name ?: ($mOrder->user->name ?? 'Guest User');
                                                    $cPhone = $mOrder->customer_phone ?: ($mOrder->user->phone ?? '—');
                                                    $cEmail = $mOrder->customer_email ?: ($mOrder->user->email ?? 'Guest Checkout');
                                                    $cAddress = $mOrder->address ?: ($mOrder->delivery_address ?: 'Standard Delivery');

                                                    // Group order items by scheduled_date
                                                    $groupedItems = $mOrder->items ? $mOrder->items->groupBy(function($item) {
                                                        return $item->scheduled_date ? \Carbon\Carbon::parse($item->scheduled_date)->format('Y-m-d') : 'Immediate Delivery';
                                                    }) : collect();
                                                @endphp
                                                <div id="modal-{{ $mModalId }}"
                                                     x-show="selected === '{{ $mModalId }}'"
                                                     class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                                                     x-transition:enter="transition ease-out duration-300"
                                                     x-transition:enter-start="opacity-0"
                                                     x-transition:enter-end="opacity-100"
                                                     x-transition:leave="transition ease-in duration-200"
                                                     x-transition:leave-start="opacity-100"
                                                     x-transition:leave-end="opacity-0"
                                                     style="display: none;">

                                                    <!-- Backdrop -->
                                                    <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md" @click="selected = null"></div>

                                                    <!-- Modal Window -->
                                                    <div id="modal-window-{{ $mModalId }}"
                                                         class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-3xl w-full overflow-hidden transform transition-all my-8 max-h-[90vh] flex flex-col border border-gray-100"
                                                         x-show="selected === '{{ $mModalId }}'"
                                                         x-transition:enter="transition ease-out duration-300"
                                                         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                         x-transition:leave="transition ease-in duration-200"
                                                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                                         x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                                                        <!-- Modal Header -->
                                                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between shrink-0 border-b border-[#C9A050]/20">
                                                            <div class="flex items-center space-x-3.5">
                                                                <div class="p-3 bg-white/10 rounded-2xl border border-white/10 text-[#C9A050] shadow-xs">
                                                                    <svg class="w-6 h-6 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                                                </div>
                                                                <div>
                                                                    <p class="text-xs text-white/60 font-semibold tracking-wide">Order Details & Schedule ({{ ucfirst($meal['meal_type']) }})</p>
                                                                    <h3 class="text-xl font-black text-white tracking-tight">{{ $vCode }}</h3>
                                                                </div>
                                                            </div>
                                                            <div class="flex items-center space-x-3">
                                                                <div id="modal-order-status-badge-{{ $mModalId }}">
                                                                    <x-status-badge :status="in_array($mOrder->status, ['assigned', 'processing']) ? 'processing' : $mOrder->status" />
                                                                </div>
                                                                <button type="button" id="modal-close-btn-{{ $mModalId }}" @click="selected = null" class="p-2.5 bg-white/10 hover:bg-white/20 text-white/80 hover:text-white rounded-2xl transition-all cursor-pointer" title="Close modal">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- Modal Body (Scrollable) -->
                                                        <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                                                            <!-- Customer & Delivery Information Card -->
                                                            <div class="bg-gray-50/80 rounded-3xl p-5 border border-gray-100">
                                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer Information</h4>
                                                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                                    <div class="flex items-center space-x-3.5">
                                                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($cName) }}&background=0A2E2A&color=C9A050&bold=true" class="w-12 h-12 rounded-2xl shadow-xs" alt="">
                                                                        <div>
                                                                            <p class="text-base font-bold text-gray-900">{{ $cName }}</p>
                                                                            <p class="text-xs text-gray-500">{{ $cEmail }}</p>
                                                                            @if ($cPhone && $cPhone !== '—')
                                                                                <a href="tel:{{ $cPhone }}" class="inline-flex items-center text-xs font-bold text-[#C9A050] hover:underline mt-0.5">
                                                                                    📞 {{ $cPhone }}
                                                                                </a>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                    <div class="space-y-1.5 text-xs text-gray-600 bg-white p-3.5 rounded-2xl border border-gray-100">
                                                                        <p><span class="font-bold text-gray-900">Delivery Address:</span></p>
                                                                        <p class="text-gray-700 leading-relaxed font-medium">📍 {{ $cAddress }}</p>
                                                                        @if ($mOrder->dinner_address)
                                                                            <p class="pt-1.5 text-[11px] text-purple-700 font-bold border-t border-purple-100">
                                                                                🌙 Dinner Address: {{ $mOrder->dinner_address }}
                                                                            </p>
                                                                        @endif
                                                                        <p class="pt-1 text-[11px] text-gray-400 border-t border-gray-100">
                                                                            Placed on: {{ $mOrder->created_at ? $mOrder->created_at->format('M d, Y · h:i A') : '—' }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            {{-- BEVERAGE FEATURE (DISABLED FOR LATER USE)
                                                            @if($mOrder->is_subscription)
                                                                <!-- Monthly Plan Complimentary Beverage Card (Read-Only) -->
                                                                <div class="bg-gradient-to-r from-[#0A2E2A]/5 via-amber-50/40 to-[#C9A050]/10 rounded-3xl p-5 border border-[#C9A050]/30 shadow-2xs">
                                                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                                                        <div class="flex items-center space-x-3.5">
                                                                            <div class="w-11 h-11 rounded-2xl bg-[#0A2E2A] text-[#C9A050] flex items-center justify-center text-xl shadow-xs border border-[#C9A050]/30">
                                                                                🍹
                                                                            </div>
                                                                            <div>
                                                                                <div class="flex items-center space-x-2">
                                                                                    <h4 class="text-xs font-black text-[#0A2E2A] uppercase tracking-wider">Monthly Plan Complimentary Beverage</h4>
                                                                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-[#C9A050]/20 text-[#0A2E2A] border border-[#C9A050]/30">Lunch Deliveries</span>
                                                                                </div>
                                                                                <p class="text-xs text-gray-500 font-medium mt-0.5">
                                                                                    Assigned during plan onboarding &middot; Order beverage is non-editable
                                                                                </p>
                                                                            </div>
                                                                        </div>

                                                                        <!-- Read-Only Dark Green & Gold Beverage Badge -->
                                                                        <div class="flex items-center space-x-2 sm:self-center">
                                                                            <div class="inline-flex items-center space-x-2.5 px-4 py-2 rounded-2xl bg-[#0A2E2A] text-white border border-[#C9A050]/40 shadow-xs">
                                                                                <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                                                                                <span class="text-xs font-black tracking-wide text-[#C9A050]">{{ $mOrder->subscription_beverage ?: 'Coca Cola' }}</span>
                                                                                <span class="text-[9px] uppercase font-bold text-gray-300 bg-white/10 px-2 py-0.5 rounded-lg border border-white/10 tracking-wider">Read-Only</span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            --}}

                                                            <!-- Complete Scheduled Dishes List with Quantities -->
                                                            <div>
                                                                <div class="flex items-center justify-between mb-3">
                                                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Complete Scheduled Dishes List</h4>
                                                                    <span class="text-xs font-bold text-[#C9A050]">{{ $mOrder->items ? $mOrder->items->sum('quantity') : 0 }} Total Servings ({{ $mOrder->items ? $mOrder->items->count() : 0 }} Dishes)</span>
                                                                </div>

                                                                <div class="space-y-4">
                                                                    @forelse ($groupedItems as $dateStr => $itemsForDate)
                                                                        @php
                                                                            $dateLabel = $dateStr === 'Immediate Delivery' 
                                                                                ? 'Immediate Delivery' 
                                                                                : \Carbon\Carbon::parse($dateStr)->format('l, F d, Y');
                                                                            $isDateToday = ($dateStr === $todayStr);
                                                                            $isDateTomorrow = ($dateStr === $tomorrowStr);
                                                                        @endphp
                                                                        <div class="rounded-2xl border {{ $isDateToday ? 'border-amber-300 bg-amber-50/20' : 'border-gray-200 bg-white' }} overflow-hidden shadow-2xs">
                                                                            <!-- Date Header -->
                                                                            <div class="px-5 py-3 {{ $isDateToday ? 'bg-amber-100/60' : 'bg-gray-50' }} border-b border-gray-200/80 flex items-center justify-between">
                                                                                <div class="flex items-center space-x-2">
                                                                                    <span class="text-sm font-bold text-gray-900">📅 {{ $dateLabel }}</span>
                                                                                    @if ($isDateToday)
                                                                                        <span class="px-2 py-0.5 bg-amber-500 text-white text-[9px] font-black rounded-full uppercase animate-pulse">TODAY</span>
                                                                                    @elseif ($isDateTomorrow)
                                                                                        <span class="px-2 py-0.5 bg-blue-600 text-white text-[9px] font-black rounded-full uppercase">TOMORROW</span>
                                                                                    @endif
                                                                                </div>
                                                                                <span class="text-xs font-bold text-gray-500">{{ $itemsForDate->sum('quantity') }} servings · {{ $itemsForDate->count() }} {{ \Illuminate\Support\Str::plural('dish', $itemsForDate->count()) }}</span>
                                                                            </div>

                                                                            <!-- Items for this specific Date -->
                                                                            <div class="divide-y divide-gray-100">
                                                                                @foreach ($itemsForDate as $item)
                                                                                    <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                                                        <div class="flex items-center space-x-3.5">
                                                                                            @if ($item->food && $item->food->image_url)
                                                                                                <img src="{{ $item->food->image_url }}" class="w-12 h-12 rounded-xl object-cover shadow-xs shrink-0" alt="">
                                                                                            @else
                                                                                                <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 font-bold shrink-0">🍽️</div>
                                                                                            @endif
                                                                                            <div>
                                                                                                <div class="flex items-center space-x-2">
                                                                                                    <p class="text-sm font-bold text-gray-900">{{ $item->food->name ?? 'Dish' }}</p>
                                                                                                    @if ($item->meal_type)
                                                                                                        <span class="px-2 py-0.5 text-[9px] font-black rounded-md uppercase tracking-wider
                                                                                                            @if(strtolower($item->meal_type) === 'breakfast') bg-amber-100 text-amber-800
                                                                                                            @elseif(strtolower($item->meal_type) === 'lunch') bg-blue-100 text-blue-800
                                                                                                            @else bg-purple-100 text-purple-800 @endif">
                                                                                                            {{ $item->meal_type }}
                                                                                                        </span>
                                                                                                    @endif
                                                                                                    @if ($item->is_paused)
                                                                                                        <span class="px-1.5 py-0.5 bg-amber-100 text-amber-800 text-[9px] font-bold rounded">
                                                                                                            ⏸️ Paused
                                                                                                        </span>
                                                                                                    @endif
                                                                                                </div>
                                                                                                @if ($item->swappedFromFood)
                                                                                                    <p class="text-[10px] text-gray-400 mt-0.5">Swapped from: {{ $item->swappedFromFood->name }}</p>
                                                                                                @endif
                                                                                                <p class="text-xs text-gray-600 mt-1">
                                                                                                    <span class="font-bold text-gray-900 bg-gray-100 px-1.5 py-0.5 rounded text-[11px]">Qty: {{ $item->quantity }}</span>
                                                                                                    <span class="text-gray-400 mx-1">×</span>
                                                                                                    Rs. {{ number_format($item->price, 2) }}
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>
                                                                                    <span class="text-sm font-black text-gray-900">
                                                                                        Rs. {{ number_format($item->price * $item->quantity, 2) }}
                                                                                    </span>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @empty
                                                                    <div class="p-6 text-center text-gray-400 text-xs bg-gray-50 rounded-2xl border border-gray-100">
                                                                        No scheduled dishes registered for this order.
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <!-- Financial Summary Breakdown -->
                                                        <div class="bg-[#0A2E2A]/5 rounded-3xl p-5 border border-[#0A2E2A]/10 space-y-2">
                                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Invoice Amounts & Breakdown</h4>
                                                            <div class="flex items-center justify-between text-xs text-gray-600">
                                                                <span>Subtotal:</span>
                                                                <span class="font-bold text-gray-900">Rs. {{ number_format(($mOrder->total_price ?? 0) + ($mOrder->discount_amount ?? 0) + ($mOrder->paused_credit_amount ?? 0), 2) }}</span>
                                                            </div>
                                                            @if ($mOrder->coupon_code && $mOrder->discount_amount > 0)
                                                                <div class="flex items-center justify-between text-xs text-emerald-700 font-semibold">
                                                                    <span>Coupon Discount ({{ $mOrder->coupon_code }}):</span>
                                                                    <span>- Rs. {{ number_format($mOrder->discount_amount, 2) }}</span>
                                                                </div>
                                                            @endif
                                                            @if ($mOrder->paused_credit_amount > 0)
                                                                <div class="flex items-center justify-between text-xs text-purple-700 font-semibold">
                                                                    <span>Paused Days Credit:</span>
                                                                    <span>- Rs. {{ number_format($mOrder->paused_credit_amount, 2) }}</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex items-center justify-between text-base font-black text-gray-900 pt-2 border-t border-[#0A2E2A]/10">
                                                                <span>Total Invoiced:</span>
                                                                <span class="text-xl font-black text-[#0A2E2A]">Rs. {{ number_format($mOrder->total_price ?? $mOrder->total_amount, 2) }}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Payment & Bank Slip Verification Card -->
                                                        <div class="bg-gray-50/90 rounded-3xl p-5 border border-gray-200/80 space-y-4">
                                                            <div class="flex items-center justify-between">
                                                                <div>
                                                                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Payment & Verification</h4>
                                                                    <p class="text-xs text-gray-500 mt-0.5">Manage bank transfer slip and payment status</p>
                                                                </div>
                                                                @if ($mOrder->payment_status === 'verified' || $mOrder->payment_status === 'paid')
                                                                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-xs font-bold rounded-lg border border-emerald-200 flex items-center gap-1">
                                                                        ✓ Payment Verified
                                                                    </span>
                                                                @elseif ($mOrder->payment_status === 'rejected')
                                                                    <span class="px-2.5 py-1 bg-rose-100 text-rose-800 text-xs font-bold rounded-lg border border-rose-200 flex items-center gap-1">
                                                                        ✕ Verification Rejected
                                                                    </span>
                                                                @else
                                                                    <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-lg border border-amber-200 flex items-center gap-1 animate-pulse">
                                                                        ⏳ Awaiting Slip Verification
                                                                    </span>
                                                                @endif
                                                            </div>

                                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                                                                <div class="space-y-2 bg-white p-4 rounded-2xl border border-gray-100">
                                                                    <div>
                                                                        <span class="text-gray-400 block text-[10px] uppercase font-bold">Payment Method</span>
                                                                        <span class="font-bold text-gray-900 text-sm">{{ ucfirst($mOrder->payment_method ?? 'Direct Bank Transfer') }}</span>
                                                                    </div>
                                                                    @if ($mOrder->payment_reference)
                                                                        <div>
                                                                            <span class="text-gray-400 block text-[10px] uppercase font-bold">Customer Reference</span>
                                                                            <span class="font-mono font-bold text-[#C9A050] text-sm">{{ $mOrder->payment_reference }}</span>
                                                                        </div>
                                                                    @endif
                                                                    @if ($mOrder->is_subscription)
                                                                        <div class="pt-1">
                                                                            <span class="inline-flex items-center px-2 py-0.5 bg-purple-50 text-purple-700 text-[10px] font-bold rounded border border-purple-200">
                                                                                🔄 Recurring Monthly Plan
                                                                            </span>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                <div class="bg-white p-4 rounded-2xl border border-gray-100 flex flex-col justify-between">
                                                                    <div>
                                                                        <span class="text-gray-400 block text-[10px] uppercase font-bold mb-1">Transfer Receipt Slip</span>
                                                                        @if ($mOrder->bank_receipt_url)
                                                                            <a href="{{ $mOrder->bank_receipt_url }}" target="_blank" class="inline-flex items-center gap-1 text-[#C9A050] hover:underline font-bold text-xs mb-2">
                                                                                📄 View Full Uploaded Slip &rarr;
                                                                            </a>
                                                                            <div class="mt-1">
                                                                                <a href="{{ $mOrder->bank_receipt_url }}" target="_blank">
                                                                                    <img src="{{ $mOrder->bank_receipt_url }}" class="h-28 w-auto max-w-full rounded-xl border border-gray-200 object-cover shadow-xs hover:opacity-90 transition-opacity" alt="Bank Slip">
                                                                                </a>
                                                                            </div>
                                                                        @else
                                                                            <p class="text-gray-400 italic text-xs py-2">No bank transfer slip attached.</p>
                                                                        @endif
                                                                    </div>

                                                                    <!-- Verification Actions -->
                                                                    <div class="flex items-center space-x-2 pt-3 mt-3 border-t border-gray-100">
                                                                        <form method="POST" action="{{ route('orders.verifyPayment', $mOrder->id) }}" class="inline-block">
                                                                            @csrf
                                                                            <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-colors shadow-xs cursor-pointer">
                                                                                ✓ Verify & Approve
                                                                            </button>
                                                                        </form>
                                                                        <form method="POST" action="{{ route('orders.rejectPayment', $mOrder->id) }}" class="inline-block" onsubmit="return confirm('Mark this transfer slip as rejected?');">
                                                                            @csrf
                                                                            <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 font-bold rounded-xl text-xs transition-colors cursor-pointer">
                                                                                ✕ Reject Slip
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                    </div>

                                                    <!-- Modal Footer -->
                                                    <div class="p-6 sm:p-7 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between gap-4 shrink-0 flex-wrap">
                                                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                            <span class="w-2.5 h-2.5 rounded-full bg-[#C9A050]"></span>
                                                            <span>Delivery route window: <strong class="text-gray-900 font-black">{{ ucfirst($meal['meal_type']) }} · {{ $meal['area'] }}</strong></span>
                                                        </div>

                                                        <div class="flex items-center space-x-2.5">
                                                            <button type="button" 
                                                                    id="meal-modal-whatsapp-btn-{{ $mModalId }}"
                                                                    @click="openWhatsAppManifest({{ json_encode($singleStopManifest) }}, {{ json_encode($rowDriverPhone) }}, {{ json_encode($rowDriverName) }})"
                                                                    class="px-4 py-2.5 bg-gradient-to-r from-[#25D366] to-[#1ebc57] hover:from-[#20ba59] hover:to-[#17a34a] text-white rounded-2xl text-xs font-black transition-all duration-300 flex items-center gap-2 shadow-md shadow-emerald-500/20 cursor-pointer hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                                WhatsApp
                                                            </button>
                                                            <form method="POST" action="{{ route('orders.updateStatus', $mOrder->id) }}" class="inline">
                                                                @csrf
                                                                @method('PATCH')
                                                                <input type="hidden" name="status" value="delivered">
                                                                <button type="submit" 
                                                                        onclick="return confirm('Mark Order #{{ $mOrder->id }} as Delivered? It will immediately leave the active views and move to Completed/History.')"
                                                                        class="px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-500 hover:to-emerald-600 text-white rounded-2xl text-xs font-black transition-all duration-300 flex items-center gap-2 shadow-md shadow-emerald-600/20 cursor-pointer hover:scale-[1.02]">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                                    Mark Delivered & Complete
                                                                </button>
                                                            </form>
                                                            <a href="{{ route('orders.invoice', $mOrder) }}" target="_blank"
                                                               id="modal-invoice-btn-{{ $mModalId }}"
                                                               class="px-4 py-2.5 bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] hover:from-black hover:to-[#0A2E2A] text-white rounded-2xl text-xs font-bold transition-all duration-300 flex items-center gap-2 shadow-md border border-white/10 hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                                Print Tax Invoice
                                                            </a>
                                                            <button type="button" id="modal-footer-close-btn-{{ $mModalId }}" @click="selected = null" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-2xl text-xs font-bold transition-all duration-300 cursor-pointer">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ auth()->user()?->role === 'Chef' ? 5 : 7 }}" class="px-6 py-24 text-center">
                                        <div class="bg-gray-50 w-20 h-20 rounded-3xl flex items-center justify-center mx-auto mb-4 text-[#C9A050] border border-gray-100 shadow-sm">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                                        </div>
                                        <h3 class="text-gray-900 font-black text-lg tracking-tight">No meals matching your criteria</h3>
                                        <p class="text-xs font-medium text-gray-400 mt-1 max-w-sm mx-auto">Try selecting "All Delivery Routes", "All Meals", or resetting active schedule filters</p>
                                        <a href="{{ route('orders', ['view' => 'meal_wise']) }}" class="inline-block mt-5 px-6 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#b88f3f] hover:to-[#a77e2e] text-[#0A2E2A] text-xs font-black rounded-2xl shadow-md transition-all duration-300 hover:scale-[1.02]">Reset All Filters</a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($paginatedMealDeliveries->hasPages())
                    <div class="p-6 border-t border-gray-100">
                        {{ $paginatedMealDeliveries->links() }}
                    </div>
                @endif
            </div>
        </div>
        <!-- END VIEW 2: MEAL-WISE VIEW -->

        <!-- ============================================================== -->
        <!-- VIEW 3: COMPLETED & HISTORY ORDERS VIEW                        -->
        <!-- ============================================================== -->
        <div x-show="viewMode === 'history'" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 translate-y-1" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             class="space-y-6">

            <!-- History Metrics Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Finished Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100/80 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:shadow-[#0A2E2A]/10 transition-all duration-300 relative overflow-hidden group min-h-[155px] flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 rounded-2xl border border-emerald-500/20 shadow-xs">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full uppercase tracking-wider">Archive</span>
                    </div>
                    <div>
                        <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">{{ $historyStats['total'] }}</h3>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Total Finished Orders</p>
                    </div>
                </div>

                <!-- Delivered Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-emerald-200/60 shadow-xl shadow-emerald-500/5 hover:shadow-2xl hover:shadow-emerald-500/10 transition-all duration-300 relative overflow-hidden group min-h-[155px] flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-emerald-500/10 text-emerald-600 rounded-2xl border border-emerald-500/20 shadow-xs">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-emerald-800 bg-emerald-50 border border-emerald-200 px-3 py-1 rounded-full uppercase tracking-wider">Delivered</span>
                    </div>
                    <div>
                        <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">{{ $historyStats['delivered'] }}</h3>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Successfully Delivered</p>
                    </div>
                </div>

                <!-- Completed Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-blue-200/60 shadow-xl shadow-blue-500/5 hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-300 relative overflow-hidden group min-h-[155px] flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500/10 text-blue-600 rounded-2xl border border-blue-500/20 shadow-xs">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-blue-800 bg-blue-50 border border-blue-200 px-3 py-1 rounded-full uppercase tracking-wider">Completed</span>
                    </div>
                    <div>
                        <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight leading-tight">{{ $historyStats['completed'] }}</h3>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Completed Subscriptions</p>
                    </div>
                </div>

                <!-- Revenue Card -->
                <div class="bg-white rounded-3xl p-6 sm:p-7 border border-[#C9A050]/30 shadow-xl shadow-[#C9A050]/5 hover:shadow-2xl hover:shadow-[#C9A050]/10 transition-all duration-300 relative overflow-hidden group min-h-[155px] flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-gradient-to-br from-[#0A2E2A] to-[#0D3833] text-[#C9A050] rounded-2xl border border-[#C9A050]/30 shadow-md">
                            <svg class="w-5 h-5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-[#0A2E2A] bg-gradient-to-r from-[#C9A050]/20 to-[#C9A050]/10 border border-[#C9A050]/30 px-3 py-1 rounded-full uppercase tracking-wider">Revenue</span>
                    </div>
                    <div>
                        <h3 class="text-2xl sm:text-3xl font-black text-gray-900 tracking-tight leading-tight">Rs. {{ number_format($historyStats['revenue'], 2) }}</h3>
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mt-1">Delivered Revenue</p>
                    </div>
                </div>
            </div>

            <!-- History Search & Status Filters -->
            <div class="bg-white rounded-3xl p-6 sm:p-7 border border-gray-100 shadow-xl shadow-[#0A2E2A]/5">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <!-- Search Input -->
                    <form method="GET" action="{{ url()->current() }}" class="relative flex-1 max-w-md">
                        <input type="hidden" name="view" value="history">
                        @if($historyStatusFilter !== 'all')
                            <input type="hidden" name="history_status" value="{{ $historyStatusFilter }}">
                        @endif
                        <input type="text" 
                               name="search" 
                               value="{{ $search }}" 
                               placeholder="Search history by order #, customer, address..." 
                               @input.debounce.1500ms="$el.form.submit()"
                               class="w-full pl-11 pr-4 py-2.5 bg-gray-50/80 border border-gray-200 rounded-2xl text-xs sm:text-sm focus:outline-none focus:bg-white focus:border-[#C9A050] focus:ring-2 focus:ring-[#C9A050]/20 transition-all duration-300 shadow-inner">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>

                    <!-- History Status Sub-Filters -->
                    <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar pb-1">
                        <span class="text-[11px] font-black text-gray-400 uppercase tracking-widest mr-2 shrink-0">Filter:</span>
                        @foreach ([
                            'all'       => 'All History (' . $historyStats['total'] . ')',
                            'delivered' => 'Delivered (' . $historyStats['delivered'] . ')',
                            'completed' => 'Completed (' . $historyStats['completed'] . ')',
                            'cancelled' => 'Cancelled (' . $historyStats['cancelled'] . ')'
                        ] as $hKey => $hLabel)
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'history', 'history_status' => $hKey, 'history_page' => null]) }}"
                               class="px-4 py-2 text-xs font-black rounded-2xl transition-all duration-300 whitespace-nowrap {{ $historyStatusFilter === $hKey ? 'bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] text-[#C9A050] border border-[#C9A050]/30 shadow-md shadow-[#0A2E2A]/15' : 'bg-gray-100/80 text-gray-600 hover:bg-gray-200 hover:text-gray-900' }}">
                                {{ $hLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- History Orders Table -->
            <div class="bg-white rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[1240px]">
                        <thead>
                            <tr class="bg-gray-50/80 border-b border-gray-100">
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Order</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[260px]">Customer & Delivery</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[260px]">Dishes & Details</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[170px]">Driver</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[130px]">Total</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Final Status</th>
                                <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[140px]">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100/70">
                            @forelse ($historyOrders as $hOrder)
                                @php
                                    $hCode = $hOrder->order_number ?: ('#ORD-' . str_pad($hOrder->id, 4, '0', STR_PAD_LEFT));
                                    $hCustName = $hOrder->customer_name ?: ($hOrder->user->name ?? 'Guest User');
                                    $hCustPhone = $hOrder->customer_phone ?: ($hOrder->user->phone ?? '—');
                                    $hCustAddress = $hOrder->address ?: ($hOrder->delivery_address ?: 'Colombo');
                                    $hItemsCount = $hOrder->items ? $hOrder->items->sum('quantity') : 0;
                                @endphp
                                <tr class="hover:bg-[#C9A050]/[0.03] transition-colors duration-200 border-b border-gray-100/70">
                                    <!-- Order ID & Timestamp -->
                                    <td class="px-6 py-5 align-middle whitespace-nowrap">
                                        <div class="flex flex-col gap-2">
                                            <span class="text-sm font-black text-gray-900 tracking-tight block">
                                                {{ $hCode }}
                                            </span>
                                            <div class="flex items-center space-x-1.5 text-xs text-gray-400">
                                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                <span>{{ $hOrder->created_at->format('M d, Y · h:i A') }}</span>
                                            </div>
                                            <div>
                                                <span class="inline-block text-[10px] font-black text-gray-600 bg-gray-100 px-2.5 py-1 rounded-md uppercase tracking-wider">
                                                    {{ $hItemsCount }} {{ \Illuminate\Support\Str::plural('dish', $hItemsCount) }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Customer & Delivery -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2 max-w-[260px]">
                                            <p class="text-sm font-black text-gray-900 truncate">{{ $hCustName }}</p>
                                            <p class="text-xs text-gray-500 font-medium flex items-center space-x-1">
                                                <span>📞</span>
                                                <a href="tel:{{ $hCustPhone }}" class="hover:text-[#C9A050] font-bold transition-colors">{{ $hCustPhone }}</a>
                                            </p>
                                            <p class="text-xs text-gray-600 line-clamp-2 leading-relaxed font-medium" title="{{ $hCustAddress }}">
                                                📍 {{ $hCustAddress }}
                                            </p>
                                        </div>
                                    </td>

                                    <!-- Dishes & Details -->
                                    <td class="px-6 py-5 align-middle">
                                        <div class="flex flex-col gap-2 max-w-[280px]">
                                            @if($hOrder->items && $hOrder->items->isNotEmpty())
                                                @foreach ($hOrder->items->take(2) as $hItem)
                                                    <div class="flex items-center space-x-2 text-xs text-gray-800 bg-gray-50/70 p-2 rounded-xl border border-gray-100">
                                                        <span class="w-1.5 h-1.5 rounded-full bg-[#C9A050] shrink-0"></span>
                                                        <span class="font-black text-gray-900">{{ $hItem->quantity }}x</span>
                                                        <span class="truncate font-medium">{{ $hItem->food->name ?? 'Dish' }}</span>
                                                    </div>
                                                @endforeach
                                                @if($hOrder->items->count() > 2)
                                                    <span class="text-[11px] text-gray-400 font-semibold block pt-0.5">
                                                        +{{ $hOrder->items->count() - 2 }} more dishes
                                                    </span>
                                                @endif
                                            @else
                                                <span class="text-xs text-gray-400 italic">No items registered</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Driver -->
                                    <td class="px-6 py-5 align-middle">
                                        @if($hOrder->driver)
                                            <div class="flex items-center space-x-2.5">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($hOrder->driver->name) }}&background=0A2E2A&color=C9A050" class="w-8 h-8 rounded-xl shrink-0 shadow-2xs" alt="">
                                                <div class="min-w-0">
                                                    <span class="text-xs font-black text-gray-900 block truncate">{{ $hOrder->driver->name }}</span>
                                                    <span class="text-[10px] text-gray-400 font-medium block">{{ $hOrder->driver->vehicle_type ?? 'Rider' }}</span>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400 italic">Unassigned</span>
                                        @endif
                                    </td>

                                    <!-- Total & Payment -->
                                    <td class="px-6 py-5 align-middle whitespace-nowrap">
                                        <div class="flex flex-col gap-2">
                                            <span class="text-sm font-black text-gray-900 block">
                                                Rs. {{ number_format($hOrder->total_price ?? $hOrder->total_amount, 2) }}
                                            </span>
                                            <div>
                                                <span class="inline-block text-[10px] font-black px-2.5 py-0.5 rounded-full uppercase tracking-wider {{ in_array($hOrder->payment_status, ['paid', 'verified']) ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-gray-100 text-gray-600' }}">
                                                    {{ ucfirst($hOrder->payment_status ?? 'paid') }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Final Status -->
                                    <td class="px-6 py-5 align-middle min-w-[140px]">
                                        @if($hOrder->status === 'delivered')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200/80 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Delivered
                                            </span>
                                        @elseif($hOrder->status === 'completed')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-blue-50 text-blue-800 border border-blue-200/80 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                                Completed
                                            </span>
                                        @elseif($hOrder->status === 'cancelled')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-black bg-rose-50 text-rose-800 border border-rose-200/80 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                Cancelled
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-gray-100 text-gray-700">
                                                {{ ucfirst($hOrder->status) }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-5 text-right align-middle whitespace-nowrap min-w-[140px]">
                                        @php
                                            $assignedHistDriver = $hOrder->driver;
                                            if (!$assignedHistDriver && !empty($hOrder->driver_id)) {
                                                $assignedHistDriver = $drivers->firstWhere('id', $hOrder->driver_id);
                                            }
                                            $histDriverPhone = $assignedHistDriver ? ($assignedHistDriver->phone ?? '') : '';
                                            $histDriverName = $assignedHistDriver ? ($assignedHistDriver->name ?? '') : '';
                                            $hOrderCode = $hOrder->order_number ?: ('#ORD-' . str_pad($hOrder->id, 4, '0', STR_PAD_LEFT));
                                            $hCName = $hOrder->customer_name ?: ($hOrder->user->name ?? 'Guest User');
                                            $hCPhone = $hOrder->customer_phone ?: ($hOrder->user->phone ?? 'N/A');
                                            $hCAddress = $hOrder->address ?: ($hOrder->delivery_address ?: 'Standard Delivery');
                                            $hDishes = [];
                                            if ($hOrder->items) {
                                                foreach ($hOrder->items as $it) {
                                                    $hDishes[] = $it->quantity . 'x ' . ($it->food->name ?? 'Dish');
                                                }
                                            }
                                            $hDishesStr = !empty($hDishes) ? implode(', ', $hDishes) : 'Dishes completed';
                                            $hManifestText = "🛵 *DELIVERY DISPATCH — MENU*\n"
                                                . "📋 *Order:* " . $hOrderCode . "\n"
                                                . "👤 *Customer:* " . $hCName . "\n"
                                                . "📍 *Address:* " . $hCAddress . "\n"
                                                . "📞 *Phone:* " . $hCPhone . "\n"
                                                . "📦 *Items:* " . $hDishesStr
                                                . ($assignedHistDriver ? ("\n🛵 *Assigned Driver:* " . $assignedHistDriver->name . ($assignedHistDriver->phone ? ' (' . $assignedHistDriver->phone . ')' : '')) : "");
                                        @endphp
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- WhatsApp Share Button -->
                                            <button type="button"
                                                    id="history-whatsapp-btn-{{ $hOrder->id }}"
                                                    @click="openWhatsAppManifest({{ json_encode($hManifestText) }}, {{ json_encode($histDriverPhone) }}, {{ json_encode($histDriverName) }})" 
                                                    class="p-2.5 text-emerald-600 hover:text-white hover:bg-emerald-600 rounded-2xl transition-all duration-300 border border-emerald-200/80 bg-emerald-50/60 shadow-xs hover:shadow-md cursor-pointer hover:scale-105" 
                                                    title="Share Order Dispatch to WhatsApp">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                            </button>
                                            <a href="{{ route('orders.invoice', $hOrder) }}" target="_blank" 
                                               id="history-invoice-btn-{{ $hOrder->id }}"
                                               class="p-2.5 text-gray-500 hover:text-[#0A2E2A] hover:bg-gray-100 rounded-2xl transition-all duration-300 border border-gray-200/80 shadow-xs hover:scale-105" 
                                               title="Print / View Tax Invoice">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                            </a>
                                            <button type="button"
                                                    id="history-view-details-btn-{{ $hOrder->id }}"
                                                    @click="selected = 'history-{{ $hOrder->id }}'" 
                                                    class="p-2.5 text-gray-500 hover:text-[#C9A050] hover:bg-[#C9A050]/10 rounded-2xl transition-all duration-300 border border-gray-200/80 shadow-xs hover:scale-105 cursor-pointer" 
                                                    title="View Historical Order Details">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </button>
                                        </div>

                                        <!-- History Order Details Modal (Rendered directly inside loop) -->
                                        <template x-teleport="body">
                                            @php
                                                $vCode = $hOrder->order_number ?: ('#ORD-' . str_pad($hOrder->id, 4, '0', STR_PAD_LEFT));
                                                $cName = $hOrder->customer_name ?: ($hOrder->user->name ?? 'Guest User');
                                                $cPhone = $hOrder->customer_phone ?: ($hOrder->user->phone ?? '—');
                                                $cEmail = $hOrder->customer_email ?: ($hOrder->user->email ?? 'Guest Checkout');
                                                $cAddress = $hOrder->address ?: ($hOrder->delivery_address ?: 'Standard Delivery');

                                                // Group order items by scheduled_date
                                                $groupedItems = $hOrder->items ? $hOrder->items->groupBy(function($item) {
                                                    return $item->scheduled_date ? \Carbon\Carbon::parse($item->scheduled_date)->format('Y-m-d') : 'Immediate Delivery';
                                                }) : collect();
                                            @endphp
                                            <div id="history-modal-{{ $hOrder->id }}"
                                                 x-show="selected === 'history-{{ $hOrder->id }}'"
                                                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                                                 x-transition:enter="transition ease-out duration-300"
                                                 x-transition:enter-start="opacity-0"
                                                 x-transition:enter-end="opacity-100"
                                                 x-transition:leave="transition ease-in duration-200"
                                                 x-transition:leave-start="opacity-100"
                                                 x-transition:leave-end="opacity-0"
                                                 style="display: none;">
                                                <!-- Backdrop -->
                                                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md transition-opacity" @click="selected = null"></div>

                                                <!-- Modal Content -->
                                                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden transform transition-all border border-gray-100"
                                                     x-show="selected === 'history-{{ $hOrder->id }}'"
                                                     x-transition:enter="transition ease-out duration-300"
                                                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                                                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                                     x-transition:leave="transition ease-in duration-200"
                                                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                                                     x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                                                     @click.away="selected = null">
                                                    
                                                    <!-- Modal Header -->
                                                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between text-white shrink-0 border-b border-[#C9A050]/20">
                                                        <div class="flex items-center space-x-3.5">
                                                            <div class="w-11 h-11 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 flex items-center justify-center font-black shadow-md text-base">
                                                                ✓
                                                            </div>
                                                            <div>
                                                                <div class="flex items-center space-x-2.5">
                                                                    <h3 class="text-xl font-black tracking-tight text-white">{{ $vCode }}</h3>
                                                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 shadow-xs">
                                                                        {{ $hOrder->status }}
                                                                    </span>
                                                                </div>
                                                                <p class="text-xs text-emerald-100/70 mt-0.5">Historical Order Archive · Delivered on {{ $hOrder->updated_at->format('M d, Y · h:i A') }}</p>
                                                            </div>
                                                        </div>
                                                        <button type="button" @click="selected = null" class="p-2.5 bg-white/10 hover:bg-white/20 text-white/80 hover:text-white rounded-xl transition-all duration-200 cursor-pointer" title="Close modal">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                        </button>
                                                    </div>

                                                    <!-- Modal Body (Scrollable) -->
                                                    <div class="p-6 sm:p-8 space-y-6 overflow-y-auto custom-scrollbar flex-1">
                                                        <!-- Customer & Delivery Information Grid -->
                                                        <div class="bg-gray-50/80 rounded-3xl p-5 border border-gray-100">
                                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Customer & Delivery Destination</h4>
                                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                                <div class="flex items-center space-x-3.5 bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs">
                                                                    <img src="https://ui-avatars.com/api/?name={{ urlencode($cName) }}&background=0A2E2A&color=C9A050&bold=true" class="w-12 h-12 rounded-2xl shadow-xs shrink-0" alt="">
                                                                    <div>
                                                                        <h5 class="text-sm font-black text-gray-900 tracking-tight">{{ $cName }}</h5>
                                                                        <p class="text-xs text-gray-500 font-medium">✉️ {{ $cEmail }}</p>
                                                                        @if ($cPhone && $cPhone !== '—')
                                                                            <a href="tel:{{ $cPhone }}" class="inline-flex items-center text-xs font-bold text-[#C9A050] hover:underline mt-1">
                                                                                📞 {{ $cPhone }}
                                                                            </a>
                                                                        @endif
                                                                    </div>
                                                                </div>

                                                                <div class="bg-white p-4 rounded-2xl border border-gray-100 shadow-2xs flex flex-col justify-between">
                                                                    <div>
                                                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1">Delivered To</span>
                                                                        <p class="text-xs text-gray-700 leading-relaxed font-medium">📍 {{ $cAddress }}</p>
                                                                    </div>
                                                                    @if($hOrder->driver)
                                                                        <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                                                                            <span class="text-gray-400 font-medium">Delivered by driver:</span>
                                                                            <span class="font-bold text-[#0A2E2A] bg-[#0A2E2A]/5 px-2.5 py-0.5 rounded-lg border border-[#0A2E2A]/10">🛵 {{ $hOrder->driver->name }} ({{ $hOrder->driver->phone ?? '—' }})</span>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Complete Scheduled Dishes List -->
                                                        <div class="space-y-3">
                                                            <div class="flex items-center justify-between">
                                                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Delivered Dishes List</h4>
                                                                <span class="text-xs font-bold text-[#C9A050]">{{ $hItemsCount }} Total Servings</span>
                                                            </div>

                                                            <div class="space-y-4">
                                                                @forelse ($groupedItems as $dateStr => $itemsForDate)
                                                                    <div class="bg-white rounded-2xl border border-gray-200/80 overflow-hidden shadow-2xs">
                                                                        <div class="bg-gray-50/90 px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                                                                            <span class="text-xs font-black text-gray-900 tracking-tight">
                                                                                📅 {{ $dateStr === 'Immediate Delivery' ? 'Immediate Delivery' : \Carbon\Carbon::parse($dateStr)->format('l, F j, Y') }}
                                                                            </span>
                                                                            <span class="text-xs font-bold text-gray-500">{{ $itemsForDate->sum('quantity') }} servings</span>
                                                                        </div>
                                                                        <div class="divide-y divide-gray-100">
                                                                            @foreach ($itemsForDate as $item)
                                                                                <div class="p-4 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                                                                                    <div class="flex items-center space-x-3.5">
                                                                                        @if ($item->food && $item->food->image_url)
                                                                                            <img src="{{ $item->food->image_url }}" class="w-12 h-12 rounded-xl object-cover shadow-xs shrink-0" alt="">
                                                                                        @else
                                                                                            <div class="w-12 h-12 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400 font-bold shrink-0">🍽️</div>
                                                                                        @endif
                                                                                        <div>
                                                                                            <div class="flex items-center space-x-2">
                                                                                                <p class="text-sm font-bold text-gray-900">{{ $item->food->name ?? 'Dish' }}</p>
                                                                                                @if ($item->meal_type)
                                                                                                    <span class="px-2 py-0.5 text-[9px] font-black rounded-md uppercase tracking-wider
                                                                                                        @if(strtolower($item->meal_type) === 'breakfast') bg-amber-100 text-amber-800
                                                                                                        @elseif(strtolower($item->meal_type) === 'lunch') bg-blue-100 text-blue-800
                                                                                                        @else bg-purple-100 text-purple-800 @endif">
                                                                                                        {{ $item->meal_type }}
                                                                                                    </span>
                                                                                                @endif
                                                                                            </div>
                                                                                            <p class="text-xs text-gray-600 mt-1">
                                                                                                <span class="font-bold text-gray-900 bg-gray-100 px-2 py-0.5 rounded text-[11px]">Qty: {{ $item->quantity }}</span>
                                                                                                <span class="text-gray-400 mx-1">×</span>
                                                                                                Rs. {{ number_format($item->price, 2) }}
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>
                                                                                    <span class="text-sm font-black text-gray-900">
                                                                                        Rs. {{ number_format($item->price * $item->quantity, 2) }}
                                                                                    </span>
                                                                                </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                @empty
                                                                    <div class="p-6 text-center text-gray-400 text-xs bg-gray-50 rounded-2xl border border-gray-100">
                                                                        No items recorded for this order.
                                                                    </div>
                                                                @endforelse
                                                            </div>
                                                        </div>

                                                        <!-- Financial Breakdown -->
                                                        <div class="bg-gradient-to-br from-[#0A2E2A]/5 to-[#C9A050]/5 rounded-3xl p-6 border border-[#0A2E2A]/10 space-y-2.5 shadow-2xs">
                                                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Total Invoiced Amount</h4>
                                                            <div class="flex items-center justify-between text-xs text-gray-600">
                                                                <span>Subtotal:</span>
                                                                <span class="font-bold text-gray-900">Rs. {{ number_format(($hOrder->total_price ?? 0) + ($hOrder->discount_amount ?? 0), 2) }}</span>
                                                            </div>
                                                            @if ($hOrder->coupon_code && $hOrder->discount_amount > 0)
                                                                <div class="flex items-center justify-between text-xs text-emerald-700 font-semibold">
                                                                    <span>Coupon Discount ({{ $hOrder->coupon_code }}):</span>
                                                                    <span>- Rs. {{ number_format($hOrder->discount_amount, 2) }}</span>
                                                                </div>
                                                            @endif
                                                            <div class="flex items-center justify-between text-base font-black text-gray-900 pt-3 border-t border-[#0A2E2A]/15">
                                                                <span>Total Paid:</span>
                                                                <span class="text-2xl font-black text-[#0A2E2A]">Rs. {{ number_format($hOrder->total_price ?? $hOrder->total_amount, 2) }}</span>
                                                            </div>
                                                        </div>

                                                        <!-- Re-activate / Change Status if needed -->
                                                        <div class="bg-gray-50/90 border border-gray-200/80 rounded-3xl p-5 shadow-2xs">
                                                            <h5 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2.5">Re-open or Adjust Order Status</h5>
                                                            <form method="POST" action="{{ route('orders.updateStatus', $hOrder->id) }}">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="flex items-center space-x-2.5">
                                                                    <select name="status" class="bg-white border border-gray-200 rounded-xl px-4 py-2.5 text-xs font-bold text-gray-800 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] shadow-2xs flex-1">
                                                                        @foreach(['delivered' => 'Delivered', 'completed' => 'Completed', 'processing' => 'Re-open to Processing', 'pending' => 'Re-open to Pending', 'cancelled' => 'Cancelled'] as $val => $lbl)
                                                                            <option value="{{ $val }}" {{ $hOrder->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#b88f3f] hover:to-[#a77e30] text-[#0A2E2A] text-xs font-black rounded-xl shrink-0 cursor-pointer shadow-md hover:shadow-lg transition-all duration-300 hover:scale-[1.02]">
                                                                        Update Status
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>

                                                    <!-- Modal Footer -->
                                                    <div class="p-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between gap-3 shrink-0 flex-wrap">
                                                        <div class="flex items-center space-x-2 text-xs font-bold text-gray-500">
                                                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                            <span>Archived historical order record</span>
                                                        </div>

                                                        <div class="flex items-center space-x-2">
                                                            <button type="button" 
                                                                    id="history-modal-whatsapp-btn-{{ $hOrder->id }}"
                                                                    @click="openWhatsAppManifest({{ json_encode($hManifestText) }}, {{ json_encode($histDriverPhone) }}, {{ json_encode($histDriverName) }})"
                                                                    class="px-5 py-2.5 bg-[#25D366] hover:bg-[#20ba59] text-white rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-2 shadow-sm cursor-pointer hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                                                WhatsApp
                                                            </button>
                                                            <a href="{{ route('orders.invoice', $hOrder) }}" target="_blank"
                                                               id="history-modal-invoice-btn-{{ $hOrder->id }}"
                                                               class="px-5 py-2.5 bg-[#0A2E2A] hover:bg-[#0D3833] text-white rounded-xl text-xs font-bold transition-all duration-200 flex items-center gap-2 shadow-sm hover:scale-[1.02]">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                                                Print Tax Invoice
                                                            </a>
                                                            <button type="button" id="history-modal-close-btn-{{ $hOrder->id }}" @click="selected = null" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-xl text-xs font-bold transition-all duration-200 cursor-pointer">
                                                                Close
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-24 text-center">
                                        <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-[#0A2E2A]/10 to-[#C9A050]/15 border border-[#C9A050]/30 flex items-center justify-center mx-auto mb-4 text-[#0A2E2A] shadow-md">
                                            <svg class="w-10 h-10 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        </div>
                                        <h3 class="text-gray-900 font-black text-lg tracking-tight">No historical orders found</h3>
                                        <p class="text-xs text-gray-500 font-medium mt-1 max-w-sm mx-auto">When active orders are marked as Delivered or Completed, their full historical archives will appear here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($historyOrders->hasPages())
                    <div class="p-6 border-t border-gray-100">
                        {{ $historyOrders->links() }}
                    </div>
                @endif
            </div>
        </div>
        <!-- END VIEW 3: COMPLETED & HISTORY ORDERS VIEW -->

    </div>

    <!-- Live Real-Time WebSockets Listener -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Echo) {
                window.Echo.channel('orders')
                    .listen('OrderStatusUpdated', (e) => {
                        if (e.stats) {
                            const totalEl = document.getElementById('total-orders-count');
                            const pendingEl = document.getElementById('pending-orders-count');
                            if (totalEl) totalEl.innerText = e.stats.total;
                            if (pendingEl) pendingEl.innerText = e.stats.pending;
                        }

                        const renderBadge = (status) => {
                            const map = {
                                'pending': { label: 'Pending', cls: 'bg-amber-50 text-amber-600 ring-amber-500/20' },
                                'processing': { label: 'Processing', cls: 'bg-blue-50 text-blue-600 ring-blue-500/20' },
                                'delivered': { label: 'Delivered', cls: 'bg-emerald-50 text-emerald-600 ring-emerald-500/20' },
                                'completed': { label: 'Completed', cls: 'bg-emerald-50 text-emerald-600 ring-emerald-500/20' },
                                'cancelled': { label: 'Cancelled', cls: 'bg-rose-50 text-rose-600 ring-rose-500/20' },
                            };
                            const info = map[status] || {
                                label: status.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()),
                                cls: 'bg-gray-100 text-gray-600 ring-gray-400/20'
                            };
                            return `
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full ring-1 ring-inset shadow-sm ${info.cls}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                    ${info.label}
                                </span>
                            `;
                        };

                        const statusBadgeContainer = document.getElementById(`order-status-badge-${e.id}`);
                        if (statusBadgeContainer) {
                            const isAssigned = Boolean(e.driver_id || e.driver || e.status === 'assigned');
                            statusBadgeContainer.innerHTML = isAssigned
                                ? `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Assigned
                                   </span>`
                                : `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                    Not Assigned
                                   </span>`;
                        }

                        const modalBadge = document.getElementById(`modal-order-status-badge-${e.id}`);
                        if (modalBadge) {
                            modalBadge.innerHTML = renderBadge(e.status);
                        }

                        const formattedId = '#ORD-' + String(e.id).padStart(4, '0');
                        const statusFormatted = e.status.replace(/_/g, ' ');
                        const toastMsg = e.action === 'created'
                            ? `New Order ${formattedId} received from ${e.customer_name}`
                            : `Order ${formattedId} is now ${statusFormatted}${e.driver ? ' (' + e.driver.name + ')' : ''}`;
                        showToast(toastMsg, e.status);
                    });

                window.Echo.channel('drivers')
                    .listen('DriverStatusUpdated', (e) => {
                        showToast(`Driver ${e.name} is now ${e.status.replace('_', ' ')}`, e.status);
                    });
            }
        });

        function showToast(message, status) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            let bgClass = 'bg-white border-l-4 border-[#C9A050] text-gray-800 shadow-xl';
            if (status === 'available' || status === 'completed' || status === 'delivered') {
                bgClass = 'bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 shadow-xl';
            } else if (status === 'on_delivery' || status === 'processing') {
                bgClass = 'bg-blue-50 border-l-4 border-blue-500 text-blue-800 shadow-xl';
            } else if (status === 'cancelled' || status === 'offline') {
                bgClass = 'bg-gray-100 border-l-4 border-gray-400 text-gray-800 shadow-xl';
            }

            toast.className = `px-5 py-4 rounded-2xl transform transition-all duration-300 translate-y-4 opacity-0 flex items-center justify-between min-w-[300px] border border-black/5 ${bgClass}`;
            toast.innerHTML = `
                <div class="flex items-center space-x-3">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    <span class="font-bold text-sm">${message}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="ml-4 opacity-40 hover:opacity-100 text-lg leading-none">&times;</button>
            `;
            
            container.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-y-4', 'opacity-0'), 10);
            setTimeout(() => {
                toast.classList.add('translate-y-4', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }

        function submitBulkAssign() {
            const driverSelect = document.getElementById('bulkDriverSelectMeal') || document.getElementById('bulkDriverSelect');
            if (!driverSelect || !driverSelect.value) {
                alert('Please select a driver to assign.');
                return;
            }

            // Gather order IDs from checked boxes in the DOM
            const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
            let orderIds = [];
            checkedBoxes.forEach(cb => {
                if (cb.value) orderIds.push(cb.value);
            });

            // Fallback: If checkboxes weren't selected via DOM, check Alpine.js state
            if (orderIds.length === 0) {
                const rootEl = document.querySelector('[x-data]');
                if (rootEl && window.Alpine) {
                    const data = window.Alpine.$data(rootEl);
                    if (data && data.selectedOrders && data.selectedOrders.length > 0) {
                        orderIds = data.selectedOrders;
                    }
                }
            }

            // Remove duplicates
            orderIds = [...new Set(orderIds)];

            if (orderIds.length === 0) {
                alert('Please select at least one meal dispatch to assign.');
                return;
            }

            const form = document.getElementById('hiddenBulkForm');
            if (!form) return;

            // Remove any previously appended dynamic inputs
            form.querySelectorAll('input[name="bulk_driver_id"], input[name="driver_id"], input[name="order_ids[]"], input[name="view"]').forEach(el => el.remove());

            // Append driver ID under both keys for fail-safe compatibility
            const bulkDriverInput = document.createElement('input');
            bulkDriverInput.type = 'hidden';
            bulkDriverInput.name = 'bulk_driver_id';
            bulkDriverInput.value = driverSelect.value;
            form.appendChild(bulkDriverInput);

            const driverInput = document.createElement('input');
            driverInput.type = 'hidden';
            driverInput.name = 'driver_id';
            driverInput.value = driverSelect.value;
            form.appendChild(driverInput);

            // Append view to return back to meal_wise
            const viewInput = document.createElement('input');
            viewInput.type = 'hidden';
            viewInput.name = 'view';
            viewInput.value = 'meal_wise';
            form.appendChild(viewInput);

            // Append order IDs
            orderIds.forEach(id => {
                const orderInput = document.createElement('input');
                orderInput.type = 'hidden';
                orderInput.name = 'order_ids[]';
                orderInput.value = id;
                form.appendChild(orderInput);
            });

            form.submit();
        }
        window.submitBulkAssign = submitBulkAssign;
        window.submitBulkAssignFromMeal = submitBulkAssign;
    </script>

    <form id="hiddenBulkForm" method="POST" action="{{ url('/orders/bulk-assign') }}" style="display: none;">
        @csrf
    </form>
</x-app-layout>
