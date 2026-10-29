# DESIGN.md — TalentRank

Pegangan visual untuk semua halaman TalentRank (SPK Promosi Jabatan, metode SAW).
Kalau bikin halaman baru, baca ini dulu. Kalau mau melanggar aturan di sini, ubah dokumen ini dulu supaya tim tahu.

## 1. Subjek, pengguna, tugas utama

- **Subjek:** keputusan promosi jabatan yang selama ini rawan subjektif (kedekatan, "like and dislike"). TalentRank menjawabnya dengan skor yang bisa ditelusuri.
- **Pengguna:** staf HRD, atasan langsung, direktur, admin sistem, dan (opsional) karyawan. Orang kantoran yang membuka aplikasi untuk menyelesaikan tugas, bukan untuk dikagumi.
- **Tugas utama layar:** menunjukkan *kenapa* seseorang ada di peringkat tertentu. Setiap angka akhir harus bisa dibongkar menjadi 5 kriteria dan bobotnya.

## 2. Elemen khas: komposisi skor

Satu-satunya elemen "berani" di aplikasi ini adalah **bar komposisi skor**: skor akhir SAW (Σ wⱼ·rᵢⱼ) digambar sebagai satu bar yang terbagi menjadi 5 segmen kriteria. Lebar tiap segmen = kontribusi kriteria tersebut.

Warna kriteria dipakai **konsisten di seluruh aplikasi** (halaman bobot, input nilai, proses SAW, ranking, approval, portal karyawan), sehingga orang belajar sekali dan bisa membaca di mana saja.

| Kode | Kriteria | Token | Hex |
|---|---|---|---|
| C1 | Masa kerja | `c1` | `#3F6E9A` |
| C2 | Kinerja | `c2` | `#21877A` |
| C3 | Kompetensi | `c3` | `#C08A1E` |
| C4 | Kedisiplinan | `c4` | `#86549B` |
| C5 | Pelatihan & sertifikasi | `c5` | `#5F8F4E` |

Warna kriteria **hanya** untuk data kriteria. Jangan dipakai untuk tombol, status, atau hiasan.

Komponen: `<x-score-bar :parts="..."/>` dan `<x-criteria-dot code="C2"/>`.

## 3. Warna dasar

| Nama | Hex | Peran |
|---|---|---|
| Marun | `#7A1F2B` | Identitas (logo, tombol utama, item navigasi aktif). Diambil dari deck presentasi kelompok. |
| Tinta | `#1A1D24` | Teks utama |
| Abu teks | `#5A6170` | Teks sekunder, header tabel |
| Garis | `#E2E5EA` | Border panel, pemisah baris |
| Kanvas | `#F3F4F6` | Latar halaman (abu dingin, **bukan krem**) |
| Permukaan | `#FFFFFF` | Panel, tabel, form |

Status (dipakai di badge, selalu dengan teks, tidak hanya warna):

| Status | Teks | Latar |
|---|---|---|
| Disetujui / selesai | `#1D6B45` | `#E6F3EC` |
| Menunggu / draft | `#8A5A00` | `#FBF1DC` |
| Ditolak | `#A42A22` | `#FBE9E7` |
| Netral / info | `#3B4250` | `#EEF0F3` |

Tombol tolak/hapus memakai merah status (`#A42A22`) dalam bentuk outline, supaya tidak tertukar dengan tombol utama marun.

## 4. Tipografi

- **Satu keluarga: Plus Jakarta Sans** (Google Fonts) — dirancang untuk identitas kota Jakarta, terasa lokal dan rapi untuk aplikasi kantor.
- Angka di tabel & skor: `tabular-nums` (class `num`), bukan font monospace.
- Skala (px): 12 / 13 / 14 (body) / 16 / 20 / 26 (judul halaman) / 34 (angka sorotan).
- Judul halaman: 26px, weight 700, letter-spacing −0.02em.
- **Sentence case** di mana-mana: judul, label, header tabel, tombol. Tidak ada label ALL CAPS, tidak ada eyebrow kecil di atas setiap judul.

