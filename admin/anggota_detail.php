<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

// Cek ID Param
if (!isset($_GET['id'])) {
    redirect('anggota_daftar.php');
}

$id_anggota = clean_input($_GET['id']);
$detail = ambil_detail_anggota($id_anggota);

if (!$detail) {
    echo "<script>alert('Data anggota tidak ditemukan!'); window.location='anggota_daftar.php';</script>";
    exit;
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-4">
                    <div
                        style="width:100px; height:100px; background:#ebf8ff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; color:#3182ce; font-size:40px; font-weight:bold; margin-bottom:15px;">
                        <?php echo substr($detail['Nama_Lengkap'], 0, 1); ?>
                    </div>
                    <h4><?php echo $detail['Nama_Lengkap']; ?></h4>
                    <p class="text-muted mb-1">@<?php echo $detail['Username']; ?></p>
                    <p class="text-small text-muted">Bergabung:
                        <?php echo date('d M Y', strtotime($detail['Tanggal_Daftar'])); ?></p>
                </div>

                <div class="border-top pt-3 text-left">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="font-weight-bold">Status Akun</h6>
                            <?php if($detail['Status_Akun']=='Aktif'): ?>
                            <span class="badge badge-success">Aktif</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Non-Aktif</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-6">
                            <h6 class="font-weight-bold">Verifikasi</h6>
                            <?php if($detail['Status_Verifikasi']=='Terverifikasi'): ?>
                            <span class="badge badge-primary">Verified</span>
                            <?php elseif($detail['Status_Verifikasi']=='Pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Ditolak</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-primary">Informasi Lengkap</h4>
                <form class="forms-sample">
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">ID Anggota</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo $detail['Id_Anggota']; ?>"
                                readonly style="background:#f8fafc;">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">NIK / KTP</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo $detail['No_KTP'] ?? '-'; ?>"
                                readonly style="background:#f8fafc;">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Email</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo $detail['Email']; ?>" readonly
                                style="background:#f8fafc;">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">No. Telepon</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control" value="<?php echo $detail['No_Telepon'] ?? '-'; ?>"
                                readonly style="background:#f8fafc;">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-sm-3 col-form-label font-weight-bold">Alamat</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" rows="3" readonly
                                style="background:#f8fafc;"><?php echo $detail['Alamat'] ?? '-'; ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3 d-flex justify-content-between">
                        <a href="anggota_daftar.php" class="btn btn-light border">Kembali</a>
                        <div>
                            <?php if($detail['Status_Verifikasi'] == 'Pending'): ?>
                            <a href="anggota_verifikasi.php?aksi=terima&id=<?php echo $detail['Id_Anggota']; ?>"
                                class="btn btn-success mr-2">Setujui</a>
                            <a href="anggota_verifikasi.php?aksi=tolak&id=<?php echo $detail['Id_Anggota']; ?>"
                                class="btn btn-danger">Tolak</a>
                            <?php else: ?>
                            <button type="button" class="btn btn-danger"
                                onclick="if(confirm('Hapus data ini?')) window.location='anggota_daftar.php?hapus=<?php echo $detail['Id_Anggota']; ?>'">Hapus
                                Data</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>