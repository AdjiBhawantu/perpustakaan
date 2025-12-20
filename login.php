<?php
session_start();

// Cek jika user sudah login, langsung arahkan ke halaman yang sesuai
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
    <title>Login & Register - Perpustakaan</title>
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

        /* Warna Alert */
        --danger-bg: #fee2e2;
        --danger-text: #dc2626;
        --danger-border: #fca5a5;
        --success-bg: #dcfce7;
        --success-text: #16a34a;
        --success-border: #86efac;
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

    /* --- 2. ALERT NOTIFICATION (BARU) --- */
    .alert {
        width: 100%;
        padding: 12px 16px;
        margin-bottom: 20px;
        border-radius: 10px;
        font-size: 0.9rem;
        font-weight: 500;
        line-height: 1.4;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: fadeInDown 0.4s ease;
    }

    .alert-danger {
        background-color: var(--danger-bg);
        color: var(--danger-text);
        border: 1px solid var(--danger-border);
    }

    .alert-success {
        background-color: var(--success-bg);
        color: var(--success-text);
        border: 1px solid var(--success-border);
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
        max-width: 850px;
        min-height: 600px;
        /* Sedikit ditambah tinggi min-nya */
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

    .input-group {
        position: relative;
        margin-bottom: 1.5rem;
    }

    .input-group label {
        display: block;
        margin-bottom: 0.5rem;
        color: var(--text-main);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .input-group input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s;
        background: #f7fafc;
    }

    .input-group input:focus {
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
        margin-top: 2rem;
        color: var(--text-sub);
        font-size: 0.95rem;
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

    /* --- 9. ANIMASI --- */
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

    /* --- 10. RESPONSIVE --- */
    @media (max-width: 768px) {
        body {
            padding: 1rem;
        }

        .form-panel {
            padding: 2rem;
        }

        .header-title {
            font-size: 1.5rem;
        }

        .container {
            min-height: auto;
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
                <div class="header-title">📖 Perpustakaan</div>
                <p class="header-sub">Selamat datang kembali! Silakan masuk untuk mengakses koleksi buku digital.</p>

                <?php if (isset($_SESSION['error_login'])): ?>
                <div class="alert alert-danger">
                    ⚠️ <?php echo $_SESSION['error_login']; unset($_SESSION['error_login']); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_register'])): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $_SESSION['success_register']; unset($_SESSION['success_register']); ?>
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
                <p class="header-sub">Bergabunglah sekarang untuk meminjam buku secara gratis dan mudah.</p>

                <?php if (isset($_SESSION['error_register'])): ?>
                <div class="alert alert-danger">
                    ⚠️ <?php echo $_SESSION['error_register']; unset($_SESSION['error_register']); ?>
                </div>
                <?php endif; ?>

                <form action="proses_daftar.php" method="POST">
                    <div class="input-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="fullname" placeholder="Sesuai KTP / Kartu Pelajar" required>
                    </div>

                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="Contoh: email@anda.com" required>
                    </div>

                    <div class="input-group">
                        <label>Username</label>
                        <input type="text" name="new_username" placeholder="Buat username unik" required>
                    </div>

                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="new_password" placeholder="Minimal 8 karakter" required>
                    </div>

                    <button type="submit" class="btn-submit">🚀 Buat Akun</button>
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