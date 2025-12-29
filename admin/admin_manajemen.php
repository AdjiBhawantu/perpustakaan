<?php
require_once 'includes/functions.php';

// Cek Login & Role
if (!isset($_SESSION['user_id'])) redirect('../login.php');
if ($_SESSION['role'] !== 'Super Admin') {
    echo "<script>alert('Akses Ditolak!'); window.location='index.php';</script>";
    exit;
}

// Logic Tambah/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['tambah_admin'])) {
        $res = tambah_admin($_POST);
        if ($res['status'] == 'success') echo "<script>alert('{$res['pesan']}'); window.location='admin_manajemen.php';</script>";
        else echo "<script>alert('{$res['pesan']}');</script>";
    }
    
    if (isset($_POST['edit_admin'])) {
        if (edit_admin($_POST)) echo "<script>alert('Data admin berhasil diperbarui!'); window.location='admin_manajemen.php';</script>";
        else echo "<script>alert('Gagal update admin.');</script>";
    }
}

// Logic Delete (Nonaktifkan)
if (isset($_GET['nonaktif'])) {
    $id = clean_input($_GET['nonaktif']);
    if ($id == $_SESSION['user_id']) {
        echo "<script>alert('Tidak bisa menonaktifkan diri sendiri!');</script>";
    } else {
        $conn->query("UPDATE admin SET Status='Nonaktif' WHERE Id_Admin='$id'");
        echo "<script>window.location='admin_manajemen.php';</script>";
    }
}

// Logic Aktifkan Kembali
if (isset($_GET['aktif'])) {
    $id = clean_input($_GET['aktif']);
    $conn->query("UPDATE admin SET Status='Aktif' WHERE Id_Admin='$id'");
    echo "<script>window.location='admin_manajemen.php';</script>";
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title text-primary"><i class="mdi mdi-account-key mr-2"></i>Manajemen Admin & Staff
                    </h4>
                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#modalTambah">
                        <i class="mdi mdi-plus"></i> Tambah Admin
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="bg-primary text-white">
                            <tr>
                                <th>Profil</th>
                                <th>Role</th>
                                <th>Kontak</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $q = $conn->query("SELECT * FROM admin ORDER BY Created_At DESC");
                            while($row = $q->fetch_assoc()) {
                                $badge = ($row['Status'] == 'Aktif') ? 'success' : 'danger';
                                
                                // --- LOGIKA FOTO PROFIL (PERBAIKAN DI SINI) ---
                                // Cek apakah kolom Foto ada isinya DAN filenya benar-benar ada
                                if (!empty($row['Foto']) && file_exists("../uploads/profil/" . $row['Foto'])) {
                                    $avatar = "../uploads/profil/" . $row['Foto'];
                                } else {
                                    // Jika tidak ada, pakai UI Avatars (Inisial Nama)
                                    $avatar = "https://ui-avatars.com/api/?name=".urlencode($row['Nama_Lengkap'])."&background=random&color=fff&size=128";
                                }
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo $avatar; ?>" class="rounded-circle mr-2"
                                            style="width:40px; height:40px; object-fit: cover; border: 1px solid #ddd;">
                                        <div>
                                            <span
                                                class="font-weight-bold"><?php echo $row['Nama_Lengkap']; ?></span><br>
                                            <small class="text-muted">@<?php echo $row['Username']; ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $row['Role']; ?></td>
                                <td><?php echo $row['Email']; ?><br><small><?php echo $row['No_Telepon']; ?></small>
                                </td>
                                <td><label
                                        class="badge badge-<?php echo $badge; ?>"><?php echo $row['Status']; ?></label>
                                </td>
                                <td><?php echo $row['Last_Login'] ? date('d/m/Y H:i', strtotime($row['Last_Login'])) : '-'; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info btn-icon-text" data-toggle="modal"
                                        data-target="#modalEdit<?php echo $row['Id_Admin']; ?>">
                                        <i class="mdi mdi-pencil"></i>
                                    </button>

                                    <?php if($row['Id_Admin'] != $_SESSION['user_id']): ?>
                                    <?php if($row['Status'] == 'Aktif'): ?>
                                    <a href="?nonaktif=<?php echo $row['Id_Admin']; ?>"
                                        class="btn btn-sm btn-danger btn-icon-text"
                                        onclick="return confirm('Nonaktifkan akun ini?')">
                                        <i class="mdi mdi-block-helper"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="?aktif=<?php echo $row['Id_Admin']; ?>"
                                        class="btn btn-sm btn-success btn-icon-text"
                                        onclick="return confirm('Aktifkan kembali akun ini?')">
                                        <i class="mdi mdi-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <div class="modal fade" id="modalEdit<?php echo $row['Id_Admin']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Edit Admin: <?php echo $row['Username']; ?></h5>
                                                <button type="button" class="close"
                                                    data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_admin"
                                                    value="<?php echo $row['Id_Admin']; ?>">

                                                <div class="form-group">
                                                    <label>Nama Lengkap</label>
                                                    <input type="text" name="nama" class="form-control"
                                                        value="<?php echo $row['Nama_Lengkap']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control"
                                                        value="<?php echo $row['Email']; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>No. Telepon</label>
                                                    <input type="text" name="telepon" class="form-control"
                                                        value="<?php echo $row['No_Telepon']; ?>">
                                                </div>
                                                <div class="form-group">
                                                    <label>Role</label>
                                                    <select name="role" class="form-control">
                                                        <option value="Super Admin"
                                                            <?php if($row['Role']=='Super Admin') echo 'selected'; ?>>
                                                            Super Admin</option>
                                                        <option value="Staff"
                                                            <?php if($row['Role']=='Staff') echo 'selected'; ?>>Staff
                                                        </option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Status Akun</label>
                                                    <select name="status" class="form-control">
                                                        <option value="Aktif"
                                                            <?php if($row['Status']=='Aktif') echo 'selected'; ?>>Aktif
                                                        </option>
                                                        <option value="Nonaktif"
                                                            <?php if($row['Status']=='Nonaktif') echo 'selected'; ?>>
                                                            Nonaktif</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Password Baru <small class="text-muted">(Kosongkan jika tidak
                                                            ingin mengubah)</small></label>
                                                    <input type="password" name="password" class="form-control"
                                                        placeholder="******">
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-light"
                                                    data-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit_admin" class="btn btn-primary">Simpan
                                                    Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Admin Baru</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="telepon" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Role</label>
                        <select name="role" class="form-control">
                            <option value="Staff">Staff</option>
                            <option value="Super Admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah_admin" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>