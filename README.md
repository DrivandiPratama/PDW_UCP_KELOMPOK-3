# Sistem Manajemen Kos dan Indekos

> **Project UCP — Pengembangan Desain Web**
> Stack: **PHP murni + MySQL + TailwindCSS**

---

## 👥 Tim Pengembang

| No | Nama | NIM | Role |
|----|------|-----|------|
| 1 | Rangga Alfarizzy | 20240140059 | Project Lead / Backend Core |
| 2 | Bima Eka Setiawan | 20240140089 | Database Engineer |
| 3 | Farhan Rasyid Mustaqim | 20240140102 | Frontend Lead / UI Designer |
| 4 | A.Muh. Fadil Asytar | 20240140133 | Room Catalog & Search |
| 5 | Drivandi Pratama | 20240140061 | Booking & Payment Module |
| 6 | Dzaki Ahmad Fauzi | 20240140082 | Owner Dashboard |
| 7 | Nur Sidik Zainu Ahmad | 20240140177 | Optional Features + QA/Docs |

Detail tanggung jawab tiap anggota: lihat [`docs/tim-assignment.md`](docs/tim-assignment.md).

---

## 🚀 Setup & Cara Menjalankan

### Prasyarat
- **XAMPP** (Apache + MySQL + PHP 8+) — https://www.apachefriends.org/
- Browser modern (Chrome, Edge, Firefox)

### Langkah Instalasi

1. **Letakkan folder project** di `htdocs` XAMPP, sehingga path menjadi:
   ```
   <xampp>/htdocs/PDW-UCP/
   ```

2. **Jalankan Apache & MySQL** di XAMPP Control Panel.

