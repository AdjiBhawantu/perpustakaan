<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

if (!isset($_GET['id'])) redirect('denda_daftar.php');
$id_denda = clean_input($_GET['id']);

// Ambil Detail Denda
$sql = "SELECT d.*, a.Nama_Lengkap, p.Id_Peminjaman, b.Judul 
        FROM denda d
        JOIN peminjaman p ON d.Id_Peminjaman = p.Id_Peminjaman
        JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
        JOIN buku b ON p.Id_Buku = b.Id_Buku
        WHERE d.Id_Denda = '$id_denda'";
$data = $conn->query($sql)->fetch_assoc();

if (!$data) die("Data denda tidak ditemukan.");

// Hitung Sisa yang harus dibayar
// Jika Sisa_Denda NULL (belum pernah bayar), maka Sisa = Total
$sisa_tagihan = ($data['Sisa_Denda'] === NULL) ? $data['Total_Denda'] : $data['Sisa_Denda'];

// PROSES PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bayar = str_replace('.', '', $_POST['jumlah_bayar']); // Hapus format ribuan
    $metode = $_POST['metode'];
    $admin_id = $_SESSION['user_id'];
    
    if ($bayar <= 0) {
        $error = "Jumlah pembayaran tidak valid.";
    } elseif ($bayar > $sisa_tagihan) {
        $error = "Jumlah bayar melebihi sisa tagihan (Maks: Rp ".number_format($sisa_tagihan).")";
    } else {
        // Mulai Transaksi
        $conn->begin_transaction();
        try {
            // 1. Catat di tabel pembayaran_denda
            $id_pembayaran = "PAY-" . time() . rand(100,999);
            $sql_pay = "INSERT INTO pembayaran_denda (Id_Pembayaran, Id_Denda, Jumlah_Bayar, Metode_Pembayaran, Diterima_Oleh, Tanggal_Bayar) 
                        VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql_pay);
            $stmt->bind_param("ssiss", $id_pembayaran, $id_denda, $bayar, $metode, $admin_id);
            $stmt->execute();
            
            // 2. Update tabel denda
            $sisa_baru = $sisa_tagihan - $bayar;
            $total_dibayar_baru = $data['Jumlah_Dibayar'] + $bayar;
            
            $status_baru = ($sisa_baru <= 0) ? 'Lunas' : 'Dibayar Sebagian';
            
            $sql_update = "UPDATE denda SET Jumlah_Dibayar = ?, Sisa_Denda = ?, Status_Pembayaran = ? WHERE Id_Denda = ?";
            $stmt_up = $conn->prepare($sql_update);
            $stmt_up->bind_param("ddss", $total_dibayar_baru, $sisa_baru, $status_baru, $id_denda);
            $stmt_up->execute();
            
            $conn->commit();
            echo "<script>alert('Pembayaran berhasil dicatat!'); window.location='denda_daftar.php';</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Gagal memproses: " . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-wallet mr-2"></i>Proses Pembayaran Denda</h4>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <div class="bg-light p-4 rounded mb-4 border">
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">ID Denda</div>
                        <div class="col-sm-8 font-weight-bold"><?php echo $data['Id_Denda']; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Anggota</div>
                        <div class="col-sm-8"><?php echo $data['Nama_Lengkap']; ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4 text-muted">Jenis Denda</div>
                        <div class="col-sm-8"><?php echo $data['Jenis_Denda']; ?></div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-sm-4">Total Tagihan</div>
                        <div class="col-sm-8">Rp <?php echo number_format($data['Total_Denda'], 0, ',', '.'); ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-sm-4">Sudah Dibayar</div>
                        <div class="col-sm-8 text-success">Rp
                            <?php echo number_format($data['Jumlah_Dibayar'], 0, ',', '.'); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4 font-weight-bold text-danger">Sisa Tagihan</div>
                        <div class="col-sm-8 font-weight-bold text-danger" style="font-size: 1.2rem;">
                            Rp <?php echo number_format($sisa_tagihan, 0, ',', '.'); ?>
                        </div>
                    </div>
                </div>

                <?php if($sisa_tagihan > 0): ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Jumlah Pembayaran (Rp)</label>
                        <input type="number" name="jumlah_bayar" class="form-control form-control-lg"
                            value="<?php echo $sisa_tagihan; ?>" max="<?php echo $sisa_tagihan; ?>" required>
                        <small class="text-muted">Masukkan nominal pembayaran. Default adalah pelunasan.</small>
                    </div>

                    <div class="form-group">
                        <label>Metode Pembayaran</label>
                        <select name="metode" class="form-control" required>
                            <option value="Tunai">Tunai / Cash</option>
                            <option value="Transfer">Transfer Bank</option>
                            <option value="E-Wallet">E-Wallet (QRIS/Gopay/OVO)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg mt-4">
                        <i class="mdi mdi-check-circle"></i> Konfirmasi Pembayaran
                    </button>
                    <a href="denda_daftar.php" class="btn btn-light btn-block mt-2">Batal</a>
                </form>
                <?php else: ?>
                <div class="alert alert-success text-center">
                    <h4><i class="mdi mdi-check-all"></i> LUNAS</h4>
                    <p>Tagihan denda ini sudah lunas.</p>
                </div>
                <a href="denda_daftar.php" class="btn btn-light btn-block">Kembali</a>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>