<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

// --- PROSES TAMBAH KATEGORI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    
    // 1. Generate ID Otomatis
    $id_kategori = generate_id_kategori();
    
    // 2. Tangkap Input
    $nama = clean_input($_POST['nama_kategori']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $created_by = $_SESSION['user_id']; // Ambil ID Admin yang login

    // 3. Validasi
    if(empty($nama)) {
        echo "<script>alert('Nama kategori tidak boleh kosong!');</script>";
    } else {
        // 4. Simpan ke Database (Sesuai Struktur DB)
        $sql = "INSERT INTO kategori_buku (Id_Kategori, Nama_Kategori, Deskripsi, Created_By, Created_At) 
                VALUES (?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $id_kategori, $nama, $deskripsi, $created_by);
        
        if ($stmt->execute()) {
            echo "<script>alert('Kategori berhasil ditambahkan!'); window.location='buku_kategori.php';</script>";
        } else {
            echo "<script>alert('Gagal menambah kategori: " . $stmt->error . "');</script>";
        }
    }
}

// --- PROSES HAPUS KATEGORI ---
if (isset($_GET['hapus'])) {
    $id = clean_input($_GET['hapus']);
    
    // Cek dulu apakah kategori dipakai di tabel buku?
    $cek = $conn->query("SELECT * FROM buku WHERE Id_Kategori = '$id'");
    if ($cek->num_rows > 0) {
        echo "<script>alert('Gagal! Kategori ini sedang digunakan oleh buku lain.'); window.location='buku_kategori.php';</script>";
    } else {
        $stmt = $conn->prepare("DELETE FROM kategori_buku WHERE Id_Kategori = ?");
        $stmt->bind_param("s", $id);
        
        if ($stmt->execute()) {
            echo "<script>alert('Kategori berhasil dihapus!'); window.location='buku_kategori.php';</script>";
        } else {
            echo "<script>alert('Gagal menghapus data.');</script>";
        }
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-plus-circle mr-2"></i>Tambah Kategori</h4>
                <p class="card-description text-muted small">Buat kategori baru untuk pengelompokan buku.</p>

                <form method="POST">
                    <div class="form-group">
                        <label>ID Kategori (Auto)</label>
                        <input type="text" class="form-control" value="Auto-Generated" disabled
                            style="background-color: #f8fafc;">
                    </div>

                    <div class="form-group">
                        <label>Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" name="nama_kategori" class="form-control"
                            placeholder="Contoh: Sains, Novel, Sejarah..." required>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi (Opsional)</label>
                        <textarea name="deskripsi" class="form-control" rows="4"
                            placeholder="Penjelasan singkat tentang kategori ini..."></textarea>
                    </div>

                    <button type="submit" name="tambah" class="btn btn-primary btn-block">
                        <i class="mdi mdi-check"></i> Simpan Kategori
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-format-list-bulleted mr-2"></i>Daftar Kategori
                </h4>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background-color: #ebf8ff; color: #3182ce;">
                            <tr>
                                <th>ID</th>
                                <th>Nama Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sql = "SELECT * FROM kategori_buku ORDER BY Nama_Kategori ASC";
                            $q = $conn->query($sql);
                            
                            if ($q && $q->num_rows > 0) {
                                while($row = $q->fetch_assoc()) {
                                    $desc = !empty($row['Deskripsi']) ? substr($row['Deskripsi'], 0, 50) . '...' : '<i class="text-muted">-</i>';
                                    echo "<tr>
                                        <td><span class='badge badge-outline-primary'>{$row['Id_Kategori']}</span></td>
                                        <td><strong>{$row['Nama_Kategori']}</strong></td>
                                        <td class='text-muted small'>{$desc}</td>
                                        <td class='text-center'>
                                            <a href='?hapus={$row['Id_Kategori']}' onclick=\"return confirm('Yakin ingin menghapus kategori ini?')\" class='btn btn-inverse-danger btn-sm btn-icon' title='Hapus'>
                                                <i class='mdi mdi-delete'></i>
                                            </a>
                                        </td>
                                    </tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='text-center py-4 text-muted'>Belum ada kategori buku.</td></tr>";
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