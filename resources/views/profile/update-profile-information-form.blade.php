<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        Identitas Akun
    </x-slot>

    <x-slot name="description">
        Perbarui nama akun dan alamat email. Data akademik serta kependudukan mahasiswa dikelola dari modul pendaftaran.
    </x-slot>

    <x-slot name="form">
        @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div x-data="{photoName: null, photoPreview: null}" class="col-span-6 sm:col-span-4">
                <input type="file" id="photo" class="hidden"
                       wire:model.live="photo"
                       x-ref="photo"
                       x-on:change="
                            photoName = $refs.photo.files[0].name;
                            const reader = new FileReader();
                            reader.onload = (e) => photoPreview = e.target.result;
                            reader.readAsDataURL($refs.photo.files[0]);
                       ">
                <x-label for="photo" value="Foto profil" />
                <div class="mt-2" x-show="!photoPreview">
                    <img src="{{ $this->user->profile_photo_url }}" alt="{{ $this->user->name }}" class="size-20 rounded-full object-cover">
                </div>
                <div class="mt-2" x-show="photoPreview" style="display: none;">
                    <span class="block size-20 rounded-full bg-cover bg-center"
                          x-bind:style="'background-image: url(\'' + photoPreview + '\')'"></span>
                </div>
                <x-secondary-button class="mt-2 me-2" type="button" x-on:click.prevent="$refs.photo.click()">
                    Pilih foto
                </x-secondary-button>
                @if ($this->user->profile_photo_path)
                    <x-secondary-button type="button" class="mt-2" wire:click="deleteProfilePhoto">
                        Hapus foto
                    </x-secondary-button>
                @endif
                <x-input-error for="photo" class="mt-2" />
            </div>
        @endif

        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="Nama lengkap" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required autocomplete="name" />
            <x-input-error for="name" class="mt-2" />
            @if($this->user->isStudent())
                <p class="mt-2 text-xs leading-5 text-slate-500">Nama terkunci saat pengajuan periode berjalan sedang diproses.</p>
            @endif
        </div>

        <div class="col-span-6 sm:col-span-4">
            <x-label for="email" value="Email" />
            <x-input id="email" type="email" class="mt-1 block w-full" wire:model="state.email" required autocomplete="username" />
            <x-input-error for="email" class="mt-2" />

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::emailVerification()) && ! $this->user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-slate-600">
                    Email belum diverifikasi.
                    <button type="button" class="font-semibold text-brand-600 underline" wire:click.prevent="sendEmailVerification">
                        Kirim ulang tautan verifikasi
                    </button>
                </p>
                @if ($this->verificationLinkSent)
                    <p class="mt-2 text-sm font-medium text-emerald-600">Tautan verifikasi baru telah dikirim.</p>
                @endif
            @endif
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">Tersimpan.</x-action-message>
        <x-button wire:loading.attr="disabled" wire:target="photo">Simpan</x-button>
    </x-slot>
</x-form-section>
