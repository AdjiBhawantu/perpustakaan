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
                <h4 class="card-title text-primary"><i class="mdi mdi-star mr-2"></i>Laporan Buku Terpopuler</h4>
                <p class="text-muted">Daftar buku berdasarkan frekuensi peminjaman tertinggi.</p>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th width="5%">Rank</th>
                                <th>Cover</th>
                                <th>Judul Buku</th>
                                <th>Penulis</th>
                                <th>Kategori</th>
                                <th class="text-center">Total Dipinjam</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Query Top 20 Buku
                            $sql = "SELECT b.*, k.Nama_Kategori 
                                    FROM buku b
                                    LEFT JOIN kategori_buku k ON b.Id_Kategori = k.Id_Kategori
                                    ORDER BY b.Jumlah_Dipinjam DESC LIMIT 20";
                            $result = $conn->query($sql);
                            $rank = 1;

                            if ($result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $cover = !empty($row['Cover_Buku']) ? "../uploads/buku/".$row['Cover_Buku'] : "images/no-cover.png";
                                    
                                    // Icon Medali
                                    $icon = "";
                                    if($rank == 1) $icon = "🥇";
                                    elseif($rank == 2) $icon = "🥈";
                                    elseif($rank == 3) $icon = "🥉";
                                    else $icon = "#".$rank;
                            ?>
                            <tr>
                                <td class="font-weight-bold h5 text-center"><?php echo $icon; ?></td>
                                <td>
                                    <img src="<?php echo $cover; ?>"
                                        style="width:40px; height:60px; border-radius:3px; object-fit:cover;">
                                </td>
                                <td>
                                    <span class="font-weight-bold"><?php echo $row['Judul']; ?></span><br>
                                    <small class="text-muted"><?php echo $row['Kode_Buku']; ?></small>
                                </td>
                                <td><?php echo $row['Penulis']; ?></td>
                                <td><?php echo $row['Nama_Kategori']; ?></td>
                                <td class="text-center">
                                    <span class="badge badge-pill badge-primary" style="font-size:1rem;">
                                        <?php echo $row['Jumlah_Dipinjam']; ?> x
                                    </span>
                                </td>
                            </tr>
                            <?php 
                                    $rank++;
                                }
                            } else {
                                echo "<tr><td colspan='6' class='text-center py-5'>Belum ada data peminjaman.</td></tr>";
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