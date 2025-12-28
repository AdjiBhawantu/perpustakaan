<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// --- LOGIC: EXPORT CSV ---
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data_buku_'.date('Ymd').'.csv');
    $output = fopen('php://output', 'w');
    
    // Perbaikan Header CSV: Pengarang -> Penulis
    fputcsv($output, array('ID Buku', 'Judul', 'Penulis', 'Penerbit', 'Kategori', 'Total', 'Tersedia'));
    
    $rows = $conn->query("SELECT * FROM buku");
    while ($row = $rows->fetch_assoc()) {
        // Perbaikan Data CSV: $row['Pengarang'] -> $row['Penulis']
        fputcsv($output, array($row['Id_Buku'], $row['Judul'], $row['Penulis'], $row['Penerbit'], $row['Id_Kategori'], $row['Jumlah_Total'], $row['Jumlah_Tersedia']));
    }
    fclose($output);
    exit;
}

// --- LOGIC: DELETE ---
if (isset($_GET['hapus'])) {
    $id = clean_input($_GET['hapus']);
    // Cek apakah ada peminjaman aktif? Jika ya, jangan hapus.
    $cek = $conn->query("SELECT * FROM peminjaman WHERE Id_Buku = '$id' AND Status_Peminjaman = 'Dipinjam'");
    if ($cek->num_rows > 0) {
        echo "<script>alert('Gagal! Buku sedang dipinjam.'); window.location='buku_daftar.php';</script>";
    } else {
        $conn->query("DELETE FROM buku WHERE Id_Buku = '$id'");
        echo "<script>alert('Buku berhasil dihapus.'); window.location='buku_daftar.php';</script>";
    }
}

// --- LOGIC: AJAX SEARCH ---
if (isset($_GET['ajax_search'])) {
    $keyword = clean_input($_GET['keyword']);
    $sql = "SELECT * FROM buku WHERE Judul LIKE '%$keyword%' OR Penulis LIKE '%$keyword%' OR Id_Buku LIKE '%$keyword%' ORDER BY Judul ASC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $status_badge = ($row['Jumlah_Tersedia'] > 0) ? '<label class="badge badge-success">Tersedia</label>' : '<label class="badge badge-danger">Habis</label>';
            $cover = !empty($row['Cover_Buku']) ? "../uploads/buku/".$row['Cover_Buku'] : "images/no-cover.png";
            
            // PERBAIKAN DI SINI: Menghapus spasi pada key array $row['Id_Buku']
            echo "<tr>
                <td>
                    <img src='$cover' style='width:50px; height:70px; border-radius:5px; object-fit:cover;'>
                </td>
                <td>
                    <strong class='text-primary'>{$row['Judul']}</strong><br>
                    <small class='text-muted'>{$row['Id_Buku']}</small>
                </td>
                <td>{$row['Penulis']}</td>
                <td>{$row['Id_Kategori']}</td>
                <td>
                    <b>{$row['Jumlah_Tersedia']}</b> / {$row['Jumlah_Total']}
                </td>
                <td>$status_badge</td>
                <td>
                    <a href='buku_tambah.php?id={$row['Id_Buku']}' class='btn btn-sm btn-info'><i class='mdi mdi-pencil'></i></a>
                    <a href='buku_stok.php?id={$row['Id_Buku']}' class='btn btn-sm btn-warning'><i class='mdi mdi-cube-send'></i></a>
                    <a href='?hapus={$row['Id_Buku']}' onclick=\"return confirm('Hapus buku ini?')\" class='btn btn-sm btn-danger'><i class='mdi mdi-delete'></i></a>
                </td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='7' class='text-center py-4'>Tidak ada data ditemukan.</td></tr>";
    }
    exit;
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title text-primary"><i class="mdi mdi-book-open-page-variant mr-2"></i>Katalog Buku
                    </h4>
                    <div>
                        <a href="?export=csv" class="btn btn-outline-success btn-sm mr-2"><i
                                class="mdi mdi-file-excel"></i> Export CSV</a>
                        <a href="buku_tambah.php" class="btn btn-primary btn-sm"><i class="mdi mdi-plus"></i> Tambah
                            Buku</a>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white"><i
                                        class="mdi mdi-magnify text-primary"></i></span>
                            </div>
                            <input type="text" id="keyword" class="form-control"
                                placeholder="Cari Judul, Penulis, atau Kode Buku...">
                        </div>
                    </div>
                    <div class="col-md-6 text-right">
                        <button class="btn btn-light btn-sm border"
                            onclick="alert('Fitur Import Excel akan segera hadir!')">
                            <i class="mdi mdi-upload"></i> Import Buku
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Cover</th>
                                <th>Info Buku</th>
                                <th>Penulis</th>
                                <th>Kategori</th>
                                <th>Stok (Ada/Total)</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tabel-buku">
                            <?php 
                            $q = $conn->query("SELECT * FROM buku ORDER BY Created_At DESC LIMIT 20");
                            while ($row = $q->fetch_assoc()) {
                                $status = ($row['Jumlah_Tersedia'] > 0) ? '<label class="badge badge-success">Tersedia</label>' : '<label class="badge badge-danger">Habis</label>';
                                $cover = !empty($row['Cover_Buku']) ? "../uploads/buku/".$row['Cover_Buku'] : "images/no-cover.png";
                            ?>
                            <tr>
                                <td><img src="<?php echo $cover; ?>"
                                        style="width:50px; height:70px; border-radius:5px; object-fit:cover;"></td>
                                <td>
                                    <strong class="text-primary"><?php echo $row['Judul']; ?></strong><br>
                                    <small class="text-muted"><?php echo $row['Id_Buku']; ?></small>
                                </td>
                                <td><?php echo $row['Penulis']; ?></td>
                                <td><?php echo $row['Id_Kategori']; ?></td>
                                <td><b><?php echo $row['Jumlah_Tersedia']; ?></b> / <?php echo $row['Jumlah_Total']; ?>
                                </td>
                                <td><?php echo $status; ?></td>
                                <td>
                                    <a href="buku_tambah.php?id=<?php echo $row['Id_Buku']; ?>"
                                        class="btn btn-sm btn-inverse-info icon-btn" title="Edit"><i
                                            class="mdi mdi-pencil"></i></a>
                                    <a href="buku_stok.php?id=<?php echo $row['Id_Buku']; ?>"
                                        class="btn btn-sm btn-inverse-warning icon-btn" title="Stok"><i
                                            class="mdi mdi-cube-send"></i></a>
                                    <a href="?hapus=<?php echo $row['Id_Buku']; ?>"
                                        onclick="return confirm('Hapus data ini?')"
                                        class="btn btn-sm btn-inverse-danger icon-btn" title="Hapus"><i
                                            class="mdi mdi-delete"></i></a>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('#keyword').on('keyup', function() {
        var key = $(this).val();
        $.get('buku_daftar.php?ajax_search=1&keyword=' + key, function(data) {
            $('#tabel-buku').html(data);
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>