<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

$is_edit = false;
$id_buku = generate_id_buku(); // Pastikan fungsi ini ada di functions.php
$judul = $pengarang = $penerbit = $tahun = $isbn = $kategori = $lokasi = $deskripsi = "";
$stok = 1;

// JIKA EDIT
if (isset($_GET['id'])) {
    $is_edit = true;
    $id_buku = clean_input($_GET['id']);
    $q = $conn->query("SELECT * FROM buku WHERE Id_Buku = '$id_buku'");
    $data = $q->fetch_assoc();
    
    if($data) {
        $judul = $data['Judul'];
        // PERBAIKAN 1: Ganti 'Pengarang' jadi 'Penulis' sesuai database
        $pengarang = $data['Penulis']; 
        $penerbit = $data['Penerbit'];
        $tahun = $data['Tahun_Terbit'];
        $isbn = $data['ISBN'];
        // Ambil nama kategori, bukan ID jika di tabel buku menyimpan nama
        // Cek struktur tabel buku, kolomnya Id_Kategori atau Kategori?
        // Berdasarkan db_perpustakaan_v2.sql, tabel buku punya kolom 'Id_Kategori'.
        // Namun kode sebelumnya sepertinya menyimpan Nama Kategori. 
        // Kita sesuaikan agar aman: Jika di DB Id_Kategori, kita simpan ID. Jika Kategori, simpan Nama.
        // Asumsi berdasarkan kode sebelumnya: Tabel buku menyimpan Nama Kategori di kolom 'Id_Kategori' atau 'Kategori' (Varchar).
        // Di SQL dump Anda: `Id_Kategori` varchar(50) DEFAULT NULL.
        $kategori = $data['Id_Kategori']; 
        
        $stok = $data['Jumlah_Total']; 
        $lokasi = $data['Lokasi_Rak'];
        $deskripsi = $data['Deskripsi'];
    }
}

