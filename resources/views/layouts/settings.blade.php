<x-app-layout :title="$title">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <nav class="flex items-center text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                <a href="#" class="hover:text-[#C9A050] transition-colors">Settings</a>
                <span class="mx-2 text-gray-300">/</span>
                <span class="text-[#0A2E2A] font-black">{{ $title }}</span>
            </nav>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#0A2E2A] tracking-tight">System Settings</h1>
        </div>
        
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 bg-white/95 backdrop-blur-md rounded-2xl shadow-md shadow-[#0A2E2A]/5 border border-gray-200/70 flex items-center justify-center text-[#0A2E2A]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-8">
        <!-- Settings Sidebar -->
        <div class="lg:w-1/3 bg-white/95 backdrop-blur-md rounded-[2.5rem] p-6 sm:p-8 flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70">
            <nav class="space-y-2">
                @php
                    $menus = [
                        ['id' => 'profile', 'label' => 'Profile', 'icon' => '👤', 'route' => '#'],
                        ['id' => 'notifications', 'label' => 'Notifications', 'icon' => '🔔', 'route' => '#'],
                        ['id' => 'appearance', 'label' => 'Appearance', 'icon' => '🎨', 'route' => '#'],
                        ['id' => 'checkout', 'label' => 'Checkout Settings', 'icon' => '⚙️', 'route' => route('settings.checkout')],
                        ['id' => 'security', 'label' => 'Security', 'icon' => '🛡️', 'route' => route('settings.security')],
                        ['id' => 'language', 'label' => 'Language & Region', 'icon' => '🌍', 'route' => '#'],
                    ];
                @endphp

                @foreach($menus as $menu)
                    <a href="{{ $menu['route'] }}" class="flex items-center space-x-4 p-4 rounded-2xl transition-all duration-300 {{ $active == $menu['id'] ? 'bg-gradient-to-r from-[#0A2E2A] via-[#0D3833] to-[#0A2E2A] text-white font-bold shadow-lg shadow-[#0A2E2A]/20 border border-[#C9A050]/20' : 'text-gray-600 hover:text-[#0A2E2A] hover:bg-[#C9A050]/5 font-semibold' }}">
                        <span class="text-xl">{{ $menu['icon'] }}</span>
                        <span class="text-sm tracking-wide">{{ $menu['label'] }}</span>
                    </a>
                @endforeach
            </nav>
        </div>

        <!-- Settings Content -->
        <div class="lg:w-2/3 bg-white/95 backdrop-blur-md rounded-[2.5rem] flex flex-col shadow-xl shadow-[#0A2E2A]/5 border border-gray-200/70 overflow-hidden">
            <div class="flex-grow overflow-y-auto p-8 sm:p-12 custom-scrollbar">
                {{ $slot }}
            </div>
            
            <div class="p-6 sm:p-8 border-t border-gray-100 bg-gray-50/50 flex justify-end">
                <button class="px-8 py-3.5 bg-gradient-to-r from-[#C9A050] to-[#b88f3f] hover:from-[#d8b05f] hover:to-[#C9A050] text-[#0A2E2A] font-black rounded-2xl shadow-lg shadow-[#C9A050]/25 transition-all duration-300 hover:scale-[1.02] text-sm">
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #E5E7EB;
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #C9A050;
        }
    </style>
</x-app-layout>