# TalentRank

Sistem Pendukung Keputusan kelayakan promosi jabatan karyawan dengan metode **Simple Additive Weighting (SAW)**.
Tugas mata kuliah SPK, Kelompok 2 — Sistem Informasi, Politeknik Negeri Subang.

Laravel 13 · PHP 8.3+ · MySQL · Blade + Tailwind CSS + Alpine.js (Vite)

## Menjalankan di lokal (Laragon)

```bash
composer install
npm install
cp .env.example .env          # sudah diarahkan ke MySQL Laragon, database: spk_promosi_jabatan
php artisan key:generate
php artisan migrate --seed    # skema + data contoh
npm run dev                   # biarkan jalan (hot reload)
php artisan serve             # buka http://127.0.0.1:8000
```

Buat database `spk_promosi_jabatan` dulu di HeidiSQL/phpMyAdmin Laragon. Untuk mengulang data dari awal: `php artisan migrate:fresh --seed`.

### Akun demo (password: `password`)

| Role | Email |
|---|---|
| Admin | admin@talentrank.test |
| HRD | hrd@talentrank.test |
| Atasan | atasan@talentrank.test (bawahan: departemen Operasional) |
| Direktur | direktur@talentrank.test |
| Karyawan | karyawan@talentrank.test (Rian Pratama) |

Atasan lain: `ratna.sari@`, `andi.kurniawan@`, `yusuf.hidayat@`, `lestari.h@` (domain sama). `wawan.setiawan@` sengaja nonaktif.

## Alur

```
HRD buat periode (draft) → atur bobot kriteria (total 100%) → mulai periode
   ↓
Atasan isi nilai bawahannya (draft boleh belum lengkap) → kirim (terkunci; HRD bisa membuka kembali)
   ↓
HRD "Hitung ulang ranking" → SAW: normalisasi → skor → peringkat; 3 teratas direkomendasikan
   ↓
Direktur setujui / tolak (tolak wajib alasan)
   ↓
HRD tutup periode (syarat: ranking sudah dihitung & semua rekomendasi diputuskan) → semua terkunci
```

Karyawan bisa melihat skor dan status rekomendasinya sendiri. Admin mengelola akun & role (tidak ikut menilai).

## Kriteria & rumus

| Kode | Kriteria | Jenis | Bobot awal | Diisi oleh |
|---|---|---|---|---|
| C1 | Masa kerja (tahun) | benefit | 15% | otomatis dari tanggal bergabung |
| C2 | Kinerja (0–100) | benefit | 30% | atasan |
| C3 | Kompetensi (0–100) | benefit | 25% | atasan |
| C4 | Kedisiplinan (hari terlambat/absen) | cost | 15% | atasan |
| C5 | Pelatihan & sertifikasi (jumlah) | benefit | 15% | atasan |

- Normalisasi: benefit `r = x / max(x)`, cost `r = min(x) / x`
- Skor akhir: `V = Σ wⱼ · rᵢⱼ`
- Seluruh jejak disimpan: `assessment_details` (nilai mentah) → `normalized_scores` (r dan w·r) → `ranking_results` (V, peringkat, keputusan).
- Bobot **dan jenis** (benefit/cost) disimpan per periode di `criteria_weights`, jadi mengubah jenis di periode baru tidak mengubah periode lama.
- Catatan: pada kriteria cost, bila nilai terkecil 0 (mis. 0 hari absen) seluruh kolom digeser +1 sehingga r = `(min+1)/(x+1)`. Tanpa geseran, `min/x` membuat semua nilai di atas 0 mendapat r = 0 (1 hari dan 30 hari dianggap sama buruknya). Kriteria tanpa nilai 0 tetap memakai `min/x` standar. Halaman Proses SAW menampilkan keterangan saat geseran dipakai.

## Fitur pendukung keputusan

| Fitur | Role | Isi |
|---|---|---|
| Analisis sensitivitas (`/hrd/sensitivitas`, `/direktur/sensitivitas`) | HRD, direktur | Skenario geser bobot ±5/10/20 poin, rentang aman bobot tiap kriteria, selisih skor kandidat terakhir yang direkomendasikan, simulasi bobot sendiri. Hanya simulasi, tidak menyimpan apa pun. |
| Log aktivitas (`/hrd/log-aktivitas`, `/admin/log-aktivitas`) | HRD, admin | Siapa melakukan apa dan kapan (bobot lama → baru, penilaian, hitung SAW, keputusan direktur, akun, login). Cari, filter, paginasi, unduh CSV. Hanya bisa dibaca. |
| Grafik | HRD, direktur, karyawan | Sebaran skor akhir, ringkasan keputusan direktur, tren skor antar periode. |

