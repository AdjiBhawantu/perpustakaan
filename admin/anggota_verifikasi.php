<?php
require_once 'includes/functions.php';

// Cek Akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

// Handle Aksi Terima/Tolak
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_verif = clean_input($_GET['id']);
    $aksi = $_GET['aksi'];
    
    $status_baru = ($aksi == 'terima') ? 'Terverifikasi' : 'Ditolak';
    
    if (proses_verifikasi_anggota($id_verif, $status_baru, $_SESSION['user_id'])) {
        echo "<script>alert('Status anggota berhasil diperbarui menjadi: $status_baru'); window.location='anggota_verifikasi.php';</script>";
    } else {
        echo "<script>alert('Terjadi kesalahan saat memproses data.');</script>";
    }
}

// Ambil Data Pending
$data_pending = ambil_anggota_pending();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin">
        <div class="card shadow-sm border-0" style="background: linear-gradient(to right, #ffffff, #f0f9ff);">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-account-clock mr-2"></i>Verifikasi Pendaftaran
                    Baru</h4>
                <p class="card-description">Berikut adalah daftar calon anggota yang menunggu persetujuan admin.</p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Tanggal Daftar</th>
                                <th>Nama Lengkap</th>
                                <th>Username / Email</th>
                                <th>Bukti KTP</th>
                                <th class="text-center">Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($data_pending && $data_pending->num_rows > 0): ?>
                            <?php while ($row = $data_pending->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['Tanggal_Daftar'])); ?></td>
                                <td>
                                    <strong class="text-dark"><?php echo $row['Nama_Lengkap']; ?></strong><br>
                                    <span class="text-muted small">ID: <?php echo $row['Id_Anggota']; ?></span>
                                </td>
                                <td>
                                    <?php echo $row['Email']; ?><br>
                                    <small class="text-muted">@<?php echo $row['Username']; ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($row['Foto_KTP'])): ?>
                                    <a href="../uploads/ktp/<?php echo $row['Foto_KTP']; ?>" target="_blank"
                                        class="btn btn-outline-primary btn-sm btn-icon-text">
                                        <i class="mdi mdi-file-document-box btn-icon-prepend"></i> Lihat KTP
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted italic">Tidak ada foto</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="?aksi=terima&id=<?php echo $row['Id_Anggota']; ?>"
                                        onclick="return confirm('Terima anggota ini?')"
                                        class="btn btn-success btn-sm btn-icon-text">
                                        <i class="mdi mdi-check btn-icon-prepend"></i> Terima
                                    </a>
                                    <a href="?aksi=tolak&id=<?php echo $row['Id_Anggota']; ?>"
                                        onclick="return confirm('Tolak pendaftaran ini?')"
                                        class="btn btn-danger btn-sm btn-icon-text ml-2">
                                        <i class="mdi mdi-close btn-icon-prepend"></i> Tolak
                                    </a>
                                    <a href="anggota_detail.php?id=<?php echo $row['Id_Anggota']; ?>"
                                        class="btn btn-light btn-sm ml-2">Detail</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="mdi mdi-check-circle-outline display-4 text-success"></i><br>
                                    Tidak ada pendaftaran pending saat ini.
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