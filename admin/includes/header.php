<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Perpustakaan Admin</title>

    <link rel="stylesheet" href="vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="vendors/feather/feather.css">
    <link rel="stylesheet" href="vendors/base/vendor.bundle.base.css">

    <link rel="stylesheet" href="vendors/flag-icon-css/css/flag-icon.min.css" />
    <link rel="stylesheet" href="vendors/font-awesome/css/font-awesome.min.css">

    <link rel="stylesheet" href="css/style.css">
    <link rel="shortcut icon" href="images/favicon.png" />

    <style>
    /* ================================================================== */
    /* === FORCE OVERRIDE: HAPUS TOTAL WARNA PINK DARI TEMPLATE === */
    /* ================================================================== */

    /* 1. Paksa Background Pink (#ec37fc) jadi Biru */
    .bg-primary,
    .btn-primary,
    .badge-primary,
    .progress-bar-primary {
        background-color: #3182ce !important;
        /* Biru Laut */
        border-color: #3182ce !important;
    }

    /* 2. Paksa Warna Teks Pink (#ec37fc) jadi Biru */
    .text-primary,
    a.text-primary,
    .navbar .navbar-brand,
    .sidebar .nav .nav-item.active>.nav-link i,
    .sidebar .nav .nav-item.active>.nav-link .menu-title,
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] i,
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] .menu-title {
        color: #3182ce !important;
    }

    /* 3. Paksa Border Pink jadi Biru */
    .border-primary {
        border-color: #3182ce !important;
    }

    /* 4. Paksa Hover Effect pada Tombol/Link (dari Pink Tua ke Biru Tua) */
    .btn-primary:hover,
    .btn-primary:focus,
    .btn-primary:active,
    a.bg-primary:hover,
    a.text-primary:hover {
        background-color: #2c5aa0 !important;
        /* Biru Gelap */
        border-color: #2c5aa0 !important;
        color: white !important;
    }

    /* 5. Khusus Text Hover agar tidak putih */
    a.text-primary:hover {
        color: #2c5aa0 !important;
        background-color: transparent !important;
    }

    /* 6. Hapus total background pink di Sidebar saat aktif */
    .sidebar .nav .nav-item.active>.nav-link,
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] {
        background: #ebf8ff !important;
        /* Biru Sangat Muda */
    }

    /* 7. Icon Sidebar saat aktif */
    .sidebar .nav .nav-item.active>.nav-link i,
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] i {
        color: #3182ce !important;
    }

    :root {
        --primary: #3182ce;
        /* Biru Laut Utama */
        --primary-dark: #2c5aa0;
        /* Biru Laut Gelap */
        --soft-blue: #ebf8ff;
        /* Biru Laut Muda (Background) */
        --text-dark: #2d3748;
        --text-gray: #718096;
    }

    body {
        background-color: #f8fafc;
        font-family: 'Segoe UI', sans-serif;
    }

    /* --- 1. NAVBAR STYLING --- */
    .navbar .navbar-brand-wrapper {
        background: white !important;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .navbar .navbar-brand {
        color: var(--primary) !important;
        font-weight: 800;
        font-size: 1.5rem;
    }

    .navbar .navbar-menu-wrapper {
        background: white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.03);
    }

    /* --- 2. SIDEBAR STYLING (FIX WARNA) --- */
    .sidebar {
        background: white;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.02);
    }

    /* User Profile Widget */
    .user-profile {
        background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
        margin: 20px;
        padding: 20px;
        border-radius: 20px;
        text-align: center;
        border: 1px solid #e2e8f0;
        animation: fadeInDown 0.8s ease;
    }

    .user-profile img {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 10px;
    }

    .user-profile .user-name {
        font-weight: 700;
        color: var(--primary-dark);
        font-size: 1rem;
    }

    .user-profile .user-designation {
        font-size: 0.8rem;
        color: var(--text-gray);
    }

    /* --- MENU ITEMS (PERBAIKAN WARNA PINK KE BIRU) --- */
    .sidebar .nav .nav-item {
        margin-bottom: 5px;
        transition: all 0.3s;
    }

    .sidebar .nav .nav-item .nav-link {
        border-radius: 0 50px 50px 0;
        margin-right: 20px;
        color: var(--text-gray);
        /* Warna Default Abu */
        font-weight: 500;
        transition: all 0.3s ease;
        background: transparent;
    }

    .sidebar .nav .nav-item .nav-link i.menu-icon {
        color: #cbd5e0;
        font-size: 1.2rem;
        transition: all 0.3s;
    }

    /* EFEK HOVER: Background Biru Muda, Teks Biru Laut */
    .sidebar .nav .nav-item:hover .nav-link {
        background: var(--soft-blue) !important;
        /* Ganti Pink ke Biru Muda */
        color: var(--primary) !important;
        /* Teks jadi Biru */
        padding-left: 25px;
        /* Efek geser */
    }

    .sidebar .nav .nav-item:hover .nav-link i.menu-icon {
        color: var(--primary) !important;
    }

    /* STATE ACTIVE: Sama dengan Hover agar konsisten */
    .sidebar .nav .nav-item.active .nav-link {
        background: var(--soft-blue) !important;
        color: var(--primary) !important;
    }

    .sidebar .nav .nav-item.active .nav-link i.menu-icon {
        color: var(--primary) !important;
    }

    /* Menu Collapse (Dropdown) saat dibuka */
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] {
        background: var(--soft-blue) !important;
        color: var(--primary) !important;
    }

    /* SUB MENU (Dropdown Items) */
    .sidebar .nav .sub-menu {
        background: white !important;
        /* Pastikan background putih, bukan pink */
        padding-left: 20px;
    }

    .sidebar .nav .sub-menu .nav-item .nav-link {
        font-size: 0.9rem;
        padding-left: 40px;
        color: var(--text-gray);
        background: transparent !important;
    }

    /* Hover pada Sub Menu */
    .sidebar .nav .sub-menu .nav-item .nav-link:hover {
        color: var(--primary) !important;
        /* Teks jadi Biru */
        background: transparent !important;
        padding-left: 45px;
        /* Sedikit geser */
    }

    .sidebar .nav .sub-menu .nav-item::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 50%;
        width: 6px;
        height: 6px;
        background: #cbd5e0;
        border-radius: 50%;
        transform: translateY(-50%);
        transition: background 0.3s;
    }

    .sidebar .nav .sub-menu .nav-item:hover::before {
        background: var(--primary);
        /* Dot jadi Biru */
    }

    /* --- 3. ANIMATION --- */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .sidebar .nav .nav-item {
        animation: fadeInLeft 0.5s ease forwards;
        opacity: 0;
    }

    .sidebar .nav .nav-item:nth-child(1) {
        animation-delay: 0.1s;
    }

    .sidebar .nav .nav-item:nth-child(2) {
        animation-delay: 0.2s;
    }

    .sidebar .nav .nav-item:nth-child(3) {
        animation-delay: 0.3s;
    }

    .sidebar .nav .nav-item:nth-child(4) {
        animation-delay: 0.4s;
    }

    .sidebar .nav .nav-item:nth-child(5) {
        animation-delay: 0.5s;
    }

    .sidebar .nav .nav-item:nth-child(6) {
        animation-delay: 0.6s;
    }

    .sidebar .nav .nav-item:nth-child(7) {
        animation-delay: 0.7s;
    }

    /* --- GLOBAL OVERRIDES --- */
    /* Hapus warna pink bawaan template jika ada */
    .text-primary {
        color: var(--primary) !important;
    }

    .bg-primary {
        background-color: var(--primary) !important;
    }

    a {
        color: var(--primary);
        transition: color 0.3s;
    }

    a:hover {
        color: var(--primary-dark);
        text-decoration: none;
    }

    /* 1. Saat menu dropdown dibuka (seperti di gambar Anda) */
    .sidebar .nav .nav-item .nav-link[aria-expanded="true"] {
        background: #ebf8ff !important;
        /* Warna Biru Laut Muda */
        color: #3182ce !important;
        /* Warna Teks Biru */
    }

    /* 2. Saat menu sedang aktif (diklik) */
    .sidebar .nav .nav-item.active>.nav-link {
        background: #ebf8ff !important;
        color: #3182ce !important;
    }

    /* 3. Garis dekorasi sebelum menu (titik/dot) saat aktif */
    .sidebar .nav .nav-item.active>.nav-link i.menu-icon {
        color: #3182ce !important;
    }
    </style>