// PROSES SIMPAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = clean_input($_POST['judul']);
    $pengarang = clean_input($_POST['pengarang']);
    $penerbit = clean_input($_POST['penerbit']);
    $tahun = clean_input($_POST['tahun']);
    $isbn = clean_input($_POST['isbn']);
    $kategori = clean_input($_POST['kategori']);
    $lokasi = clean_input($_POST['lokasi']);
    $deskripsi = clean_input($_POST['deskripsi']);
    $admin_id = $_SESSION['user_id'];
    
    // Upload Gambar
    $gambar = "";
    if (!empty($_FILES['cover']['name'])) {
        $upload = upload_cover($_FILES['cover']);
        if ($upload) $gambar = $upload;
    } else {
        // Jika edit dan tidak upload baru, pakai gambar lama
        if ($is_edit) $gambar = $data['Cover_Buku']; // PERBAIKAN: Di DB nama kolomnya 'Cover_Buku' bukan 'Gambar'
    }

    if ($is_edit) {
        // UPDATE
        // PERBAIKAN 2: Ubah 'Pengarang' -> 'Penulis', 'Gambar' -> 'Cover_Buku', 'Kategori' -> 'Id_Kategori'
        $sql = "UPDATE buku SET Judul=?, Penulis=?, Penerbit=?, Tahun_Terbit=?, ISBN=?, Id_Kategori=?, Lokasi_Rak=?, Deskripsi=?, Updated_At=NOW() WHERE Id_Buku=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $judul, $pengarang, $penerbit, $tahun, $isbn, $kategori, $lokasi, $deskripsi, $id_buku);
        
        if ($gambar) {
             $conn->query("UPDATE buku SET Cover_Buku='$gambar' WHERE Id_Buku='$id_buku'");
        }
    } else {
        // INSERT BARU
        $stok_awal = clean_input($_POST['stok']);
        
        // PERBAIKAN 3: Sesuaikan nama kolom dengan database
        // Kolom DB: Id_Buku, Kode_Buku, Judul, Penulis, Penerbit, Tahun_Terbit, ISBN, Id_Kategori, Jumlah_Total, Jumlah_Tersedia, Lokasi_Rak, Cover_Buku, Deskripsi, Created_By, Created_At
        // Kita perlu generate Kode_Buku juga jika belum ada. Asumsi Id_Buku = Kode_Buku atau berbeda.
        // Di fungsi generate_id_buku() outputnya "BK-...". Kita pakai ini untuk Id_Buku dan Kode_Buku.
        
        $sql = "INSERT INTO buku (Id_Buku, Kode_Buku, Judul, Penulis, Penerbit, Tahun_Terbit, ISBN, Id_Kategori, Jumlah_Total, Jumlah_Tersedia, Lokasi_Rak, Cover_Buku, Deskripsi, Created_By, Created_At) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        // Bind parameter: 14 parameter (termasuk Kode_Buku yang kita samakan dengan Id_Buku)
        // Urutan tipe: s s s s s s s s i i s s s s
        $stmt->bind_param("ssssssssiissss", $id_buku, $id_buku, $judul, $pengarang, $penerbit, $tahun, $isbn, $kategori, $stok_awal, $stok_awal, $lokasi, $gambar, $deskripsi, $admin_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Data buku berhasil disimpan!'); window.location='buku_daftar.php';</script>";
    } else {
        $error = "Gagal menyimpan: " . $conn->error;
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary">
                    <?php echo $is_edit ? "Edit Data Buku" : "Tambah Buku Baru"; ?>
                </h4>

                <?php if(isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

                <form class="forms-sample" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kode Buku (Auto)</label>
                                <input type="text" class="form-control" name="id_buku" value="<?php echo $id_buku; ?>"
                                    readonly style="background:#f0f4f8;">
                            </div>
                            <div class="form-group">
                                <label>Judul Buku <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="judul" value="<?php echo $judul; ?>"
                                    required>
                            </div>
                            <div class="form-group">
                                <label>ISBN</label>
                                <input type="text" class="form-control" name="isbn" value="<?php echo $isbn; ?>">
                            </div>
                            <div class="form-group">
                                <label>Penulis / Pengarang</label>
                                <input type="text" class="form-control" name="pengarang"
                                    value="<?php echo $pengarang; ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Penerbit</label>
                                <input type="text" class="form-control" name="penerbit"
                                    value="<?php echo $penerbit; ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Kategori</label>
                                <select class="form-control" name="kategori" required>
                                    <option value="">- Pilih Kategori -</option>
                                    <?php
                                    // Ambil kategori dari tabel kategori_buku
                                    $kats = $conn->query("SELECT * FROM kategori_buku ORDER BY Nama_Kategori ASC");
                                    while($k = $kats->fetch_assoc()) {
                                        // Value disimpan adalah Id_Kategori sesuai relasi DB
                                        $sel = ($kategori == $k['Id_Kategori']) ? 'selected' : '';
                                        echo "<option value='{$k['Id_Kategori']}' $sel>{$k['Nama_Kategori']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Tahun Terbit</label>
                                <input type="number" class="form-control" name="tahun" value="<?php echo $tahun; ?>">
                            </div>
                            <div class="form-group">
                                <label>Jumlah Stok Awal</label>
                                <input type="number" class="form-control" name="stok" value="<?php echo $stok; ?>"
                                    min="1"
                                    <?php echo $is_edit ? 'readonly style="background:#f0f4f8;" title="Edit stok melalui menu Manajemen Stok"' : ''; ?>
                                    required>
                                <?php if($is_edit): ?><small class="text-muted">Untuk ubah stok, gunakan menu
                                    <b>Manajemen Stok</b></small><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Lokasi Rak</label>
                                <input type="text" class="form-control" name="lokasi" value="<?php echo $lokasi; ?>"
                                    placeholder="Contoh: Rak A-02">
                            </div>
                            <div class="form-group">
                                <label>Cover Buku</label>
                                <input type="file" class="form-control file-upload-info" name="cover">
                                <?php if($is_edit && !empty($data['Cover_Buku'])): ?>
                                <small>Cover saat ini: <a href="../uploads/buku/<?php echo $data['Cover_Buku']; ?>"
                                        target="_blank">Lihat</a></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi / Sinopsis</label>
                        <textarea class="form-control" name="deskripsi" rows="4"><?php echo $deskripsi; ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary mr-2"><i class="mdi mdi-content-save"></i> Simpan
                        Data</button>
                    <a href="buku_daftar.php" class="btn btn-light">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>