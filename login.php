<?php
session_start();

// Cek jika user sudah login
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Register - LibraryHub</title>
    <style>
    /* --- 1. VARIABLE & RESET --- */
    :root {
        --primary: #3182ce;
        --primary-dark: #2c5aa0;
        --bg-body: #f0f4f8;
        --text-main: #2d3748;
        --text-sub: #718096;
        --white: #ffffff;
        --shadow-lg: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);

        /* Warna Notifikasi */
        --error-bg: #fee2e2;
        --error-border: #fecaca;
        --error-text: #991b1b;

        --success-bg: #dcfce7;
        --success-border: #bbf7d0;
        --success-text: #166534;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: linear-gradient(120deg, #e0c3fc 0%, #8ec5fc 100%);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }

    /* --- 2. NOTIFICATION CARD --- */
    .notification-card {
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 12px;
        font-size: 0.95rem;
        display: flex;
        align-items: flex-start;
        gap: 12px;
        animation: slideInDown 0.5s ease-out;
        border-left: 5px solid transparent;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .notification-card.error {
        background-color: var(--white);
        color: var(--error-text);
        border-left-color: #ef4444;
        background-image: linear-gradient(to right, var(--error-bg), #fff);
    }

    .notification-card.success {
        background-color: var(--white);
        color: var(--success-text);
        border-left-color: #22c55e;
        background-image: linear-gradient(to right, var(--success-bg), #fff);
    }

    .notification-icon {
        font-size: 1.2rem;
        margin-top: -2px;
    }

    .notification-content {
        flex: 1;
        line-height: 1.5;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* --- 3. NAVIGASI --- */
    .nav-back {
        width: 100%;
        max-width: 850px;
        margin-bottom: 1.5rem;
        display: flex;
        animation: fadeInDown 0.6s ease-out;
    }

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        background: var(--white);
        padding: 10px 20px;
        border-radius: 50px;
        color: var(--primary);
        font-weight: 600;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .btn-back:hover {
        transform: translateX(-5px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    }

    /* --- 4. CONTAINER UTAMA --- */
    .container {
        background: var(--white);
        border-radius: 20px;
        box-shadow: var(--shadow-lg);
        position: relative;
        overflow: hidden;
        width: 100%;
        max-width: 900px;
        min-height: 650px;
        animation: fadeInUp 0.8s ease-out;
    }

    /* --- 5. MECHANISM SLIDING --- */
    .form-slider {
        display: flex;
        width: 200%;
        height: 100%;
        transition: transform 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    .container.mode-signup .form-slider {
        transform: translateX(-50%);
    }

    /* --- 6. PANEL FORM --- */
    .form-panel {
        flex: 0 0 50%;
        max-width: 50%;
        padding: 3rem 4rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        /* Tambahan agar form panjang bisa discroll */
        height: 100%;
        max-height: 700px;
        /* Batas tinggi */
        overflow-y: auto;
        /* Scroll jika konten panjang */
    }

    /* Custom Scrollbar */
    .form-panel::-webkit-scrollbar {
        width: 8px;
    }

    .form-panel::-webkit-scrollbar-track {
        background: #f1f1f1;
    }

    .form-panel::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 4px;
    }

    .form-panel::-webkit-scrollbar-thumb:hover {
        background: #aaa;
    }

    /* --- 7. ELEMEN FORM --- */
    .header-title {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-main);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .header-sub {
        color: var(--text-sub);
        margin-bottom: 2rem;
        font-size: 1rem;
        line-height: 1.5;
    }

    .input-row {
        display: flex;
        gap: 15px;
    }

    .input-row .input-group {
        flex: 1;
    }

    .input-group {
        position: relative;
        margin-bottom: 1.2rem;
    }

    .input-group label {
        display: block;
        margin-bottom: 0.4rem;
        color: var(--text-main);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .input-group input,
    .input-group select,
    .input-group textarea {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 0.95rem;
        transition: all 0.3s;
        background: #f7fafc;
        font-family: inherit;
    }

    .input-group textarea {
        resize: vertical;
    }

    .input-group input:focus,
    .input-group select:focus,
    .input-group textarea:focus {
        outline: none;
        border-color: var(--primary);
        background: var(--white);
        box-shadow: 0 0 0 4px rgba(49, 130, 206, 0.1);
    }

    .btn-submit {
        width: 100%;
        padding: 14px;
        border: none;
        border-radius: 10px;
        background: linear-gradient(135deg, #63b3ed 0%, #4299e1 100%);
        color: var(--white);
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-top: 10px;
        margin-bottom: 20px;
        /* Space bawah */
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(66, 153, 225, 0.4);
    }

    .btn-submit:active {
        transform: scale(0.98);
    }

    /* --- 8. FOOTER TOGGLE --- */
    .toggle-area {
        text-align: center;
        margin-top: 1rem;
        color: var(--text-sub);
        font-size: 0.95rem;
        padding-bottom: 20px;
    }

    .toggle-link {
        color: var(--primary);
        font-weight: 700;
        cursor: pointer;
        margin-left: 5px;
        text-decoration: none;
        transition: color 0.2s;
    }

    .toggle-link:hover {
        text-decoration: underline;
        color: var(--primary-dark);
    }

    /* --- 9. ANIMASI & RESPONSIVE --- */
    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .form-panel {
            padding: 2rem;
            max-width: 100%;
            flex: 0 0 100%;
        }

        .header-title {
            font-size: 1.5rem;
        }

        .container {
            min-height: auto;
        }

        .input-row {
            flex-direction: column;
            gap: 0;
        }
    }
    </style>
</head>

<body>

    <div class="nav-back">
        <a href="index.php" class="btn-back">← Kembali ke Beranda</a>
    </div>

    <div class="container" id="mainContainer">
        <div class="form-slider">

            <div class="form-panel">
                <div class="header-title">📖 LibraryHub</div>
                <p class="header-sub">Selamat datang kembali! Silakan masuk untuk mengakses koleksi buku digital.</p>

                <?php if (isset($_SESSION['error_login'])): ?>
                <div class="notification-card error">
                    <span class="notification-icon">⚠️</span>
                    <span class="notification-content">
                        <b>Login Gagal</b><br>
                        <?php echo $_SESSION['error_login']; unset($_SESSION['error_login']); ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_register'])): ?>
                <div class="notification-card success">
                    <span class="notification-icon">✅</span>
                    <span class="notification-content">
                        <b>Registrasi Berhasil!</b><br>
                        <?php echo $_SESSION['success_register']; unset($_SESSION['success_register']); ?>
                    </span>
                </div>
                <?php endif; ?>

                <form action="proses_login.php" method="POST">
                    <div class="input-group">
                        <label>Username atau Email</label>
                        <input type="text" name="username" placeholder="Masukan username anda" required
                            autocomplete="username">
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required
                            autocomplete="current-password">
                    </div>

                    <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 0.9rem;">
                        <label style="color: var(--text-sub); cursor: pointer;">
                            <input type="checkbox"> Ingat saya
                        </label>
                        <a href="#" style="color: var(--primary); text-decoration: none; font-weight: 600;">Lupa
                            Password?</a>
                    </div>

                    <button type="submit" class="btn-submit">🔐 Masuk Sekarang</button>
                </form>

                <div class="toggle-area">
                    Belum punya akun?
                    <span class="toggle-link" onclick="toggleMode()">Daftar Member Baru</span>
                </div>
            </div>

            <div class="form-panel">
                <div class="header-title">✨ Member Baru</div>
                <p class="header-sub">Lengkapi data diri Anda untuk menjadi anggota perpustakaan.</p>

                <?php if (isset($_SESSION['error_register'])): ?>
                <div class="notification-card error">
                    <span class="notification-icon">🚫</span>
                    <span class="notification-content">
                        <b>Gagal Mendaftar</b><br>
                        <?php echo $_SESSION['error_register']; unset($_SESSION['error_register']); ?>
                    </span>
                </div>
                <?php endif; ?>

                <form action="proses_daftar.php" method="POST">
                    <div class="input-row">
                        <div class="input-group">
                            <label>Username</label>
                            <input type="text" name="new_username" placeholder="Buat username unik" required>
                        </div>
                        <div class="input-group">
                            <label>Password</label>
                            <input type="password" name="new_password" placeholder="Min. 8 karakter" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="fullname" placeholder="Sesuai KTP / Kartu Pelajar" required>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>NIK / No. KTP</label>
                            <input type="number" name="nik" placeholder="16 digit NIK" required>
                        </div>
                        <div class="input-group">
                            <label>Jenis Kelamin</label>
                            <select name="gender" required>
                                <option value="">- Pilih -</option>
                                <option value="Laki-laki">Laki-laki</option>
                                <option value="Perempuan">Perempuan</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="email@contoh.com" required>
                        </div>
                        <div class="input-group">
                            <label>No. Telepon / WA</label>
                            <input type="number" name="no_telp" placeholder="08xxxxxxxxxx" required>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Alamat Lengkap</label>
                        <textarea name="alamat" rows="2" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..."
                            required></textarea>
                    </div>

                    <button type="submit" class="btn-submit">🚀 Daftar Sekarang</button>
                </form>

                <div class="toggle-area">
                    Sudah punya akun?
                    <span class="toggle-link" onclick="toggleMode()">Login Disini</span>
                </div>
            </div>

        </div>
    </div>

    <script>
    function toggleMode() {
        const container = document.getElementById('mainContainer');
        container.classList.toggle('mode-signup');
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // Logic untuk tetap di tab REGISTER jika ada error register
    <?php if (isset($_SESSION['register_mode'])): ?>
    toggleMode();
    <?php unset($_SESSION['register_mode']); ?>
    <?php endif; ?>
    </script>
</body>

</html>