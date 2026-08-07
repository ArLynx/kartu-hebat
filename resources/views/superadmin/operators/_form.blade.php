@php
    $operator = $operator ?? null;
    $selectedRole = old('role', $operator?->role?->value ?? '');
    $selectedStatus = old('status', $operator?->status ?? 'active');
    $isDesa = $selectedRole === 'operator_desa';
@endphp

<div class="grid gap-6 lg:grid-cols-2" x-data="{ role: @js($selectedRole) }">
    <div>
        <label for="name" class="form-label">Nama <span class="text-red-500">*</span></label>
        <input id="name" name="name" type="text" class="form-input" value="{{ old('name', $operator->name ?? '') }}" maxlength="255" required>
        @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="form-label">Email <span class="text-red-500">*</span></label>
        <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $operator->email ?? '') }}" maxlength="255" required>
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="role" class="form-label">Role <span class="text-red-500">*</span></label>
        <select id="role" name="role" class="form-input" x-model="role" required>
            <option value="">Pilih role</option>
            @foreach($roles as $value => $label)
                <option value="{{ $value }}" @selected($selectedRole === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('role')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="status" class="form-label">Status</label>
        <select id="status" name="status" class="form-input">
            <option value="active" @selected($selectedStatus === 'active')>Aktif</option>
            <option value="inactive" @selected($selectedStatus === 'inactive')>Nonaktif</option>
        </select>
        @error('status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <div x-show="role === 'operator_desa'" x-cloak>
            <label for="village_id" class="form-label">Desa <span class="text-red-500">*</span></label>
            <select id="village_id" name="village_id" class="form-input" :required="role === 'operator_desa'">
                <option value="">Pilih desa</option>
                @foreach($villages as $village)
                    <option value="{{ $village->id }}" @selected((string) old('village_id', $operator->village_id ?? '') === (string) $village->id)>
                        {{ $village->name }}{{ $village->kecamatan ? ' — Kec. '.$village->kecamatan->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('village_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            <p class="form-help">Batasi ke 200 desa pertama. Gunakan seeder untuk desa lainnya.</p>
        </div>

        <div x-show="role === 'operator_kecamatan'" x-cloak>
            <label for="kecamatan_id" class="form-label">Kecamatan <span class="text-red-500">*</span></label>
            <select id="kecamatan_id" name="kecamatan_id" class="form-input" :required="role === 'operator_kecamatan'">
                <option value="">Pilih kecamatan</option>
                @foreach($kecamatans as $kecamatan)
                    <option value="{{ $kecamatan->id }}" @selected((string) old('kecamatan_id', $operator->kecamatan_id ?? '') === (string) $kecamatan->id)>
                        {{ $kecamatan->name }}
                    </option>
                @endforeach
            </select>
            @error('kecamatan_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <div x-show="role && role !== 'operator_desa' && role !== 'operator_kecamatan'" x-cloak>
            <label class="form-label">Cakupan</label>
            <p class="form-input cursor-not-allowed bg-slate-50">Seluruh kabupaten (otomatis)</p>
            <p class="form-help">Operator dinas/kabupaten melihat data lintas kecamatan dalam kabupaten yang sama.</p>
        </div>
    </div>

    <div>
        <label for="password" class="form-label">Password {{ $operator ? 'Baru (opsional)' : '*' }}</label>
        <input id="password" name="password" type="password" class="form-input" minlength="8" @required(!$operator)>
        <p class="form-help">Minimal 8 karakter, harus mengandung huruf dan angka. {{ $operator ? 'Kosongkan jika tidak ingin mengubah.' : 'Password akan dibuat otomatis jika dikosongkan.' }}</p>
        @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="form-input" minlength="8">
        @error('password_confirmation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<input type="hidden" name="kabupaten_id" value="{{ old('kabupaten_id', $operator->kabupaten_id ?? '') }}">

<div class="mt-7 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
    Pastikan role dan wilayah sudah benar. Perubahan role akan mengubah scope verifikasi operator di <code>Application::scopeVisibleTo()</code>.
</div>

<div class="mt-7 flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-6">
    <a href="{{ route('superadmin.operators.index') }}" class="btn-secondary">Batal</a>
    <button type="submit" class="btn-primary">{{ $operator ? 'Simpan Perubahan' : 'Buat Operator' }}</button>
</div>