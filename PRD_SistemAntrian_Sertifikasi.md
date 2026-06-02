# PRD Final — Sistem Antrian Sertifikasi Kompetensi
**Versi 1.1 — Status: FINAL, Siap Development**

---

## 1. Overview

Sistem antrian berbasis web untuk **sertifikasi kompetensi SMK** dengan 6 panel ujian yang berjalan bersamaan. Setiap panel mewakili satu jurusan per angkatan. Sistem menampilkan siapa yang **presentasi** dan siapa yang menjadi **observer** secara *real-time* di layar publik, disertai notifikasi suara (text-to-speech).

---

## 2. Konteks Sistem: Sertifikasi Peer-Observer

Dalam sertifikasi ini, peserta **saling menguji satu sama lain**:
- **Presenter**: 1 siswa yang sedang melakukan uji kompetensi
- **Observer 1 & 2**: 2 siswa berikutnya dalam antrian yang bertugas mengamati/menilai

Contoh antrian RPL Kelas 10:
```
Urutan  | Nama Siswa    | Status saat giliran ke-1
--------|---------------|---------------------------
1       | Andi          | 🎤 Presentasi
2       | Budi          | 👁️ Observer 1
3       | Citra         | 👁️ Observer 2
4       | Dina          | ⏳ Menunggu
5       | Eko           | ⏳ Menunggu
...
```
Saat Andi selesai → Budi presentasi, Citra & Dina jadi observer, dst.

---

## 3. Panel Sesi (6 Panel Simultan)

| Panel | Angkatan | Jurusan |
|-------|----------|---------|
| Panel 1 | Kelas 10 | RPL (Rekayasa Perangkat Lunak) |
| Panel 2 | Kelas 10 | DKV (Desain Komunikasi Visual) |
| Panel 3 | Kelas 10 | TKJ (Teknik Komputer dan Jaringan) |
| Panel 4 | Kelas 11 | RPL |
| Panel 5 | Kelas 11 | DKV |
| Panel 6 | Kelas 11 | TKJ |

Setiap panel memiliki antrian **independen** dan dapat dikelola secara terpisah.

---

## 4. Roles & Akses

| Role | Akses | Keterangan |
|------|-------|-----------|
| **Super Admin** | Login email/password | Kelola semua 6 panel, data peserta, import Excel |
| **Panel Operator** | Login per panel (PIN atau akun per jurusan) | Operasikan tombol panggil untuk 1 panel tertentu |
| **Publik (Peserta)** | Tanpa login | Lihat display board semua panel |

> **Catatan**: Observer adalah siswa biasa, **tidak perlu login**. Nama mereka muncul otomatis di layar berdasarkan posisi antrian.

---

## 5. Fitur Lengkap

### 5.1 Admin Dashboard
- [ ] Kelola 6 panel (buka/tutup sesi per panel)
- [ ] CRUD data peserta per panel
- [ ] **Import Excel** dengan:
  - Download template Excel (.xlsx)
  - Upload file → **preview data** sebelum konfirmasi import
  - Validasi data (nama kosong, duplikat, dll)
- [ ] Atur ulang urutan antrian (drag & drop / shuffle)
- [ ] Reset antrian
- [ ] Monitor semua 6 panel dari satu layar (overview)

### 5.2 Panel Operator (per Jurusan)
- [ ] Halaman operasional per panel
- [ ] Tampilkan status terkini: siapa presenter & 2 observer saat ini
- [ ] Tombol **"Mulai / Panggil Giliran Berikutnya"**
- [ ] Tombol **"Selesai"** → geser antrian (presenter selesai, observer naik jadi presenter)
- [ ] Tombol **"Lewati"** → skip presenter, taruh di akhir antrian
- [ ] Lihat daftar antrian lengkap panel tersebut

### 5.3 Display Board (Layar Publik)
- [ ] URL: `/display` → tampilkan **semua 6 panel** dalam grid
- [ ] URL: `/display/{panel}` → tampilkan **1 panel** fullscreen (untuk TV per ruangan)
- [ ] Setiap panel card menampilkan:
  - Nama panel (misal: "RPL — Kelas 10")
  - 🎤 **Presenter**: [Nama Siswa]
  - 👁️ **Observer 1**: [Nama Siswa]
  - 👁️ **Observer 2**: [Nama Siswa]
  - Antrian berikutnya (3–5 nama)
  - Progress: X / Y selesai
- [ ] **Animasi highlight** saat ada perubahan (nama baru dipanggil)
- [ ] **Text-to-Speech**: Saat peserta dipanggil, browser membacakan:
  > *"Perhatian! [Nama Presenter] silakan menuju meja ujian. Observer: [Nama Observer 1] dan [Nama Observer 2]"*
- [ ] Update **real-time** via WebSocket (Laravel Reverb)
- [ ] Fallback polling setiap 5 detik jika WebSocket tidak tersedia

### 5.4 Laporan
- [ ] Riwayat pemanggilan per panel (waktu mulai, selesai, durasi)
- [ ] Export ke Excel per panel atau semua panel
- [ ] Statistik: rata-rata waktu ujian

---

## 6. Alur Sistem

```
[Admin] Setup Sesi
    └── Buat 6 panel → Import peserta per panel (Excel + Preview)
    └── Buka sesi semua panel

[Operator Panel RPL Kelas 10]
    └── Klik "Mulai" → Sistem assign: Andi=Presenter, Budi=Observer1, Citra=Observer2
    └── Broadcast ke semua display board
    └── TTS berbunyi: "Andi silakan maju..."

[Display Board /display]
    └── Semua 6 panel ter-update real-time
    └── Panel RPL Kelas 10: highlight Andi, Budi, Citra

[Operator]
    └── Klik "Selesai" → Andi selesai
    └── Sistem geser: Budi=Presenter, Citra=Observer1, Dina=Observer2
```

