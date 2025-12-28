<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

if (!isset($_GET['id'])) redirect('buku_daftar.php');
$id_buku = clean_input($_GET['id']);

// Ambil data buku
$q = $conn->query("SELECT * FROM buku WHERE Id_Buku = '$id_buku'");
$buku = $q->fetch_assoc();
if (!$buku) die("Buku tidak ditemukan.");

// PROSES UPDATE STOK
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis = $_POST['jenis_transaksi'];
    $jumlah = (int)$_POST['jumlah'];
    $ket = clean_input($_POST['keterangan']);
    
    if ($jumlah > 0) {
        if (update_stok_buku($id_buku, $jenis, $jumlah, $ket, $_SESSION['user_id'])) {
            echo "<script>alert('Stok berhasil diperbarui!'); window.location='buku_stok.php?id=$id_buku';</script>";
        } else {
            $error = "Gagal update stok. Pastikan stok mencukupi untuk pengurangan.";
        }
    } else {
        $error = "Jumlah harus lebih dari 0.";
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
            <div class="card-body text-center">
                <img src="<?php echo !empty($buku['Cover_Buku']) ? '../uploads/buku/'.$buku['Cover_Buku'] : 'images/no-cover.png'; ?>"
                    style="width:120px; height:160px; object-fit:cover; border-radius:10px; margin-bottom:15px;">
                <h5 class="font-weight-bold"><?php echo $buku['Judul']; ?></h5>
                <p class="text-muted"><?php echo $buku['Id_Buku']; ?></p>

                <div class="border-top pt-3 mt-3">
                    <div class="row">
                        <div class="col-6 border-right">
                            <h3><?php echo $buku['Jumlah_Total']; ?></h3>
                            <small class="text-muted">Total Fisik</small>
                        </div>
                        <div class="col-6">
                            <h3 class="text-primary"><?php echo $buku['Jumlah_Tersedia']; ?></h3>
                            <small class="text-muted">Bisa Dipinjam</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary">Kelola Stok Buku</h4>
                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>Jenis Transaksi</label>
                        <select class="form-control" name="jenis_transaksi" required>
                            <option value="Tambah">➕ Tambah Stok Baru (Beli/Sumbangan)</option>
                            <option value="Kurang">➖ Kurangi Stok (Pemusnahan)</option>
                            <option value="Rusak">🛠️ Lapor Rusak (Kurangi Tersedia)</option>
                            <option value="Hilang">❌ Lapor Hilang (Kurangi Total)</option>
                            <option value="Perbaikan">✅ Selesai Perbaikan (Kembali Tersedia)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jumlah Buku</label>
                        <input type="number" name="jumlah" class="form-control" min="1" required>
                    </div>

                    <div class="form-group">
                        <label>Keterangan / Alasan</label>
                        <textarea name="keterangan" class="form-control" rows="3"
                            placeholder="Contoh: Pembelian Tahap 2 dari Dana BOS" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">Simpan Perubahan Stok</button>
                    <a href="buku_daftar.php" class="btn btn-light btn-block mt-2">Kembali</a>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 grid-margin">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Riwayat Perubahan Stok Buku Ini</h4>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr class="bg-light">
                                <th>Tanggal</th>
                                <th>Transaksi</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                                <th>Oleh</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // PERBAIKAN QUERY DISINI:
                            // 1. Ganti r.Admin_Id menjadi r.Dilakukan_Oleh
                            // 2. Ganti r.Tanggal menjadi r.Created_At
                            $hist = $conn->query("SELECT r.*, a.Nama_Lengkap 
                                                  FROM riwayat_stok r 
                                                  LEFT JOIN admin a ON r.Dilakukan_Oleh = a.Id_Admin 
                                                  WHERE Id_Buku = '$id_buku' 
                                                  ORDER BY r.Created_At DESC LIMIT 5");
                            
                            if($hist && $hist->num_rows > 0) {
                                while($h = $hist->fetch_assoc()) {
                                    $badge = ($h['Jenis_Transaksi'] == 'Tambah' || $h['Jenis_Transaksi'] == 'Perbaikan') ? 'success' : 'danger';
                                    
                                    // PERBAIKAN TAMPILAN TANGGAL
                                    $tgl = isset($h['Created_At']) ? date('d/m/Y H:i', strtotime($h['Created_At'])) : '-';
                                    
                                    echo "<tr>
                                        <td>{$tgl}</td>
                                        <td><label class='badge badge-$badge'>{$h['Jenis_Transaksi']}</label></td>
                                        <td>{$h['Jumlah']}</td>
                                        <td>{$h['Keterangan']}</td>
                                        <td>{$h['Nama_Lengkap']}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center'>Belum ada riwayat.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                    <div class="text-center mt-3">
                        <a href="buku_riwayat.php?id=<?php echo $id_buku; ?>">Lihat Semua Riwayat &rarr;</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>