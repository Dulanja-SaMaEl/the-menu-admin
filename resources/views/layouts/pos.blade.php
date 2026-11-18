<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Theme Persistence Script -->
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('menu_theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
            } catch (e) {}
        })();
    </script>

    <title>{{ config('app.name', 'Menu') }} - POS Counter Terminal</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif

    <style>
        [x-cloak] { display: none !important; }
        
        /* Custom scrollbar for fast POS interaction */
        .pos-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .pos-scrollbar::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .pos-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(10, 46, 42, 0.25);
            border-radius: 9999px;
        }
        .dark .pos-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(201, 160, 80, 0.35);
        }

        .logo-blend {
            mix-blend-mode: screen !important;
            aspect-ratio: 2048 / 1374 !important;
        }
    </style>
</head>
<body class="h-full w-full bg-[#081C19] text-gray-100 font-sans antialiased overflow-hidden flex flex-col selection:bg-[#C9A050] selection:text-white"
      x-data="posShell()">

    <!-- TOP BAR: DEDICATED POS TERMINAL HEADER (NO ADMIN SIDEBAR) -->
    <header class="shrink-0 h-16 bg-[#051411] border-b border-[#143B33] px-4 sm:px-6 flex items-center justify-between z-30 select-none shadow-md">
        
        <!-- Left: Logo & Terminal Identifier -->
        <div class="flex items-center space-x-3.5">
            <div class="flex items-center space-x-2.5">
                <img src="{{ asset('images/logo.svg') }}" 
                     alt="Menu Logo" 
                     class="h-8 sm:h-9 w-auto object-contain logo-blend mix-blend-screen filter brightness-125" />
                <span class="text-sm sm:text-base font-black tracking-wider uppercase text-white">The Menu</span>
            </div>

            <div class="h-5 w-px bg-white/15"></div>

            <div class="flex items-center space-x-2 bg-[#0A2E2A] border border-[#C9A050]/40 rounded-xl px-2.5 py-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-[11px] sm:text-xs font-black uppercase tracking-wider text-[#C9A050]">POS Terminal</span>
            </div>
        </div>

        <!-- Center: Live Clock & Date -->
        <div class="hidden md:flex items-center space-x-2 bg-black/30 border border-white/10 rounded-2xl px-3.5 py-1.5 text-xs text-gray-300 font-mono">
            <svg class="w-3.5 h-3.5 text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-bold text-white" x-text="clockTime">--:--:-- --</span>
            <span class="text-gray-500">•</span>
            <span class="text-gray-300 font-medium" x-text="clockDate">--- --, ----</span>
        </div>

        <!-- Right: Actions & User Info -->
        <div class="flex items-center space-x-2 sm:space-x-3">
            
            <!-- Cashier Badge -->
            <div class="hidden sm:flex items-center space-x-2 bg-white/5 border border-white/10 rounded-xl px-3 py-1.5 text-xs">
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="text-gray-400 font-medium">Cashier:</span>
                <span class="font-bold text-white truncate max-w-[120px]">{{ auth()->user()->name ?? 'Counter' }}</span>
            </div>

            <!-- Fullscreen Toggle Button -->
            <button type="button"
                    @click="toggleFullscreen()"
                    title="Toggle Fullscreen"
                    class="p-2 sm:p-2.5 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 text-gray-300 hover:text-white transition-colors cursor-pointer"
                    aria-label="Toggle Fullscreen">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="!isFullscreen">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
                </svg>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="isFullscreen" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>

            <!-- Admin Panel Button (Only visible if user has admin privileges) -->
            @if(auth()->user() && auth()->user()->role !== 'POS')
                <a href="{{ route('dashboard') }}" 
                   title="Switch to Admin Management Console"
                   class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-[#C9A050]/15 hover:bg-[#C9A050]/25 border border-[#C9A050]/40 text-[#C9A050] text-xs font-bold transition-all">
                    <span>Admin Panel</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>
            @endif

            <!-- Logout Button -->
            <form action="{{ route('logout') }}" method="POST" class="inline" @submit="return confirmLogout($event)">
                @csrf
                <button type="submit" 
                        title="End Shift & Sign Out"
                        class="flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-red-950/60 hover:bg-red-900 border border-red-500/40 text-red-200 text-xs font-bold transition-colors cursor-pointer">
                    <svg class="w-3.5 h-3.5 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    <span>Sign Out</span>
                </button>
            </form>

        </div>
    </header>

    <!-- MAIN TERMINAL VIEWPORT -->
    <main class="flex-1 min-h-0 overflow-hidden relative">
        {{ $slot }}
    </main>

    <script>
        function posShell() {
            return {
                clockTime: '',
                clockDate: '',
                isFullscreen: false,

                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);

                    document.addEventListener('fullscreenchange', () => {
                        this.isFullscreen = !!document.fullscreenElement;
                    });
                },

                updateClock() {
                    const now = new Date();
                    this.clockTime = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                    this.clockDate = now.toLocaleDateString([], { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    } else if (document.exitFullscreen) {
                        document.exitFullscreen().catch(() => {});
                    }
                },

                confirmLogout(e) {
                    if (!confirm('Are you sure you want to end your POS shift and sign out?')) {
                        e.preventDefault();
                        return false;
                    }
                    return true;
                }
            };
        }
    </script>
</body>
</html>
