@props(['status'])

<span {{ $attributes->class(['status-chip', 'status-'.$status->tone()]) }}>
    {{ $status->label() }}
</span>
