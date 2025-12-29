<?php
require_once 'includes/functions.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) redirect('../login.php');
if ($_SESSION['role'] !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak! Hanya Super Admin yang bisa mengakses halaman ini.'); window.location='index.php';</script>";
    exit;
}

// Logic Simpan Pengaturan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_setting'])) {
    if (update_pengaturan($_POST)) {
        echo "<script>alert('Pengaturan berhasil disimpan!'); window.location='setting_sistem.php';</script>";
    } else {
        $error = "Gagal menyimpan pengaturan.";
    }
}

// Logic Download Backup
if (isset($_POST['backup_db'])) {
    backup_database();
}

$set = ambil_pengaturan();
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-3 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="#umum" class="list-group-item list-group-item-action active font-weight-bold"
                        data-toggle="list">
                        <i class="mdi mdi-cogs mr-2"></i> Pengaturan Umum
                    </a>
                    <a href="#aturan" class="list-group-item list-group-item-action font-weight-bold"
                        data-toggle="list">
                        <i class="mdi mdi-gavel mr-2"></i> Aturan & Denda
                    </a>
                    <a href="#backup" class="list-group-item list-group-item-action font-weight-bold"
                        data-toggle="list">
                        <i class="mdi mdi-database mr-2"></i> Backup Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-9 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form method="POST">
                    <div class="tab-content">

                        <div class="tab-pane fade show active" id="umum">
                            <h4 class="card-title text-primary mb-4">Profil Perpustakaan</h4>
                            <div class="form-group">
                                <label>Nama Aplikasi / Perpustakaan</label>
                                <input type="text" name="nama" class="form-control"
                                    value="<?php echo $set['Nama_Perpus']; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Kontak</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?php echo $set['Email']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Nomor Telepon</label>
                                <input type="text" name="telepon" class="form-control"
                                    value="<?php echo $set['No_Telepon']; ?>">
                            </div>
                            <div class="form-group">
                                <label>Alamat Lengkap</label>
                                <textarea name="alamat" class="form-control"
                                    rows="3"><?php echo $set['Alamat']; ?></textarea>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="aturan">
                            <h4 class="card-title text-primary mb-4">Aturan Peminjaman & Denda</h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Durasi Peminjaman (Hari)</label>
                                        <div class="input-group">
                                            <input type="number" name="durasi" class="form-control"
                                                value="<?php echo $set['Durasi_Pinjam']; ?>" required>
                                            <div class="input-group-append"><span class="input-group-text">Hari</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Max Buku Dipinjam</label>
                                        <div class="input-group">
                                            <input type="number" name="max_pinjam" class="form-control"
                                                value="<?php echo $set['Max_Pinjam']; ?>" required>
                                            <div class="input-group-append"><span class="input-group-text">Buku</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <h5 class="text-danger mb-3">Tarif Denda</h5>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Keterlambatan (Per Hari)</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" name="denda" class="form-control"
                                            value="<?php echo $set['Denda_Per_Hari']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Kerusakan Ringan</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" name="rusak_kecil" class="form-control"
                                            value="<?php echo $set['Biaya_Rusak_Ringan']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Kerusakan Berat</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" name="rusak_besar" class="form-control"
                                            value="<?php echo $set['Biaya_Rusak_Berat']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-4 col-form-label">Buku Hilang</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                        <input type="number" name="hilang" class="form-control"
                                            value="<?php echo $set['Biaya_Hilang']; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="backup">
                            <h4 class="card-title text-primary mb-4">Maintenance Database</h4>
                            <div class="alert alert-info">
                                <i class="mdi mdi-information-outline"></i> Fitur ini akan mengunduh seluruh data
                                perpustakaan dalam format <b>.SQL</b>. Simpan file tersebut di tempat aman.
                            </div>
                            <div class="text-center py-4">
                                <button type="submit" name="backup_db" class="btn btn-success btn-lg btn-icon-text">
                                    <i class="mdi mdi-cloud-download btn-icon-prepend"></i> Backup Database Sekarang
                                </button>
                            </div>
                        </div>

                    </div>

                    <div class="border-top pt-3 mt-3 text-right">
                        <button type="submit" name="simpan_setting" class="btn btn-primary mr-2">
                            <i class="mdi mdi-content-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>