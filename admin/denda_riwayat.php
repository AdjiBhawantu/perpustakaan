<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Hitung Total Pemasukan
$q_total = $conn->query("SELECT SUM(Jumlah_Bayar) as total FROM pembayaran_denda");
$total_income = $q_total->fetch_assoc()['total'] ?? 0;

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card bg-primary text-white shadow">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <h3 class="mb-0 font-weight-bold">Rp <?php echo number_format($total_income, 0, ',', '.'); ?></h3>
                    <p class="mb-0 mt-2">Total Pemasukan Denda</p>
                </div>
                <i class="mdi mdi-chart-line" style="font-size: 4rem; opacity: 0.5;"></i>
            </div>
        </div>
    </div>

    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary">Riwayat Transaksi Keuangan</h4>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Pembayaran</th>
                                <th>Tanggal</th>
                                <th>Anggota</th>
                                <th>ID Denda</th>
                                <th>Metode</th>
                                <th>Admin Penerima</th>
                                <th class="text-right">Jumlah Masuk</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT pd.*, a.Nama_Lengkap, ad.Nama_Lengkap as Admin_Name 
                                    FROM pembayaran_denda pd
                                    JOIN denda d ON pd.Id_Denda = d.Id_Denda
                                    JOIN peminjaman p ON d.Id_Peminjaman = p.Id_Peminjaman
                                    JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                                    LEFT JOIN admin ad ON pd.Diterima_Oleh = ad.Id_Admin
                                    ORDER BY pd.Tanggal_Bayar DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                            ?>
                            <tr>
                                <td><?php echo $row['Id_Pembayaran']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['Tanggal_Bayar'])); ?></td>
                                <td><?php echo $row['Nama_Lengkap']; ?></td>
                                <td><a
                                        href="denda_bayar.php?id=<?php echo $row['Id_Denda']; ?>"><?php echo $row['Id_Denda']; ?></a>
                                </td>
                                <td><?php echo $row['Metode_Pembayaran']; ?></td>
                                <td><?php echo $row['Admin_Name']; ?></td>
                                <td class="text-right font-weight-bold text-success">
                                    + Rp <?php echo number_format($row['Jumlah_Bayar'], 0, ',', '.'); ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center py-4'>Belum ada transaksi pembayaran.</td></tr>";
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