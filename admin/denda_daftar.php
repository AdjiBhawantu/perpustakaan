<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Filter Status
$where_status = "";
if (isset($_GET['status']) && $_GET['status'] != '') {
    $status = clean_input($_GET['status']);
    $where_status = "WHERE d.Status_Pembayaran = '$status'";
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title text-primary"><i class="mdi mdi-cash-multiple mr-2"></i>Daftar Tagihan Denda
                    </h4>
                    <div>
                        <a href="denda_daftar.php" class="btn btn-sm btn-light border mr-1">Semua</a>
                        <a href="denda_daftar.php?status=Belum Dibayar"
                            class="btn btn-sm btn-danger text-white mr-1">Belum Lunas</a>
                        <a href="denda_daftar.php?status=Lunas" class="btn btn-sm btn-success text-white">Lunas</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>ID Denda</th>
                                <th>Anggota</th>
                                <th>Jenis</th>
                                <th>Total Tagihan</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT d.*, a.Nama_Lengkap, b.Judul 
                                    FROM denda d
                                    JOIN peminjaman p ON d.Id_Peminjaman = p.Id_Peminjaman
                                    JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                                    JOIN buku b ON p.Id_Buku = b.Id_Buku
                                    $where_status
                                    ORDER BY d.Created_At DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    // Warna Badge Status
                                    $badge = 'warning';
                                    if($row['Status_Pembayaran'] == 'Lunas') $badge = 'success';
                                    elseif($row['Status_Pembayaran'] == 'Belum Dibayar') $badge = 'danger';
                                    
                                    // Hitung Sisa (Jika null, berarti sisa = total)
                                    $sisa = $row['Sisa_Denda'] ?? $row['Total_Denda'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?php echo $row['Id_Denda']; ?></strong><br>
                                    <small
                                        class="text-muted"><?php echo date('d/m/Y', strtotime($row['Created_At'])); ?></small>
                                </td>
                                <td>
                                    <?php echo $row['Nama_Lengkap']; ?><br>
                                    <small class="text-muted">Buku:
                                        <?php echo substr($row['Judul'], 0, 20); ?>...</small>
                                </td>
                                <td><?php echo $row['Jenis_Denda']; ?></td>
                                <td class="font-weight-bold">Rp
                                    <?php echo number_format($row['Total_Denda'], 0, ',', '.'); ?></td>
                                <td class="text-danger">Rp <?php echo number_format($sisa, 0, ',', '.'); ?></td>
                                <td><label
                                        class="badge badge-<?php echo $badge; ?>"><?php echo $row['Status_Pembayaran']; ?></label>
                                </td>
                                <td>
                                    <?php if($row['Status_Pembayaran'] != 'Lunas'): ?>
                                    <a href="denda_bayar.php?id=<?php echo $row['Id_Denda']; ?>"
                                        class="btn btn-primary btn-sm">
                                        <i class="mdi mdi-cash-usd"></i> Bayar
                                    </a>
                                    <?php else: ?>
                                    <button class="btn btn-light btn-sm" disabled><i class="mdi mdi-check"></i>
                                        Selesai</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='7' class='text-center py-4'>Tidak ada data denda.</td></tr>";
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