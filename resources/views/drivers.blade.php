<x-app-layout>
    @php
        $statusStyles = [
            'available'   => ['label' => 'Available',   'class' => 'bg-green-50 text-green-600 border-green-100', 'dot' => 'bg-green-500'],
            'busy'        => ['label' => 'Busy',        'class' => 'bg-amber-50 text-amber-600 border-amber-100', 'dot' => 'bg-amber-500'],
            'on_delivery' => ['label' => 'On Delivery', 'class' => 'bg-blue-50 text-blue-600 border-blue-100',   'dot' => 'bg-blue-500'],
            'offline'     => ['label' => 'Offline',     'class' => 'bg-gray-100 text-gray-500 border-gray-200',   'dot' => 'bg-gray-400'],
        ];
    @endphp

    <div class="space-y-6" x-data="{ 
        showModal: false,
        isEditing: false,
        driverData: {},
        formAction: '{{ route('drivers.store') }}',
        formMethod: 'POST',
        openCreateModal() {
            this.isEditing = false;
            this.driverData = { name: '', email: '', phone: '', vehicle_type: '', vehicle_number: '', status: 'available', max_orders: 5 };
            this.formAction = '{{ route('drivers.store') }}';
            this.formMethod = 'POST';
            this.showModal = true;
        },
        openEditModal(driver) {
            this.isEditing = true;
            this.driverData = { ...driver, max_orders: driver.max_orders ?? 5 };
            this.formAction = '/drivers/' + driver.id;
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

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <nav class="flex items-center text-xs font-bold text-gray-500 mb-2 uppercase tracking-wider">
                    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A050] transition-colors">Dashboard</a>
                    <svg class="w-3.5 h-3.5 mx-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <span class="text-[#0A2E2A] font-black">Fleet Operations</span>
                </nav>
                <h1 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight">Drivers Management</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 font-medium">Manage your delivery fleet, vehicle dispatches, and real-time route assignments</p>
            </div>
            <button @click="openCreateModal()" class="px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] rounded-2xl text-xs sm:text-sm font-black flex items-center space-x-2.5 shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] shrink-0 self-start sm:self-auto cursor-pointer">
                <svg class="w-4 h-4 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                <span>Add New Driver</span>
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-[#0A2E2A]/10 text-[#0A2E2A] rounded-2xl flex items-center justify-center shadow-xs">
                        <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-[#0A2E2A] bg-[#0A2E2A]/10 px-2.5 py-1 rounded-full border border-[#0A2E2A]/15">Fleet</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight" id="total-drivers-count">{{ $stats['total'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Total Registered Drivers</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-xs border border-emerald-100">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">Active</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight" id="available-drivers-count">{{ $stats['available'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Available for Dispatch</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-xs border border-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">En Route</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight" id="on_delivery-drivers-count">{{ $stats['on_delivery'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Currently on Delivery</p>
            </div>
            <div class="bg-white/95 backdrop-blur-md p-6 sm:p-7 rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 hover:border-[#C9A050]/40 transition-all duration-300">
                <div class="flex items-center justify-between mb-4">
                    <div class="w-12 h-12 bg-gray-100 text-gray-500 rounded-2xl flex items-center justify-center shadow-xs border border-gray-200">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-wider text-gray-500 bg-gray-100 px-2.5 py-1 rounded-full border border-gray-200">Off Duty</span>
                </div>
                <h3 class="text-3xl sm:text-4xl font-black text-gray-900 tracking-tight" id="offline-drivers-count">{{ $stats['offline'] }}</h3>
                <p class="text-xs text-gray-500 font-medium mt-1">Offline Personnel</p>
            </div>
        </div>

        <!-- Drivers Table -->
        <div class="bg-white/95 backdrop-blur-md rounded-[2.5rem] shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <div class="p-6 sm:p-8 border-b border-gray-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white/40">
                <div>
                    <h2 class="text-xl font-black text-gray-900 tracking-tight">Delivery Fleet Roster</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Live personnel statuses, contact numbers, and capacity metrics</p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full sm:w-auto">
                    <!-- Search Input -->
                    <form method="GET" action="{{ url()->current() }}" class="relative w-full sm:w-72">
                        <input type="text"
                               name="search"
                               value="{{ $search ?? '' }}"
                               placeholder="Search driver, phone, vehicle..."
                               @input.debounce.1500ms="$el.form.submit()"
                               class="w-full pl-11 pr-4 py-3 text-xs sm:text-sm font-bold border border-gray-200 rounded-2xl focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] bg-gray-50/50 focus:bg-white text-gray-900 transition-all duration-200 shadow-inner">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </form>
                    <span class="text-xs font-bold text-[#0A2E2A] bg-[#C9A050]/15 border border-[#C9A050]/30 px-3 py-2.5 rounded-xl text-center shrink-0">
                        {{ count($drivers) }} Couriers
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1000px]">
                    <thead>
                        <tr class="bg-gray-50/80 border-b border-gray-100">
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[220px]">Driver</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Contact</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Status</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[160px]">Vehicle</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[120px]">Deliveries</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest min-w-[150px]">Max Capacity</th>
                            <th class="py-6 px-6 align-middle text-[11px] font-black text-gray-400 uppercase tracking-widest text-right min-w-[120px]">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($drivers as $driver)
                            @php $s = $statusStyles[$driver->status] ?? $statusStyles['offline']; @endphp
                            <tr id="driver-row-{{ $driver->id }}" class="border-b border-gray-100/80 hover:bg-[#C9A050]/[0.03] transition-all duration-300 group">
                                <td class="py-7 px-6 align-middle">
                                    <div class="flex items-center space-x-3.5">
                                        <img src="https://ui-avatars.com/api/?name={{ urlencode($driver->name) }}&background=0A2E2A&color=C9A050" class="w-11 h-11 rounded-2xl shadow-sm ring-1 ring-[#C9A050]/20 shrink-0" alt="">
                                        <div class="flex flex-col gap-1 min-w-0">
                                            <p class="text-sm font-bold text-gray-900 truncate">{{ $driver->name }}</p>
                                            <p class="text-xs text-gray-400 truncate">{{ $driver->email ?? '—' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-7 px-6 align-middle whitespace-nowrap">
                                    <p class="text-sm font-semibold text-gray-700">{{ $driver->phone ?? '—' }}</p>
                                </td>
                                <td class="py-7 px-6 align-middle whitespace-nowrap">
                                    <div id="driver-status-badge-{{ $driver->id }}">
                                        @if ($driver->status === 'on_delivery')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                                On Delivery
                                                @if($driver->active_orders_count > 0)
                                                    <span class="ml-1 px-1.5 py-0.5 bg-blue-100 text-blue-800 rounded-md text-[10px] font-black">
                                                        {{ $driver->active_orders_count }} active
                                                    </span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                Available
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="py-7 px-6 align-middle whitespace-nowrap">
                                    @if ($driver->vehicle_type || $driver->vehicle_number)
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-50 text-gray-800 border border-gray-200/80 driver-vehicle-pill shadow-2xs">
                                            <span class="font-bold text-gray-900">{{ $driver->vehicle_type }}</span>
                                            <span class="text-gray-400 font-normal ml-1">· {{ $driver->vehicle_number }}</span>
                                        </span>
                                    @else
                                        <p class="text-sm text-gray-400">—</p>
                                    @endif
                                </td>
                                <td class="py-7 px-6 align-middle whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-gray-50 border border-gray-200/80 shadow-2xs">
                                        <span class="text-sm font-black text-gray-900">{{ $driver->completed_orders_count ?? 0 }}</span>
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">completed</span>
                                    </div>
                                </td>
                                <td class="py-7 px-6 align-middle whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold bg-[#C9A050]/10 text-[#0A2E2A] border border-[#C9A050]/30 whitespace-nowrap shadow-2xs">
                                        📦 {{ $driver->max_orders ?? 5 }} Orders Max
                                    </span>
                                </td>
                                <td class="py-7 px-6 text-right align-middle whitespace-nowrap">
                                    <div class="flex items-center justify-end space-x-2">
                                        <!-- Edit -->
                                        <button @click="openEditModal({{ $driver->toJson() }})" class="p-2.5 text-gray-400 hover:text-[#0A2E2A] hover:bg-[#C9A050]/20 hover:-translate-y-0.5 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer" title="Edit driver">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('drivers.destroy', $driver) }}" onsubmit="return confirm('Remove this driver?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2.5 text-gray-400 hover:text-red-600 hover:bg-red-50 hover:-translate-y-0.5 transition-all bg-gray-50 border border-gray-200/80 rounded-xl cursor-pointer" title="Remove driver">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-20 px-6 text-center align-middle">
                                    <div class="bg-gray-50 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 border border-gray-100">
                                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <h3 class="text-gray-900 font-bold">No drivers found</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        @if(!empty($search))
                                            No couriers match your search "{{ $search }}". Try a different name, phone, or vehicle number.
                                        @else
                                            Add your first driver to get started
                                        @endif
                                    </p>
                                    @if(!empty($search))
                                        <a href="{{ route('drivers') }}" class="inline-flex items-center gap-1.5 mt-4 px-4 py-2 bg-gray-100 hover:bg-[#C9A050]/20 text-[#0A2E2A] text-xs font-bold rounded-xl transition-all">
                                            Clear Search
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Add New Driver Modal -->
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

                <div class="fixed inset-0 bg-gray-950/70 backdrop-blur-md" @click="showModal = false"></div>

                <div class="relative bg-white rounded-[2.5rem] shadow-2xl shadow-[#0A2E2A]/25 max-w-lg w-full overflow-hidden transform transition-all border border-gray-100"
                     x-show="showModal"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 translate-y-4">

                    <div class="bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] px-8 py-6 flex items-center justify-between border-b border-[#C9A050]/20">
                        <div class="flex items-center space-x-3.5">
                            <div class="w-12 h-12 rounded-2xl bg-[#C9A050] border border-[#b88f3f] flex items-center justify-center text-[#0A2E2A] shadow-md shrink-0">
                                <svg class="w-6 h-6 text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black text-white tracking-tight" x-text="isEditing ? 'Edit Driver Profile' : 'Add New Courier'"></h3>
                                <p class="text-xs text-emerald-100 font-medium">Configure courier vehicle, capacity, and credentials</p>
                            </div>
                        </div>
                        <button type="button" @click="showModal = false" class="p-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl transition-colors cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    <form method="POST" :action="formAction" class="p-8 space-y-5">
                        @csrf
                        <template x-if="isEditing">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div>
                            <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Full Name</label>
                            <input type="text" name="name" x-model="driverData.name" required
                                class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all @error('name') border-red-300 ring-1 ring-red-200 @enderror"
                                placeholder="e.g. Saman Kumara">
                            @error('name')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Email Address</label>
                                <input type="email" name="email" x-model="driverData.email"
                                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all @error('email') border-red-300 ring-1 ring-red-200 @enderror"
                                    placeholder="driver@example.com">
                                @error('email')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Phone Number</label>
                                <input type="text" name="phone" x-model="driverData.phone"
                                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all"
                                    placeholder="+94 76 123 4567">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Vehicle Type</label>
                                <input type="text" name="vehicle_type" x-model="driverData.vehicle_type"
                                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all"
                                    placeholder="Motorbike / Tuk / Van">
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Vehicle Number Plate</label>
                                <input type="text" name="vehicle_number" x-model="driverData.vehicle_number"
                                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all"
                                    placeholder="e.g. BE-4521">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Max Capacity</label>
                                <input type="number" name="max_orders" x-model="driverData.max_orders" min="1" max="100" required
                                    class="w-full px-5 py-3.5 bg-gray-50 border border-gray-200 rounded-2xl text-sm font-semibold text-gray-900 focus:ring-2 focus:ring-[#C9A050] focus:border-[#C9A050] transition-all @error('max_orders') border-red-300 ring-1 ring-red-200 @enderror" placeholder="5">
                                <p class="text-[10px] text-gray-400 mt-1 font-medium">Max orders per run.</p>
                                @error('max_orders')<p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-black uppercase tracking-wider text-gray-700 mb-2">Dispatch Status</label>
                                <div class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-2xl flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-600">Calculated</span>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold"
                                          :class="driverData.active_orders_count > 0 ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-emerald-50 text-emerald-700 border border-emerald-200'">
                                        <span class="w-1.5 h-1.5 rounded-full" :class="driverData.active_orders_count > 0 ? 'bg-blue-500 animate-pulse' : 'bg-emerald-500'"></span>
                                        <span x-text="driverData.active_orders_count > 0 ? 'On Delivery' : 'Available'"></span>
                                    </span>
                                </div>
                                <p class="text-[10px] text-gray-400 mt-1 font-medium">Updates automatically.</p>
                            </div>
                        </div>


                        <div class="flex space-x-3 pt-3">
                            <button type="button" @click="showModal = false" class="flex-1 px-6 py-3.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl text-xs sm:text-sm transition-colors cursor-pointer">Cancel</button>
                            <button type="submit" class="flex-1 px-6 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl text-xs sm:text-sm shadow-lg shadow-[#C9A050]/25 hover:shadow-xl transition-all duration-300 cursor-pointer">Save Courier</button>
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
                        // Update stats
                        const stats = e.stats;
                        document.getElementById('total-drivers-count').innerText = stats.total;
                        document.getElementById('available-drivers-count').innerText = stats.available;
                        document.getElementById('on_delivery-drivers-count').innerText = stats.on_delivery;
                        document.getElementById('offline-drivers-count').innerText = stats.offline;

                        // Update driver status badge if it exists
                        const badgeContainer = document.getElementById(`driver-status-badge-${e.id}`);
                        if (badgeContainer) {
                            const isDelivery = e.status === 'on_delivery';
                            badgeContainer.innerHTML = isDelivery
                                ? `<span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full bg-blue-50 text-blue-700 border border-blue-200 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                    On Delivery
                                   </span>`
                                : `<span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-bold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Available
                                   </span>`;
                        }

                        // Show Toast Notification
                        showToast(`${e.name} is now ${e.status.replace('_', ' ')}`, e.status);
                    });
            }
        });

        function showToast(message, status) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            
            const toast = document.createElement('div');
            
            // Set styles based on status
            let bgClass = 'bg-white border-l-4 border-gray-400';
            if (status === 'available') bgClass = 'bg-green-50 border-l-4 border-green-500 text-green-700';
            else if (status === 'on_delivery') bgClass = 'bg-blue-50 border-l-4 border-blue-500 text-blue-700';
            else if (status === 'offline') bgClass = 'bg-gray-100 border-l-4 border-gray-500 text-gray-700';

            toast.className = `px-4 py-3 rounded shadow-lg transform transition-all duration-300 translate-y-4 opacity-0 flex items-center justify-between min-w-[250px] ${bgClass}`;
            toast.innerHTML = `
                <span class="font-medium text-sm">${message}</span>
                <button onclick="this.parentElement.remove()" class="ml-4 opacity-50 hover:opacity-100 text-lg leading-none">&times;</button>
            `;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-y-4', 'opacity-0');
            }, 10);
            
            // Remove after 5 seconds
            setTimeout(() => {
                toast.classList.add('translate-y-4', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    </script>
    </div>
</x-app-layout>