## 5. Layout

```
┌──────────┬───────────────────────────────────────────────┐
│ TalentRank  │  Judul halaman                 [Aksi utama]   │
│          │  Satu kalimat keterangan                      │
│ Navigasi │ ─────────────────────────────────────────────  │
│ per role │  Konten (maks 1200px, rata kiri)              │
│          │                                               │
│ ──────── │                                               │
│ Periode  │                                               │
│ Akun     │                                               │
└──────────┴───────────────────────────────────────────────┘
```

- Sidebar putih 256px, item aktif ditandai garis marun 3px di kiri + teks marun. Di layar < 1024px sidebar jadi drawer.
- Konten rata kiri. Angka di tabel rata kanan.
- Spasi kelipatan 4px. Jarak antar seksi 32px, antar elemen dalam panel 16px.

## 6. Bentuk & kedalaman

- Radius bertingkat: kontrol (input, tombol, badge) **6px**; panel **10px**; tidak ada yang bulat penuh kecuali avatar.
- Panel dibedakan dengan border 1px `Garis`, **tanpa shadow**. Shadow hanya untuk elemen yang benar-benar melayang (dropdown, modal, drawer).
- Tidak ada gradient dekoratif, tidak ada glassmorphism/blur.
- Jangan pecah semua konten jadi kartu yang seragam. Data tabular → tabel. Ringkasan angka → satu strip ringkasan, bukan 4 kartu identik.

## 7. Gerak

- Hanya gerak yang menjawab aksi pengguna: buka drawer, buka detail, konfirmasi simpan. Durasi 150–200ms, `ease-out`.
- Tidak ada animasi fade-in per seksi saat halaman dimuat.
- Hormati `prefers-reduced-motion`.

## 8. Bahasa

- Bahasa Indonesia baku tapi santai, kalimat aktif, singkat.
- Tombol menyebut hasilnya: "Simpan nilai", "Hitung ulang ranking", "Setujui promosi" — bukan "Submit"/"Proses".
- Aksi yang sama memakai kata yang sama di seluruh alur ("Setujui" → toast "Promosi disetujui").
- Pesan error menjelaskan apa yang salah dan cara memperbaiki. Layar kosong mengajak bertindak ("Belum ada periode aktif. Buat periode penilaian untuk mulai.").
- Istilah: **Atasan** (role `manager` di kode), **Pimpinan/Direktur** (role `direktur`).

## 9. Aksesibilitas (batas minimum)

- Kontras teks ≥ 4.5:1.
- Fokus keyboard selalu terlihat: ring 2px marun dengan offset 2px.
- Status tidak boleh hanya warna — selalu ada teks.
- Semua input punya `<label>`.

## 10. Komponen Blade

| Komponen | Kegunaan |
|---|---|
| `<x-app-layout>` | Kerangka halaman ber-sidebar (`title`, `subtitle`, slot `actions`) |
| `<x-panel>` | Wadah ber-border, opsional `title` dan slot `actions` |
| `<x-btn>` | Tombol: `variant` = `primary` / `secondary` / `ghost` / `danger` |
| `<x-badge>` | Status: `tone` = `success` / `warning` / `danger` / `neutral` |
| `<x-score-bar>` | Bar komposisi skor 5 kriteria |
| `<x-criteria-dot>` | Penanda warna kriteria |
| `<x-icon>` | Ikon garis (set Lucide, inline SVG) |
| `<x-stat-strip>` | Strip ringkasan angka |

## 11. Data

Selama backend belum tersambung, data contoh ada di `app/Support/DemoData.php` dan perhitungan SAW asli di `app/Services/SawCalculator.php`. Angka di semua halaman dihitung, bukan diketik manual, sehingga konsisten. Saat backend siap, ganti sumber data di `DemoData` tanpa mengubah view.
