<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">

    <!-- Instant Anti-FOUC Theme Persistence Script -->
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

    <title>{{ config('app.name', 'Menu') }} - {{ $title ?? 'Dashboard' }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700&display=swap" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @endif
    <style>
        [x-cloak] { display: none !important; }

        /* Smooth UI Transition */
        html.theme-transition,
        html.theme-transition *,
        html.theme-transition *::before,
        html.theme-transition *::after {
            transition: background-color 0.25s ease, border-color 0.25s ease, color 0.25s ease, box-shadow 0.25s ease !important;
        }

        #sidebar-container {
            background-color: #0A2E2A !important;
        }
        .sidebar-nav-item {
            color: #ffffff;
        }
        .sidebar-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }
        .sidebar-nav-item.active {
            background-color: #C9A050 !important;
            color: #0A2E2A !important;
            font-weight: 700 !important;
        }
        .sidebar-nav-item.active svg {
            color: #0A2E2A !important;
            stroke: #0A2E2A !important;
        }
        .sidebar-nav-item.active span {
            color: #0A2E2A !important;
            font-weight: 700 !important;
        }

        /* ==========================================================================
           BESPOKE ULTRA-LUXURY DARK THEME ENGINE
           Signature Midnight Emerald & Imperial Gold Palette
           Canvas: #061513 | Header: #081C19 | Cards: #0A211E | Borders: #163832
           ========================================================================== */
        html.dark {
            color-scheme: dark;
            background-color: #061513;
        }

        /* Main Canvas / Body & Workspace */
        html.dark body {
            background-color: #061513 !important;
            color: #F5F3EF !important;
        }
        html.dark main {
            background-color: #061513 !important;
        }

        /* Top Header Bar */
        html.dark header {
            background-color: #081C19 !important;
            border-color: #15352F !important;
            box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.6) !important;
        }
        html.dark header button.bg-gray-50 {
            background-color: #0D2824 !important;
            color: #F5F3EF !important;
            border-color: #163832 !important;
        }
        html.dark header button.bg-gray-50:hover {
            background-color: #123831 !important;
            color: #D8B060 !important;
        }

        /* Sidebar Container */
        html.dark #sidebar-container,
        html.dark aside#sidebar-container {
            background-color: #05110F !important;
            border-right-color: #15352F !important;
            box-shadow: 4px 0 24px rgba(0, 0, 0, 0.7) !important;
        }
        html.dark #sidebar-container > div:first-child {
            background-color: #05110F !important;
            border-bottom-color: #15352F !important;
        }
        html.dark #sidebar-container .mt-auto {
            background-color: #030B0A !important;
            border-top-color: #15352F !important;
        }
        html.dark #sidebar-container .mt-auto .bg-\[\#0A2E2A\],
        html.dark #sidebar-container .mt-auto [style*="background-color: #0A2E2A"] {
            background-color: #081C19 !important;
            border-color: #163832 !important;
        }
        html.dark .sidebar-nav-item {
            color: #8FA8A3 !important;
        }
        html.dark .sidebar-nav-item:hover {
            background-color: rgba(255, 255, 255, 0.06) !important;
            color: #F5F3EF !important;
        }
        html.dark .sidebar-nav-item.active {
            background-color: #C9A050 !important;
            color: #05110F !important;
            box-shadow: 0 0 18px rgba(201, 160, 80, 0.4) !important;
        }
        html.dark .sidebar-nav-item.active svg {
            color: #05110F !important;
            stroke: #05110F !important;
        }
        html.dark .sidebar-nav-item.active span {
            color: #05110F !important;
        }

        /* Primary Surface Cards, KPI Boxes, Containers & Modals */
        html.dark .bg-white,
        html.dark .bg-white\/95,
        html.dark .bg-white\/90,
        html.dark .bg-white\/85,
        html.dark .bg-white\/80 {
            background-color: #0A211E !important;
            border-color: #163832 !important;
        }
        html.dark .border-gray-100,
        html.dark .border-gray-200,
        html.dark .border-gray-200\/60,
        html.dark .border-gray-200\/70,
        html.dark .border-gray-200\/80,
        html.dark .border-gray-300 {
            border-color: #163832 !important;
        }
        html.dark .hover\:border-\[\#C9A050\]\/40:hover,
        html.dark .hover\:border-\[\#C9A050\]:hover,
        html.dark .hover\:border-\[\#C9A050\]\/20:hover {
            border-color: rgba(201, 160, 80, 0.35) !important;
        }
        html.dark .shadow-xl,
        html.dark .shadow-2xl,
        html.dark .shadow-lg,
        html.dark .shadow-md {
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5) !important;
        }

        /* Secondary Surfaces, Table Headers & Inner Panels */
        html.dark .bg-gray-50,
        html.dark .bg-gray-50\/40,
        html.dark .bg-gray-50\/50,
        html.dark .bg-gray-50\/60,
        html.dark .bg-gray-50\/70,
        html.dark .bg-gray-50\/80,
        html.dark .bg-gray-100,
        html.dark .bg-slate-50,
        html.dark .bg-slate-100,
        html.dark thead,
        html.dark th {
            background-color: #081A17 !important;
            border-color: #13302B !important;
        }
        html.dark th {
            color: #8FA8A3 !important;
        }
        html.dark .divide-gray-50 > * + *,
        html.dark .divide-gray-100 > * + *,
        html.dark .divide-gray-200 > * + *,
        html.dark .divide-gray-200\/60 > * + *,
        html.dark .divide-y > * + *,
        html.dark .divide-x > * + * {
            border-color: #13302B !important;
        }

        /* Table Rows & Subtle Gold Shimmer Hover */
        html.dark tbody tr {
            border-color: #13302B !important;
        }
        html.dark tbody tr:hover,
        html.dark .hover\:bg-gray-50:hover,
        html.dark .hover\:bg-gray-50\/40:hover,
        html.dark .hover\:bg-gray-50\/60:hover,
        html.dark .hover\:bg-gray-50\/80:hover,
        html.dark .hover\:bg-\[\#C9A050\]\/5:hover,
        html.dark .hover\:bg-amber-50\/20:hover {
            background-color: rgba(201, 160, 80, 0.05) !important;
        }

        /* Typography System */
        html.dark h1,
        html.dark h2,
        html.dark h3,
        html.dark h4,
        html.dark h5,
        html.dark h6,
        html.dark .text-gray-900,
        html.dark .text-gray-800,
        html.dark .text-slate-900,
        html.dark .text-slate-800,
        html.dark nav span.text-\[\#0A2E2A\],
        html.dark h1.text-\[\#0A2E2A\],
        html.dark h2.text-\[\#0A2E2A\],
        html.dark strong.text-\[\#0A2E2A\],
        html.dark span.text-\[\#0A2E2A\],
        html.dark p.text-\[\#0A2E2A\],
        html.dark div.text-\[\#0A2E2A\] {
            color: #F5F3EF !important;
        }
        html.dark .text-gray-700,
        html.dark .text-gray-600,
        html.dark .text-gray-500,
        html.dark .text-slate-700,
        html.dark .text-slate-600,
        html.dark .text-slate-500 {
            color: #8FA8A3 !important;
        }
        html.dark .text-gray-400,
        html.dark .text-slate-400 {
            color: #5E7A74 !important;
        }
        html.dark .text-\[\#C9A050\],
        html.dark .text-amber-600 {
            color: #D8B060 !important;
        }

        /* ==========================================================================
           1. TOP ACTION & PRIMARY CTA BUTTONS (High-Contrast System)
           ========================================================================== */
        /* Gold CTA Buttons (Export Ledger, View Transactions, Add Dish, Add Driver, Create Event) */
        html.dark a[class*="from-\[\#C9A050\]"],
        html.dark button[class*="from-\[\#C9A050\]"],
        html.dark a.bg-\[\#C9A050\],
        html.dark button.bg-\[\#C9A050\] {
            background: linear-gradient(to right, #D8B060, #C9A050) !important;
            color: #061513 !important;
            font-weight: 800 !important;
            border: 1px solid rgba(216, 176, 96, 0.7) !important;
            box-shadow: 0 4px 18px rgba(201, 160, 80, 0.35) !important;
        }
        html.dark a[class*="from-\[\#C9A050\]"] *,
        html.dark button[class*="from-\[\#C9A050\]"] *,
        html.dark a.bg-\[\#C9A050\] *,
        html.dark button.bg-\[\#C9A050\] * {
            color: #061513 !important;
            font-weight: 800 !important;
        }
        html.dark a[class*="from-\[\#C9A050\]"] svg,
        html.dark button[class*="from-\[\#C9A050\]"] svg,
        html.dark a.bg-\[\#C9A050\] svg,
        html.dark button.bg-\[\#C9A050\] svg {
            color: #061513 !important;
            stroke: #061513 !important;
        }
        html.dark a[class*="from-\[\#C9A050\]"]:hover,
        html.dark button[class*="from-\[\#C9A050\]"]:hover,
        html.dark a.bg-\[\#C9A050\]:hover,
        html.dark button.bg-\[\#C9A050\]:hover {
            background: linear-gradient(to right, #E2BC6B, #D8B060) !important;
            box-shadow: 0 6px 22px rgba(201, 160, 80, 0.5) !important;
            color: #061513 !important;
        }

        /* Secondary White Action Buttons (View Orders, Export Report) */
        html.dark a.bg-white[class*="border"],
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]) {
            background-color: #0E2925 !important;
            border: 1px solid #C9A050 !important;
            color: #D8B060 !important;
            font-weight: 700 !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.35) !important;
        }
        html.dark a.bg-white[class*="border"] *,
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]) * {
            color: #D8B060 !important;
        }
        html.dark a.bg-white[class*="border"] svg,
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]) svg {
            color: #D8B060 !important;
            stroke: #D8B060 !important;
        }
        html.dark a.bg-white[class*="border"]:hover,
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]):hover {
            background-color: #16463D !important;
            border-color: #E2BC6B !important;
            color: #F5F3EF !important;
        }
        html.dark a.bg-white[class*="border"]:hover *,
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]):hover * {
            color: #F5F3EF !important;
        }
        html.dark a.bg-white[class*="border"]:hover svg,
        html.dark button.bg-white[class*="border"]:not([class*="theme-toggle-btn"]):hover svg {
            color: #F5F3EF !important;
            stroke: #F5F3EF !important;
        }

        /* Dark Green Buttons (Add Beverage, Add Category) */
        html.dark a.bg-\[\#0A2E2A\],
        html.dark button.bg-\[\#0A2E2A\],
        html.dark a[class*="bg-\[\#0A2E2A\]"]:not(.sidebar-nav-item):not(.active):not([class*="rounded-full"]):not([class*="text-\[10px\]"]):not([class*="text-\[11px\]"]),
        html.dark button[class*="bg-\[\#0A2E2A\]"]:not(.sidebar-nav-item):not([class*="rounded-full"]):not([class*="text-\[10px\]"]):not([class*="text-\[11px\]"]) {
            background-color: #123831 !important;
            border: 1px solid #C9A050 !important;
            color: #D8B060 !important;
            font-weight: 800 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.45) !important;
        }
        html.dark a.bg-\[\#0A2E2A\] *,
        html.dark button.bg-\[\#0A2E2A\] * {
            color: #D8B060 !important;
        }
        html.dark a.bg-\[\#0A2E2A\] svg,
        html.dark button.bg-\[\#0A2E2A\] svg {
            color: #D8B060 !important;
            stroke: #D8B060 !important;
        }
        html.dark a.bg-\[\#0A2E2A\]:hover,
        html.dark button.bg-\[\#0A2E2A\]:hover {
            background-color: #1A4E44 !important;
            border-color: #E2BC6B !important;
            color: #F5F3EF !important;
            box-shadow: 0 6px 20px rgba(201, 160, 80, 0.25) !important;
        }
        html.dark a.bg-\[\#0A2E2A\]:hover *,
        html.dark button.bg-\[\#0A2E2A\]:hover * {
            color: #F5F3EF !important;
        }

        /* ==========================================================================
           2. ACTIVE & INACTIVE FILTER PILLS (Transactions, Customers, Foods, Orders)
           ========================================================================== */
        /* Active Filter Pills (Elevated Emerald + Gold Border & Luminous Text) */
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-xl"],
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-2xl"],
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-xl"],
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-2xl"],
        html.dark a[class*="from-\[\#0A2E2A\]"][class*="rounded-xl"],
        html.dark a[class*="from-\[\#0A2E2A\]"][class*="rounded-2xl"] {
            background: #143D35 !important;
            background-image: none !important;
            border: 1px solid #C9A050 !important;
            color: #D8B060 !important;
            font-weight: 800 !important;
            box-shadow: 0 0 14px rgba(201, 160, 80, 0.25) !important;
        }
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-xl"] *,
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-2xl"] *,
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-xl"] *,
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-2xl"] *,
        html.dark a[class*="from-\[\#0A2E2A\]"][class*="rounded-xl"] *,
        html.dark a[class*="from-\[\#0A2E2A\]"][class*="rounded-2xl"] * {
            color: #D8B060 !important;
        }
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-xl"]:hover,
        html.dark a.bg-\[\#0A2E2A\][class*="rounded-2xl"]:hover,
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-xl"]:hover,
        html.dark button.bg-\[\#0A2E2A\][class*="rounded-2xl"]:hover {
            background-color: #1A4E44 !important;
            border-color: #E2BC6B !important;
            color: #F5F3EF !important;
        }

        /* Inactive Filter Pills (Crisp Dark Track with Readable Sage) */
        html.dark a.bg-gray-100[class*="rounded-xl"],
        html.dark a.bg-gray-100[class*="rounded-2xl"],
        html.dark a.bg-gray-100\/80[class*="rounded-xl"],
        html.dark a.bg-gray-100\/80[class*="rounded-2xl"],
        html.dark a.bg-gray-50[class*="rounded-xl"],
        html.dark a.bg-gray-50[class*="rounded-2xl"],
        html.dark a.bg-gray-50\/80[class*="rounded-xl"],
        html.dark a.bg-gray-50\/80[class*="rounded-2xl"],
        html.dark a.bg-gray-100:not([class*="from-"]):not(.sidebar-nav-item) {
            background-color: #081A17 !important;
            border: 1px solid #163832 !important;
            color: #8FA8A3 !important;
        }
        html.dark a.bg-gray-100[class*="rounded-xl"]:hover,
        html.dark a.bg-gray-100[class*="rounded-2xl"]:hover,
        html.dark a.bg-gray-100\/80[class*="rounded-xl"]:hover,
        html.dark a.bg-gray-100\/80[class*="rounded-2xl"]:hover,
        html.dark a.bg-gray-50[class*="rounded-xl"]:hover,
        html.dark a.bg-gray-50[class*="rounded-2xl"]:hover,
        html.dark a.bg-gray-100:hover:not([class*="from-"]):not(.sidebar-nav-item) {
            background-color: #0E2925 !important;
            border-color: rgba(201, 160, 80, 0.4) !important;
            color: #F5F3EF !important;
        }

        /* ==========================================================================
           3. TABLE ACTION ICON BUTTONS (ACTIONS Column)
           ========================================================================== */
        html.dark td .flex.items-center.justify-end button,
        html.dark td .flex.items-center.justify-end a,
        html.dark td.text-right button,
        html.dark td.text-right a,
        html.dark table td button[class*="p-2"],
        html.dark table td a[class*="p-2"],
        html.dark table td button[class*="p-2.5"],
        html.dark table td a[class*="p-2.5"] {
            background-color: #0F2B26 !important;
            border: 1px solid #1E4940 !important;
            color: #F5F3EF !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
            transition: all 0.2s ease !important;
        }
        html.dark td .flex.items-center.justify-end button svg,
        html.dark td .flex.items-center.justify-end a svg,
        html.dark td.text-right button svg,
        html.dark td.text-right a svg,
        html.dark table td button[class*="p-2"] svg,
        html.dark table td a[class*="p-2"] svg,
        html.dark table td button[class*="p-2.5"] svg,
        html.dark table td a[class*="p-2.5"] svg {
            color: #F5F3EF !important;
            stroke: #F5F3EF !important;
        }
        html.dark td .flex.items-center.justify-end button:hover,
        html.dark td .flex.items-center.justify-end a:hover,
        html.dark td.text-right button:hover,
        html.dark td.text-right a:hover,
        html.dark table td button[class*="p-2"]:hover,
        html.dark table td a[class*="p-2"]:hover,
        html.dark table td button[class*="p-2.5"]:hover,
        html.dark table td a[class*="p-2.5"]:hover {
            background-color: #16463D !important;
            border-color: #C9A050 !important;
            color: #D8B060 !important;
            transform: translateY(-1px) !important;
        }
        html.dark td .flex.items-center.justify-end button:hover svg,
        html.dark td .flex.items-center.justify-end a:hover svg,
        html.dark td.text-right button:hover svg,
        html.dark td.text-right a:hover svg {
            color: #D8B060 !important;
            stroke: #D8B060 !important;
        }

        /* Delete / Trash action button specifically */
        html.dark td button[class*="hover:text-red"],
        html.dark td button[class*="text-red"],
        html.dark td button[title*="Delete"],
        html.dark td button[title*="Remove"] {
            background-color: #0F2B26 !important;
            border: 1px solid #1E4940 !important;
            color: #F87171 !important;
        }
        html.dark td button[class*="hover:text-red"] svg,
        html.dark td button[class*="text-red"] svg,
        html.dark td button[title*="Delete"] svg,
        html.dark td button[title*="Remove"] svg {
            color: #F87171 !important;
            stroke: #F87171 !important;
        }
        html.dark td button[class*="hover:text-red"]:hover,
        html.dark td button[class*="text-red"]:hover,
        html.dark td button[title*="Delete"]:hover,
        html.dark td button[title*="Remove"]:hover {
            background-color: rgba(239, 68, 68, 0.22) !important;
            border-color: #EF4444 !important;
            color: #FCA5A5 !important;
        }
        html.dark td button[class*="hover:text-red"]:hover svg,
        html.dark td button[class*="text-red"]:hover svg,
        html.dark td button[title*="Delete"]:hover svg,
        html.dark td button[title*="Remove"]:hover svg {
            color: #FCA5A5 !important;
            stroke: #FCA5A5 !important;
        }

        /* One-Click Deliver / Checkmark button in Orders table */
        html.dark td button[class*="text-emerald-700"],
        html.dark td button[class*="bg-emerald-50"] {
            background-color: rgba(16, 185, 129, 0.18) !important;
            border: 1px solid rgba(16, 185, 129, 0.45) !important;
            color: #34D399 !important;
        }
        html.dark td button[class*="text-emerald-700"] svg,
        html.dark td button[class*="bg-emerald-50"] svg {
            color: #34D399 !important;
            stroke: #34D399 !important;
        }
        html.dark td button[class*="text-emerald-700"]:hover,
        html.dark td button[class*="bg-emerald-50"]:hover {
            background-color: #10B981 !important;
            color: #061513 !important;
        }
        html.dark td button[class*="text-emerald-700"]:hover svg,
        html.dark td button[class*="bg-emerald-50"]:hover svg {
            color: #061513 !important;
            stroke: #061513 !important;
        }

        /* ==========================================================================
           4. DRIVERS PAGE (Vehicle Pills, Deliveries & Capacity)
           ========================================================================== */
        html.dark .driver-vehicle-pill {
            background-color: #0E2925 !important;
            border: 1px solid #1B423B !important;
        }
        html.dark .driver-vehicle-pill span:first-child {
            color: #F5F3EF !important;
            font-weight: 700 !important;
        }
        html.dark .driver-vehicle-pill span:last-child {
            color: #D8B060 !important;
        }
        html.dark td .inline-flex.rounded-xl.bg-gray-50 {
            background-color: #0E2925 !important;
            border: 1px solid #1B423B !important;
        }
        html.dark td .inline-flex.rounded-xl.bg-gray-50 span.text-gray-900 {
            color: #F5F3EF !important;
        }
        html.dark td .inline-flex.rounded-xl.bg-gray-50 span.text-gray-400 {
            color: #8FA8A3 !important;
        }
        html.dark td span.rounded-xl.bg-\[\#C9A050\]\/10 {
            background-color: rgba(201, 160, 80, 0.12) !important;
            border-color: rgba(201, 160, 80, 0.35) !important;
            color: #D8B060 !important;
        }

        /* ==========================================================================
           5. EVENTS PAGE (Schedule Assign Driver Button)
           ========================================================================== */
        html.dark .event-assign-btn,
        html.dark td button[class*="from-\[\#C9A050\]"][class*="text-\[11px\]"] {
            background: #143D35 !important;
            background-image: none !important;
            border: 1px solid #C9A050 !important;
            color: #D8B060 !important;
            box-shadow: 0 0 10px rgba(201, 160, 80, 0.25) !important;
            font-weight: 800 !important;
        }
        html.dark .event-assign-btn:hover,
        html.dark td button[class*="from-\[\#C9A050\]"][class*="text-\[11px\]"]:hover {
            background-color: #1A4E44 !important;
            border-color: #E2BC6B !important;
            color: #F5F3EF !important;
            box-shadow: 0 0 16px rgba(201, 160, 80, 0.4) !important;
        }

        /* ==========================================================================
           6. FOOD & MENU (Beverages KPI Card & View Mode Switcher)
           ========================================================================== */
        /* KPI Metric Numbers */
        html.dark .beverage-kpi-count,
        html.dark p.text-\[\#0A2E2A\].text-2xl,
        html.dark p.text-\[\#0A2E2A\].text-3xl,
        html.dark .grid p.text-\[\#0A2E2A\],
        html.dark .grid > div p.font-black {
            color: #F5F3EF !important;
        }
        html.dark .border-\[\#C9A050\]\/40 p.text-\[\#0A2E2A\],
        html.dark .beverage-kpi-count {
            color: #D8B060 !important;
        }

        /* View Mode Switcher (Grid / Table) */
        html.dark .bg-black\/50.p-1\.5.rounded-2xl {
            background-color: #081A17 !important;
            border: 1px solid #163832 !important;
        }
        html.dark .bg-black\/50 button.text-gray-300 {
            color: #8FA8A3 !important;
        }
        html.dark .bg-black\/50 button.text-gray-300 svg {
            stroke: #8FA8A3 !important;
        }
        html.dark .bg-black\/50 button.text-gray-300:hover {
            color: #F5F3EF !important;
        }
        html.dark .bg-black\/50 button.text-gray-300:hover svg {
            stroke: #F5F3EF !important;
        }
        html.dark .bg-black\/50 button.bg-\[\#C9A050\] {
            background-color: #C9A050 !important;
            color: #061513 !important;
            font-weight: 800 !important;
        }
        html.dark .bg-black\/50 button.bg-\[\#C9A050\] svg {
            color: #061513 !important;
            stroke: #061513 !important;
        }

        /* ==========================================================================
           7. ORDERS (Target Schedule Badges) & MESSAGES (Chat Bubbles)
           ========================================================================== */
        /* Orders Schedule & Meal Badges */
        html.dark td span.rounded-full.bg-purple-50,
        html.dark span.bg-purple-50.text-purple-700 {
            background-color: rgba(201, 160, 80, 0.14) !important;
            border: 1px solid rgba(201, 160, 80, 0.35) !important;
            color: #D8B060 !important;
        }
        html.dark td .bg-white.text-\[\#0A2E2A\] {
            background-color: #0E2925 !important;
            border: 1px solid #1B423B !important;
            color: #D8B060 !important;
        }
        html.dark td span.bg-amber-100.text-amber-900 {
            background-color: rgba(245, 158, 11, 0.16) !important;
            border: 1px solid rgba(245, 158, 11, 0.35) !important;
            color: #FBBF24 !important;
        }
        html.dark td span.bg-sky-100.text-sky-900 {
            background-color: rgba(59, 130, 246, 0.16) !important;
            border: 1px solid rgba(59, 130, 246, 0.35) !important;
            color: #60A5FA !important;
        }
        html.dark td span.bg-purple-100.text-purple-900 {
            background-color: rgba(139, 92, 246, 0.16) !important;
            border: 1px solid rgba(139, 92, 246, 0.35) !important;
            color: #A78BFA !important;
        }
        html.dark td span.bg-rose-100.text-rose-800 {
            background-color: rgba(239, 68, 68, 0.16) !important;
            border: 1px solid rgba(239, 68, 68, 0.35) !important;
            color: #F87171 !important;
        }
        html.dark td span.bg-blue-100.text-blue-800 {
            background-color: rgba(59, 130, 246, 0.16) !important;
            border: 1px solid rgba(59, 130, 246, 0.35) !important;
            color: #60A5FA !important;
        }

        /* Messages Outgoing Chat Bubbles & Send Button */
        html.dark .bg-gradient-to-r.from-\[\#0A2E2A\].to-\[\#0D3833\] {
            background: #143D35 !important;
            background-image: none !important;
            border: 1px solid #215248 !important;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        }
        html.dark .bg-gradient-to-r.from-\[\#0A2E2A\].to-\[\#0D3833\] p {
            color: #F5F3EF !important;
        }
        html.dark .bg-gradient-to-r.from-\[\#0A2E2A\].to-\[\#0D3833\] p.text-\[\#C9A050\] {
            color: #D8B060 !important;
            font-weight: 700 !important;
        }
        html.dark button[type="submit"][class*="from-\[\#C9A050\]"] svg,
        html.dark button[type="submit"][class*="from-\[\#C9A050\]"] {
            color: #061513 !important;
            fill: #061513 !important;
        }

        /* ==========================================================================
           FORMS, INPUTS, SELECTS, MODALS & SCROLLBARS
           ========================================================================== */
        html.dark input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]),
        html.dark textarea,
        html.dark select,
        html.dark .input {
            background-color: #061513 !important;
            border-color: #193E37 !important;
            color: #F5F3EF !important;
        }
        html.dark input::placeholder,
        html.dark textarea::placeholder {
            color: #5E7A74 !important;
        }
        html.dark input:focus,
        html.dark textarea:focus,
        html.dark select:focus,
        html.dark .input:focus {
            border-color: #C9A050 !important;
            box-shadow: 0 0 0 2px rgba(201, 160, 80, 0.25) !important;
            outline: none !important;
        }
        html.dark select option {
            background-color: #0A211E !important;
            color: #F5F3EF !important;
        }
        html.dark input[type="checkbox"],
        html.dark input[type="radio"] {
            background-color: #061513 !important;
            border-color: #193E37 !important;
            color: #C9A050 !important;
        }

        /* Filter Tabs Container */
        html.dark .bg-white\/90.backdrop-blur-md.p-1\.5.rounded-2xl,
        html.dark .bg-white.p-1\.5.rounded-2xl {
            background-color: #081A17 !important;
            border-color: #13302B !important;
        }
        html.dark button.text-gray-400.hover\:text-gray-800 {
            color: #8FA8A3 !important;
        }
        html.dark button.text-gray-400.hover\:text-gray-800:hover {
            color: #F5F3EF !important;
        }

        /* Status Badges - Translucent Glass Pills */
        /* Emerald / Green */
        html.dark .bg-emerald-50,
        html.dark .bg-green-50 {
            background-color: rgba(16, 185, 129, 0.14) !important;
            color: #34D399 !important;
            border-color: rgba(16, 185, 129, 0.3) !important;
        }
        html.dark .text-emerald-600,
        html.dark .text-emerald-700,
        html.dark .text-green-600,
        html.dark .text-green-700 {
            color: #34D399 !important;
        }
        html.dark .border-emerald-100,
        html.dark .border-emerald-200,
        html.dark .border-green-100,
        html.dark .border-green-200 {
            border-color: rgba(16, 185, 129, 0.3) !important;
        }

        /* Amber / Yellow */
        html.dark .bg-amber-50,
        html.dark .bg-yellow-50 {
            background-color: rgba(245, 158, 11, 0.14) !important;
            color: #FBBF24 !important;
            border-color: rgba(245, 158, 11, 0.3) !important;
        }
        html.dark .text-amber-600,
        html.dark .text-amber-700,
        html.dark .text-amber-800,
        html.dark .text-yellow-600 {
            color: #FBBF24 !important;
        }
        html.dark .border-amber-100,
        html.dark .border-amber-200,
        html.dark .border-yellow-200 {
            border-color: rgba(245, 158, 11, 0.3) !important;
        }

        /* Red / Rose */
        html.dark .bg-rose-50,
        html.dark .bg-red-50 {
            background-color: rgba(239, 68, 68, 0.14) !important;
            color: #F87171 !important;
            border-color: rgba(239, 68, 68, 0.3) !important;
        }
        html.dark .text-rose-600,
        html.dark .text-rose-700,
        html.dark .text-red-600,
        html.dark .text-red-700 {
            color: #F87171 !important;
        }
        html.dark .border-rose-100,
        html.dark .border-rose-200,
        html.dark .border-red-100,
        html.dark .border-red-200 {
            border-color: rgba(239, 68, 68, 0.3) !important;
        }

        /* Blue / Sky */
        html.dark .bg-blue-50,
        html.dark .bg-sky-50 {
            background-color: rgba(59, 130, 246, 0.14) !important;
            color: #60A5FA !important;
            border-color: rgba(59, 130, 246, 0.3) !important;
        }
        html.dark .text-blue-600,
        html.dark .text-blue-700,
        html.dark .text-sky-600 {
            color: #60A5FA !important;
        }
        html.dark .border-blue-100,
        html.dark .border-blue-200 {
            border-color: rgba(59, 130, 246, 0.3) !important;
        }

        /* Purple / Violet / Indigo */
        html.dark .bg-purple-50,
        html.dark .bg-indigo-50,
        html.dark .bg-violet-50 {
            background-color: rgba(139, 92, 246, 0.14) !important;
            color: #A78BFA !important;
            border-color: rgba(139, 92, 246, 0.3) !important;
        }
        html.dark .text-purple-600,
        html.dark .text-purple-700,
        html.dark .text-indigo-600 {
            color: #A78BFA !important;
        }
        html.dark .border-purple-100,
        html.dark .border-purple-200,
        html.dark .border-indigo-200 {
            border-color: rgba(139, 92, 246, 0.3) !important;
        }

        /* Gray / Neutral Badges */
        html.dark .bg-gray-100.text-gray-500,
        html.dark .bg-gray-100.text-gray-600 {
            background-color: rgba(94, 122, 116, 0.14) !important;
            color: #8FA8A3 !important;
            border-color: rgba(94, 122, 116, 0.3) !important;
        }

        /* Modals & Overlays */
        html.dark .bg-gray-950\/70,
        html.dark .bg-gray-900\/60,
        html.dark .bg-gray-900\/70 {
            background-color: rgba(3, 11, 10, 0.88) !important;
            backdrop-filter: blur(12px) !important;
        }
        html.dark [x-teleport="body"] .bg-white,
        html.dark .fixed.inset-0 .bg-white {
            background-color: #0A211E !important;
            border-color: #163832 !important;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.85) !important;
        }
        html.dark button.hover\:bg-gray-100:hover {
            background-color: rgba(255, 255, 255, 0.08) !important;
            color: #F5F3EF !important;
        }

        /* Custom Scrollbars */
        html.dark ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        html.dark ::-webkit-scrollbar-track {
            background: #061513;
        }
        html.dark ::-webkit-scrollbar-thumb {
            background: #183B35;
            border-radius: 9999px;
        }
        html.dark ::-webkit-scrollbar-thumb:hover {
            background: #C9A050;
        }

        /* Chart SVG tracks in Dark Mode */
        html.dark svg circle[stroke="#F1F5F9"] {
            stroke: #13302B !important;
        }
        html.dark svg circle[stroke="#0A2E2A"] {
            stroke: #1E4E46 !important;
        }
        html.dark .border-white {
            border-color: #0A211E !important;
        }

        /* Pagination Controls */
        html.dark nav[role="navigation"] a,
        html.dark nav[role="navigation"] span {
            background-color: #0A211E !important;
            border-color: #163832 !important;
            color: #8FA8A3 !important;
        }
        html.dark nav[role="navigation"] a:hover {
            background-color: #0D2824 !important;
            color: #D8B060 !important;
        }
        html.dark nav[role="navigation"] span[aria-current="page"] > span {
            background-color: #C9A050 !important;
            color: #061513 !important;
            border-color: #C9A050 !important;
        }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50/70 text-slate-900 selection:bg-[#C9A050]/20 selection:text-[#0A2E2A]">
    <div class="flex h-screen overflow-hidden" 
         x-data="{ sidebarOpen: window.innerWidth >= 1024, mobileMenuOpen: false }"
         @resize.window="if (window.innerWidth >= 1024) mobileMenuOpen = false">

        <!-- Mobile Drawer Backdrop -->
        <div x-show="mobileMenuOpen" 
             x-cloak
             class="fixed inset-0 bg-gray-950/70 backdrop-blur-md z-40 lg:hidden"
             @click="mobileMenuOpen = false"
             x-transition:enter="transition-opacity ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
        </div>

        <!-- Sidebar (Desktop Responsive Rail / Mobile Off-canvas Drawer) -->
        <aside 
            id="sidebar-container"
            class="bg-[#0A2E2A] bg-gray-900 text-white flex flex-col shrink-0 transition-all duration-300 ease-in-out fixed inset-y-0 left-0 z-50 w-72 lg:static lg:z-auto border-r border-[#C9A050]/20 shadow-2xl shadow-black/40"
            style="background-color: #0A2E2A !important;"
            :class="{
                'translate-x-0': mobileMenuOpen,
                '-translate-x-full lg:translate-x-0': !mobileMenuOpen,
                'lg:w-64': sidebarOpen,
                'lg:w-20': !sidebarOpen
            }"
        >
            <!-- Brand Header -->
            <div class="p-5 sm:p-6 flex items-center justify-between border-b border-[#C9A050]/20 bg-[#0A2E2A]" style="background-color: #0A2E2A !important;">
                <a href="{{ route('dashboard') }}" class="flex items-center justify-center w-full transition-all duration-300">
                    <img src="{{ asset('images/logo.svg') }}" alt="Menu Logo" class="h-24 sm:h-28 w-4/5 mx-auto object-contain transition-all duration-300 drop-shadow-md mix-blend-screen filter brightness-125 contrast-125" :class="sidebarOpen ? '' : 'lg:w-10 lg:h-10 lg:scale-100'" />
                </a>

                <!-- Mobile Close Drawer Button -->
                <button @click="mobileMenuOpen = false" class="lg:hidden p-2 text-white hover:text-[#C9A050] rounded-xl hover:bg-white/10 transition-colors cursor-pointer" aria-label="Close menu">
                    <svg class="w-6 h-6 text-white hover:text-[#C9A050]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-grow px-3 mt-4 space-y-1.5 overflow-y-auto custom-scrollbar">
                @php
                    $navItems = [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'match' => 'dashboard', 'icon' => 'M4 6h16M4 12h16m-7 6h7'],
                        ['route' => 'orders', 'label' => 'Orders', 'match' => 'orders*', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                        ['route' => 'transactions.index', 'label' => 'Transactions', 'match' => 'transactions*', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z'],
                        ['route' => 'customers.index', 'label' => 'Customers', 'match' => 'customers*', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'offers', 'label' => 'Special Offers', 'match' => 'offers*', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
                        ['route' => 'drivers', 'label' => 'Drivers', 'match' => 'drivers*', 'icon' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6 0a1 1 0 001 1h1'],
                        ['route' => 'events', 'label' => 'Events', 'match' => 'events*', 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
                        ['route' => 'foods.index', 'label' => 'Food & Menu', 'match' => 'foods*', 'alt_match' => 'categories*', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'],
                        ['route' => 'messages', 'label' => 'Messages', 'match' => 'messages*', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z'],
                        ['route' => 'notifications', 'label' => 'Notifications', 'match' => 'notifications*', 'icon' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9'],
                        ['route' => 'bills', 'label' => 'Bills & POS', 'match' => 'bills*', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'users.index', 'label' => 'Users', 'match' => 'users*', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'logs.index', 'label' => 'History & Logs', 'match' => 'logs*', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'settings.checkout', 'label' => 'Settings', 'match' => 'settings*', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'],
                    ];
                @endphp

                @foreach($navItems as $item)
                    @php
                        $isRestrictedForChef = in_array($item['label'], [
                            'Transactions', 'Customers', 'Special Offers', 'Drivers', 'Events', 'Bills & POS', 'Users', 'History & Logs', 'Settings'
                        ]);
                    @endphp
                    @if(auth()->user()?->role === 'Chef' && $isRestrictedForChef)
                        @continue
                    @endif
                    @php 
                        $isActive = request()->routeIs($item['match']) || (isset($item['alt_match']) && (request()->routeIs($item['alt_match']) || ($item['route'] === 'foods.index' && request()->routeIs('beverages*')))); 
                    @endphp
                    <a href="{{ route($item['route']) }}" 
                       class="sidebar-nav-item flex items-center space-x-3.5 px-3.5 py-3 rounded-2xl transition-all duration-200 group {{ $isActive ? 'active bg-[#C9A050] text-[#0A2E2A] font-bold shadow-lg shadow-black/25' : 'text-gray-300 hover:text-white hover:bg-white/10 font-medium' }}"
                       :title="sidebarOpen ? '' : '{{ $item['label'] }}'">
                        <div class="flex-shrink-0 flex items-center justify-center w-6 h-6">
                            <svg class="w-5 h-5 transition-transform duration-200 {{ $isActive ? 'text-[#0A2E2A]' : 'text-gray-300 group-hover:text-white group-hover:scale-110' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $item['icon'] }}"></path>
                            </svg>
                        </div>
                        <span class="text-sm tracking-tight truncate transition-colors duration-200 {{ $isActive ? 'text-[#0A2E2A] font-bold' : 'text-gray-300 group-hover:text-white font-medium' }}" :class="sidebarOpen ? 'opacity-100' : 'lg:hidden'">
                            {{ $item['label'] }}
                        </span>
                        @if($isActive)
                            <span class="ml-auto w-2 h-2 rounded-full bg-[#0A2E2A]" :class="sidebarOpen ? 'block' : 'hidden'"></span>
                        @endif
                    </a>
                @endforeach
            </nav>

            <!-- User Footer Card -->
            <div class="mt-auto p-4 border-t border-[#C9A050]/20 bg-[#06201D]" style="background-color: #06201D !important;">
                <div class="bg-[#0A2E2A] rounded-2xl p-3.5 border border-[#C9A050]/30 shadow-lg shadow-black/40" :class="sidebarOpen ? '' : 'lg:p-2'" style="background-color: #0A2E2A !important;">
                    <div class="flex items-center space-x-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'Admin') }}&background=0A2E2A&color=C9A050&bold=true" 
                             class="w-10 h-10 rounded-xl flex-shrink-0 shadow-sm border-2 border-[#C9A050] ring-1 ring-[#C9A050]/50" 
                             alt="{{ auth()->user()->name ?? 'User' }}">
                        <div class="overflow-hidden min-w-0 flex-1" :class="sidebarOpen ? '' : 'lg:hidden'">
                            <p class="text-sm font-extrabold text-white truncate tracking-tight">{{ auth()->user()->name ?? 'Administrator' }}</p>
                            <div class="flex items-center space-x-1.5 mt-0.5">
                                <span class="inline-block w-2 h-2 rounded-full bg-[#C9A050] animate-pulse"></span>
                                <p class="text-xs font-bold text-[#C9A050] truncate uppercase tracking-wider">{{ auth()->user()->role ?? 'System Admin' }}</p>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" 
                                class="w-full mt-3 py-2.5 text-xs font-bold text-[#C9A050] hover:text-[#0A2E2A] bg-black/30 hover:bg-[#C9A050] transition-all duration-200 border border-[#C9A050]/40 hover:border-[#C9A050] rounded-xl flex items-center justify-center space-x-2 cursor-pointer group shadow-sm"
                                :title="sidebarOpen ? '' : 'Log out'">
                            <svg class="w-4 h-4 text-[#C9A050] group-hover:text-[#0A2E2A] transition-colors flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            <span class="text-[#C9A050] group-hover:text-[#0A2E2A] transition-colors font-bold" :class="sidebarOpen ? 'inline' : 'lg:hidden'">Log out</span>
                        </button>
                    </form>
                </div>
                <p class="mt-3 text-[11px] text-[#C9A050]/70 text-center font-medium" :class="sidebarOpen ? '' : 'lg:hidden'">© 2026 Menu Luxury Management</p>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="flex-1 flex flex-col min-w-0 overflow-y-auto overflow-x-hidden">
            <!-- Modern Header Bar -->
            <header class="sticky top-0 z-30 bg-white/85 backdrop-blur-md px-4 sm:px-8 py-3.5 flex items-center justify-between border-b border-gray-100 shadow-[0_4px_20px_-4px_rgba(10,46,42,0.05)]">
                <div class="flex items-center space-x-3">
                    <!-- Mobile Hamburger Button -->
                    <button @click="mobileMenuOpen = true" 
                            class="lg:hidden p-2.5 bg-gray-50 hover:bg-gray-100 rounded-2xl text-gray-700 active:scale-95 transition-all cursor-pointer"
                            aria-label="Open navigation">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>

                    <!-- Desktop Sidebar Rail Toggle -->
                    <button @click="sidebarOpen = !sidebarOpen" 
                            class="hidden lg:flex p-2.5 bg-gray-50 hover:bg-[#C9A050]/10 rounded-2xl text-gray-600 hover:text-[#0A2E2A] hover:shadow-xs active:scale-95 transition-all cursor-pointer"
                            :title="sidebarOpen ? 'Collapse sidebar' : 'Expand sidebar'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                    </button>

                    <!-- Mobile Title -->
                    <span class="text-base font-black text-gray-900 lg:hidden truncate tracking-tight">Menu Admin</span>
                </div>
                
                <div class="flex items-center space-x-3 sm:space-x-4" x-data="{ notificationsOpen: false }">
                    <!-- Global Search Box -->
                    <form method="GET" action="{{ url()->current() }}" class="relative w-36 sm:w-56 md:w-64">
                        <input type="text" 
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Search anything..." 
                               @input.debounce.1500ms="$el.form.submit()"
                               class="input pl-9 pr-3 py-2 bg-gray-50 hover:bg-gray-100/80 focus:bg-white rounded-2xl border border-gray-200/60 focus:border-[#C9A050] focus:ring-2 focus:ring-[#C9A050]/20 w-full text-xs sm:text-sm font-bold transition-all shadow-inner">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </form>
                    
                    <!-- Luxury Light/Dark Mode Toggle Button -->
                    <div x-data="{
                        isDark: document.documentElement.classList.contains('dark'),
                        toggleTheme() {
                            document.documentElement.classList.add('theme-transition');
                            this.isDark = !this.isDark;
                            if (this.isDark) {
                                document.documentElement.classList.add('dark');
                                localStorage.setItem('menu_theme', 'dark');
                            } else {
                                document.documentElement.classList.remove('dark');
                                localStorage.setItem('menu_theme', 'light');
                            }
                            window.dispatchEvent(new CustomEvent('theme-changed', { detail: { theme: this.isDark ? 'dark' : 'light' } }));
                            setTimeout(() => {
                                document.documentElement.classList.remove('theme-transition');
                            }, 300);
                        }
                    }">
                        <button type="button" 
                                @click="toggleTheme()" 
                                class="theme-toggle-btn relative p-2.5 rounded-2xl border transition-all duration-300 cursor-pointer active:scale-95 flex items-center justify-center group"
                                :class="isDark 
                                    ? 'bg-[#0A211E] border-[#163832] text-[#D8B060] hover:border-[#D8B060]/50 hover:shadow-[0_0_15px_rgba(201,160,80,0.25)]' 
                                    : 'bg-gray-50 hover:bg-[#C9A050]/10 border-gray-200/60 text-gray-600 hover:text-[#0A2E2A]'"
                                :title="isDark ? 'Switch to Light Mode' : 'Switch to Ultra-Luxury Dark Mode'"
                                aria-label="Toggle theme">
                            <!-- Sun Icon (Active in Dark Mode to switch back to Light) -->
                            <svg x-show="isDark" x-cloak class="w-5 h-5 transition-transform duration-300 group-hover:rotate-45 text-[#D8B060]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            <!-- Moon Icon (Active in Light Mode to switch to Dark) -->
                            <svg x-show="!isDark" x-cloak class="w-5 h-5 transition-transform duration-300 group-hover:-rotate-12 text-gray-600 group-hover:text-[#0A2E2A]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                    </div>

                    <!-- Notifications Dropdown -->
                    <div class="relative">
                        <button @click="notificationsOpen = !notificationsOpen" 
                                @click.away="notificationsOpen = false" 
                                class="p-2.5 bg-gray-50 hover:bg-[#C9A050]/10 rounded-2xl text-gray-600 hover:text-[#0A2E2A] active:scale-95 transition-all relative cursor-pointer"
                                aria-label="Notifications">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                            <span class="absolute top-2 right-2 w-2.5 h-2.5 bg-[#C9A050] rounded-full border-2 border-white animate-pulse"></span>
                        </button>

                        <div x-show="notificationsOpen" 
                             x-cloak
                             x-transition:enter="transition ease-out duration-200"
                             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                             class="absolute right-0 mt-3 w-[calc(100vw-2rem)] max-w-sm sm:w-80 bg-white rounded-3xl shadow-2xl border border-gray-100 z-[110] overflow-hidden">
                            
                            <div class="p-5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                                <h3 class="font-bold text-gray-900 text-sm">Notifications</h3>
                                <span class="px-2.5 py-1 bg-amber-50 text-[#C9A050] text-[10px] font-bold rounded-full uppercase border border-[#C9A050]/20">Active Updates</span>
                            </div>

                            <div class="max-h-[360px] overflow-y-auto custom-scrollbar divide-y divide-gray-50">
                                <a href="{{ route('orders') }}" class="flex items-start p-4 hover:bg-gray-50/80 transition-colors">
                                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl mr-3 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate">Orders Live Sync</p>
                                        <p class="text-[11px] text-gray-500 truncate">Mobile orders and meal plan orders updated.</p>
                                        <p class="text-[10px] text-gray-400 mt-1">Real-time DB connection active</p>
                                    </div>
                                </a>

                                <a href="{{ route('drivers') }}" class="flex items-start p-4 hover:bg-gray-50/80 transition-colors">
                                    <div class="p-2.5 bg-green-50 text-green-600 rounded-xl mr-3 flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-bold text-gray-900 truncate">Driver Fleet</p>
                                        <p class="text-[11px] text-gray-500 truncate">Fleet availability status is tracked live.</p>
                                        <p class="text-[10px] text-gray-400 mt-1">Ready for dispatch</p>
                                    </div>
                                </a>
                            </div>

                            <a href="{{ route('notifications') }}" class="block p-3.5 bg-gray-50 text-center text-xs font-bold text-[#C9A050] hover:bg-gray-100 transition-colors">
                                View Notification Center →
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Body Content -->
            <div class="p-4 sm:p-6 lg:p-8 flex-1">
                {{ $slot }}
            </div>
        </main>
    </div>

    <!-- Toast Notification Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 flex flex-col space-y-2 pointer-events-none"></div>
</body>
</html>
