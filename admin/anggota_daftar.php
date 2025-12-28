<?php
// Load Functions & Header
require_once 'includes/functions.php';

// Cek Akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

// Handle Request Hapus
if (isset($_GET['hapus'])) {
    $id_hapus = clean_input($_GET['hapus']);
    if (hapus_anggota($id_hapus, $_SESSION['user_id'])) {
        echo "<script>alert('Data anggota berhasil dihapus!'); window.location='anggota_daftar.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!');</script>";
    }
}

// Ambil Data
$data_anggota = ambil_semua_anggota();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title text-primary"><i class="mdi mdi-account-multiple mr-2"></i>Daftar Seluruh
                        Anggota</h4>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>#</th>
                                <th>Profil Anggota</th>
                                <th>Kontak</th>
                                <th>Tgl Daftar</th>
                                <th>Status Akun</th>
                                <th>Verifikasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data_anggota && $data_anggota->num_rows > 0): ?>
                            <?php $no = 1; while ($row = $data_anggota->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div
                                            style="width:40px; height:40px; background:#ebf8ff; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#3182ce; font-weight:bold; margin-right:10px;">
                                            <?php echo substr($row['Nama_Lengkap'], 0, 1); ?>
                                        </div>
                                        <div>
                                            <span
                                                class="font-weight-bold"><?php echo $row['Nama_Lengkap']; ?></span><br>
                                            <small class="text-muted">ID: <?php echo $row['Id_Anggota']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="mdi mdi-email-outline"></i> <?php echo $row['Email']; ?><br>
                                    <small><i class="mdi mdi-phone"></i>
                                        <?php echo $row['No_Telepon'] ?? '-'; ?></small>
                                </td>
                                <td><?php echo date('d M Y', strtotime($row['Tanggal_Daftar'])); ?></td>

                                <td>
                                    <?php if($row['Status_Akun'] == 'Aktif'): ?>
                                    <label class="badge badge-success">Aktif</label>
                                    <?php else: ?>
                                    <label class="badge badge-danger">Non-Aktif</label>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                            if ($row['Status_Verifikasi'] == 'Terverifikasi') {
                                                echo '<i class="mdi mdi-check-circle text-success"></i> Verified';
                                            } elseif ($row['Status_Verifikasi'] == 'Pending') {
                                                echo '<label class="badge badge-warning">Pending</label>';
                                            } else {
                                                echo '<label class="badge badge-danger">Ditolak</label>';
                                            }
                                            ?>
                                </td>

                                <td>
                                    <a href="anggota_detail.php?id=<?php echo $row['Id_Anggota']; ?>"
                                        class="btn btn-sm btn-info btn-icon-text">
                                        <i class="mdi mdi-eye btn-icon-prepend"></i> Detail
                                    </a>
                                    <a href="?hapus=<?php echo $row['Id_Anggota']; ?>"
                                        onclick="return confirm('Yakin ingin menghapus anggota ini? Data peminjaman terkait mungkin akan hilang/error.')"
                                        class="btn btn-sm btn-danger btn-icon-text ml-1">
                                        <i class="mdi mdi-delete"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">Belum ada data anggota.</td>
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