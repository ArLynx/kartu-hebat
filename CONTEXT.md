# Kartu Hebat Mahasiswa

Sistem beasiswa daerah (Kabupaten Murung Raya) tempat mahasiswa mendaftar beasiswa, lalu pengajuan diverifikasi berjenjang desa → kecamatan → lintas dinas → seleksi kabupaten hingga hasil dipublikasikan.

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

**Keputusan Verifikasi (MS/BTL/TMS)**:
Hasil tunggal seorang operator terhadap sebuah application — MS (Memenuhi Syarat) meneruskan, BTL (Butuh Perbaikan) mengembalikan ke mahasiswa untuk disubmit ulang, TMS (Tidak Memenuhi Syarat) final menolak.

**Verifikasi Desa**:
Tahap pertama alur, dilakukan operator desa/kelurahan tempat mahasiswa berdomisili.

**Verifikasi Kecamatan**:
Tahap kedua alur, dilakukan operator kecamatan. Tidak ada status antara — application masuk langsung dari verifikasi desa.

**Verifikasi Lintas Dinas**:
Tahap ketiga alur, tiga dinas (Dukcapil, Sosial, Pendidikan) memverifikasi paralel. Semua dinas MS sebelum application lolos ke seleksi.

**Seleksi Kabupaten**:
Tahap keempat, operator kabupaten menghitung skor, menentukan peringkat, dan memutuskan DITERIMA/DITOLAK lalu memublikasikan hasil.
_Avoid_: County selection

**current_step**:
Kolom lama pada applications yang mencatat langkah wizard; tidak pernah dibaca dan telah dihapus. Langkah wizard mahasiswa tetap dilacak di dalam modul pendaftaran.
