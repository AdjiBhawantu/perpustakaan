<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

// Cek ID di URL
if (!isset($_GET['id'])) {
    redirect('anggota_daftar.php');
}

$id_anggota = clean_input($_GET['id']);
$data = ambil_detail_anggota($id_anggota);

// Jika anggota tidak ditemukan
if (!$data) {
    echo "<script>alert('Anggota tidak ditemukan!'); window.location='anggota_daftar.php';</script>";
    exit;
}

// PROSES FORM SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $aksi = $_POST['aksi']; // 'suspend' atau 'aktifkan'
    $alasan = clean_input($_POST['alasan']);
    
    if (empty($alasan)) {
        $error = "Alasan wajib diisi sebagai bukti riwayat!";
    } else {
        $status_baru = ($aksi == 'suspend') ? 'Nonaktif' : 'Aktif';
        
        // Tambahkan ID Anggota ke dalam pesan alasan agar mudah dicari di log
        $alasan_lengkap = "[$id_anggota] $alasan"; 
        
        if (update_status_anggota($id_anggota, $status_baru, $alasan_lengkap, $_SESSION['user_id'])) {
            $success = "Status anggota berhasil diperbarui!";
            // Refresh data terbaru
            $data = ambil_detail_anggota($id_anggota);
        } else {
            $error = "Gagal memperbarui database.";
        }
    }
}

// Ambil Riwayat
$riwayat = ambil_riwayat_status($id_anggota);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-primary mb-4">
                    <i class="mdi mdi-account-alert mr-2"></i>Kontrol Status Anggota
                </h4>

                <div class="d-flex align-items-center mb-4 p-3 rounded"
                    style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div
                        style="width:50px; height:50px; background:#ebf8ff; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#3182ce; font-weight:bold; font-size:1.2rem; margin-right:15px;">
                        <?php echo substr($data['Nama_Lengkap'], 0, 1); ?>
                    </div>
                    <div>
                        <h5 class="mb-0 font-weight-bold text-dark"><?php echo $data['Nama_Lengkap']; ?></h5>
                        <small class="text-muted">ID: <?php echo $data['Id_Anggota']; ?></small>
                    </div>
                </div>

                <?php if ($data['Status_Akun'] == 'Aktif'): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="mdi mdi-check-circle mr-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Status: AKTIF</strong><br>
                        Anggota dapat meminjam buku & login.
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-danger d-flex align-items-center" role="alert"
                    style="background-color: #fff5f5; border-color: #feb2b2; color: #c53030;">
                    <i class="mdi mdi-block-helper mr-2" style="font-size: 1.5rem;"></i>
                    <div>
                        <strong>Status: DIBLOKIR / NON-AKTIF</strong><br>
                        Akses login & peminjaman dibekukan.
                    </div>
                </div>
                <?php endif; ?>

                <hr>

                <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label class="font-weight-bold">Alasan Tindakan</label>
                        <textarea name="alasan" class="form-control" rows="4"
                            placeholder="Contoh: Terlambat mengembalikan buku lebih dari 3 bulan..." required
                            style="border: 2px solid #e2e8f0;"></textarea>
                        <small class="text-muted">Alasan ini akan tercatat permanen di riwayat.</small>
                    </div>

                    <?php if ($data['Status_Akun'] == 'Aktif'): ?>
                    <input type="hidden" name="aksi" value="suspend">
                    <button type="submit" class="btn btn-danger btn-block btn-lg"
                        onclick="return confirm('Yakin ingin memblokir anggota ini?');">
                        <i class="mdi mdi-lock text-white"></i> Blokir / Suspend Akun
                    </button>
                    <?php else: ?>
                    <input type="hidden" name="aksi" value="aktifkan">
                    <button type="submit" class="btn btn-success btn-block btn-lg"
                        onclick="return confirm('Aktifkan kembali akun ini?');">
                        <i class="mdi mdi-lock-open-variant text-white"></i> Aktifkan Kembali Akun
                    </button>
                    <?php endif; ?>

                    <a href="anggota_daftar.php" class="btn btn-light btn-block mt-3">Batal / Kembali</a>
                </form>

            </div>
        </div>
    </div>

    <div class="col-md-7 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-primary mb-4">
                    <i class="mdi mdi-history mr-2"></i>Riwayat Status Akun
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background-color: #ebf8ff;">
                            <tr>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($riwayat && $riwayat->num_rows > 0): ?>
                            <?php while ($row = $riwayat->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size: 0.85rem; width: 25%;">
                                    <?php echo date('d M Y', strtotime($row['Created_At'])); ?><br>
                                    <small class="text-muted"><?php echo date('H:i', strtotime($row['Created_At'])); ?>
                                        WIB</small>
                                </td>
                                <td style="width: 25%;">
                                    <?php if (strpos($row['Aktivitas'], 'Suspend') !== false): ?>
                                    <span class="badge badge-danger">Suspend</span>
                                    <?php elseif (strpos($row['Aktivitas'], 'Reaktivasi') !== false): ?>
                                    <span class="badge badge-success">Reaktivasi</span>
                                    <?php else: ?>
                                    <span class="badge badge-info">Info</span>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: normal; word-wrap: break-word;">
                                    <?php 
                                                // Bersihkan ID dari pesan agar lebih rapi saat ditampilkan
                                                $desc = str_replace("[$id_anggota]", "", $row['Deskripsi']);
                                                echo $desc; 
                                            ?>
                                    <div class="mt-1">
                                        <small class="text-muted font-italic">Oleh: Admin</small>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="mdi mdi-file-document-box-outline display-4"></i><br>
                                    Belum ada riwayat perubahan status.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>