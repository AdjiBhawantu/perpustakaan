<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-account-star mr-2"></i>Laporan Keaktifan Anggota
                </h4>
                <p class="text-muted">Peringkat anggota berdasarkan jumlah buku yang pernah dipinjam.</p>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Nama Anggota</th>
                                <th>ID Anggota</th>
                                <th>Email</th>
                                <th>Status Akun</th>
                                <th class="text-center">Total Peminjaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Query Menghitung Jumlah Peminjaman per Anggota
                            $sql = "SELECT a.Id_Anggota, a.Nama_Lengkap, a.Email, a.Status_Akun, 
                                    COUNT(p.Id_Peminjaman) as total_pinjam 
                                    FROM anggota a
                                    LEFT JOIN peminjaman p ON a.Id_Anggota = p.Id_Anggota
                                    GROUP BY a.Id_Anggota
                                    ORDER BY total_pinjam DESC LIMIT 50";
                            $result = $conn->query($sql);
                            $no = 1;

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $status_badge = ($row['Status_Akun'] == 'Aktif') ? 'success' : 'danger';
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td class="font-weight-bold"><?php echo $row['Nama_Lengkap']; ?></td>
                                <td><?php echo $row['Id_Anggota']; ?></td>
                                <td><?php echo $row['Email']; ?></td>
                                <td><label
                                        class="badge badge-<?php echo $status_badge; ?>"><?php echo $row['Status_Akun']; ?></label>
                                </td>
                                <td class="text-center">
                                    <h5 class="text-primary mb-0 font-weight-bold"><?php echo $row['total_pinjam']; ?>
                                    </h5>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-5'>Belum ada data anggota.</td></tr>";
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