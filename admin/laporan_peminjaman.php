<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Filter Tanggal
$tgl_awal = $_GET['tgl_awal'] ?? date('Y-m-01'); // Default awal bulan
$tgl_akhir = $_GET['tgl_akhir'] ?? date('Y-m-d'); // Default hari ini

// Query Statistik Ringkasan
$sql_stat = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status_Peminjaman = 'Dipinjam' THEN 1 ELSE 0 END) as dipinjam,
                SUM(CASE WHEN Status_Peminjaman = 'Dikembalikan' THEN 1 ELSE 0 END) as dikembalikan,
                SUM(CASE WHEN Status_Peminjaman = 'Terlambat' THEN 1 ELSE 0 END) as terlambat
             FROM peminjaman 
             WHERE Tgl_Pinjam BETWEEN '$tgl_awal' AND '$tgl_akhir'";
$stat = $conn->query($sql_stat)->fetch_assoc();

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-chart-bar mr-2"></i>Laporan Peminjaman</h4>

                <form method="GET" class="form-inline mb-4">
                    <label class="mr-2">Periode:</label>
                    <input type="date" name="tgl_awal" class="form-control mr-2" value="<?php echo $tgl_awal; ?>">
                    <span class="mr-2">s/d</span>
                    <input type="date" name="tgl_akhir" class="form-control mr-2" value="<?php echo $tgl_akhir; ?>">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="mdi mdi-filter"></i> Filter</button>
                    <a href="laporan_peminjaman.php" class="btn btn-light btn-sm ml-2">Reset</a>
                </form>

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white text-center p-3">
                            <h3><?php echo $stat['total']; ?></h3>
                            <small>Total Transaksi</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white text-center p-3">
                            <h3><?php echo $stat['dipinjam']; ?></h3>
                            <small>Sedang Dipinjam</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white text-center p-3">
                            <h3><?php echo $stat['dikembalikan']; ?></h3>
                            <small>Berhasil Dikembalikan</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white text-center p-3">
                            <h3><?php echo $stat['terlambat']; ?></h3>
                            <small>Kasus Terlambat</small>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3">Rincian Data</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="bg-light">
                            <tr>
                                <th>ID</th>
                                <th>Tgl Pinjam</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Status</th>
                                <th>Tgl Kembali</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT p.*, a.Nama_Lengkap, b.Judul 
                                    FROM peminjaman p
                                    JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                                    JOIN buku b ON p.Id_Buku = b.Id_Buku
                                    WHERE p.Tgl_Pinjam BETWEEN '$tgl_awal' AND '$tgl_akhir'
                                    ORDER BY p.Tgl_Pinjam DESC";
                            $result = $conn->query($sql);

                            if($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_clr = ($row['Status_Peminjaman'] == 'Dikembalikan') ? 'success' : 
                                                  (($row['Status_Peminjaman'] == 'Terlambat') ? 'danger' : 'warning');
                                    echo "<tr>
                                        <td>{$row['Id_Peminjaman']}</td>
                                        <td>".date('d/m/Y', strtotime($row['Tgl_Pinjam']))."</td>
                                        <td>{$row['Nama_Lengkap']}</td>
                                        <td>{$row['Judul']}</td>
                                        <td><span class='badge badge-$status_clr'>{$row['Status_Peminjaman']}</span></td>
                                        <td>".($row['Tgl_Kembali_Aktual'] ? date('d/m/Y', strtotime($row['Tgl_Kembali_Aktual'])) : '-')."</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>Tidak ada data pada periode ini.</td></tr>";
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