3. **Buat database** lewat phpMyAdmin (http://localhost/phpmyadmin):
   - Nama database: `kos_indekos`
   - Collation: `utf8mb4_unicode_ci`

4. **Import schema database**:
   - Buka database `kos_indekos`
   - Tab **Import** → pilih file `sql/schema.sql` → **Go**

5. **(Opsional) Import data demo kamar/booking/pengumuman**:
   - Import `sql/seed.sql` (data demo — kamar, booking, tagihan, pengumuman)

6. **Sesuaikan konfigurasi (opsional)** di `config/database.php`:
   ```php
   const DB_HOST = 'localhost';
   const DB_NAME = 'kos_indekos';
   const DB_USER = 'root';
   const DB_PASS = '';     // default XAMPP: kosong
   ```

7. **Buka aplikasi**: **http://localhost/PDW-UCP/public/**

> **Akun demo dibuat otomatis.** Saat pertama kali membuka halaman login (jika tabel
> `users` masih kosong), sistem otomatis men-seed 4 akun demo dengan password ter-hash
> bcrypt. **Tidak perlu file setup manual** dan tidak perlu dihapus — aman dijalankan
> di device baru manapun. Tinggal login pakai akun di bawah.

---

## 🔐 Akun Demo

| Role | Email | Password |
|------|-------|----------|
| **Pemilik Kos** | `owner@kos.id` | `owner123` |
| **Penghuni**    | `penghuni@kos.id` | `pengguna123` |
| Penghuni        | `budi@kos.id` | `pengguna123` |
| Penghuni        | `citra@kos.id` | `pengguna123` |

---

## 📋 Fitur

### 🌐 Publik (Calon Penghuni)
- ✅ **Room Catalog** — daftar kamar dengan foto, tipe, ukuran, fasilitas, harga, status
- ✅ **Detail kamar** dengan galeri foto
- ✅ **Filter & Search** berdasarkan tipe / harga / fasilitas / status
- ✅ **Pengumuman** publik

### 👤 Penghuni (setelah disetujui)
- ✅ **Ajukan Sewa** kamar dengan formulir lengkap
- ✅ **Tagihan Saya** — rekap tagihan bulanan + status lunas/belum
- ✅ **Upload Bukti Pembayaran** (validasi MIME & ukuran)
- ✅ Lihat pengumuman

### 🛠️ Pemilik Kos (Owner Dashboard)
- ✅ **Dashboard** statistik: kamar, hunian, pendapatan bulan ini
- ✅ **CRUD Kamar** (foto, fasilitas, harga, status)
- ✅ **Approve / Reject** permohonan sewa → otomatis generate tagihan bulanan
- ✅ **Verifikasi Bukti Pembayaran** → otomatis mark tagihan lunas
- ✅ **Manajemen Penghuni** + tampilan tunggakan
- ✅ **Pengumuman** CRUD
- ✅ **Laporan Keuangan** dengan grafik (Chart.js) + print

---

## 🛡️ Keamanan

- 🔒 Password di-hash dengan `password_hash()` (**bcrypt**)
- 🔒 Semua query menggunakan **PDO prepared statements** (anti SQL injection)
- 🔒 Output di-escape via helper `e()` (anti XSS)
- 🔒 **CSRF token** pada seluruh form sensitif (login, register, booking, dll)
- 🔒 **Session regenerate** setelah login (anti session fixation)
- 🔒 **MIME validation** + size limit pada upload file
- 🔒 `.htaccess` block direct access ke `config/`, `includes/`, `sql/`
- 🔒 PHP execution disabled di folder `uploads/`

---

## 🗂️ Struktur Folder

```
PDW-UCP/
├── README.md                    # File ini
├── .htaccess                    # Block sensitive folders + PHP config
├── config/
│   └── database.php             # Koneksi PDO + konstanta APP
├── includes/
│   ├── auth.php                 # Session, login, role guard
│   ├── helpers.php              # Utility: e(), rp(), CSRF, upload
│   ├── header.php               # Layout atas (navbar + Tailwind CDN)
│   ├── footer.php               # Layout bawah
│   └── admin-sidebar.php        # Sidebar dashboard owner
├── sql/
│   ├── schema.sql               # Struktur tabel (7 tabel)
│   └── seed.sql                 # Data demo (kamar, booking, dll)
├── assets/
│   ├── css/style.css            # Custom styles (badges, buttons, table)
│   ├── js/app.js                # Confirm, preview upload
│   └── img/
├── uploads/                     # File yang di-upload
│   ├── kamar/                   # Foto kamar
│   ├── bukti/                   # Bukti pembayaran
│   └── .htaccess                # Disable PHP execution
├── public/                      # 🌐 Entry point web
│   ├── index.php                # Katalog kamar (publik)
│   ├── detail.php               # Detail kamar
│   ├── booking.php              # Form ajukan sewa
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   ├── pengumuman.php           # List pengumuman
│   ├── tagihan.php              # Billing summary penghuni
│   ├── upload-bukti.php         # Upload bukti pembayaran
│   └── admin/                   # Owner Dashboard
│       ├── index.php            # Dashboard utama
│       ├── kamar.php            # List & CRUD kamar
│       ├── kamar-form.php       # Form tambah/edit kamar
│       ├── booking.php          # Approve/reject permohonan
│       ├── pembayaran.php       # Verifikasi pembayaran
│       ├── penghuni.php         # Daftar penghuni
│       ├── pengumuman.php       # CRUD pengumuman
│       └── laporan.php          # Laporan keuangan + chart
└── docs/
    ├── tim-assignment.md        # Pembagian tugas detail per anggota
    ├── erd.md                   # ERD (Mermaid syntax)
    ├── use-case-diagram.html    # Use Case Diagram (Print → Save as PDF)
    └── activity-diagram.html    # Activity Diagram (Print → Save as PDF)
```

---

## 🗄️ Database Schema

7 tabel dengan relasi terintegrasi:

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun (role: owner / penghuni) |
| `kamar` | Master data kamar (tipe, harga, fasilitas, status) |
| `foto_kamar` | Galeri foto per kamar |
| `booking` | Permohonan sewa (status: pending / ongoing / completed / rejected) |
| `tagihan` | Tagihan bulanan (auto-generated dari booking approved) |
| `pembayaran` | Bukti transfer + verifikasi |
| `pengumuman` | Broadcast info dari pemilik |

📌 Lihat **ERD lengkap** di [`docs/erd.md`](docs/erd.md) (Mermaid diagram).

---

## 📊 Diagram (untuk Submisi)

Dua diagram UML disediakan dalam bentuk HTML **siap-cetak ke PDF**:

| File | Diagram | Cara export PDF |
|------|---------|-----------------|
| `docs/use-case-diagram.html` | **Use Case** — 3 aktor + 16 use case dengan system boundary | Buka di browser → klik tombol **🖨 Print / Save as PDF** atau **Ctrl+P** → Destination: **Save as PDF** |
| `docs/activity-diagram.html` | **Activity** — 3 flow utama (Booking, Approval, Pembayaran) dengan swimlane | Sama — Ctrl+P → Save as PDF |

**Tips print yang baik:**
- Aktifkan **Background graphics: ON** di Chrome/Edge agar warna swimlane ikut tercetak
- Margin: **Default** atau **None**
- Use case: **Landscape**, Activity: **Portrait** (sudah di-set otomatis via CSS `@page`)

---

## 🧪 Alur Kerja Utama (untuk Demo)

### Flow 1 — Calon Penghuni Booking Kamar
1. Buka katalog → filter kamar → pilih → lihat detail
2. Klik **"Ajukan Sewa"** → register / login dulu
3. Isi formulir → submit → dapat kode booking `BK-YYYY-NNN`
4. Status: **pending** (menunggu approval pemilik)

### Flow 2 — Pemilik Approve Booking
1. Login sebagai owner → buka **Dashboard → Permohonan**
2. Tinjau detail → **Setujui** atau **Tolak**
3. Jika disetujui (transaction atomic):
   - Booking status → `ongoing`
   - Kamar status → `terisi`
   - **Sistem auto-generate tagihan bulanan** sebanyak `durasi_bulan`

### Flow 3 — Pembayaran & Verifikasi
1. Penghuni buka **"Tagihan Saya"** → pilih tagihan belum lunas
2. Klik **"Upload Bukti"** → isi nominal, metode, foto → submit
3. Pembayaran masuk dengan status `pending`
4. Owner buka **Dashboard → Pembayaran** → review bukti
5. Klik **Verifikasi** → tagihan otomatis ditandai `lunas`

---

## 🐛 Troubleshooting

### Database connection failed
- Pastikan MySQL aktif di XAMPP
- Cek nama database `kos_indekos` sudah dibuat di phpMyAdmin
- Cek kredensial di `config/database.php`

### Upload gagal "File terlalu besar"
- Limit default: 3 MB untuk gambar
- Edit `php.ini`: `upload_max_filesize = 5M` dan `post_max_size = 6M`
- Restart Apache setelah edit

### Halaman blank putih
- Aktifkan PHP error display di `php.ini`:
  ```ini
  display_errors = On
  error_reporting = E_ALL
  ```
- Cek log di `xampp/apache/logs/error.log`

### Login gagal padahal email & password benar
- Akun demo dibuat otomatis saat tabel `users` kosong. Kalau tabel sudah ada data
  (mis. dari import `seed.sql` versi lama), hapus isi tabel `users` lalu buka ulang
  halaman login agar auto-seed berjalan:
  ```sql
  DELETE FROM users;
  ```
- Pastikan `schema.sql` sudah diimport sehingga tabel `users` tersedia.

---

## 📊 Bobot Penilaian (sesuai PRD)

| Kriteria | Bobot | Modul Terkait |
|----------|-------|---------------|
| Logika Pemesanan & Verifikasi Pembayaran | **35%** | `booking.php`, `upload-bukti.php`, `admin/booking.php`, `admin/pembayaran.php` |
| Fungsionalitas Pemilik Kos (Dashboard)   | **25%** | `admin/index.php` + seluruh `admin/` |
| Implementasi Antarmuka (Front-end)       | **20%** | `header.php`, `style.css`, layout responsive mobile-first |
| Fitur Tambahan                            | **10%** | Pengumuman, Laporan Keuangan, Filter Advanced |
| Kualitas Source Code                      | **10%** | Struktur modular, prepared statements, helpers, dokumentasi |

---

## 🔧 Stack & Tools

- **Backend**: PHP 8+ (PDO MySQL, sessions, password_hash)
- **Database**: MySQL / MariaDB (InnoDB, utf8mb4)
- **Frontend**: TailwindCSS via CDN + custom CSS
- **Chart**: Chart.js (CDN)
- **Icons**: Heroicons inline SVG
- **Fonts**: Plus Jakarta Sans (Google Fonts)
- **Dev environment**: XAMPP

---

## 📜 Lisensi

Project akademik — Universitas Muhammadiyah Yogyakarta © 2026.
