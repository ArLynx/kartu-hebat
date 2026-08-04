@props(['for'])

@if ($errors->has($for))
    <ul {{ $attributes->merge(['class' => 'text-sm text-red-600 space-y-1']) }}>
        <li>{{ $errors->first($for) }}</li>
    </ul>
@endif