@php
    $map = [
        // Orders
        'pending'           => ['label' => 'Pending',          'cls' => 'bg-amber-50 text-amber-600 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30'],
        'processing'        => ['label' => 'Processing',       'cls' => 'bg-blue-50 text-blue-600 ring-blue-500/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30'],
        'completed'         => ['label' => 'Completed',        'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'],
        'cancelled'         => ['label' => 'Cancelled',        'cls' => 'bg-rose-50 text-rose-600 ring-rose-500/20 dark:bg-rose-950/40 dark:text-rose-300 dark:ring-rose-500/30'],
        'delivered'         => ['label' => 'Delivered',        'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'],
        'out_for_delivery'  => ['label' => 'Out for Delivery',  'cls' => 'bg-indigo-50 text-indigo-600 ring-indigo-500/20 dark:bg-indigo-950/40 dark:text-indigo-300 dark:ring-indigo-500/30'],
        // Drivers
        'available'         => ['label' => 'Available',        'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'],
        'busy'              => ['label' => 'Busy',             'cls' => 'bg-orange-50 text-orange-600 ring-orange-500/20 dark:bg-orange-950/40 dark:text-orange-300 dark:ring-orange-500/30'],
        'on_delivery'       => ['label' => 'On Delivery',      'cls' => 'bg-blue-50 text-blue-600 ring-blue-500/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30'],
        'offline'           => ['label' => 'Offline',          'cls' => 'bg-gray-100 text-gray-500 ring-gray-400/20 dark:bg-[#123831] dark:text-[#8FA8A3] dark:ring-[#1E4940]'],
        // Events
        'upcoming'          => ['label' => 'Upcoming',         'cls' => 'bg-amber-50 text-amber-600 ring-amber-500/20 dark:bg-amber-950/40 dark:text-amber-300 dark:ring-amber-500/30'],
        'ongoing'           => ['label' => 'Ongoing',          'cls' => 'bg-blue-50 text-blue-600 ring-blue-500/20 dark:bg-blue-950/40 dark:text-blue-300 dark:ring-blue-500/30'],
        'event_completed'   => ['label' => 'Completed',        'cls' => 'bg-emerald-50 text-emerald-600 ring-emerald-500/20 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-500/30'],
    ];

    $cfg = $map[$status]
        ?? ['label' => ucwords(str_replace('_', ' ', $status)), 'cls' => 'bg-gray-100 text-gray-600 ring-gray-400/20 dark:bg-[#123831] dark:text-[#A3B8B3] dark:ring-[#1E4940]'];
@endphp

<span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-[11px] font-semibold rounded-full ring-1 ring-inset shadow-sm {{ $cfg['cls'] }}">
    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
    {{ $cfg['label'] }}
</span>
