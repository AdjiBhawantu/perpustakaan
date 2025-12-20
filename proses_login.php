<?php
// FILE: proses_login.php

// Panggil fungsi utama (Pastikan path sesuai struktur folder root -> admin/includes)
require_once 'admin/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Tangkap Input
    $username = clean_input($_POST['username']);
    $password = $_POST['password']; 

    // Jalankan Sistem Login
    $result = login_system($username, $password);

    if ($result['status'] == 'success') {
        // Jika Sukses -> Redirect ke Dashboard/Home
        header("Location: " . base_url($result['redirect']));
        exit;
    } else {
        // Jika Gagal -> Simpan pesan error di session & kembalikan ke login.php
        $_SESSION['error_login'] = $result['pesan'];
        header("Location: login.php");
        exit;
    }
} else {
    // Jika akses langsung tanpa POST
    header("Location: login.php");
    exit;
}
?>