<?php
// FILE: admin/ajax_cari.php

// Panggil koneksi saja, tidak perlu header/footer HTML penuh
require_once dirname(__DIR__) . '/config/database.php';

// Cek apakah ada request POST
if (isset($_POST['keyword'])) {
    
    $keyword = mysqli_real_escape_string($conn, $_POST['keyword']);
    
    // Query Pencarian (Cari berdasarkan Nama, Username, ID, atau Email)
    $sql = "SELECT * FROM anggota 
            WHERE Nama_Lengkap LIKE '%$keyword%' 
            OR Username LIKE '%$keyword%' 
            OR Id_Anggota LIKE '%$keyword%' 
            OR Email LIKE '%$keyword%'
            ORDER BY Nama_Lengkap ASC LIMIT 10"; // Limit 10 agar tidak berat
            
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        ?>
<div class="table-responsive animate-up">
    <table class="table table-hover">
        <thead style="background-color: #ebf8ff; color: #3182ce;">
            <tr>
                <th>Profil</th>
                <th>Kontak</th>
                <th>Status</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
            <?php 
                            // Inisial Nama untuk Avatar
                            $inisial = substr($row['Nama_Lengkap'], 0, 1);
                        ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div
                            style="width:40px; height:40px; background:#e0f2fe; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#0284c7; font-weight:bold; margin-right:15px; font-size: 1.2rem;">
                            <?= $inisial ?>
                        </div>
                        <div>
                            <div style="font-weight: 700; color: #2d3748;"><?= $row['Nama_Lengkap'] ?></div>
                            <small class="text-muted">ID: <?= $row['Id_Anggota'] ?></small>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="font-size: 0.9rem;">
                        <i class="mdi mdi-email-outline mr-1"></i> <?= $row['Email'] ?><br>
                        <i class="mdi mdi-phone mr-1"></i> <?= $row['No_Telepon'] ?? '-' ?>
                    </div>
                </td>
                <td>
                    <?php if($row['Status_Akun'] == 'Aktif'): ?>
                    <span class="badge badge-success px-3 py-2" style="border-radius: 50px;">Aktif</span>
                    <?php else: ?>
                    <span class="badge badge-danger px-3 py-2" style="border-radius: 50px;">Non-Aktif</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="anggota_detail.php?id=<?= $row['Id_Anggota'] ?>" class="btn btn-sm btn-outline-primary"
                        style="border-radius: 10px;">
                        <i class="mdi mdi-eye"></i> Detail
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
<?php
    } else {
        // JIKA TIDAK ADA HASIL
        echo '
        <div class="text-center py-5">
            <div style="font-size: 4rem; opacity: 0.2;">😕</div>
            <h5 class="text-muted mt-3">Data tidak ditemukan</h5>
            <p class="text-muted">Coba kata kunci lain seperti nama atau ID anggota.</p>
        </div>
        ';
    }
}
?>