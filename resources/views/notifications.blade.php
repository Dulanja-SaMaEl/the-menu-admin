<x-app-layout>
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-[#0A2E2A]/5 border border-[#0A2E2A]/10 mb-2">
                <span class="w-2 h-2 rounded-full bg-[#C9A050]"></span>
                <span class="text-[11px] font-bold uppercase tracking-wider text-[#0A2E2A]">Activity Center</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] tracking-tight">System Notifications</h1>
            <p class="text-xs sm:text-sm text-gray-500 mt-0.5">Stay up to date with new orders, dispatch updates, and team alerts</p>
        </div>

        <div class="flex items-center space-x-4">
            <!-- Unread summary badge -->
            <div class="flex items-center space-x-2.5 bg-white/95 backdrop-blur-md px-5 py-2.5 rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70">
                <span class="relative flex h-2.5 w-2.5">
                    @if($unreadCount > 0)
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#C9A050] opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#C9A050]"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-gray-300"></span>
                    @endif
                </span>
                <span class="text-xs font-black text-[#0A2E2A]">
                    <span>{{ $unreadCount }}</span> Unread Alerts
                </span>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto space-y-6" x-data="{
        markAllRead() {
            fetch('{{ route('notifications.markAllRead') }}', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => window.location.reload());
        },
        markRead(id) {
            fetch('/notifications/' + id + '/read', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            }).then(() => window.location.reload());
        },
        iconFor(type) {
            return {
                order: 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
                event: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                driver: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                message: 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
                system: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
            }[type] || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        }
    }">
        <!-- Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <!-- Type Filter -->
            <div class="flex bg-white/95 backdrop-blur-md p-1.5 rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-x-auto no-scrollbar">
                <a href="{{ request()->fullUrlWithQuery(['category' => 'all', 'page' => 1]) }}" class="{{ $category === 'all' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">All</a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'order', 'page' => 1]) }}" class="{{ $category === 'order' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">Orders</a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'event', 'page' => 1]) }}" class="{{ $category === 'event' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">Events</a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'driver', 'page' => 1]) }}" class="{{ $category === 'driver' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">Drivers</a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'message', 'page' => 1]) }}" class="{{ $category === 'message' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">Messages</a>
                <a href="{{ request()->fullUrlWithQuery(['category' => 'system', 'page' => 1]) }}" class="{{ $category === 'system' ? 'bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] font-black shadow-md shadow-[#C9A050]/20' : 'text-gray-500 hover:text-gray-900 font-semibold hover:bg-gray-50' }} px-4 py-2 text-xs rounded-xl transition-all whitespace-nowrap">System</a>
            </div>

            <!-- Unread / All toggle + Mark all read -->
            <div class="flex items-center space-x-3">
                <div class="flex bg-white/95 backdrop-blur-md p-1 rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70">
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'all', 'page' => 1]) }}" class="{{ $status === 'all' ? 'bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] text-white font-bold shadow-sm' : 'text-gray-500 hover:bg-gray-50 font-semibold' }} px-4 py-1.5 text-xs rounded-xl transition-all">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'unread', 'page' => 1]) }}" class="{{ $status === 'unread' ? 'bg-gradient-to-r from-[#0A2E2A] to-[#0D3833] text-white font-bold shadow-sm' : 'text-gray-500 hover:bg-gray-50 font-semibold' }} px-4 py-1.5 text-xs rounded-xl transition-all">Unread</a>
                </div>
                <button @click="markAllRead()" @if($unreadCount === 0) disabled @endif class="text-xs font-black text-[#C9A050] hover:underline disabled:text-gray-300 disabled:no-underline transition-colors">
                    Mark all as read
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-3">
            @forelse($notifications as $notification)
                <div
                    class="bg-white/95 backdrop-blur-md p-6 rounded-[2.5rem] flex items-start space-x-5 transition-all duration-300 shadow-xl shadow-[#0A2E2A]/5 border group relative overflow-hidden {{ !$notification->is_read ? 'border-[#C9A050]/40 bg-[#C9A050]/[0.02]' : 'border-gray-200/70' }}"
                >
                    <!-- Unread Indicator -->
                    @if(!$notification->is_read)
                        <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-[#C9A050] to-[#b88f3f]"></div>
                    @endif

                    <!-- Type Icon -->
                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 shadow-sm {{ match($notification->type) { 'order' => 'bg-blue-50 text-blue-600 border border-blue-200/60', 'event' => 'bg-amber-50 text-amber-600 border border-amber-200/60', 'driver' => 'bg-emerald-50 text-emerald-600 border border-emerald-200/60', 'message' => 'bg-purple-50 text-purple-600 border border-purple-200/60', default => 'bg-gray-100 text-gray-600 border border-gray-200' } }}">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="iconFor('{{ $notification->type }}')"></path>
                        </svg>
                    </div>

                    <div class="flex-1">
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center space-x-2">
                                <h4 class="text-base font-extrabold text-[#0A2E2A]">{{ $notification->title }}</h4>
                                <!-- Unread dot badge -->
                                @if(!$notification->is_read)
                                    <span class="w-2.5 h-2.5 rounded-full bg-[#C9A050] shadow-sm animate-pulse" title="Unread"></span>
                                @endif
                            </div>
                            <span class="text-xs text-gray-400 font-semibold">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-600 leading-relaxed">{{ $notification->message }}</p>

                        <!-- Actions -->
                        <div class="mt-4 flex space-x-2 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
                            @if(!$notification->is_read)
                                <button @click="markRead({{ $notification->id }})" class="px-3.5 py-1.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] text-[#0A2E2A] text-[10px] font-black rounded-xl uppercase tracking-wider hover:scale-105 transition-all shadow-sm">Mark read</button>
                            @endif
                            <a href="{{ match($notification->type) { 'order' => route('orders'), 'event' => route('events'), 'driver' => route('drivers'), 'message' => route('messages'), default => route('dashboard') } }}"
                               @if(!$notification->is_read)
                                   @click="fetch('/notifications/{{ $notification->id }}/read', { method: 'POST', keepalive: true, headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })"
                               @endif
                               class="inline-block px-3.5 py-1.5 bg-gray-100 hover:bg-[#0A2E2A] text-gray-700 hover:text-white text-[10px] font-black rounded-xl transition-all uppercase tracking-wider">View details</a>
                            
                            @if(!$notification->is_read)
                                <button @click="markRead({{ $notification->id }})" type="button" class="px-3.5 py-1.5 bg-gray-100 hover:bg-red-50 text-gray-500 hover:text-red-500 text-[10px] font-black rounded-xl transition-all uppercase tracking-wider">Dismiss</button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <!-- Empty State -->
                <div class="bg-white/95 backdrop-blur-md py-20 text-center rounded-[2.5rem] border border-dashed border-[#C9A050]/30 shadow-xl shadow-[#0A2E2A]/5">
                    <div class="bg-gray-50 w-16 h-16 rounded-3xl flex items-center justify-center mx-auto mb-4 border border-gray-100">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </div>
                    <h3 class="text-[#0A2E2A] font-extrabold text-lg">No notifications found</h3>
                    <p class="text-sm text-gray-500 mt-1">Try changing your filter settings</p>
                </div>
            @endforelse

            <div class="pt-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
