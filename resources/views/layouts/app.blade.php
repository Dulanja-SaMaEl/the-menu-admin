@props(['title' => null])

<x-app-layout :title="$title">
    {{ $slot }}
</x-app-layout>
