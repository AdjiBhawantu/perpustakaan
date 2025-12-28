<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Ambil Data Peminjaman Aktif (Status = Dipinjam)
// Kita gunakan tabel peminjaman langsung
$sql = "SELECT p.*, a.Nama_Lengkap, b.Judul, b.Cover_Buku,
        DATEDIFF(CURRENT_DATE, p.Tgl_Kembali_Rencana) as Telat_Hari
        FROM peminjaman p
        JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
        JOIN buku b ON p.Id_Buku = b.Id_Buku
        WHERE p.Status_Peminjaman = 'Dipinjam'
        ORDER BY p.Tgl_Kembali_Rencana ASC";
$result = $conn->query($sql);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-book-open-page-variant mr-2"></i>Sedang Dipinjam
                </h4>
                <p class="card-description">Daftar buku yang sedang berada di tangan anggota.</p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Buku</th>
                                <th>Peminjam</th>
                                <th>Tgl Pinjam</th>
                                <th>Jatuh Tempo</th>
                                <th>Status Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <?php 
                                        $telat = $row['Telat_Hari'];
                                        $badge_waktu = ($telat > 0) 
                                            ? '<span class="badge badge-danger">Telat '.$telat.' Hari</span>' 
                                            : '<span class="badge badge-success">Aman</span>';
                                    ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($row['Cover_Buku']) ? '../uploads/buku/'.$row['Cover_Buku'] : 'images/no-cover.png'; ?>"
                                            style="width:40px; height:60px; border-radius:3px; margin-right:10px;">
                                        <div>
                                            <span
                                                class="font-weight-bold"><?php echo substr($row['Judul'], 0, 30); ?>...</span><br>
                                            <small class="text-muted"><?php echo $row['Id_Buku']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php echo $row['Nama_Lengkap']; ?><br>
                                    <small class="text-muted"><?php echo $row['Id_Anggota']; ?></small>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['Tgl_Pinjam'])); ?></td>
                                <td class="font-weight-bold">
                                    <?php echo date('d/m/Y', strtotime($row['Tgl_Kembali_Rencana'])); ?></td>
                                <td><?php echo $badge_waktu; ?></td>
                                <td>
                                    <a href="pinjam_kembali.php?id=<?php echo $row['Id_Peminjaman']; ?>"
                                        class="btn btn-warning btn-sm text-white">
                                        <i class="mdi mdi-keyboard-return"></i> Pengembalian
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">Tidak ada buku yang sedang dipinjam.</td>
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