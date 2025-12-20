<?php
// logout.php
session_start();
require_once 'config/database.php'; // Butuh koneksi untuk catat log logout jika mau

// Catat log logout (opsional, tapi bagus untuk keamanan)
if(isset($_SESSION['user_type']) && isset($_SESSION['user_id'])) {
    // Kita lakukan manual insert log disini karena session akan dihancurkan
    $user_type = $_SESSION['user_type'];
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $agent = $_SERVER['HTTP_USER_AGENT'];
    
    $conn->query("INSERT INTO log_aktivitas (User_Type, User_Id, Aktivitas, Deskripsi, IP_Address, User_Agent) 
                  VALUES ('$user_type', '$user_id', 'Logout', 'User keluar sistem', '$ip', '$agent')");
}

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login
header("Location: login.php");
exit;
?>