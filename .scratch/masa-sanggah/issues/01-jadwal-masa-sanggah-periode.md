# 01: Jadwal Masa Sanggah pada periode

**What to build:** Superadmin dapat menentukan jendela **Masa Sanggah** untuk sebuah periode dengan mengisi tanggal mulai dan tanggal selesai. Daftar periode menampilkan jadwal yang tersimpan beserta statusnya, sehingga terlihat jelas mana yang belum dijadwalkan, belum dimulai, sedang berlangsung, atau sudah berakhir. Kedua tanggal opsional, tetapi bila diisi keduanya harus berurutan. Istilah *Masa Sanggah* dan *Sanggahan* dicatat di glossary, dan keputusan desain (hanya TMS, teks saja, satu kali, hanya periode aktif) dicatat sebagai ADR.

**Blocked by:** None (can start immediately)

**Status:** resolved

- [x] Superadmin dapat menyimpan tanggal mulai dan tanggal selesai masa sanggah pada sebuah periode, dan tanggal itu tetap tersimpan saat form dibuka kembali.
- [x] Form menolak tanggal selesai yang lebih awal dari tanggal mulai, dengan pesan validasi yang menyebut kedua field.
- [x] Kedua tanggal boleh dikosongkan; periode tanpa jadwal tetap bisa disimpan dan tidak menampilkan tanggal palsu.
- [x] Daftar periode menampilkan status masa sanggah untuk hari ini: belum dijadwalkan, belum dimulai, sedang berlangsung, atau telah berakhir.
- [x] Domain menyediakan pembacaan status jendela yang dipakai konsisten oleh daftar periode dan pemakai berikutnya, sehingga logika "hari ini di antara dua tanggal" hanya ada di satu tempat.
- [x] Glossary memuat entri Masa Sanggah (jendela waktu milik periode) dan Sanggahan (keberatan milik satu application), tanpa detail implementasi.
- [x] ADR mencatat alasan sanggahan dibatasi pada TMS, berbentuk teks tanpa lampiran, hanya satu kali, dan hanya untuk periode aktif.
- [x] Feature test menutup: simpan tanggal, validasi urutan, izin tanggal kosong, dan pembacaan status jendela.

## Comments

Implementasi:

- Kolom `tanggal_mulai_sanggah`/`tanggal_selesai_sanggah` (date, nullable) pada `periodes`, di-cast `date` dan masuk `$fillable`, mengikuti preseden LPJ.
- `Periode::masaSanggahStatus(): MasaSanggahStatus` adalah satu-satunya tempat logika "hari ini di antara dua tanggal" untuk masa sanggah; daftar periode memakai hasilnya langsung. Enum menyediakan `label()`, `tone()`, dan `isScheduled()`.
- Validasi form: kedua tanggal nullable, tetapi `required_with` membuat jadwal setengah jadi ditolak; `tanggal_selesai_sanggah` wajib `after_or_equal:tanggal_mulai_sanggah` dengan pesan yang menyebut kedua field. Ini menutup celah jadwal satu sisi yang tersimpan tetapi tidak pernah tampil.
- Daftar periode mendapat kolom "Masa Sanggah" berisi rentang tanggal (atau "Belum diatur") plus badge status hari ini.
- Glossary mendapat entri Masa Sanggah dan Sanggahan; ADR `docs/adr/0001-sanggahan-hanya-tms-teks-sekali-periode-aktif.md`.
- Test: `tests/Feature/MasaSanggahScheduleTest.php` (6 test) — simpan & muat ulang, urutan terbalik, hapus jadwal, jadwal satu sisi, empat label status, dan batas inklusif lewat route. Seluruh suite: 230 lulus, 4 skip.
