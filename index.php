<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpustakaan - Sistem Peminjaman Buku Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <nav id="navbar">
        <a href="#" class="logo">
            📖 Perpustakaan
        </a>
        <div class="nav-container">
            <ul class="nav-links">
                <li><a href="#home" class="active">Home</a></li>
                <li><a href="#catalog">Catalog</a></li>
                <li><a href="#popular">Buku Terpopuler</a></li>
                <li><a href="#about">About</a></li>
            </ul>
            <a href="login.php" class="login-btn">Login</a>
        </div>
        <div class="mobile-menu-btn" id="mobileMenuBtn">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>

    <div class="mobile-menu" id="mobileMenu">
        <ul class="mobile-nav-links">
            <li><a href="#home">🏠 Home</a></li>
            <li><a href="#catalog">📚 Catalog</a></li>
            <li><a href="#popular">⭐ Buku Terpopuler</a></li>
            <li><a href="#about">ℹ️ About</a></li>
            <li><a href="#login" style="background: #3182ce; color: white; text-align: center;">🔐 Login</a></li>
        </ul>
    </div>

    <section class="hero" id="home">
        <div class="hero-container">
            <h1>Sistem Peminjaman Buku <span>Digital</span></h1>
            <p>Pinjam buku favorit Anda dengan mudah dan cepat. Akses ribuan koleksi buku dari berbagai kategori, kelola
                peminjaman online, dan nikmati kemudahan membaca kapan saja, di mana saja.</p>

            <div class="search-container">
                <input type="text" placeholder="Cari judul buku, penulis, atau ISBN untuk peminjaman...">
                <button class="search-btn">🔍 Cari</button>
            </div>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number">15,000+</div>
                    <div class="stat-label">Koleksi Buku</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">8,500+</div>
                    <div class="stat-label">Anggota Aktif</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99%</div>
                    <div class="stat-label">Kepuasan Pengguna</div>
                </div>
            </div>
        </div>
    </section>

    <section class="content" id="catalog">
        <h2 class="section-title">📚 Katalog Buku Terbaru</h2>
        <p class="section-subtitle">Jelajahi koleksi buku terbaru yang baru saja ditambahkan ke perpustakaan kami.</p>

        <div class="catalog-grid">
            <div class="card-book">
                <div class="card-thumb">
                    📙
                    <span class="card-badge">Novel</span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Laut Bercerita</h3>
                    <div class="card-author">Leila S. Chudori</div>
                    <div class="card-footer">
                        <div class="card-rating">⭐ 4.9</div>
                        <a href="#" class="btn-card-action">Pinjam +</a>
                    </div>
                </div>
            </div>

            <div class="card-book">
                <div class="card-thumb">
                    📘
                    <span class="card-badge">Teknologi</span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Clean Code</h3>
                    <div class="card-author">Robert C. Martin</div>
                    <div class="card-footer">
                        <div class="card-rating">⭐ 4.8</div>
                        <a href="#" class="btn-card-action">Pinjam +</a>
                    </div>
                </div>
            </div>

            <div class="card-book">
                <div class="card-thumb">
                    📗
                    <span class="card-badge">Bisnis</span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Zero to One</h3>
                    <div class="card-author">Peter Thiel</div>
                    <div class="card-footer">
                        <div class="card-rating">⭐ 4.7</div>
                        <a href="#" class="btn-card-action">Pinjam +</a>
                    </div>
                </div>
            </div>

            <div class="card-book">
                <div class="card-thumb">
                    📕
                    <span class="card-badge">Self Help</span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Filosofi Teras</h3>
                    <div class="card-author">Henry Manampiring</div>
                    <div class="card-footer">
                        <div class="card-rating">⭐ 4.9</div>
                        <a href="#" class="btn-card-action">Pinjam +</a>
                    </div>
                </div>
            </div>
            <div class="card-book">
                <div class="card-thumb">
                    📓
                    <span class="card-badge">Fiksi</span>
                </div>
                <div class="card-body">
                    <h3 class="card-title">Bumi Manusia</h3>
                    <div class="card-author">Pramoedya A. Toer</div>
                    <div class="card-footer">
                        <div class="card-rating">⭐ 5.0</div>
                        <a href="#" class="btn-card-action">Pinjam +</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="content" id="popular" style="background-color: #f8fafc; border-radius: 20px;">
        <h2 class="section-title">🏆 Buku Paling Banyak Dipinjam</h2>
        <p class="section-subtitle">Daftar buku yang paling populer dan banyak dipinjam oleh anggota perpustakaan minggu
            ini</p>

        <div class="leaderboard">
            <div class="book-item">
                <div class="rank-badge first">1</div>
                <div class="book-thumbnail">📖</div>
                <div class="book-details">
                    <div class="book-title-text">Atomic Habits</div>
                    <div class="book-author-text">James Clear</div>
                    <div class="book-metrics">
                        <span class="metric"><span class="metric-icon">👁️</span> 1,234</span>
                        <span class="metric"><span class="metric-icon">⭐</span> 4.8/5</span>
                        <span class="metric"><span class="metric-icon">📥</span> 456 peminjaman</span>
                    </div>
                </div>
                <span class="trending-up">↗ Trending</span>
                <button class="borrow-btn">Pinjam Sekarang</button>
            </div>

            <div class="book-item">
                <div class="rank-badge second">2</div>
                <div class="book-thumbnail">📕</div>
                <div class="book-details">
                    <div class="book-title-text">Sapiens: A Brief History</div>
                    <div class="book-author-text">Yuval Noah Harari</div>
                    <div class="book-metrics">
                        <span class="metric"><span class="metric-icon">👁️</span> 1,089</span>
                        <span class="metric"><span class="metric-icon">⭐</span> 4.7/5</span>
                        <span class="metric"><span class="metric-icon">📥</span> 398 peminjaman</span>
                    </div>
                </div>
                <span class="trending-up">↗ Trending</span>
                <button class="borrow-btn">Pinjam Sekarang</button>
            </div>

            <div class="book-item">
                <div class="rank-badge third">3</div>
                <div class="book-thumbnail">📗</div>
                <div class="book-details">
                    <div class="book-title-text">The Psychology of Money</div>
                    <div class="book-author-text">Morgan Housel</div>
                    <div class="book-metrics">
                        <span class="metric"><span class="metric-icon">👁️</span> 987</span>
                        <span class="metric"><span class="metric-icon">⭐</span> 4.9/5</span>
                        <span class="metric"><span class="metric-icon">📥</span> 367 peminjaman</span>
                    </div>
                </div>
                <button class="borrow-btn">Pinjam Sekarang</button>
            </div>

            <div class="book-item">
                <div class="rank-badge other">4</div>
                <div class="book-thumbnail">📘</div>
                <div class="book-details">
                    <div class="book-title-text">Deep Work</div>
                    <div class="book-author-text">Cal Newport</div>
                    <div class="book-metrics">
                        <span class="metric"><span class="metric-icon">👁️</span> 876</span>
                        <span class="metric"><span class="metric-icon">⭐</span> 4.6/5</span>
                        <span class="metric"><span class="metric-icon">📥</span> 334 peminjaman</span>
                    </div>
                </div>
                <button class="borrow-btn">Pinjam Sekarang</button>
            </div>

            <div class="book-item">
                <div class="rank-badge other">5</div>
                <div class="book-thumbnail">📙</div>
                <div class="book-details">
                    <div class="book-title-text">Thinking, Fast and Slow</div>
                    <div class="book-author-text">Daniel Kahneman</div>
                    <div class="book-metrics">
                        <span class="metric"><span class="metric-icon">👁️</span> 823</span>
                        <span class="metric"><span class="metric-icon">⭐</span> 4.5/5</span>
                        <span class="metric"><span class="metric-icon">📥</span> 312 peminjaman</span>
                    </div>
                </div>
                <button class="borrow-btn">Pinjam Sekarang</button>
            </div>
        </div>
    </section>

    <script src="assets/js/script.js"></script>
</body>

</html>