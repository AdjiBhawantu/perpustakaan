<?php
// FILE: admin/includes/functions.php

// 1. CEK SESSION STATUS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KONEKSI DATABASE
$db_path = dirname(__DIR__, 2) . '/config/database.php';

if (file_exists($db_path)) {
    require_once $db_path;
} else {
    die("Error: Database config not found at: " . $db_path);
}

// --- HELPER FUNCTIONS ---

function base_url($path = '') {
    if (defined('BASE_URL')) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    return 'http://localhost/perpustakaan/' . ltrim($path, '/');
}

function redirect($path) {
    echo '<script>window.location.href="' . base_url($path) . '";</script>';
    exit;
}

function clean_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// --- LOGGING SYSTEM ---

function catat_log($user_type, $user_id, $aktivitas, $deskripsi) {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $sql = "INSERT INTO log_aktivitas (User_Type, User_Id, Aktivitas, Deskripsi, IP_Address, User_Agent) VALUES (?, ?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ssssss", $user_type, $user_id, $aktivitas, $deskripsi, $ip, $agent);
        $stmt->execute();
    }
}

// --- AUTHENTICATION LOGIC (MD5) ---

function login_system($username, $password) {
    global $conn;
    if (!$conn) return ['status' => 'error', 'pesan' => 'Koneksi database terputus!'];

    $password_md5 = md5($password);

    // Cek Admin
    $sql_admin = "SELECT * FROM admin WHERE Username = ?";
    if ($stmt = $conn->prepare($sql_admin)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            if ($admin['Password'] === $password_md5) {
                if ($admin['Status'] !== 'Aktif') return ['status' => 'error', 'pesan' => 'Akun Admin Nonaktif.'];
                
                $_SESSION['user_id'] = $admin['Id_Admin'];
                $_SESSION['user_type'] = 'Admin';
                $_SESSION['role'] = $admin['Role']; 
                $_SESSION['nama'] = $admin['Nama_Lengkap'];
                $_SESSION['login_time'] = time();

                $conn->query("UPDATE admin SET Last_Login = CURRENT_TIMESTAMP WHERE Id_Admin = '{$admin['Id_Admin']}'");
                catat_log('Admin', $admin['Id_Admin'], 'Login Berhasil', 'Login sebagai ' . $admin['Role']);
                return ['status' => 'success', 'redirect' => 'admin/index.php'];
            }
        }
    }

    // Cek Anggota
    $sql_anggota = "SELECT * FROM anggota WHERE Username = ? OR Email = ?";
    if ($stmt = $conn->prepare($sql_anggota)) {
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $anggota = $result->fetch_assoc();
            if ($anggota['Password'] === $password_md5) {
                if ($anggota['Status_Verifikasi'] == 'Pending') return ['status' => 'error', 'pesan' => 'Akun sedang diverifikasi admin. Mohon tunggu.'];
                if ($anggota['Status_Verifikasi'] == 'Ditolak') return ['status' => 'error', 'pesan' => 'Pendaftaran Anda ditolak.'];
                if ($anggota['Status_Akun'] !== 'Aktif') return ['status' => 'error', 'pesan' => 'Akun Anda ditangguhkan (Suspend).'];

                $_SESSION['user_id'] = $anggota['Id_Anggota'];
                $_SESSION['user_type'] = 'Anggota';
                $_SESSION['nama'] = $anggota['Nama_Lengkap'];
                
                $conn->query("UPDATE anggota SET Last_Login = CURRENT_TIMESTAMP WHERE Id_Anggota = '{$anggota['Id_Anggota']}'");
                return ['status' => 'success', 'redirect' => 'index.php']; 
            }
        }
    }
    return ['status' => 'error', 'pesan' => 'Username atau Password Salah!'];
}

