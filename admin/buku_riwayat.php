<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

$filter_sql = "";
if (isset($_GET['id'])) {
    $id_filter = clean_input($_GET['id']);
    $filter_sql = "WHERE r.Id_Buku = '$id_filter'";
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title text-primary"><i class="mdi mdi-history mr-2"></i>Laporan Riwayat Stok</h4>
                    <?php if(!empty($filter_sql)): ?>
                    <a href="buku_riwayat.php" class="btn btn-sm btn-light border">Tampilkan Semua</a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Waktu</th>
                                <th>Judul Buku</th>
                                <th>Transaksi</th>
                                <th>Jml</th>
                                <th>Keterangan</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // PERBAIKAN QUERY:
                            // 1. Menggunakan r.Dilakukan_Oleh bukan r.Id_Admin
                            // 2. Mengambil Created_At
                            $sql = "SELECT r.*, b.Judul, a.Nama_Lengkap 
                                    FROM riwayat_stok r 
                                    JOIN buku b ON r.Id_Buku = b.Id_Buku 
                                    LEFT JOIN admin a ON r.Dilakukan_Oleh = a.Id_Admin 
                                    $filter_sql 
                                    ORDER BY r.Created_At DESC";
                                    
                            $q = $conn->query($sql);

                            if ($q && $q->num_rows > 0) {
                                while($row = $q->fetch_assoc()) {
                                    $color = ($row['Jenis_Transaksi'] == 'Tambah' || $row['Jenis_Transaksi'] == 'Perbaikan') ? 'success' : 'danger';
                                    
                                    // PERBAIKAN TANGGAL: Menggunakan kolom Created_At dari database
                                    $tanggal = isset($row['Created_At']) ? date('d/m/Y H:i', strtotime($row['Created_At'])) : '-';
                                    
                                    echo "<tr>
                                        <td>{$tanggal}</td>
                                        <td>
                                            <span class='text-dark font-weight-bold'>{$row['Judul']}</span><br>
                                            <small class='text-muted'>{$row['Id_Buku']}</small>
                                        </td>
                                        <td><label class='badge badge-$color'>{$row['Jenis_Transaksi']}</label></td>
                                        <td class='font-weight-bold'>{$row['Jumlah']}</td>
                                        <td>{$row['Keterangan']}</td>
                                        <td>{$row['Nama_Lengkap']}</td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-5'>Belum ada data riwayat stok.</td></tr>";
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