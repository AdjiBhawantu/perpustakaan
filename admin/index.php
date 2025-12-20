<?php
// --- 1. LOGIKA UTAMA (BACKEND) ---
// Wajib diletakkan paling atas
require_once 'includes/functions.php';

// Cek Login & Role Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

// --- 2. QUERY DATA STATISTIK ---
// Total Buku
$q1 = $conn->query("SELECT COUNT(*) as total FROM buku");
$d1 = $q1->fetch_assoc();
$total_buku = $d1['total'] ?? 0;

// Anggota Aktif
$q2 = $conn->query("SELECT COUNT(*) as total FROM anggota WHERE Status_Akun='Aktif'");
$d2 = $q2->fetch_assoc();
$total_anggota = $d2['total'] ?? 0;

// Peminjaman Sedang Berjalan
$q3 = $conn->query("SELECT COUNT(*) as total FROM peminjaman WHERE Status_Peminjaman='Dipinjam'");
$d3 = $q3->fetch_assoc();
$total_pinjam = $d3['total'] ?? 0;

// Menunggu Verifikasi (Pending)
$q4 = $conn->query("SELECT COUNT(*) as total FROM anggota WHERE Status_Verifikasi='Pending'");
$d4 = $q4->fetch_assoc();
$total_pending = $d4['total'] ?? 0;

// --- 3. INCLUDE HEADER (Memuat Navbar & CSS Global) ---
include 'includes/header.php'; 
?>

<style>
/* Card Stats dengan Hover Effect */
.card-stat {
    border-radius: 15px;
    border: none;
    transition: transform 0.3s, box-shadow 0.3s;
    overflow: hidden;
    position: relative;
    background: white;
}

.card-stat:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(49, 130, 206, 0.15);
}

.card-stat .icon-bg {
    position: absolute;
    right: -10px;
    bottom: -10px;
    font-size: 100px;
    opacity: 0.1;
    color: var(--primary);
    transform: rotate(-15deg);
    transition: all 0.3s;
}

.card-stat:hover .icon-bg {
    transform: rotate(0deg) scale(1.1);
    opacity: 0.15;
}