</head>

<body>
    <div class="container-scroller">

        <nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
            <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-center">
                <a class="navbar-brand brand-logo" href="index.php">
                    <i class="mdi mdi-book-open-page-variant mr-2"></i>Perpustakaan
                </a>
                <a class="navbar-brand brand-logo-mini" href="index.php"><i
                        class="mdi mdi-book-open-page-variant mr-2"></i></a>
            </div>

            <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
                <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-toggle="minimize">
                    <span class="icon-menu text-primary"></span>
                </button>

                <ul class="navbar-nav mr-lg-2">
                    <li class="nav-item nav-search d-none d-lg-block">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text" id="search">
                                    <i class="icon-search"></i>
                                </span>
                            </div>
                            <input type="text" class="form-control" placeholder="Cari data perpustakaan..."
                                aria-label="search" aria-describedby="search">
                        </div>
                    </li>
                </ul>

                <ul class="navbar-nav navbar-nav-right">
                    <li class="nav-item dropdown d-flex mr-4 ">
                        <a class="nav-link count-indicator dropdown-toggle d-flex align-items-center justify-content-center"
                            id="notificationDropdown" href="#" data-toggle="dropdown">
                            <i class="icon-cog text-primary"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list"
                            aria-labelledby="notificationDropdown">
                            <p class="mb-0 font-weight-normal float-left dropdown-header">Pengaturan Akun</p>
                            <a class="dropdown-item preview-item" href="admin_profil.php">
                                <i class="icon-head text-primary"></i> Profil Saya
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item preview-item" href="../logout.php">
                                <i class="icon-inbox text-danger"></i> Keluar (Logout)
                            </a>
                        </div>
                    </li>
                </ul>

                <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button"
                    data-toggle="offcanvas">
                    <span class="icon-menu"></span>
                </button>
            </div>
        </nav>

        <div class="container-fluid page-body-wrapper">

            <nav class="sidebar sidebar-offcanvas" id="sidebar">

                <div class="user-profile">
                    <div class="user-image">
                        <img src="images/faces/face28.png" alt="profile"
                            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['nama'] ?? 'Admin'); ?>&background=3182ce&color=fff'">
                    </div>
                    <div class="user-name">
                        <?php echo $_SESSION['nama'] ?? 'Administrator'; ?>
                    </div>
                    <div class="user-designation">
                        <?php echo $_SESSION['role'] ?? 'Super Admin'; ?>
                    </div>
                </div>

                <ul class="nav">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="icon-box menu-icon"></i>
                            <span class="menu-title">Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-anggota" aria-expanded="false"
                            aria-controls="ui-anggota">
                            <i class="icon-head menu-icon"></i>
                            <span class="menu-title">Data Anggota</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-anggota">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="anggota_daftar.php">Daftar Semua</a>
                                </li>
                                <li class="nav-item"> <a class="nav-link" href="anggota_verifikasi.php">Verifikasi <span
                                            class="badge badge-primary ml-2"
                                            style="background:var(--primary)">Baru</span></a></li>
                                <li class="nav-item"> <a class="nav-link" href="cari_anggota.php">Cari Anggota</a>
                                </li>


                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-buku" aria-expanded="false"
                            aria-controls="ui-buku">
                            <i class="icon-book menu-icon"></i>
                            <span class="menu-title">Katalog Buku</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-buku">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="buku_daftar.php">Data Buku</a></li>
                                <li class="nav-item"> <a class="nav-link" href="buku_tambah.php">Tambah Buku</a></li>
                                <li class="nav-item"> <a class="nav-link" href="buku_kategori.php">Kategori</a></li>
                                <li class="nav-item"> <a class="nav-link" href="buku_riwayat.php">Riwayat Stok</a></li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-pinjam" aria-expanded="false"
                            aria-controls="ui-pinjam">
                            <i class="icon-repeat menu-icon"></i>
                            <span class="menu-title">Transaksi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-pinjam">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="pinjam_pengajuan.php">Pengajuan
                                        Masuk</a></li>
                                <li class="nav-item"> <a class="nav-link" href="pinjam_aktif.php">Sedang Dipinjam</a>
                                </li>
                                <li class="nav-item"> <a class="nav-link" href="pinjam_kembali.php">Pengembalian</a>
                                </li>
                                <li class="nav-item"> <a class="nav-link" href="pinjam_riwayat.php">Riwayat Lengkap</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-denda" aria-expanded="false"
                            aria-controls="ui-denda">
                            <i class="icon-paper menu-icon"></i>
                            <span class="menu-title">Keuangan & Denda</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-denda">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="denda_daftar.php">Daftar Denda</a></li>
                                <li class="nav-item"> <a class="nav-link" href="denda_buat.php">Input Denda</a></li>
                                <li class="nav-item"> <a class="nav-link" href="denda_bayar.php">Pembayaran</a></li>
                                <li class="nav-item"> <a class="nav-link" href="denda_riwayat.php">Laporan Keuangan</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-laporan" aria-expanded="false"
                            aria-controls="ui-laporan">
                            <i class="icon-bar-graph menu-icon"></i>
                            <span class="menu-title">Pusat Laporan</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-laporan">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="laporan_peminjaman.php">Statistik
                                        Peminjaman</a></li>
                                <li class="nav-item"> <a class="nav-link" href="laporan_populer.php">Buku Terpopuler</a>
                                </li>
                                <li class="nav-item"> <a class="nav-link" href="laporan_anggota.php">Keaktifan
                                        Anggota</a></li>
                                <li class="nav-item"> <a class="nav-link" href="log_aktivitas.php">Audit Log System</a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" data-toggle="collapse" href="#ui-setting" aria-expanded="false"
                            aria-controls="ui-setting">
                            <i class="icon-cog menu-icon"></i>
                            <span class="menu-title">Konfigurasi</span>
                            <i class="menu-arrow"></i>
                        </a>
                        <div class="collapse" id="ui-setting">
                            <ul class="nav flex-column sub-menu">
                                <li class="nav-item"> <a class="nav-link" href="admin_manajemen.php">Data Admin</a></li>
                                <li class="nav-item"> <a class="nav-link" href="setting_sistem.php">Setting Aplikasi</a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="main-panel">
                <div class="content-wrapper">
                    ```