<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

$id_admin = $_SESSION['user_id'];
$pesan_sukses = "";
$pesan_error = "";

// --- 1. LOGIKA UPDATE PROFIL & FOTO ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profil'])) {
    $nama = clean_input($_POST['nama']);
    $email = clean_input($_POST['email']);
    $telp = clean_input($_POST['telepon']);
    
    // Handle Upload Foto
    $foto_sql = "";
    if (!empty($_FILES['foto']['name'])) {
        $target_dir = "../uploads/profil/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
        $new_name = "ADM_" . time() . "." . $file_ext;
        $target_file = $target_dir . $new_name;
        
        $allow = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allow) && $_FILES["foto"]["size"] <= 2000000) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $foto_sql = ", Foto='$new_name'";
                // Update session foto jika perlu (opsional)
            }
        } else {
            $pesan_error = "Format foto harus JPG/PNG dan maks 2MB.";
        }
    }

    if (empty($pesan_error)) {
        $sql = "UPDATE admin SET Nama_Lengkap=?, Email=?, No_Telepon=? $foto_sql WHERE Id_Admin=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $nama, $email, $telp, $id_admin);
        
        if ($stmt->execute()) {
            $_SESSION['nama'] = $nama; // Refresh session nama
            $pesan_sukses = "Profil berhasil diperbarui!";
            catat_log('Admin', $id_admin, 'Update Profil', 'Mengubah data profil akun.');
        } else {
            $pesan_error = "Gagal mengupdate profil.";
        }
    }
}

// --- 2. LOGIKA GANTI PASSWORD ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ganti_password'])) {
    $pass_lama = md5($_POST['pass_lama']);
    $pass_baru = $_POST['pass_baru'];
    $pass_konf = $_POST['pass_konf'];

    // Cek password lama
    $cek = $conn->query("SELECT Password FROM admin WHERE Id_Admin='$id_admin'")->fetch_assoc();
    
    if ($cek['Password'] === $pass_lama) {
        if ($pass_baru === $pass_konf) {
            if (strlen($pass_baru) >= 6) {
                $pass_hash = md5($pass_baru);
                $conn->query("UPDATE admin SET Password='$pass_hash' WHERE Id_Admin='$id_admin'");
                $pesan_sukses = "Password berhasil diubah!";
                catat_log('Admin', $id_admin, 'Ganti Password', 'Berhasil mengubah kata sandi.');
            } else {
                $pesan_error = "Password baru minimal 6 karakter.";
            }
        } else {
            $pesan_error = "Konfirmasi password tidak cocok.";
        }
    } else {
        $pesan_error = "Password lama salah.";
    }
}

// --- AMBIL DATA ADMIN ---
$data = $conn->query("SELECT * FROM admin WHERE Id_Admin='$id_admin'")->fetch_assoc();
$avatar = !empty($data['Foto']) ? "../uploads/profil/".$data['Foto'] : "https://ui-avatars.com/api/?name=".urlencode($data['Nama_Lengkap'])."&background=3182ce&color=fff";

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-4 grid-margin stretch-card">
        <div class="card shadow-sm border-0 text-center">
            <div class="card-body">
                <div class="mb-4">
                    <img src="<?php echo $avatar; ?>" class="img-lg rounded-circle mb-3"
                        style="width: 120px; height: 120px; object-fit: cover; border: 4px solid #ebf8ff;">
                    <h4 class="font-weight-bold text-primary mb-1"><?php echo $data['Nama_Lengkap']; ?></h4>
                    <p class="text-muted mb-1">@<?php echo $data['Username']; ?></p>
                    <span class="badge badge-primary"><?php echo $data['Role']; ?></span>
                </div>

                <div class="border-top pt-3 text-left">
                    <div class="py-2">
                        <small class="text-muted d-block">Email</small>
                        <span class="font-weight-medium"><?php echo $data['Email']; ?></span>
                    </div>
                    <div class="py-2">
                        <small class="text-muted d-block">No. Telepon</small>
                        <span class="font-weight-medium"><?php echo $data['No_Telepon'] ?: '-'; ?></span>
                    </div>
                    <div class="py-2">
                        <small class="text-muted d-block">Bergabung Sejak</small>
                        <span
                            class="font-weight-medium"><?php echo date('d M Y', strtotime($data['Created_At'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <?php if($pesan_sukses): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="mdi mdi-check-circle mr-1"></i> <?php echo $pesan_sukses; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>

                <?php if($pesan_error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="mdi mdi-alert-circle mr-1"></i> <?php echo $pesan_error; ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
                <?php endif; ?>

                <ul class="nav nav-tabs" id="profileTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="edit-tab" data-toggle="tab" href="#edit"
                            role="tab">
                            <i class="mdi mdi-account-edit mr-1"></i> Edit Profil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="security-tab" data-toggle="tab" href="#security"
                            role="tab">
                            <i class="mdi mdi-lock-reset mr-1"></i> Ganti Password
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="log-tab" data-toggle="tab" href="#log" role="tab">
                            <i class="mdi mdi-history mr-1"></i> Aktivitas Login
                        </a>
                    </li>
                </ul>

                <div class="tab-content pt-4" id="profileTabContent">

                    <div class="tab-pane fade show active" id="edit" role="tabpanel">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Nama Lengkap</label>
                                <div class="col-sm-9">
                                    <input type="text" name="nama" class="form-control"
                                        value="<?php echo $data['Nama_Lengkap']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo $data['Email']; ?>" required>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">No. Telepon</label>
                                <div class="col-sm-9">
                                    <input type="text" name="telepon" class="form-control"
                                        value="<?php echo $data['No_Telepon']; ?>">
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Foto Profil</label>
                                <div class="col-sm-9">
                                    <input type="file" name="foto" class="form-control-file border p-2 rounded">
                                    <small class="text-muted">Format: JPG, PNG. Maks: 2MB.</small>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" name="update_profil" class="btn btn-primary">
                                    <i class="mdi mdi-content-save"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="security" role="tabpanel">
                        <form method="POST">
                            <div class="form-group">
                                <label>Password Lama</label>
                                <input type="password" name="pass_lama" class="form-control"
                                    placeholder="Masukkan password saat ini" required>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label>Password Baru</label>
                                <input type="password" name="pass_baru" class="form-control"
                                    placeholder="Minimal 6 karakter" minlength="6" required>
                            </div>
                            <div class="form-group">
                                <label>Konfirmasi Password Baru</label>
                                <input type="password" name="pass_konf" class="form-control"
                                    placeholder="Ulangi password baru" required>
                            </div>
                            <div class="text-right">
                                <button type="submit" name="ganti_password" class="btn btn-danger">
                                    <i class="mdi mdi-key-change"></i> Update Password
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="log" role="tabpanel">
                        <h6 class="text-primary mb-3">10 Aktivitas Terakhir Anda</h6>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Aktivitas</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $logs = $conn->query("SELECT * FROM log_aktivitas WHERE User_Id='$id_admin' ORDER BY Created_At DESC LIMIT 10");
                                    if ($logs->num_rows > 0) {
                                        while($l = $logs->fetch_assoc()) {
                                            echo "<tr>
                                                <td>".date('d/m/Y H:i', strtotime($l['Created_At']))."</td>
                                                <td>{$l['Aktivitas']} - <small class='text-muted'>{$l['Deskripsi']}</small></td>
                                                <td>{$l['IP_Address']}</td>
                                            </tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>Belum ada aktivitas.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>