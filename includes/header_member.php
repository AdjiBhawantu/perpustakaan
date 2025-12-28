<?php 
session_start();
require_once 'config/database.php';

// --- 1. LOGIKA PROSES PENGAJUAN PEMINJAMAN (UPDATED) ---
$pesan_notifikasi = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajukan_pinjam'])) {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    $id_pengajuan = "REQ-" . date("ymd") . rand(100, 999);
    $id_anggota = $_SESSION['user_id'];
    $id_buku = $_POST['id_buku'];
    
    // LOGIKA BARU: Hitung Tanggal Otomatis
    $durasi = (int)$_POST['durasi']; // Ambil durasi (1-7)
    
    $tgl_pinjam = date('Y-m-d'); // Otomatis hari ini
    $tgl_kembali = date('Y-m-d', strtotime("+$durasi days")); // Hari ini + durasi
    
    $catatan = htmlspecialchars($_POST['catatan']);

    // Panggil Stored Procedure (Parameter tetap sama, data tanggal hasil hitungan PHP)
    $stmt = $conn->prepare("CALL SP_Ajukan_Peminjaman(?, ?, ?, ?, ?, ?, @result)");
    $stmt->bind_param("ssssss", $id_pengajuan, $id_anggota, $id_buku, $tgl_pinjam, $tgl_kembali, $catatan);
    
    if ($stmt->execute()) {
        $res = $conn->query("SELECT @result as pesan");
        $row = $res->fetch_assoc();
        $pesan_notifikasi = $row['pesan'];
        
        echo "<script>alert('$pesan_notifikasi'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan sistem.');</script>";
    }
}

// --- 2. DATA STATISTIK ---
$q_buku = $conn->query("SELECT COUNT(*) as total, SUM(Jumlah_Tersedia) as tersedia FROM buku");
$d_buku = $q_buku->fetch_assoc();
$total_buku = $d_buku['total'] ?? 0;
$buku_tersedia = $d_buku['tersedia'] ?? 0;

$q_anggota = $conn->query("SELECT COUNT(*) as total FROM anggota WHERE Status_Akun='Aktif'");
$d_anggota = $q_anggota->fetch_assoc();
$total_anggota = $d_anggota['total'] ?? 0;

$q_pinjam = $conn->query("SELECT COUNT(*) as total FROM peminjaman");
$d_pinjam = $q_pinjam->fetch_assoc();
$total_transaksi = $d_pinjam['total'] ?? 0;

