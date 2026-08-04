@props(['href', 'active' => false, 'icon'])

<a href="{{ $href }}" {{ $attributes->class(['sidebar-link', 'sidebar-link-active' => $active]) }}>
    <x-icon :name="$icon" class="h-5 w-5 shrink-0" />
    <span>{{ $slot }}</span>
</a>
