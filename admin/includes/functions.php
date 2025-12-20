<?php
// FILE: admin/includes/functions.php

// 1. CEK SESSION STATUS (PENTING: Agar tidak error "Session cannot be started")
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. KONEKSI DATABASE
// Naik 2 level: admin/includes -> admin -> ROOT
$db_path = dirname(__DIR__, 2) . '/config/database.php';

if (file_exists($db_path)) {
    require_once $db_path;
} else {
    // Matikan proses jika DB tidak ketemu, jangan lanjut ke HTML
    die("Error: Database config not found at: " . $db_path);
}

// 3. FUNGSI BANTUAN

function base_url($path = '') {
    if (defined('BASE_URL')) {
        return BASE_URL . '/' . ltrim($path, '/');
    }
    return 'http://localhost/perpustakaan/' . ltrim($path, '/');
}

function redirect($path) {
    // Gunakan JS Redirect agar aman dari error "Headers already sent"
    echo '<script>window.location.href="' . base_url($path) . '";</script>';
    exit;
}

function clean_input($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// 4. LOGGING
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

// --- AUTHENTICATION LOGIC (MD5 VERSION) ---

function login_system($username, $password) {
    global $conn;

    // Cek koneksi database
    if (!$conn) {
        return ['status' => 'error', 'pesan' => 'Koneksi database terputus!'];
    }

    // Enkripsi password input dengan MD5 (Sesuai request)
    $password_md5 = md5($password);

    // 1. CEK KE TABEL ADMIN
    $sql_admin = "SELECT * FROM admin WHERE Username = ?";
    if ($stmt = $conn->prepare($sql_admin)) {
        $stmt->bind_param("s", $username);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Bandingkan Password MD5
                if ($admin['Password'] === $password_md5) {
                    
                    if ($admin['Status'] !== 'Aktif') {
                        return ['status' => 'error', 'pesan' => 'Akun Admin dinonaktifkan.'];
                    }

                    // Set Session
                    $_SESSION['user_id'] = $admin['Id_Admin'];
                    $_SESSION['user_type'] = 'Admin';
                    $_SESSION['role'] = $admin['Role']; 
                    $_SESSION['nama'] = $admin['Nama_Lengkap'];
                    $_SESSION['login_time'] = time();

                    // Update Last Login
                    $conn->query("UPDATE admin SET Last_Login = CURRENT_TIMESTAMP WHERE Id_Admin = '{$admin['Id_Admin']}'");
                    
                    catat_log('Admin', $admin['Id_Admin'], 'Login Berhasil', 'Login sebagai ' . $admin['Role']);
                    return ['status' => 'success', 'redirect' => 'admin/index.php'];
                } else {
                    return ['status' => 'error', 'pesan' => 'Password Admin salah!'];
                }
            }
        }
    } else {
        return ['status' => 'error', 'pesan' => 'SQL Error (Admin): ' . $conn->error];
    }

    // 2. CEK KE TABEL ANGGOTA
    $sql_anggota = "SELECT * FROM anggota WHERE Username = ? OR Email = ?";
    if ($stmt = $conn->prepare($sql_anggota)) {
        $stmt->bind_param("ss", $username, $username);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $anggota = $result->fetch_assoc();

                // Bandingkan Password MD5
                if ($anggota['Password'] === $password_md5) {
                    
                    if ($anggota['Status_Verifikasi'] == 'Pending') {
                        return ['status' => 'error', 'pesan' => 'Akun menunggu verifikasi admin.'];
                    } elseif ($anggota['Status_Verifikasi'] == 'Ditolak') {
                        return ['status' => 'error', 'pesan' => 'Registrasi Ditolak.'];
                    }

                    if ($anggota['Status_Akun'] !== 'Aktif') {
                        return ['status' => 'error', 'pesan' => 'Akun Anggota Suspend/Nonaktif.'];
                    }

                    // Set Session
                    $_SESSION['user_id'] = $anggota['Id_Anggota'];
                    $_SESSION['user_type'] = 'Anggota';
                    $_SESSION['role'] = 'Member';
                    $_SESSION['nama'] = $anggota['Nama_Lengkap'];
                    $_SESSION['login_time'] = time();

                    $conn->query("UPDATE anggota SET Last_Login = CURRENT_TIMESTAMP WHERE Id_Anggota = '{$anggota['Id_Anggota']}'");

                    catat_log('Anggota', $anggota['Id_Anggota'], 'Login Berhasil', 'Login member berhasil');
                    return ['status' => 'success', 'redirect' => 'index.php']; 
                } else {
                    return ['status' => 'error', 'pesan' => 'Password Anggota salah!'];
                }
            }
        }
    } else {
        return ['status' => 'error', 'pesan' => 'SQL Error (Anggota): ' . $conn->error];
    }

    return ['status' => 'error', 'pesan' => 'Username/Email tidak terdaftar!'];
}

// --- REGISTRATION LOGIC (MD5 VERSION) ---

function register_anggota($data) {
    global $conn;
    
    if (!$conn) {
        return ['status' => 'error', 'pesan' => 'Koneksi database terputus!'];
    }

    $nama = clean_input($data['fullname']);
    $email = clean_input($data['email']);
    $username = clean_input($data['new_username']);
    
    // Enkripsi MD5 saat daftar
    $password = md5($data['new_password']); 
    
    $id_anggota = "AGT-" . time();
    
    // Cek Duplikat
    $sql_cek = "SELECT * FROM anggota WHERE Username = ? OR Email = ?";
    if ($stmt = $conn->prepare($sql_cek)) {
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return ['status' => 'error', 'pesan' => 'Username atau Email sudah terdaftar!'];
        }
    }

    // Insert Data
    $sql_insert = "INSERT INTO anggota (Id_Anggota, Username, Password, Nama_Lengkap, Email, Status_Verifikasi, Status_Akun) 
                   VALUES (?, ?, ?, ?, ?, 'Pending', 'Nonaktif')";
            
    if ($stmt = $conn->prepare($sql_insert)) {
        $stmt->bind_param("sssss", $id_anggota, $username, $password, $nama, $email);
        
        try {
            if ($stmt->execute()) {
                catat_log('Anggota', $id_anggota, 'Registrasi', 'Pendaftaran anggota baru');
                return ['status' => 'success', 'pesan' => 'Pendaftaran berhasil! Tunggu verifikasi admin.'];
            } else {
                return ['status' => 'error', 'pesan' => 'Gagal Execute Insert: ' . $stmt->error];
            }
        } catch (Exception $e) {
            return ['status' => 'error', 'pesan' => 'Exception: ' . $e->getMessage()];
        }
    } else {
        return ['status' => 'error', 'pesan' => 'SQL Error (Insert): ' . $conn->error];
    }
}
?>