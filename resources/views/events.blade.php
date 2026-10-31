<x-app-layout>
    @php
        $statusColor = [
            'available'   => 'text-green-600 bg-green-50 border-green-100',
            'on_delivery' => 'text-blue-600 bg-blue-50 border-blue-100',
            'offline'     => 'text-gray-500 bg-gray-100 border-gray-200',
        ];
    @endphp

    <div class="space-y-6" x-data="{ 
        showAssignModal: false, assignEventId: null, assignDriverId: null,
        showModal: false, isEditing: false, eventData: {}, formAction: '', formMethod: 'POST',
        openCreateModal() {
            this.isEditing = false;
            this.eventData = { name: '', start_date: '', end_date: '', total_cost: 0 };
            this.formAction = '{{ route('events.store') }}';
            this.formMethod = 'POST';
            this.showModal = true;
        },
        openEditModal(event) {
            this.isEditing = true;
            this.eventData = { ...event };
            this.eventData.start_date = event.start_date ? event.start_date.split('T')[0] : '';
            this.eventData.end_date = event.end_date ? event.end_date.split('T')[0] : '';
            this.formAction = '/events/' + event.id;
            this.formMethod = 'PUT';
            this.showModal = true;
        }
    }">

        @if (session('success'))
            <div class="flex items-center justify-between bg-green-50 border border-green-200 text-green-700 px-5 py-3 rounded-2xl text-sm font-medium">
                <span>{{ session('success') }}</span>
                <button @click="window.location.reload()" class="text-green-500 hover:text-green-700">&times;</button>
            </div>
        @endif

        @if (session('error'))
            <div class="flex items-center justify-between bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-2xl text-sm font-medium">
                <span>{{ session('error') }}</span>
                <button @click="window.location.reload()" class="text-red-500 hover:text-red-700">&times;</button>
            </div>
        @endif

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#0A2E2A]/5 border border-[#0A2E2A]/10 mb-2">
                    <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#0A2E2A]">Catering & Banquets</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] tracking-tight">Events Management</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Manage catering events, special banquets, and assign delivery drivers</p>
            </div>
            <button @click="openCreateModal()" class="px-5 py-2.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm flex items-center space-x-2 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] shrink-0 self-start sm:self-auto min-h-[40px]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>Create Event</span>
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 border border-gray-200/70 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:border-[#C9A050]/40 transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#0A2E2A]/10 rounded-2xl flex items-center justify-center border border-[#0A2E2A]/10">
                        <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                    <span class="text-xs font-black text-[#0A2E2A] bg-[#0A2E2A]/10 border border-[#0A2E2A]/15 px-3 py-1 rounded-full">All</span>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['total'] }}</h3>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-1">Total Events</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 border border-gray-200/70 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:border-[#C9A050]/40 transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center border border-blue-500/10">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-xs font-black text-blue-700 bg-blue-500/10 border border-blue-500/20 px-3 py-1 rounded-full">Soon</span>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['upcoming'] }}</h3>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-1">Upcoming</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 border border-gray-200/70 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:border-[#C9A050]/40 transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center border border-emerald-500/10">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path></svg>
                    </div>
                    <span class="text-xs font-black text-emerald-700 bg-emerald-500/10 border border-emerald-500/20 px-3 py-1 rounded-full">Assigned</span>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['assigned'] }}</h3>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-1">With Driver</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 border border-gray-200/70 shadow-xl shadow-[#0A2E2A]/5 hover:shadow-2xl hover:border-[#C9A050]/40 transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center border border-amber-500/10">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                    <span class="text-xs font-black text-amber-700 bg-amber-500/10 border border-amber-500/20 px-3 py-1 rounded-full">Pending</span>
                </div>
                <h3 class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ $stats['unassigned'] }}</h3>
                <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mt-1">Needs Driver</p>
            </div>
        </div>

        <!-- Events Table -->
        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <div class="p-6 sm:p-7 border-b border-gray-100/80 bg-gradient-to-r from-gray-50/50 via-white to-gray-50/30 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-[#0A2E2A] tracking-tight">Event Schedule</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Catering events, menus, and assigned courier drivers</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[950px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[240px]">Event</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[160px]">Host</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[160px]">Date</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[140px]">Cost</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[180px]">Driver</th>
                            <th class="px-6 py-4.5 text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[130px]">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100/70">
                        @forelse ($events as $event)
                            @php
                                $sameDay = $event->start_date->equalTo($event->end_date);
                                $dateLabel = $sameDay
                                    ? $event->start_date->format('M d, Y')
                                    : $event->start_date->format('M d') . ' – ' . $event->end_date->format('M d, Y');
                            @endphp
                            <tr class="hover:bg-[#C9A050]/[0.03] transition-all duration-300">
                                <td class="px-6 py-5 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-11 h-11 bg-gradient-to-br from-[#0A2E2A] to-[#0D3833] rounded-2xl flex items-center justify-center shadow-md shadow-[#0A2E2A]/10 shrink-0">
                                            <svg class="w-5 h-5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <div class="flex flex-col gap-0.5">
                                            <p class="text-sm font-bold text-gray-900 leading-snug">{{ $event->name }}</p>
                                            <p class="text-xs text-gray-400 font-medium">{{ $event->items->count() }} menu items</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <p class="text-sm font-medium text-gray-700">{{ $event->user->name ?? '—' }}</p>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 text-gray-700">{{ $dateLabel }}</span>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    <span class="text-sm font-extrabold text-[#0A2E2A]">Rs. {{ number_format($event->total_cost, 2) }}</span>
                                </td>
                                <td class="px-6 py-5 align-middle">
                                    @if ($event->driver)
                                        <div class="flex items-center space-x-2.5">
                                            <img src="https://ui-avatars.com/api/?name={{ urlencode($event->driver->name) }}&background=0A2E2A&color=C9A050" class="w-8 h-8 rounded-xl shadow-sm border border-gray-200" alt="">
                                            <span class="text-sm font-bold text-gray-900">{{ $event->driver->name }}</span>
                                        </div>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200">Unassigned</span>
                                    @endif
                                </td>
                                <td class="px-6 py-5 align-middle text-right">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button @click="showAssignModal = true; assignEventId = {{ $event->id }}; assignDriverId = {{ $event->driver_id ?? 'null' }}" class="event-assign-btn px-3.5 py-1.5 text-[11px] font-black bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] rounded-xl hover:shadow-md transition-all duration-200 uppercase tracking-wider">Assign</button>
                                        <button @click="openEditModal({{ $event->toJson() }})" class="p-2 text-gray-400 hover:text-[#C9A050] hover:bg-[#C9A050]/10 rounded-xl transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <form method="POST" action="{{ route('events.destroy', $event) }}" onsubmit="return confirm('Delete this event?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-20 text-center">
                                    <div class="bg-gray-50 w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <h3 class="text-[#0A2E2A] font-extrabold text-lg">No events found</h3>
                                    <p class="text-sm text-gray-500 mt-1">Create your first event or banquet to get started</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Assign Driver Modal -->
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

                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showAssignModal = false"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showAssignModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <form method="POST" :action="'/events/' + assignEventId + '/assign-driver'">
                        @csrf
                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] border-b border-[#C9A050]/20 px-8 py-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white">Assign Driver</h3>
                                <p class="text-sm text-white/70 mt-0.5">Event <span class="font-bold text-[#C9A050]" x-text="'#' + assignEventId"></span></p>
                            </div>
                            <button type="button" @click="showAssignModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="p-8">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Select an available driver</p>
                            <div class="space-y-3 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
                                <label class="group relative flex items-center p-4 border-2 rounded-2xl cursor-pointer transition-all"
                                       :class="assignDriverId == null ? 'border-[#C9A050] bg-[#C9A050]/5' : 'border-gray-100 hover:border-[#C9A050]/30 hover:bg-[#C9A050]/5'">
                                    <input type="radio" name="driver_id" value="" :checked="assignDriverId == null" class="w-5 h-5 text-[#C9A050] border-gray-300 focus:ring-[#C9A050]">
                                    <div class="ml-4 flex items-center space-x-4">
                                        <div class="p-3 bg-gray-100 rounded-2xl">
                                            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-900">No driver / Remove assignment</p>
                                            <p class="text-[11px] text-gray-400">Clears current driver assignment</p>
                                        </div>
                                    </div>
                                </label>

                                @foreach ($drivers as $driver)
                                    @php $dStatus = $driver->status; @endphp
                                    <label class="group relative flex items-center p-4 border-2 rounded-2xl cursor-pointer transition-all"
                                           :class="assignDriverId == {{ $driver->id }} ? 'border-[#C9A050] bg-[#C9A050]/5' : 'border-gray-100 hover:border-[#C9A050]/30 hover:bg-[#C9A050]/5'"
                                           :disabled="('{{ $dStatus }}' !== 'available') && ({{ $driver->id }} != assignDriverId)">
                                        <input type="radio" name="driver_id" value="{{ $driver->id }}" :checked="assignDriverId == {{ $driver->id }}" class="w-5 h-5 text-[#C9A050] border-gray-300 focus:ring-[#C9A050]">
                                        <div class="ml-4 flex items-center space-x-4">
                                            <div class="relative">
                                                <img src="https://ui-avatars.com/api/?name={{ urlencode($driver->name) }}&background=0A2E2A&color=C9A050" class="w-12 h-12 rounded-2xl shadow-sm border border-gray-100" alt="">
                                                @if ($dStatus === 'available')
                                                    <span class="absolute -bottom-1 -right-1 flex h-4 w-4">
                                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                        <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 border-2 border-white"></span>
                                                    </span>
                                                @elseif ($dStatus === 'busy')
                                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-amber-500 border-2 border-white rounded-full"></div>
                                                @elseif ($dStatus === 'on_delivery')
                                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-blue-500 border-2 border-white rounded-full"></div>
                                                @else
                                                    <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-gray-400 border-2 border-white rounded-full"></div>
                                                @endif
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-gray-900 group-hover:text-[#0A2E2A] transition-colors">{{ $driver->name }}</p>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-[10px] font-extrabold uppercase tracking-wider
                                                        @if ($dStatus === 'available') text-emerald-600
                                                        @elseif ($dStatus === 'busy') text-amber-600
                                                        @elseif ($dStatus === 'on_delivery') text-blue-600
                                                        @else text-gray-400 @endif">{{ $dStatus === 'on_delivery' ? 'On Delivery' : ($dStatus === 'busy' ? 'Busy' : ($dStatus === 'available' ? 'Available' : 'Offline')) }}</span>
                                                    <span class="text-[10px] text-gray-300">•</span>
                                                    <span class="text-[11px] text-gray-400">{{ $driver->phone ?? 'No phone' }}</span>
                                                    @if ($driver->vehicle_type || $driver->vehicle_number)
                                                        <span class="text-[10px] text-gray-300">•</span>
                                                        <span class="text-[11px] text-gray-400">{{ $driver->vehicle_type }} {{ $driver->vehicle_number }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>

                            <div class="mt-8 flex space-x-4">
                                <button type="button" @click="showAssignModal = false" class="flex-1 px-6 py-3.5 rounded-2xl font-bold text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Cancel</button>
                                <button type="submit" class="flex-1 px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] text-sm">Confirm Assignment</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>

        <!-- Add/Edit Event Modal -->
        <template x-teleport="body">
            <div x-show="showModal"
                 class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-x-hidden overflow-y-auto"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 style="display: none;">

                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" @click="showModal = false"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <form method="POST" :action="formAction" class="p-0">
                        @csrf
                        <template x-if="isEditing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>
                        <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] border-b border-[#C9A050]/20 px-8 py-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-black text-white" x-text="isEditing ? 'Edit Event' : 'Create Event'"></h3>
                                <p class="text-xs text-white/70 mt-0.5">Fill in the event and catering banquet details</p>
                            </div>
                            <button type="button" @click="showModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </div>

                        <div class="p-8 space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Event Name</label>
                                <input type="text" name="name" x-model="eventData.name" required
                                    class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm"
                                    placeholder="e.g. Wedding Reception, Corporate Dinner">
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Start Date</label>
                                    <input type="date" name="start_date" x-model="eventData.start_date" required
                                        class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">End Date</label>
                                    <input type="date" name="end_date" x-model="eventData.end_date" required
                                        class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Total Cost (Rs.)</label>
                                <input type="number" name="total_cost" x-model="eventData.total_cost" required min="0" step="0.01"
                                    class="w-full px-5 py-3.5 rounded-2xl border border-gray-200 focus:border-[#C9A050] focus:ring-4 focus:ring-[#C9A050]/15 outline-none transition-all duration-200 text-sm">
                            </div>

                            <div class="flex space-x-4 pt-3">
                                <button type="button" @click="showModal = false" class="flex-1 px-6 py-3.5 rounded-2xl font-bold text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">Cancel</button>
                                <button type="submit" class="flex-1 px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] text-sm">Save Event</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <script type="module">
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Echo) {
                window.Echo.channel('drivers')
                    .listen('DriverStatusUpdated', (e) => {
                        showToast(`Driver ${e.name} is now ${e.status.replace('_', ' ')}`, e.status);
                    });

                window.Echo.channel('orders')
                    .listen('OrderStatusUpdated', (e) => {
                        const formattedId = '#ORD-' + String(e.id).padStart(4, '0');
                        showToast(`Order ${formattedId} is now ${e.status.replace(/_/g, ' ')}`, e.status);
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
    </script>
</x-app-layout>
