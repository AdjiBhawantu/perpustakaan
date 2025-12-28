<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// Simple Pagination
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Ambil Data Log
$sql = "SELECT * FROM log_aktivitas ORDER BY Created_At DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

// Hitung Total Data (untuk pagination next/prev logic sederhana)
$total_rows = $conn->query("SELECT COUNT(*) as count FROM log_aktivitas")->fetch_assoc()['count'];
$total_pages = ceil($total_rows / $limit);

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-security mr-2"></i>Audit Log System</h4>
                <p class="text-muted">Rekam jejak aktivitas pengguna di dalam sistem.</p>

                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Waktu</th>
                                <th>User</th>
                                <th>Tipe</th>
                                <th>Aktivitas</th>
                                <th>Deskripsi</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="font-size:0.85rem; white-space:nowrap;">
                                    <?php echo date('d/m/Y H:i:s', strtotime($row['Created_At'])); ?>
                                </td>
                                <td>
                                    <span class="font-weight-bold"><?php echo $row['User_Id']; ?></span>
                                </td>
                                <td>
                                    <?php if($row['User_Type'] == 'Admin'): ?>
                                    <span class="badge badge-primary">Admin</span>
                                    <?php else: ?>
                                    <span class="badge badge-info">Anggota</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-primary font-weight-bold"><?php echo $row['Aktivitas']; ?></td>
                                <td style="max-width: 300px; word-wrap: break-word;">
                                    <?php echo $row['Deskripsi']; ?>
                                </td>
                                <td class="text-muted small"><?php echo $row['IP_Address']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Belum ada log aktivitas.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <small class="text-muted">Menampilkan halaman <?php echo $page; ?> dari
                        <?php echo $total_pages; ?></small>
                    <div>
                        <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>" class="btn btn-sm btn-outline-primary"><i
                                class="mdi mdi-chevron-left"></i> Prev</a>
                        <?php endif; ?>

                        <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>" class="btn btn-sm btn-outline-primary">Next <i
                                class="mdi mdi-chevron-right"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>