function register_anggota($data) {
    global $conn;
    
    // Cek koneksi
    if (!$conn) {
        return ['status' => 'error', 'pesan' => 'Masalah koneksi database.'];
    }

    // 1. Tangkap & Bersihkan Input Dasar
    $nama = clean_input($data['fullname']);
    $email = clean_input($data['email']);
    $username = clean_input($data['new_username']);
    $password = md5($data['new_password']); 
    
    // 2. Tangkap Input Tambahan (Sesuai Database)
    $nik = clean_input($data['nik']);
    $no_telp = clean_input($data['no_telp']);
    $gender = clean_input($data['gender']);
    $alamat = clean_input($data['alamat']);
    
    $id_anggota = "AGT-" . time();
    
    // 3. Cek Duplikat (Username, Email, atau NIK)
    $cek_sql = "SELECT Username, Email, No_KTP FROM anggota WHERE Username='$username' OR Email='$email' OR No_KTP='$nik'";
    $cek_result = $conn->query($cek_sql);
    
    if ($cek_result && $cek_result->num_rows > 0) {
        $existing = $cek_result->fetch_assoc();
        if ($existing['Username'] == $username) return ['status' => 'error', 'pesan' => 'Username sudah digunakan!'];
        if ($existing['Email'] == $email) return ['status' => 'error', 'pesan' => 'Email sudah terdaftar!'];
        if ($existing['No_KTP'] == $nik) return ['status' => 'error', 'pesan' => 'NIK / No. KTP sudah terdaftar!'];
    }

    // 4. Proses Insert Data Lengkap
    $sql_insert = "INSERT INTO anggota 
        (Id_Anggota, Username, Password, Nama_Lengkap, Email, No_KTP, No_Telepon, Jenis_Kelamin, Alamat, Status_Verifikasi, Status_Akun, Tanggal_Daftar) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Nonaktif', NOW())";
            
    if ($stmt = $conn->prepare($sql_insert)) {
        // s = string (9 parameter string)
        $stmt->bind_param("sssssssss", $id_anggota, $username, $password, $nama, $email, $nik, $no_telp, $gender, $alamat);
        
        try {
            if ($stmt->execute()) {
                catat_log('Anggota', $id_anggota, 'Registrasi', 'Pendaftaran anggota baru');
                return ['status' => 'success', 'pesan' => 'Pendaftaran Berhasil! Akun Anda sedang menunggu verifikasi Admin.'];
            } else {
                return ['status' => 'error', 'pesan' => 'Gagal menyimpan data: ' . $stmt->error];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'pesan' => 'Terjadi kesalahan sistem. Silakan coba lagi.'];
        }
    } else {
        return ['status' => 'error', 'pesan' => 'Kesalahan Query Database.'];
    }
}

// ==========================================
// === SISTEM CRUD ANGGOTA (MANAGEMENT) ===
// ==========================================

function ambil_semua_anggota() {
    global $conn;
    return $conn->query("SELECT * FROM anggota ORDER BY Tanggal_Daftar DESC");
}

function ambil_anggota_pending() {
    global $conn;
    return $conn->query("SELECT * FROM anggota WHERE Status_Verifikasi = 'Pending' ORDER BY Tanggal_Daftar ASC");
}