/* Welcome Banner */
.banner-welcome {
    background: linear-gradient(120deg, #3182ce 0%, #63b3ed 100%);
    color: white;
    border-radius: 20px;
    padding: 30px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 25px rgba(49, 130, 206, 0.3);
}

.banner-welcome h3 {
    font-weight: 800;
    font-size: 1.8rem;
    margin-bottom: 10px;
}

.banner-welcome p {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Tabel Modern */
.table-modern thead th {
    background-color: #f1f5f9;
    color: #64748b;
    font-weight: 700;
    text-transform: uppercase;
    font-size: 0.85rem;
    border: none;
}

.table-modern td {
    vertical-align: middle;
    font-weight: 500;
    color: #334155;
}

.table-modern tr:hover td {
    background-color: #f8fafc;
}

/* Badge Status */
.badge-status {
    padding: 6px 12px;
    border-radius: 30px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-blue {
    background: #e0f2fe;
    color: #0284c7;
}

.badge-green {
    background: #dcfce7;
    color: #16a34a;
}

.badge-orange {
    background: #ffedd5;
    color: #ea580c;
}
</style>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="banner-welcome">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3>Halo, <?php echo $_SESSION['nama']; ?>! 👋</h3>
                    <p>Selamat datang di panel admin Perpustakaan. Anda memiliki <b><?php echo $total_pending; ?></b>
                        anggota baru yang menunggu verifikasi.</p>
                </div>
                <div class="d-none d-md-block">
                    <i class="mdi mdi-book-open-variant" style="font-size: 80px; opacity: 0.2;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-stat">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-circle bg-light mr-3">
                        <i class="mdi mdi-library text-primary icon-md"></i>
                    </div>
                    <h4 class="card-title mb-0 text-muted">Total Koleksi</h4>
                </div>
                <h2 class="font-weight-bold mb-1"><?php echo number_format($total_buku); ?></h2>
                <small class="text-muted">Judul buku tersedia</small>
                <i class="mdi mdi-library icon-bg"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-stat">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-circle bg-light mr-3">
                        <i class="mdi mdi-account-group text-success icon-md"></i>
                    </div>
                    <h4 class="card-title mb-0 text-muted">Anggota Aktif</h4>
                </div>
                <h2 class="font-weight-bold mb-1"><?php echo number_format($total_anggota); ?></h2>
                <small class="text-muted">Pengguna terverifikasi</small>
                <i class="mdi mdi-account-group icon-bg text-success"></i>
            </div>
        </div>
    </div>

    <div class="col-md-4 grid-margin stretch-card">
        <div class="card card-stat">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="p-2 rounded-circle bg-light mr-3">
                        <i class="mdi mdi-book-open-page-variant text-warning icon-md"></i>
                    </div>
                    <h4 class="card-title mb-0 text-muted">Sedang Dipinjam</h4>
                </div>
                <h2 class="font-weight-bold mb-1"><?php echo number_format($total_pinjam); ?></h2>
                <small class="text-muted">Transaksi aktif saat ini</small>
                <i class="mdi mdi-book-open-page-variant icon-bg text-warning"></i>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12 grid-margin stretch-card">
        <div class="card" style="border-radius: 20px; border:none; box-shadow: 0 5px 20px rgba(0,0,0,0.05);">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0" style="font-weight: 800; color: #1e293b;">
                        Peminjaman Terbaru
                    </h4>
                    <a href="pinjam_aktif.php" class="btn btn-sm btn-primary btn-icon-text"
                        style="border-radius: 50px; padding: 8px 20px;">
                        <i class="mdi mdi-eye btn-icon-prepend"></i> Lihat Semua
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-modern table-hover">
                        <thead>
                            <tr>
                                <th>Peminjam</th>
                                <th>Judul Buku</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tenggat Waktu</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query 5 Peminjaman Terakhir
                            $sql_recent = "SELECT p.*, a.Nama_Lengkap, b.Judul 
                                           FROM peminjaman p 
                                           JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                                           JOIN buku b ON p.Id_Buku = b.Id_Buku
                                           ORDER BY p.Tgl_Pinjam DESC LIMIT 5";
                            $q_recent = $conn->query($sql_recent);
                            
                            if ($q_recent && $q_recent->num_rows > 0) {
                                while($row = $q_recent->fetch_assoc()) {
                                    // Logic Badge Warna
                                    if ($row['Status_Peminjaman'] == 'Dipinjam') {
                                        $badge = '<span class="badge-status badge-orange">Dipinjam</span>';
                                    } elseif ($row['Status_Peminjaman'] == 'Dikembalikan') {
                                        $badge = '<span class="badge-status badge-green">Selesai</span>';
                                    } else {
                                        $badge = '<span class="badge-status badge-blue">'.$row['Status_Peminjaman'].'</span>';
                                    }
                                    
                                    // Inisial Avatar
                                    $initial = substr($row['Nama_Lengkap'], 0, 1);
                                    
                                    echo "<tr>
                                        <td>
                                            <div class='d-flex align-items-center'>
                                                <div style='width:35px; height:35px; background:#eff6ff; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#3b82f6; font-weight:bold; margin-right:12px;'>
                                                    {$initial}
                                                </div>
                                                <div>
                                                    <div style='font-weight:600;'>{$row['Nama_Lengkap']}</div>
                                                    <small class='text-muted' style='font-size:0.75rem;'>ID: {$row['Id_Anggota']}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td style='font-weight:500;'>{$row['Judul']}</td>
                                        <td>".date('d M Y', strtotime($row['Tgl_Pinjam']))."</td>
                                        <td>".date('d M Y', strtotime($row['Tgl_Kembali_Rencana']))."</td>
                                        <td>{$badge}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center py-5 text-muted'>Belum ada aktivitas peminjaman terbaru.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>