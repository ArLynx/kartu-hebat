@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800']) }}>
        <div class="font-bold">Periksa kembali data pendaftaran Anda:</div>

        <ul class="mt-2 list-disc list-inside space-y-1 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
