@props(['icon', 'label', 'route'])

<li class="py-2 px-3 hover:bg-white/10 rounded">
    <a href="{{ route($route) }}" class="flex items-center space-x-2">
        <span>{{ $icon }}</span>
        <span>{{ $label }}</span>
    </a>
</li>

