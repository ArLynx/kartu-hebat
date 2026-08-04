@if(session('success') || session('warning') || session('error') || $errors->any())
    @php
        $tone = session('error') || $errors->any() ? 'red' : (session('warning') ? 'amber' : 'emerald');
        $message = session('error') ?? session('warning') ?? session('success');
    @endphp
    <div data-auto-dismiss class="mb-6 rounded-xl border px-4 py-3 text-sm
        {{ $tone === 'red' ? 'border-red-200 bg-red-50 text-red-800' : '' }}
        {{ $tone === 'amber' ? 'border-amber-200 bg-amber-50 text-amber-800' : '' }}
        {{ $tone === 'emerald' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : '' }}">
        @if($message)
            <p class="font-semibold">{{ $message }}</p>
        @endif
        @if($errors->any())
            <ul class="mt-1 list-inside list-disc">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </div>
@endif