function ambil_detail_anggota($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM anggota WHERE Id_Anggota = ?");
    $stmt->bind_param("s", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function proses_verifikasi_anggota($id, $status, $admin_id) {
    global $conn;
    $status_akun = ($status == 'Terverifikasi') ? 'Aktif' : 'Nonaktif';
    $stmt = $conn->prepare("UPDATE anggota SET Status_Verifikasi = ?, Status_Akun = ? WHERE Id_Anggota = ?");
    $stmt->bind_param("sss", $status, $status_akun, $id);
    if ($stmt->execute()) {
        $aktivitas = ($status == 'Terverifikasi') ? 'Approve Anggota' : 'Reject Anggota';
        catat_log('Admin', $admin_id, $aktivitas, "Memproses verifikasi anggota ID: $id -> $status");
        return true;
    }
    return false;
}

function hapus_anggota($id, $admin_id) {
    global $conn;
    $stmt = $conn->prepare("DELETE FROM anggota WHERE Id_Anggota = ?");
    $stmt->bind_param("s", $id);
    if ($stmt->execute()) {
        catat_log('Admin', $admin_id, 'Hapus Anggota', "Menghapus data anggota ID: $id");
        return true;
    }
    return false;
}

// ==========================================
// === SISTEM SUSPEND & REAKTIVASI ===
// ==========================================

// 1. Fungsi Ubah Status (Suspend/Aktifkan)
function update_status_anggota($id_anggota, $status_baru, $alasan, $admin_id) {
    global $conn;
    
    // Update status akun
    $stmt = $conn->prepare("UPDATE anggota SET Status_Akun = ? WHERE Id_Anggota = ?");
    $stmt->bind_param("ss", $status_baru, $id_anggota);
    
    if ($stmt->execute()) {
        // Catat ke Log Aktivitas
        $aktivitas = ($status_baru == 'Nonaktif') ? 'Suspend Anggota' : 'Reaktivasi Anggota';
        $deskripsi = "Mengubah status menjadi $status_baru. Alasan: $alasan";
        
        catat_log('Admin', $admin_id, $aktivitas, $deskripsi);
        return true;
    }
    return false;
}

// 2. Ambil Riwayat Status (Log Spesifik User)
function ambil_riwayat_status($id_anggota) {
    global $conn;
    // Menggunakan ORDER BY Created_At (Bukan Tanggal, karena di log_aktivitas namanya Created_At)
    $sql = "SELECT * FROM log_aktivitas 
            WHERE Deskripsi LIKE '%$id_anggota%' 
            OR (Aktivitas IN ('Suspend Anggota', 'Reaktivasi Anggota', 'Registrasi', 'Approve Anggota') AND Deskripsi LIKE '%$id_anggota%')
            ORDER BY Created_At DESC";
    
    return $conn->query($sql);
}

// ==========================================
// === SISTEM MANAJEMEN BUKU & STOK ===
// ==========================================

// 1. Generate ID Buku Otomatis (BK-AngkaRandom)
function generate_id_buku() {
    return "BK-" . date("ymd") . rand(100, 999);
}

// 2. Generate ID Kategori Otomatis (KAT-AngkaRandom)
function generate_id_kategori() {
    return "KAT-" . date("ymd") . rand(100, 999);
}

// 3. Ambil Semua Kategori
function ambil_kategori() {
    global $conn;
    return $conn->query("SELECT * FROM kategori_buku ORDER BY Nama_Kategori ASC");
}

// 4. Upload Cover Buku
function upload_cover($file) {
    $target_dir = "../uploads/buku/";
    
    // Buat folder jika belum ada
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $filename = time() . "_" . basename($file["name"]);
    $target_file = $target_dir . $filename;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Validasi
    $check = getimagesize($file["tmp_name"]);
    if($check === false) return false; // Bukan gambar
    if($file["size"] > 2000000) return false; // Max 2MB
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") return false;

    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $filename;
    }
    return false;
}

// 5. Update Stok Buku (Transaksi)
function update_stok_buku($id_buku, $jenis_transaksi, $jumlah, $keterangan, $admin_id) {
    global $conn;
    
    // Ambil data buku saat ini
    $q = $conn->query("SELECT Jumlah_Total, Jumlah_Tersedia FROM buku WHERE Id_Buku = '$id_buku'");
    $buku = $q->fetch_assoc();
    
    if (!$buku) return false;

    $total_baru = $buku['Jumlah_Total'];
    $tersedia_baru = $buku['Jumlah_Tersedia'];

    // Logika Perubahan Stok
    if ($jenis_transaksi == 'Tambah') {
        $total_baru += $jumlah;
        $tersedia_baru += $jumlah;
    } elseif ($jenis_transaksi == 'Kurang' || $jenis_transaksi == 'Rusak' || $jenis_transaksi == 'Hilang') {
        $total_baru -= $jumlah;
        $tersedia_baru -= $jumlah;
    }
    // Perbaikan: Tidak mengubah total, hanya menambah tersedia (dari rusak ke tersedia)
    elseif ($jenis_transaksi == 'Perbaikan') {
        $tersedia_baru += $jumlah; 
    }

    // Validasi agar tidak minus
    if ($total_baru < 0 || $tersedia_baru < 0) return false;

    // 1. Update Tabel Buku
    $sql_update = "UPDATE buku SET Jumlah_Total = ?, Jumlah_Tersedia = ?, Updated_At = NOW() WHERE Id_Buku = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("iis", $total_baru, $tersedia_baru, $id_buku);
    
    if ($stmt->execute()) {
        // 2. Catat Riwayat
        // PERBAIKAN: Menggunakan kolom 'Created_At' dan 'Dilakukan_Oleh' (Bukan 'Tanggal' dan 'Admin_Id')
        $sql_log = "INSERT INTO riwayat_stok (Id_Buku, Jenis_Transaksi, Jumlah, Keterangan, Created_At, Dilakukan_Oleh) VALUES (?, ?, ?, ?, NOW(), ?)";
        $stmt_log = $conn->prepare($sql_log);
        $stmt_log->bind_param("ssiss", $id_buku, $jenis_transaksi, $jumlah, $keterangan, $admin_id);
        $stmt_log->execute();
        return true;
    }
    return false;
}
// ==========================================
// === SISTEM TRANSAKSI PEMINJAMAN ===
// ==========================================

// 1. Generate ID Peminjaman (PJ-timestamp)
function generate_id_peminjaman() {
    return "PJ-" . date("ymd") . rand(100, 999);
}

// 2. Ambil Daftar Pengajuan (Filter Status)
function ambil_pengajuan($status = 'Semua') {
    global $conn;
    
    if ($status == 'Menunggu') {
        // Menggunakan View sesuai request
        return $conn->query("SELECT * FROM view_pengajuan_menunggu ORDER BY Tgl_Pengajuan ASC");
    } else {
        // Join manual untuk riwayat lengkap
        $sql = "SELECT p.*, a.Nama_Lengkap, b.Judul, b.Cover_Buku
                FROM pengajuan_peminjaman p
                JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
                JOIN buku b ON p.Id_Buku = b.Id_Buku
                ORDER BY p.Tgl_Pengajuan DESC";
        return $conn->query($sql);
    }
}

// 3. Ambil Detail 1 Pengajuan
function ambil_detail_pengajuan($id) {
    global $conn;
    // Join lengkap untuk halaman proses
    $sql = "SELECT p.*, 
            a.Nama_Lengkap, a.Email, a.No_Telepon, a.Foto_KTP,
            b.Judul, b.Cover_Buku, b.Penulis, b.Jumlah_Tersedia, b.Lokasi_Rak
            FROM pengajuan_peminjaman p
            JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
            JOIN buku b ON p.Id_Buku = b.Id_Buku
            WHERE p.Id_Pengajuan = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// 4. Proses Pengajuan (Panggil Stored Procedure)
function proses_pengajuan($id_pengajuan, $id_admin, $status, $alasan) {
    global $conn;
    
    $id_peminjaman_baru = generate_id_peminjaman();
    $pesan_hasil = "";

    // Syntax Panggil SP di MySQL: CALL SP_Nama(param, @output)
    $sql = "CALL SP_Setujui_Peminjaman(?, ?, ?, ?, ?, @result)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $id_pengajuan, $id_admin, $id_peminjaman_baru, $status, $alasan);
    
    if ($stmt->execute()) {
        // Ambil pesan output dari SP
        $res = $conn->query("SELECT @result as pesan");
        $row = $res->fetch_assoc();
        $pesan_hasil = $row['pesan'];
        
        // Cek apakah output mengandung kata 'SUCCESS'
        if (strpos($pesan_hasil, 'SUCCESS') !== false) {
            return ['status' => 'success', 'pesan' => $pesan_hasil];
        } else {
            return ['status' => 'error', 'pesan' => $pesan_hasil];
        }
    } else {
        return ['status' => 'error', 'pesan' => "Database Error: " . $conn->error];
    }
}
// ==========================================
// === SISTEM PENGATURAN (SETTINGS) ===
// ==========================================

// 1. Ambil Data Pengaturan
function ambil_pengaturan() {
    global $conn;
    $res = $conn->query("SELECT * FROM pengaturan WHERE Id = 1");
    if ($res->num_rows > 0) {
        return $res->fetch_assoc();
    }
    // Return default jika kosong
    return [
        'Nama_Perpus' => 'LibraryHub',
        'Denda_Per_Hari' => 1000,
        'Durasi_Pinjam' => 7,
        'Max_Pinjam' => 3
    ];
}

// 2. Update Pengaturan
function update_pengaturan($data) {
    global $conn;
    $nama = clean_input($data['nama']);
    $alamat = clean_input($data['alamat']);
    $email = clean_input($data['email']);
    $telp = clean_input($data['telepon']);
    $denda = (int)$data['denda'];
    $durasi = (int)$data['durasi'];
    $max = (int)$data['max_pinjam'];
    $rusak_k = (int)$data['rusak_kecil'];
    $rusak_b = (int)$data['rusak_besar'];
    $hilang = (int)$data['hilang'];

    $sql = "UPDATE pengaturan SET 
            Nama_Perpus=?, Alamat=?, Email=?, No_Telepon=?, 
            Denda_Per_Hari=?, Durasi_Pinjam=?, Max_Pinjam=?,
            Biaya_Rusak_Ringan=?, Biaya_Rusak_Berat=?, Biaya_Hilang=?
            WHERE Id = 1";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiiiiii", $nama, $alamat, $email, $telp, $denda, $durasi, $max, $rusak_k, $rusak_b, $hilang);
    return $stmt->execute();
}

// ==========================================
// === MANAJEMEN ADMIN (CRUD) ===
// ==========================================

// 1. Tambah Admin Baru
function tambah_admin($data) {
    global $conn;
    $username = clean_input($data['username']);
    
    // Cek Username
    $cek = $conn->query("SELECT Username FROM admin WHERE Username='$username'");
    if($cek->num_rows > 0) return ['status'=>'error', 'pesan'=>'Username sudah digunakan!'];

    $nama = clean_input($data['nama']);
    $email = clean_input($data['email']);
    $telp = clean_input($data['telepon']);
    $password = md5($data['password']); // MD5 sesuai sistem login Anda
    $role = clean_input($data['role']);
    $id_admin = "ADM-" . time();

    $sql = "INSERT INTO admin (Id_Admin, Username, Password, Nama_Lengkap, Email, No_Telepon, Role, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Aktif')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssss", $id_admin, $username, $password, $nama, $email, $telp, $role);
    
    if($stmt->execute()) return ['status'=>'success', 'pesan'=>'Admin berhasil ditambahkan'];
    return ['status'=>'error', 'pesan'=>'Gagal menambah admin'];
}

