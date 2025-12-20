<?php
// proses_daftar.php
require_once 'admin/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    if(empty($_POST['fullname']) || empty($_POST['new_username']) || empty($_POST['new_password'])) {
        $_SESSION['error_register'] = "Semua data wajib diisi!";
        $_SESSION['register_mode'] = true; // Agar tetap di panel register saat redirect
        header("Location: login.php");
        exit;
    }

    $result = register_anggota($_POST);

    if ($result['status'] == 'success') {
        // Sukses daftar -> Pindah ke tab Login
        $_SESSION['success_register'] = $result['pesan'];
        header("Location: login.php");
    } else {
        // Gagal daftar -> Tetap di tab Register dan tampilkan error
        $_SESSION['error_register'] = $result['pesan'];
        $_SESSION['register_mode'] = true; // Trigger JS untuk geser panel otomatis
        header("Location: login.php");
    }
    exit;

} else {
    header("Location: login.php");
    exit;
}
?>