---

## 7. Status Peserta

| Status | Icon | Keterangan |
|--------|------|-----------|
| `waiting` | ⏳ | Menunggu giliran |
| `presenting` | 🎤 | Sedang presentasi/ujian |
| `observing` | 👁️ | Sedang menjadi observer |
| `done` | ✅ | Selesai semua (sudah presentasi DAN sudah jadi observer) |
| `skipped` | ⏭️ | Dilewati, masuk antrian akhir |

> **Logika done**: Siswa dianggap selesai setelah **presentasi** selesai (bukan saat jadi observer, karena observer adalah bagian dari menunggu giliran).

---

## 8. Tech Stack Final

| Layer | Teknologi | Alasan |
|-------|-----------|--------|
| **Backend** | Laravel 11 | Framework utama |
| **Frontend** | Blade + Alpine.js | Interaktivitas ringan tanpa SPA |
| **Real-time** | Laravel Reverb (WebSocket) | Built-in Laravel, gratis, self-hosted |
| **Database** | MySQL | Standar server sekolah |
| **UI** | Tailwind CSS + custom CSS | Modern, responsif |
| **Import/Export** | Maatwebsite/Laravel-Excel | Mature, support preview |
| **Auth** | Laravel Breeze | Simple auth untuk admin/operator |
| **TTS** | Web Speech API (browser-native) | Tidak perlu library tambahan |

---

## 9. Struktur Database

### `panels`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint | PK |
| name | string | "RPL Kelas 10" |
| grade | enum | `10`, `11` |
| major | enum | `RPL`, `DKV`, `TKJ` |
| status | enum | `inactive`, `active`, `closed` |
| operator_pin | string | PIN 4 digit untuk operator panel |

### `participants`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint | PK |
| panel_id | bigint | FK ke panels |
| name | string | Nama lengkap siswa |
| student_number | string | NIS |
| queue_order | int | Urutan antrian |
| status | enum | `waiting`, `presenting`, `observing`, `done`, `skipped` |
| presented_at | timestamp | Waktu mulai presentasi |
| done_at | timestamp | Waktu selesai |

### `queue_logs`
| Kolom | Tipe | Keterangan |
|-------|------|-----------|
| id | bigint | PK |
| panel_id | bigint | FK |
| presenter_id | bigint | FK ke participants |
| observer1_id | bigint | FK ke participants |
| observer2_id | bigint | FK ke participants |
| started_at | timestamp | |
| ended_at | timestamp | |
| action | enum | `started`, `done`, `skipped` |

---

## 10. Routes

| Route | Akses | Deskripsi |
|-------|-------|-----------|
| `GET /` | Publik | Redirect ke `/display` |
| `GET /display` | Publik | Display Board semua 6 panel |
| `GET /display/{panel}` | Publik | Display Board 1 panel fullscreen |
| `GET /admin` | Admin | Dashboard overview semua panel |
| `GET /admin/panels/{panel}/participants` | Admin | Kelola peserta per panel |
| `POST /admin/panels/{panel}/import` | Admin | Import Excel + preview |
| `GET /admin/panels/{panel}/template` | Admin | Download template Excel |
| `GET /operator/{panel}` | Operator | Panel operasional (butuh PIN) |
| `POST /operator/{panel}/next` | Operator | Panggil giliran berikutnya |
| `POST /operator/{panel}/done` | Operator | Tandai presenter selesai |
| `POST /operator/{panel}/skip` | Operator | Lewati presenter |
| `GET /admin/reports` | Admin | Laporan & export |

---

## 11. Template Excel (Import Peserta)

Kolom yang wajib ada di template:
| Kolom | Keterangan | Contoh |
|-------|-----------|--------|
| NIS | Nomor Induk Siswa | `123456` |
| Nama Lengkap | Nama siswa | `Ahmad Fauzi` |
| Urutan (opsional) | Nomor urut antrian | `1` (jika kosong, urut otomatis) |

---

## 12. Non-Functional Requirements

| Aspek | Target |
|-------|--------|
| Real-time latency | < 500ms dari klik → display update |
| Concurrent users | 200+ penonton display bersamaan |
| Browser TTS | Chrome, Edge (support Web Speech API) |
| Responsive | Display board optimal di TV 1080p & tablet |
| Server | Ubuntu/Debian + Nginx + PHP 8.2+ + MySQL |

---

## 13. Milestone Development

| Fase | Konten | Estimasi |
|------|--------|----------|
| **Fase 1** | Setup Laravel, Auth, DB Migration, Seeder 6 panel | 0.5 hari |
| **Fase 2** | Model, Relationship, Queue Logic | 0.5 hari |
| **Fase 3** | Admin: CRUD Peserta, Import Excel + Preview, Template | 1 hari |
| **Fase 4** | Operator Panel: Tombol Panggil/Selesai/Skip | 0.5 hari |
| **Fase 5** | Display Board UI + Real-time Reverb + TTS | 1 hari |
| **Fase 6** | Laporan, Export, Polish UI, Testing | 1 hari |
| **Total** | | **~4–5 hari** |

---

*PRD Final — Semua keputusan desain terkonfirmasi. Siap masuk fase implementasi.*