// 2. Edit Admin
function edit_admin($data) {
    global $conn;
    $id = clean_input($data['id_admin']);
    $nama = clean_input($data['nama']);
    $email = clean_input($data['email']);
    $telp = clean_input($data['telepon']);
    $role = clean_input($data['role']);
    $status = clean_input($data['status']);

    $sql = "UPDATE admin SET Nama_Lengkap=?, Email=?, No_Telepon=?, Role=?, Status=? WHERE Id_Admin=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $nama, $email, $telp, $role, $status, $id);
    
    if(!empty($data['password'])) {
        $pass = md5($data['password']);
        $conn->query("UPDATE admin SET Password='$pass' WHERE Id_Admin='$id'");
    }

    if($stmt->execute()) return true;
    return false;
}

// 3. Backup Database (Simple SQL Dump)
function backup_database() {
    global $conn;
    // Logika backup sederhana: Ambil semua tabel dan strukturnya
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $return = "";
    foreach($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $num_fields = $result->field_count;
        
        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
        $return .= "\n\n".$row2[1].";\n\n";
        
        for ($i = 0; $i < $num_fields; $i++) {
            while($row = $result->fetch_row()) {
                $return .= "INSERT INTO $table VALUES(";
                for($j=0; $j<$num_fields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return .= '"'.$row[$j].'"' ; } else { $return .= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return .= ");\n";
            }
        }
        $return .="\n\n\n";
    }
    
    // Force Download
    $filename = 'backup-db-'.date('Y-m-d-H-i-s').'.sql';
    header('Content-Type: application/octet-stream');
    header("Content-Transfer-Encoding: Binary"); 
    header("Content-disposition: attachment; filename=\"".$filename."\""); 
    echo $return; 
    exit;
}
?>