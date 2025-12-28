<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Filter Tanggal (Opsional, default bulan ini)
$sql = "SELECT p.*, a.Nama_Lengkap, b.Judul 
        FROM peminjaman p
        JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
        JOIN buku b ON p.Id_Buku = b.Id_Buku
        WHERE p.Status_Peminjaman != 'Dipinjam'
        ORDER BY p.Updated_At DESC LIMIT 100";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-history mr-2"></i>Riwayat Transaksi Selesai</h4>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID Transaksi</th>
                                <th>Anggota</th>
                                <th>Buku</th>
                                <th>Tgl Kembali</th>
                                <th>Status Akhir</th>
                                <th>Kondisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['Id_Peminjaman']; ?></td>
                                <td><?php echo $row['Nama_Lengkap']; ?></td>
                                <td><?php echo $row['Judul']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['Tgl_Kembali_Aktual'])); ?></td>
                                <td>
                                    <?php if($row['Status_Peminjaman'] == 'Dikembalikan'): ?>
                                    <span class="badge badge-success">Selesai</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger"><?php echo $row['Status_Peminjaman']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['Kondisi_Kembali']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Belum ada riwayat transaksi.</td>
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