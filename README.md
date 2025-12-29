# 📚 LibraryHub - Sistem Informasi Manajemen Perpustakaan Digital

![Project Status](https://img.shields.io/badge/Status-Completed-success)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-MariaDB-orange)
![Bootstrap](https://img.shields.io/badge/Bootstrap-4-purple)

**LibraryHub** adalah aplikasi berbasis web yang dirancang untuk memodernisasi proses manajemen perpustakaan. Aplikasi ini menangani siklus lengkap peminjaman buku, mulai dari pendaftaran anggota, manajemen stok buku, pengajuan peminjaman, persetujuan admin, pengembalian otomatis dengan kalkulasi denda, hingga pelaporan keuangan.

---

## 🌟 Fitur Utama

### 👥 Modul Anggota (Frontend)
* **Katalog Buku Interaktif:** Pencarian buku, filter kategori, dan indikator ketersediaan stok *real-time*.
* **Sistem Peminjaman Cerdas:** Mengajukan peminjaman dengan memilih durasi (1-7 hari), tanpa perlu input tanggal manual.
* **Dashboard Anggota:** Memantau status peminjaman, riwayat transaksi, dan notifikasi keterlambatan/jatuh tempo.
* **Notifikasi Cerdas:** Peringatan otomatis jika buku mendekati jatuh tempo atau sudah terlambat.

### 🛡️ Modul Admin (Backend)
* **Dashboard Statistik:** Ringkasan visual total buku, anggota aktif, transaksi berjalan, dan pendapatan denda.
* **Manajemen Inventaris:** CRUD Buku dengan manajemen stok otomatis (menggunakan *Database Triggers*).
* **Sirkulasi Peminjaman:**
    * Verifikasi pengajuan peminjaman (Approve/Reject).
    * Proses pengembalian dengan cek kondisi buku (Baik/Rusak/Hilang).
    * **Kalkulasi Denda Otomatis** berdasarkan hari keterlambatan.
* **Manajemen Keuangan:** Pelacakan denda (Belum Dibayar/Lunas) dan pembayaran parsial/cicilan.
* **Laporan & Audit:**
    * Laporan Peminjaman, Buku Populer, dan Keaktifan Anggota.
    * **Audit Log System:** Merekam setiap aktivitas user (Login, Input Data, Hapus Data) demi keamanan.
* **Pengaturan Sistem:** Konfigurasi global (Nama App, Tarif Denda, Durasi Max Pinjam) & Backup Database.
* **Manajemen Pengguna:** Kelola Staff/Admin dan verifikasi pendaftaran anggota baru.

---

## 🛠️ Teknologi yang Digunakan

* **Bahasa Pemrograman:** PHP (Native/Procedural)
* **Database:** MySQL / MariaDB
* **Frontend:** HTML5, CSS3, JavaScript (Vanilla + jQuery), Bootstrap (Admin Template)
* **Fitur Database Lanjutan:**
    * **Stored Procedures:** `SP_Ajukan_Peminjaman`, `SP_Setujui_Peminjaman` untuk integritas transaksi.
    * **Triggers:** Otomatis update stok buku saat peminjaman/pengembalian terjadi.
    * **Views:** Untuk laporan statistik yang kompleks.


## 📂 Struktur Folder

```text
libraryhub/
├── admin/                  # Halaman & Logika Admin
│   ├── includes/           # Header, Footer, Functions
│   ├── admin_manajemen.php # CRUD Admin
│   ├── denda_*.php         # Fitur Keuangan
│   ├── pinjam_*.php        # Fitur Sirkulasi
│   └── ...
├── assets/                 # CSS, JS, Images, Vendors
├── config/                 # Koneksi Database
├── uploads/                # Cover Buku & Foto Profil
├── index.php               # Landing Page & Katalog
├── login.php               # Halaman Login
└── db_perpustakaan_v2.sql  # File Database
