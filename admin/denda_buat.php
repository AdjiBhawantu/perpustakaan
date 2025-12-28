<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// PROSES SIMPAN DENDA
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pinjam = clean_input($_POST['id_peminjaman']);
    $jenis = clean_input($_POST['jenis_denda']);
    $jumlah = str_replace('.', '', $_POST['jumlah']);
    $catatan = clean_input($_POST['catatan']);
    $admin_id = $_SESSION['user_id'];
    
    // Generate ID
    $id_denda = "DND-" . time() . rand(100,999);
    
    // Tentukan kolom biaya (Kerusakan/Kehilangan)
    $col_biaya = ($jenis == 'Kehilangan') ? 'Biaya_Kehilangan' : 'Biaya_Kerusakan';
    
    // Insert Query
    // Kita set Jumlah_Hari_Terlambat 0 karena ini denda manual (kerusakan/hilang)
    $sql = "INSERT INTO denda (Id_Denda, Id_Peminjaman, Jenis_Denda, $col_biaya, Total_Denda, Catatan, Dibuat_Oleh, Created_At) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssddss", $id_denda, $id_pinjam, $jenis, $jumlah, $jumlah, $catatan, $admin_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Denda berhasil dibuat!'); window.location='denda_daftar.php';</script>";
    } else {
        $error = "Gagal membuat denda: " . $conn->error;
    }
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-file-document-box-plus mr-2"></i>Input Denda
                    Manual</h4>
                <p class="card-description">Gunakan fitur ini untuk denda susulan (Kerusakan/Kehilangan).</p>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <label>ID Peminjaman</label>
                        <select name="id_peminjaman" class="form-control" required>
                            <option value="">-- Pilih Transaksi --</option>
                            <?php
                            // Ambil 50 transaksi terakhir
                            $q = $conn->query("SELECT p.Id_Peminjaman, a.Nama_Lengkap, b.Judul 
                                               FROM peminjaman p 
                                               JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                                               JOIN buku b ON p.Id_Buku = b.Id_Buku
                                               ORDER BY p.Tgl_Pinjam DESC LIMIT 50");
                            while($r = $q->fetch_assoc()) {
                                echo "<option value='{$r['Id_Peminjaman']}'>{$r['Id_Peminjaman']} - {$r['Nama_Lengkap']} ({$r['Judul']})</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Jenis Pelanggaran</label>
                        <select name="jenis_denda" class="form-control" required>
                            <option value="Kerusakan">Kerusakan Buku</option>
                            <option value="Kehilangan">Kehilangan Buku</option>
                            <option value="Keterlambatan">Keterlambatan (Susulan)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nominal Denda (Rp)</label>
                        <input type="number" name="jumlah" class="form-control" placeholder="Contoh: 50000" min="1000"
                            required>
                    </div>

                    <div class="form-group">
                        <label>Catatan / Keterangan</label>
                        <textarea name="catatan" class="form-control" rows="3"
                            placeholder="Contoh: Halaman sobek di bab 3..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2">Simpan Denda</button>
                    <a href="denda_daftar.php" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>