Setelah `git pull` yang membawa migrasi baru, jalankan `php artisan migrate`.

## Profil, pengaturan, bahasa (`/akun`, semua role)

- **Profil**: ubah nama & nomor HP (email diatur admin). Foto profil: unggah/seret file, potong bulat (perbesar, putar, balik), 8 filter + kecerahan/kontras, hapus foto, atau pilih avatar siap pakai / inisial nama (pratinjau dulu, tersimpan setelah tombol **Simpan**). Server membaca ulang foto dengan GD (maks. 4096×4096, 5 MB) dan menyimpannya sebagai WebP 512×512 di disk privat; foto disajikan lewat `/avatar/{user}/{versi}` khusus pengguna yang login.
- **Keamanan**: ganti password, daftar sesi login aktif (browser, OS, IP), keluarkan satu sesi atau semua perangkat lain (wajib password).
- **Tampilan**: tema terang / gelap / ikuti sistem, ukuran teks kecil / sedang / besar, bahasa Indonesia / English. Semua **tersimpan di akun** (`users.theme`, `text_size`, `locale`) sehingga sama di setiap perangkat & setiap login; diterapkan sebelum halaman tergambar (tanpa kedip).
- **Bahasa**: teks dibungkus `__()`; kamus Inggris di `lang/en.json` (nama peran: kunci `role:*` di `lang/id.json` & `lang/en.json`). Komponen judul halaman, panel, keadaan kosong, status, menu, dan tombol menerjemahkan otomatis. Pesan server & notifikasi mengikuti bahasa pembaca. Tes `LocalePreferenceTest` gagal bila ada teks `__()` tanpa padanan Inggris.
- **Hapus akun**: hanya **admin** (halaman Pengguna & role, wajib password admin). Admin tidak bisa menghapus akunnya sendiri; admin/HRD/direktur aktif terakhir tidak bisa dihapus. Data penilaian & keputusan tetap tersimpan.
- **Notifikasi**: lonceng & menu akun di bar atas; riwayat lengkap di `/notifikasi`.
- **Intro logo**: halaman login memutar animasi logo (bulatan naik tangga, lalu logo mengecil tepat menimpa logo login) setiap dibuka/dimuat ulang, kecuali sesudah login gagal, saat ganti bahasa, atau bila "kurangi gerakan" aktif. Keluar: bulatan turun tangga.
- Setelah `git pull`: `php artisan migrate` lalu `npm install && npm run build`.

## Struktur kode

| Lokasi | Isi |
|---|---|
| `database/migrations` | Skema sesuai ERD (roles, users, departments, positions, employees, assessment_periods, criteria, criteria_weights, assessments, assessment_details, normalized_scores, ranking_results) |
| `app/Models` | Model Eloquent + relasi |
| `app/Services/SawCalculator.php` | Rumus SAW murni (tanpa database), diuji unit |
| `app/Services/*Service.php` | Aturan bisnis: penilaian, perhitungan ranking (transaksi + lock), periode, keputusan direktur |
| `app/Http/Requests` | Validasi input + pesan bahasa Indonesia |
| `app/Queries/PromotionData.php` | Query baca untuk halaman (paginasi & filter di database, tanpa N+1) |
| `app/Http/Controllers` | Tipis: validasi → service → redirect/view |
| `resources/views` | Tampilan (Blade) |

Pelanggaran aturan bisnis selalu dikembalikan sebagai pesan validasi (toast + pesan di field), tidak pernah error 500.

## Keamanan

- Akses per role lewat middleware `role:`; atasan hanya bisa membuka/menilai bawahannya (`employees.supervisor_id`).
- Login dikunci 5 menit setelah 5 kali gagal; sesi berakhir setelah 30 menit tidak aktif.
- Akun nonaktif langsung dikeluarkan dari semua sesi; admin tidak bisa menonaktifkan/mengubah role dirinya sendiri.
- Admin tidak menentukan password: sistem membuat password sementara acak (ditampilkan sekali), pengguna menggantinya di menu *Ganti password*.
- Header keamanan (CSP ber-nonce, X-Frame-Options, nosniff, Referrer-Policy, HSTS di HTTPS); logout hanya via POST.
- Ekspor CSV aman dari formula injection; pencarian LIKE di-escape.
- Seeder menolak berjalan di production tanpa `DEMO_SEED_PASSWORD` (≥ 12 karakter).

## Test

```bash
php artisan test
```

Mencakup: rumus SAW, alur lengkap satu periode, validasi & aturan bisnis, hak akses tiap role, keamanan akun,
**crawler yang login sebagai setiap role lalu membuka semua link dan mengirim semua form** (tidak boleh ada error 500),
serta uji performa (jumlah query per halaman tidak boleh naik saat data bertambah).
