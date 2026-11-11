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

## 3. Warna dasar (mengikuti Figma Make)

| Nama | Hex | Peran |
|---|---|---|
| Biru | `#2563EB` | Identitas, tombol utama, fokus, dan item navigasi aktif. |
| Biru gelap | `#0B1F33` → `#174867` | Gradien panel informasi halaman login. |
| Tinta | `#1E293B` | Teks utama |
| Abu teks | `#64748B` | Teks sekunder |
| Garis | `#E2E8F0` | Border panel, pemisah baris |
| Kanvas | `#F8FAFC` | Latar halaman |
| Permukaan | `#FFFFFF` | Panel, tabel, form |

Status (dipakai di badge, selalu dengan teks, tidak hanya warna):

| Status | Teks | Latar |
|---|---|---|
| Disetujui / selesai | `#0F766E` | `#CCFBF1` |
| Menunggu / draft | `#B45309` | `#FEF3C7` |
| Ditolak | `#B91C1C` | `#FEE2E2` |
| Netral / info | `#475569` | `#F1F5F9` |

Tombol utama memakai biru; aksi destruktif memakai merah status dan selalu menyebut tindakan dengan jelas.

## 4. Tipografi

- **Keluarga acuan Figma: Manrope.** Aplikasi memakai font lokal yang sudah dibundel sebagai fallback agar tampilan tidak bergantung pada layanan font pihak ketiga.
- Angka di tabel & skor: `tabular-nums` (class `num`), bukan font monospace.
- Skala (px): 12 / 13 / 14 (body) / 16 / 20 / 26 (judul halaman) / 34 (angka sorotan).
- Judul halaman: 26px, weight 700, letter-spacing −0.02em.
- Sentence case untuk judul, label, dan tombol. Header tabel mengikuti gaya uppercase ringkas pada prototipe.

## 5. Layout

```
┌──────────┬───────────────────────────────────────────────┐
│ TalentRank  │  Judul halaman                 [Aksi utama]   │
│          │  Satu kalimat keterangan                      │
│ Navigasi │ ─────────────────────────────────────────────  │
│ per role │  Konten (maks 98rem, rata kiri)               │
│          │                                               │
│ ──────── │                                               │
│ Periode  │                                               │
│ Akun     │                                               │
└──────────┴───────────────────────────────────────────────┘
```

- Sidebar putih 256px, item aktif memakai latar biru muda, teks biru, dan garis biru 3px di kiri. Di layar < 1024px sidebar jadi drawer.
- Konten rata kiri. Angka di tabel rata kanan.
- Spasi kelipatan 4px. Jarak antar seksi 32px, antar elemen dalam panel 16px.

## 6. Bentuk & kedalaman

- Radius acuan Figma: kontrol **8px**, panel **12px**, badge berbentuk pil, avatar bulat.
- Panel memakai border 1px `Garis` dan shadow tipis; overlay memakai shadow lebih kuat.
- Gradien hanya untuk panel cerita login; jangan gunakan sebagai dekorasi pada halaman kerja.
- Ringkasan angka memakai kartu terpisah; data tabular tetap memakai tabel.

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
- Fokus keyboard selalu terlihat: ring 2px biru dengan offset 2px.
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
| `<x-stat-strip>` | Kartu ringkasan angka responsif |

## 11. Data

Halaman membaca data melalui `app/Queries/PromotionData.php`; kalkulasi SAW berada di `app/Services/SawCalculator.php` dan layanan terkait. View harus menampilkan data aplikasi, bukan menyalin angka contoh prototipe.
