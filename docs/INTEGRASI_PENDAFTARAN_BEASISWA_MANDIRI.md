# Integrasi Pendaftaran Beasiswa Mandiri

## Prinsip integrasi

Autentikasi, peran pengguna, dan kerangka tampilan tetap menggunakan Sistem Kartu Hebat Mahasiswa. Namun, domain pendaftaran mahasiswa menggunakan struktur dari `beasiswa.zip` secara mandiri.

Tidak terdapat relasi antara `pendaftarans` dan `applications`. Pemilihan kategori tidak diterjemahkan menjadi jalur Akademik atau Tidak Mampu pada tabel lama.

## Alur mahasiswa

1. Mahasiswa login melalui autentikasi Sistem Kartu Hebat.
2. Dashboard mengakses `pendaftarans` berdasarkan `user_id`.
3. Mahasiswa memilih kategori dari `kategori_beasiswas` pada `periodes` aktif.
4. Sistem membuat draft pada `pendaftarans`.
5. Mahasiswa melengkapi `data_pribadis` dan NIM awal pada `pendidikans`.

## Perintah peningkatan versi

```bash
php artisan optimize:clear
php artisan migrate --seed
```

Tidak diperlukan tabel `scholarship_periods` atau `scholarship_categories`.
