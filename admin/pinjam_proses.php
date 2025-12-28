<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

if (!isset($_GET['id'])) redirect('pinjam_pengajuan.php');
$id = clean_input($_GET['id']);

// Ambil Detail
$data = ambil_detail_pengajuan($id);
if (!$data) die("Data tidak ditemukan.");

// PROSES SUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['aksi']; // 'Disetujui' atau 'Ditolak'
    $alasan = clean_input($_POST['alasan']);
    $admin_id = $_SESSION['user_id'];

    if ($status == 'Ditolak' && empty($alasan)) {
        $error = "Wajib mengisi alasan jika menolak pengajuan!";
    } else {
        // Panggil Fungsi Wrapper SP
        $hasil = proses_pengajuan($id, $admin_id, $status, $alasan);
        
        if ($hasil['status'] == 'success') {
            echo "<script>alert('{$hasil['pesan']}'); window.location='pinjam_pengajuan.php';</script>";
        } else {
            $error = $hasil['pesan'];
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-7 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary">Detail Pengajuan</h4>

                <div class="d-flex align-items-start mb-4 bg-light p-3 rounded">
                    <img src="<?php echo !empty($data['Cover_Buku']) ? '../uploads/buku/'.$data['Cover_Buku'] : 'images/no-cover.png'; ?>"
                        style="width:80px; height:110px; object-fit:cover; border-radius:5px; margin-right:15px;">
                    <div>
                        <h5 class="font-weight-bold mb-1"><?php echo $data['Judul']; ?></h5>
                        <p class="text-muted mb-1">Penulis: <?php echo $data['Penulis']; ?></p>
                        <p class="mb-0">
                            Stok Tersedia:
                            <?php if($data['Jumlah_Tersedia'] > 0): ?>
                            <span class="badge badge-success"><?php echo $data['Jumlah_Tersedia']; ?> Eks</span>
                            <?php else: ?>
                            <span class="badge badge-danger">Habis (0)</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Peminjam</label>
                            <div class="font-weight-bold"><?php echo $data['Nama_Lengkap']; ?></div>
                            <small>(ID: <?php echo $data['Id_Anggota']; ?>)</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="text-muted">Rencana Pinjam</label>
                            <div class="font-weight-bold">
                                <?php echo date('d M Y', strtotime($data['Tgl_Pinjam_Diinginkan'])); ?> s.d <br>
                                <?php echo date('d M Y', strtotime($data['Tgl_Kembali_Diinginkan'])); ?>
                            </div>
                            <span class="badge badge-info mt-1"><?php echo $data['Durasi_Hari']; ?> Hari</span>
                        </div>
                    </div>
                </div>

                <hr>
                <div class="form-group">
                    <label class="text-muted">Catatan Anggota:</label>
                    <div class="p-3 border rounded bg-white">
                        <?php echo !empty($data['Catatan_Anggota']) ? $data['Catatan_Anggota'] : '- Tidak ada catatan -'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-5 grid-margin stretch-card">
        <div class="card shadow-sm border-left-primary">
            <div class="card-body">
                <h4 class="card-title">Proses Persetujuan</h4>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if($data['Status_Pengajuan'] != 'Menunggu'): ?>
                <div class="alert alert-secondary text-center">
                    <h4>Status: <?php echo $data['Status_Pengajuan']; ?></h4>
                    <p>Diproses oleh admin pada <?php echo $data['Diproses_At']; ?></p>
                    <?php if(!empty($data['Alasan_Penolakan'])): ?>
                    <hr>
                    <small>Alasan: <?php echo $data['Alasan_Penolakan']; ?></small>
                    <?php endif; ?>
                </div>
                <a href="pinjam_pengajuan.php" class="btn btn-light btn-block">Kembali</a>
                <?php else: ?>

                <form method="POST" id="formProses">
                    <div class="form-group">
                        <label>Keputusan Admin</label>
                        <select class="form-control form-control-lg font-weight-bold" name="aksi" id="aksi"
                            onchange="toggleAlasan()">
                            <option value="Disetujui" class="text-success">✅ Setujui Peminjaman</option>
                            <option value="Ditolak" class="text-danger">❌ Tolak Pengajuan</option>
                        </select>
                    </div>

                    <div id="info-setuju" class="alert alert-success">
                        <small><i class="mdi mdi-information"></i> Sistem akan otomatis membuat ID Peminjaman baru dan
                            mengurangi stok buku.</small>
                    </div>

                    <div class="form-group" id="div-alasan" style="display:none;">
                        <label>Alasan Penolakan <span class="text-danger">*</span></label>
                        <textarea name="alasan" class="form-control" rows="4"
                            placeholder="Contoh: Stok buku rusak, Anggota masih punya tanggungan..."></textarea>
                    </div>

                    <?php if($data['Jumlah_Tersedia'] > 0): ?>
                    <button type="submit" class="btn btn-primary btn-block btn-lg"
                        onclick="return confirm('Yakin dengan keputusan ini?')">
                        <i class="mdi mdi-check-circle"></i> Simpan Keputusan
                    </button>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="mdi mdi-alert"></i> <b>Stok Buku Habis!</b><br>
                        Anda tidak dapat menyetujui pengajuan ini kecuali stok tersedia kembali.
                    </div>
                    <input type="hidden" name="aksi" value="Ditolak">
                    <div class="form-group">
                        <label>Alasan Penolakan</label>
                        <textarea name="alasan" class="form-control" rows="3"
                            readonly>Maaf, stok buku saat ini sedang habis.</textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="mdi mdi-close-circle"></i> Tolak Otomatis
                    </button>
                    <?php endif; ?>

                    <a href="pinjam_pengajuan.php" class="btn btn-light btn-block mt-2">Batal</a>
                </form>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleAlasan() {
    var aksi = document.getElementById("aksi").value;
    var divAlasan = document.getElementById("div-alasan");
    var infoSetuju = document.getElementById("info-setuju");

    if (aksi === "Ditolak") {
        divAlasan.style.display = "block";
        infoSetuju.style.display = "none";
    } else {
        divAlasan.style.display = "none";
        infoSetuju.style.display = "block";
    }
}
</script>

<?php include 'includes/footer.php'; ?>