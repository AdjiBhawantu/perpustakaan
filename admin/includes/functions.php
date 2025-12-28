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
?>