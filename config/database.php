<?php
// config/database.php

// Aktifkan pelaporan error MySQLi (PENTING untuk Debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_perpustakaan_v2";

try {
    $conn = new mysqli($host, $user, $pass, $db);
    $conn->set_charset("utf8mb4"); // Support karakter khusus
} catch (mysqli_sql_exception $e) {
    // Jika koneksi gagal, tampilkan pesan error asli
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Base URL (Sesuaikan dengan folder project Anda)
// Pastikan tidak ada slash (/) di akhir
define('BASE_URL', 'http://localhost/perpustakaan');
?>