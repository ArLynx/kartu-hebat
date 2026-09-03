# Kartu Hebat Mahasiswa

Sistem beasiswa daerah (Kabupaten Murung Raya) tempat mahasiswa mendaftar beasiswa, lalu pengajuan diverifikasi lintas dinas → seleksi kabupaten hingga hasil dipublikasikan.

## Language

**Pendaftaran**:
Data pengajuan beasiswa yang diisi mahasiswa lewat wizard 7 langkah (data pribadi, pendidikan, prestasi, orang tua, dokumen, review, submit). Tersimpan di tabel `pendaftarans`.
_Avoid_: Formulir, pengajuan

**Application**:
Catatan kerja (work record) yang dibentuk dari pendaftaran saat submit, menjadi objek yang berjalan di alur verifikasi. Tersimpan di tabel `applications` dan dihubungkan ke pendaftaran lewat `pendaftaran_id`.
_Avoid_: Pengajuan, submission

**Jalur (ApplicationType)**:
Kategori seleksi yang menentukan kriteria dan strategi skoring: AKADEMIK, TIDAK_MAMPU, DISABILITAS, NON_AKADEMIK.
_Avoid_: Tipe pengajuan, track

**Keputusan Verifikasi (MS/TMS)**:
Hasil tunggal seorang operator dinas terhadap sebuah application — MS (Memenuhi Syarat) meneruskan, TMS (Tidak Memenuhi Syarat) final menolak. Tidak ada keputusan BTL.

**Verifikasi Lintas Dinas**:
Tahap verifikasi di mana dinas berwenang memverifikasi berkas dan menetapkan keputusan secara paralel: tiga dinas wajib umum (Dukcapil, Sosial, Pendidikan), ditambah Dinas Kesehatan (Dinkes) untuk jalur Disabilitas dan Parsepor untuk jalur Non-Akademik/Prestasi. Dikelola oleh modul mendalam `AgencyVerificationService` yang mengonsolidasikan autosave penilaian checklist dokumen, pelacakan putaran revisi (`round`), mutasi data profil terverifikasi (desil sosial/pendidikan dan status kependudukan), hingga konsensus lintas dinas. Semua dinas wajib harus memutuskan MS agar aplikasi berlanjut ke Seleksi Kabupaten; jika ada satu dinas memutuskan TMS, aplikasi langsung menjadi TMS.

**Seleksi Kabupaten**:
Tahap akhir di mana sistem secara otomatis menghitung skor dan menentukan peringkat kandidat. Operator mengunduh rekap Excel hasil pemeringkatan untuk ditinjau dan disetujui (ACC) oleh pimpinan. Setelah disetujui, operator mengunggah kembali berkas Excel keputusan final beserta dokumen PDF SK penetapan untuk memublikasikan hasil secara massal. Publikasi ini menetapkan status akhir DITERIMA/DITOLAK, membuat pengumuman resmi ke publik, dan mengirim notifikasi ke mahasiswa.
_Avoid_: County selection

**current_step**:
Kolom lama pada applications yang mencatat langkah wizard; tidak pernah dibaca dan telah dihapus. Langkah wizard mahasiswa tetap dilacak di dalam modul pendaftaran.

## Keterbatasan yang disengaja

**Audit & mass delete**: Trait `Auditable` mencatat event Eloquent (`created`/`updated`/`deleted`). `Model::query()->delete()` (mass delete) melewati event Eloquent sehingga tidak tercatat di `audit_logs`. Disengaja: baris dihapus massal hanya lewat relasi `cascadeOnDelete` atau pembersihan terskala; jika jejak audit mass delete kelak dianggap kritikal, gunakan loop per-instance `->delete()` atau catat sebelum mass delete di titik yang memicu.

**Role desa & kecamatan**: role `operator_desa` dan `operator_kecamatan` masih tersedia (untuk laporan/riwayat) tetapi tidak lagi memverifikasi di alur utama. Tahap verifikasi desa/kecamatan dan status terkait (SUBMITTED, VERIFIKASI_DESA, VERIFIKASI_KECAMATAN, BTL_DESA, BTL_KECAMATAN) sudah dihapus.