function get_cover($filename) {
    if (!empty($filename) && file_exists("uploads/buku/" . $filename)) {
        return "uploads/buku/" . $filename;
    }
    return "assets/images/no-cover.png"; 
}?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan - Sistem Peminjaman Buku Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
    /* --- GLOBAL LAYOUT --- */
    body {
        margin: 0;
        font-family: 'Segoe UI', sans-serif;
        background-color: #f8fafc;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .section-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .section-title {
        font-size: 2.5rem;
        font-weight: 800;
        color: #2d3748;
        margin-bottom: 10px;
    }

    .section-desc {
        color: #718096;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }

    .highlight {
        color: #3182ce;
    }

    /* --- HERO STATS (MODERN CARDS) --- */
    .hero-stats {
        display: flex;
        gap: 20px;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .stat-card {
        background: rgba(255, 255, 255, 0.95);
        padding: 20px;
        border-radius: 15px;
        display: flex;
        align-items: center;
        gap: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.5);
        backdrop-filter: blur(10px);
        transition: transform 0.3s, box-shadow 0.3s;
        min-width: 200px;
        flex: 1;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(49, 130, 206, 0.2);
        border-color: #3182ce;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: #ebf8ff;
        color: #3182ce;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: background 0.3s, color 0.3s;
    }

    .stat-card:hover .stat-icon {
        background: #3182ce;
        color: white;
    }

    .stat-info h4 {
        font-size: 1.8rem;
        font-weight: 800;
        color: #2d3748;
        margin: 0;
        line-height: 1;
    }

    .stat-info p {
        margin: 5px 0 0;
        font-size: 0.9rem;
        color: #718096;
        font-weight: 600;
    }

    .stat-sub {
        font-size: 0.75rem;
        color: #38a169;
        display: block;
        margin-top: 2px;
    }

    /* --- KATALOG & BUKU --- */
    .catalog-section {
        padding: 80px 0;
        background-color: #f7fafc;
    }

    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 30px;
    }

    .book-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
        border: 1px solid #edf2f7;
    }

    .book-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
        border-color: #bee3f8;
    }

    .book-cover {
        height: 350px;
        width: 100%;
        overflow: hidden;
        position: relative;
        background: #eee;
    }

    .book-cover img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .book-card:hover .book-cover img {
        transform: scale(1.08);
    }

    .category-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.9);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        color: #3182ce;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(5px);
    }

    .book-info {
        padding: 20px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .book-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #1a202c;
        margin-bottom: 5px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .book-author {
        color: #718096;
        font-size: 0.9rem;
        margin-bottom: 15px;
    }

    .book-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: auto;
        margin-bottom: 15px;
        font-size: 0.85rem;
        color: #4a5568;
    }

    .borrow-btn {
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        text-decoration: none;
        display: block;
    }

    .btn-primary-action {
        background: #3182ce;
        color: white;
    }

    .btn-primary-action:hover {
        background: #2b6cb0;
        box-shadow: 0 4px 12px rgba(49, 130, 206, 0.3);
    }

    .btn-disabled {
        background: #e2e8f0;
        color: #a0aec0;
        cursor: not-allowed;
    }

    /* --- POPULAR SECTION --- */
    .popular-section {
        padding: 80px 0;
        background: white;
    }

    .popular-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .popular-card {
        display: flex;
        align-items: center;
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #edf2f7;
        position: relative;
        transition: transform 0.2s;
    }

    .popular-card:hover {
        transform: translateY(-5px);
        border-color: #3182ce;
    }

    .rank-number {
        position: absolute;
        top: -10px;
        left: -10px;
        width: 40px;
        height: 40px;
        background: #3182ce;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(49, 130, 206, 0.4);
        z-index: 2;
    }

    .rank-1 {
        background: linear-gradient(135deg, #ECC94B 0%, #D69E2E 100%);
    }

    .rank-2 {
        background: linear-gradient(135deg, #A0AEC0 0%, #718096 100%);
    }

    .rank-3 {
        background: linear-gradient(135deg, #F6AD55 0%, #DD6B20 100%);
    }

    .pop-thumb img {
        width: 80px;
        height: 110px;
        border-radius: 8px;
        object-fit: cover;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .pop-details {
        margin-left: 20px;
        flex-grow: 1;
    }

    .pop-title {
        font-weight: 700;
        font-size: 1.1rem;
        color: #2d3748;
        margin-bottom: 5px;
    }

    .pop-stats {
        display: inline-block;
        background: #ebf8ff;
        color: #3182ce;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-top: 5px;
    }

    /* --- MODAL --- */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .close-modal {
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #aaa;
    }

    .close-modal:hover {
        color: #000;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #4a5568;
    }

    .form-control {
        width: 100%;
        padding: 12px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border 0.3s;
    }

    .form-control:focus {
        border-color: #3182ce;
        outline: none;
    }

    .btn-submit {
        width: 100%;
        background: #3182ce;
        color: white;
        padding: 14px;
        border: none;
        border-radius: 10px;
        font-weight: bold;
        cursor: pointer;
        margin-top: 15px;
        font-size: 1rem;
        transition: background 0.3s;
    }

    .btn-submit:hover {
        background: #2c5aa0;
    }

    .badge-success {
        background: #dcfce7;
        color: #166534;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
    }

    .badge-danger {
        background: #fee2e2;
        color: #991b1b;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: bold;
    }
    </style>
</head>

<body>
    <nav id="navbar">
        <a href="#" class="logo">📖 LibraryHub</a>
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="index.php?#home" class="active">Home</a></li>
                <li><a href="index.php?#catalog">Katalog</a></li>
                <li><a href="index.php?#popular">Terpopuler</a></li>
                <li><a href="status_peminjaman.php">Status Peminjaman</a></li>
            </ul>

            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-menu">
                <span style="color: white; font-weight: 600; margin-right: 15px;">
                    Halo, <?php echo htmlspecialchars($_SESSION['nama']); ?>
                </span>
                <a href="logout.php" class="login-btn" style="background: #e53e3e;">Logout</a>
            </div>
            <?php else: ?>
            <a href="login.php" class="login-btn">Login Member</a>
            <?php endif; ?>
        </div>
        <div class="mobile-menu-btn" id="mobileMenuBtn"><span></span><span></span><span></span></div>
    </nav>