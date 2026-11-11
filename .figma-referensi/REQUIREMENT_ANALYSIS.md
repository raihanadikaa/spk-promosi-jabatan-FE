# Analisis Requirement — SPK Promosi Jabatan Metode SAW

Dokumen ini merangkum hasil telaah `srs kelompok 2.docx` sebagai acuan utama dan `Requirement SRS Terbaru.xlsx` sebagai pendukung. Nilai status pada Excel tidak dipakai sebagai bukti implementasi.

## Aktor dan Hak Akses

| Aktor | Hak akses utama |
| --- | --- |
| Admin | CRUD user, melihat role tetap, dan pengelolaan teknis. Tidak menilai. |
| HRD | CRUD data master, periode, kriteria dan bobot; monitor assessment; menjalankan SAW; melihat seluruh ranking. |
| Atasan / Manager | Menilai dan memperbarui assessment bawahan langsung selama diizinkan; melihat ranking bawahan dengan ranking global. |
| Pimpinan / Direktur | Melihat seluruh hasil, meninjau kandidat, menyetujui, menolak, atau meminta review ulang. Tidak menginput nilai. |
| Karyawan | Opsional. Hanya melihat nilai dan riwayat milik sendiri setelah periode ditutup. |

Setiap user memiliki tepat satu role. Menu prototipe berubah sesuai role yang terdeteksi saat login.

## Halaman dan Cakupan

| Kode | Halaman | Status prototipe |
| --- | --- | --- |
| UI-01 | Login | Tersedia, validasi lokal, routing berdasarkan akun |
| UI-02 | Dashboard Admin | Tersedia |
| UI-03 | Manajemen User & Role | Tersedia, tab user dan role read-only |
| UI-04 | Dashboard HRD | Tersedia |
| UI-05 | Data Karyawan | Tersedia |
| UI-06 | Data Departemen & Jabatan | Tersedia |
| UI-07 | Periode Penilaian | Tersedia |
| UI-08 | Kriteria & Bobot | Tersedia, validasi total 100% |
| UI-09 | Monitor Status Assessment | Tersedia |
| UI-10 | Proses Perhitungan SAW | Tersedia, matriks X/R/W/V dan konfirmasi |
| UI-11 | Hasil & Ranking | Tersedia untuk HRD dan Pimpinan |
| UI-12 | Dashboard Atasan | Tersedia |
| UI-13 | Penilaian / Input Nilai | Tersedia, validasi 0–100, draf dan kirim |
| UI-14 | Hasil Rekomendasi Bawahan | Tersedia dengan cakupan bawahan |
| UI-15 | Dashboard Pimpinan | Tersedia |
| UI-16 | Daftar Rekomendasi Promosi | Tersedia |
| UI-17 | Detail & Persetujuan Promosi | Tersedia, alasan reject wajib |
| UI-18 | Nilai Saya | Tersedia dan ditandai sebagai fitur opsional |

Dashboard Karyawan ditampilkan hanya sebagai pintu masuk role opsional sesuai struktur menu SRS. Halaman ranking Pimpinan memakai komponen hasil bersama sesuai matriks hak akses.

## Aturan SAW yang Diakomodasi

- Kriteria awal: C1 Masa Kerja 15%, C2 Kinerja/KPI 30%, C3 Kompetensi Teknis 25%, C4 Kedisiplinan & Etika 15%, C5 Pelatihan & Sertifikasi 15%.
- Semua kriteria awal bertipe benefit; UI tetap dapat menampilkan tipe benefit/cost.
- Normalisasi benefit `rᵢⱼ = xᵢⱼ / max(xᵢⱼ)` dan cost `rᵢⱼ = min(xᵢⱼ) / xᵢⱼ`.
- Nilai preferensi `Vᵢ = Σ wⱼ·rᵢⱼ`, presisi tampilan 4 desimal.
- Ranking menurun berdasarkan V; ambang rekomendasi mengikuti BR-08.
- Peringkat 1–3 dengan V ≥ 0,900: Direkomendasikan.
- V ≥ 0,850 selain kondisi tersebut: Memenuhi Syarat.
- V < 0,850: Dipertimbangkan.
- Data contoh matriks dan ranking berasal dari Appendix B.6 SRS.

## Interaksi Prototipe

- Login dengan validasi field dan kebijakan format password.
- Navigasi dan menu berbasis role, pemilih periode, notifikasi, logout, dan ubah password.
- Pencarian, filter, paginasi, tab, modal form, dialog konfirmasi, toast sukses, status kosong/validasi pada konteks relevan.
- Validasi total bobot 100%, nilai assessment 0–100, dan alasan penolakan wajib.
- Simulasi lokal untuk tambah data, impor, pengingat, ekspor, perhitungan, dan keputusan.

Tidak ada backend nyata. Autentikasi, penyimpanan, email, ekspor berkas, dan kalkulasi pada prototipe disimulasikan secara lokal.

## Requirement Opsional / Prioritas Sedang

- Portal Karyawan dan role Karyawan adalah opsional; prototipe tersedia namun diberi penanda.
- Impor Excel/CSV, salin bobot periode lalu, dan simulasi bobot bersifat `sebaiknya` / prioritas sedang. Impor dan salin bobot disimulasikan; simulasi bobot tidak dijadikan alur wajib.
- Penautan user ke karyawan bersifat opsional.

## Konflik yang Memerlukan Konfirmasi

SRS Word tetap menjadi acuan sementara:

1. Waktu muat ranking 1.000 karyawan: Word ≤ 3 detik, Excel ≤ 10 detik.
2. Waktu ekspor PDF/XLS: Word ≤ 10 detik, Excel ≤ 5 detik.
3. Penguncian akun setelah lima kali gagal login: FR-AUTH-07 Word menyebut 15 menit, NFR-SEC-05 Excel menyebut 5 menit. Teks prototipe sementara menggunakan 15 menit.

## Belum Terakomodasi / Belum Terverifikasi

- Integrasi backend, RBAC sisi server, hash bcrypt, session timeout, HTTP 403, audit log, SMTP, backup, retensi data, dan keamanan transport belum diimplementasikan karena prototipe frontend lokal.
- Ekspor PDF/XLS/CSV, impor Excel/CSV, dan notifikasi email hanya berupa feedback simulasi.
- Target performa dan concurrency belum dapat diverifikasi pada prototipe.
- Penerbitan SK, penggajian, dan integrasi HRIS sengaja tidak dibuat karena berada di luar ruang lingkup.
- Lampiran pendukung kandidat disebut pada alur detail, tetapi format, sumber, dan aksi pengelolaannya tidak dijelaskan. Tidak dibuat mekanisme unggah baru; perlu konfirmasi.
- Nilai usulan jabatan pada data dummy hanya untuk memperlihatkan struktur UI, bukan aturan penentuan jabatan.
