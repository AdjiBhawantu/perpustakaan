<?php

include 'includes/header_member.php';
?>

<style>
.duration-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    /* 4 kolom */
    gap: 10px;
    margin-top: 10px;
}

/* Sembunyikan radio button asli */
.duration-option input[type="radio"] {
    display: none;
}

/* Style Label sebagai Tombol */
.duration-option label {
    display: block;
    padding: 10px;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
    color: #4a5568;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Efek Hover */
.duration-option label:hover {
    border-color: #bee3f8;
    background: #ebf8ff;
}

/* State Checked (Saat dipilih) */
.duration-option input[type="radio"]:checked+label {
    background: #3182ce;
    color: white;
    border-color: #3182ce;
    box-shadow: 0 4px 6px rgba(49, 130, 206, 0.3);
    transform: translateY(-2px);
}

#info-kembali {
    margin-top: 15px;
    padding: 10px;
    background: #f0fff4;
    border: 1px solid #c6f6d5;
    border-radius: 8px;
    color: #2f855a;
    font-size: 0.9rem;
    display: none;
    /* Sembunyi default */
}
</style>

<section id="home" class="hero">
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">Jelajahi Dunia<br>Lewat <span class="highlight">Buku Digital</span></h1>
            <p class="hero-subtitle">Akses ribuan koleksi buku terbaik secara gratis. Pinjam buku favoritmu kapan
                saja dan di mana saja.</p>
            <div class="hero-buttons">
                <a href="#catalog" class="cta-btn primary">Mulai Membaca</a>
                <a href="#popular" class="cta-btn secondary">Lihat Terpopuler</a>
            </div>

            <div class="hero-stats">
                <div class="stat-card">
                    <div class="stat-icon">📚</div>
                    <div class="stat-info">
                        <h4 class="counter" data-target="<?php echo $total_buku; ?>">0</h4>
                        <p>Koleksi Judul</p>
                        <span class="stat-sub">✔ <?php echo $buku_tersedia; ?> Copy Tersedia</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h4 class="counter" data-target="<?php echo $total_anggota; ?>">0</h4>
                        <p>Member Aktif</p>
                        <span class="stat-sub">Bergabunglah!</span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🔥</div>
                    <div class="stat-info">
                        <h4 class="counter" data-target="<?php echo $total_transaksi; ?>">0</h4>
                        <p>Transaksi</p>
                        <span class="stat-sub">Buku dipinjam</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="hero-image">
            <div class="book-stack">
                <div class="book book-1"></div>
                <div class="book book-2"></div>
                <div class="book book-3"></div>
            </div>
        </div>
    </div>
</section>

<section id="catalog" class="catalog-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Katalog <span class="highlight">Terbaru</span></h2>
            <p class="section-desc">Koleksi buku-buku terbaru yang baru saja mendarat di rak digital kami.</p>
        </div>

        <div class="catalog-grid">
            <?php
                $sql = "SELECT * FROM buku WHERE Status != 'Tidak Tersedia' ORDER BY Created_At DESC LIMIT 8";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $tersedia = $row['Jumlah_Tersedia'] > 0;
                        $cover = get_cover($row['Cover_Buku']);
                        $kat_id = $row['Id_Kategori'];
                        $kat_nm = $conn->query("SELECT Nama_Kategori FROM kategori_buku WHERE Id_Kategori='$kat_id'")->fetch_assoc()['Nama_Kategori'] ?? 'Umum';
                ?>
            <div class="book-card">
                <div class="book-cover">
                    <img src="<?php echo $cover; ?>" alt="<?php echo $row['Judul']; ?>">
                    <span class="category-badge"><?php echo $kat_nm; ?></span>
                </div>
                <div class="book-info">
                    <h3 class="book-title"><?php echo $row['Judul']; ?></h3>
                    <p class="book-author">by <?php echo $row['Penulis']; ?></p>

                    <div class="book-meta">
                        <span><?php echo $row['Tahun_Terbit']; ?></span>
                        <?php if ($tersedia): ?>
                        <span class="badge badge-success">Stok: <?php echo $row['Jumlah_Tersedia']; ?></span>
                        <?php else: ?>
                        <span class="badge badge-danger">Habis</span>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($tersedia): ?>
                    <button class="borrow-btn btn-primary-action"
                        onclick="openModal('<?php echo $row['Id_Buku']; ?>', '<?php echo addslashes($row['Judul']); ?>')">Pinjam
                        Sekarang</button>
                    <?php else: ?>
                    <button class="borrow-btn btn-disabled" disabled>Stok Habis</button>
                    <?php endif; ?>
                    <?php else: ?>
                    <a href="login.php" class="borrow-btn btn-primary-action">Login untuk Pinjam</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php 
                    }
                } else {
                    echo "<p style='text-align:center; width:100%; grid-column: 1/-1;'>Belum ada buku tersedia.</p>";
                }
                ?>
        </div>
    </div>
</section>

<section id="popular" class="popular-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Paling Sering <span class="highlight">Dipinjam</span></h2>
            <p class="section-desc">Buku-buku favorit yang paling banyak dibaca oleh anggota kami.</p>
        </div>

        <div class="popular-grid">
            <?php
                $sql_pop = "SELECT * FROM buku ORDER BY Jumlah_Dipinjam DESC LIMIT 3";
                $res_pop = $conn->query($sql_pop);
                $rank = 1;

                while ($row = $res_pop->fetch_assoc()) {
                    $cover = get_cover($row['Cover_Buku']);
                    $rank_class = ($rank == 1) ? 'rank-1' : (($rank == 2) ? 'rank-2' : 'rank-3');
                ?>
            <div class="popular-card">
                <div class="rank-number <?php echo $rank_class; ?>">#<?php echo $rank; ?></div>
                <div class="pop-thumb"><img src="<?php echo $cover; ?>" alt="Cover"></div>
                <div class="pop-details">
                    <div class="pop-title"><?php echo $row['Judul']; ?></div>
                    <div class="book-author" style="margin-bottom: 5px;"><?php echo $row['Penulis']; ?></div>
                    <div class="pop-stats">🔥 <?php echo $row['Jumlah_Dipinjam']; ?>x Dipinjam</div>

                    <div style="margin-top: 15px;">
                        <?php if (isset($_SESSION['user_id']) && $row['Jumlah_Tersedia'] > 0): ?>
                        <button class="borrow-btn btn-primary-action" style="padding: 8px; font-size: 0.9rem;"
                            onclick="openModal('<?php echo $row['Id_Buku']; ?>', '<?php echo addslashes($row['Judul']); ?>')">Pinjam</button>
                        <?php else: ?>
                        <a href="login.php" class="borrow-btn btn-primary-action"
                            style="padding: 8px; font-size: 0.9rem;">Lihat</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php $rank++; } ?>
        </div>
    </div>
</section>

<div id="borrowModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <h2 style="color: #2d3748; margin-bottom: 20px;">📚 Ajukan Peminjaman</h2>

        <form method="POST" action="">
            <input type="hidden" name="ajukan_pinjam" value="1">
            <input type="hidden" id="modal_id_buku" name="id_buku">

            <div class="form-group">
                <label>Judul Buku</label>
                <input type="text" id="modal_judul_buku" class="form-control" readonly
                    style="background: #f7fafc; color:#718096;">
            </div>

            <div class="form-group">
                <label>Berapa lama Anda ingin meminjam?</label>
                <div class="duration-grid">
                    <?php for($i=1; $i<=7; $i++): ?>
                    <div class="duration-option">
                        <input type="radio" name="durasi" id="hari_<?php echo $i; ?>" value="<?php echo $i; ?>"
                            <?php echo ($i==7) ? 'checked' : ''; ?> onchange="hitungTanggal(this.value)">
                        <label for="hari_<?php echo $i; ?>"><?php echo $i; ?> Hari</label>
                    </div>
                    <?php endfor; ?>
                </div>

                <div id="info-kembali">
                    <i class="fa fa-calendar-check-o"></i> Estimasi Pengembalian: <b id="tgl_kembali_text">-</b>
                </div>
            </div>

            <div class="form-group">
                <label>Catatan (Opsional)</label>
                <textarea name="catatan" class="form-control" rows="2"
                    placeholder="Keperluan tugas / penelitian..."></textarea>
            </div>

            <button type="submit" class="btn-submit">Kirim Pengajuan</button>
        </form>
    </div>
</div>

<footer style="background: #1a202c; color: white; padding: 60px 0;">
    <div class="container" style="text-align: center;">
        <h3 style="font-size: 1.8rem; margin-bottom: 10px;">📖 LibraryHub</h3>
        <p style="color: #a0aec0; max-width: 500px; margin: 0 auto;">Platform perpustakaan digital modern untuk masa
            depan pendidikan yang lebih baik.</p>
        <div
            style="margin-top: 40px; border-top: 1px solid #2d3748; padding-top: 20px; font-size: 0.9rem; color: #718096;">
            Copyright © 2025 LibraryHub. All rights reserved.
        </div>
    </div>
</footer>

<script>
// Modal Logic
const modal = document.getElementById("borrowModal");
const idInput = document.getElementById("modal_id_buku");
const judulInput = document.getElementById("modal_judul_buku");

function openModal(id, judul) {
    modal.style.display = "block";
    idInput.value = id;
    judulInput.value = judul;

    // Reset dan Trigger perhitungan tanggal default (7 hari)
    document.getElementById("hari_7").checked = true;
    hitungTanggal(7);
}

function closeModal() {
    modal.style.display = "none";
}

window.onclick = function(event) {
    if (event.target == modal) closeModal();
}

// Logic Hitung Tanggal Kembali (JS)
function hitungTanggal(durasi) {
    const today = new Date();
    const returnDate = new Date(today);
    returnDate.setDate(today.getDate() + parseInt(durasi));

    // Format Tanggal Indonesia (dd Mei yyyy)
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    const dateStr = returnDate.toLocaleDateString('id-ID', options);

    document.getElementById('tgl_kembali_text').innerText = dateStr;
    document.getElementById('info-kembali').style.display = 'block';
}

// Animasi Counter Angka
const counters = document.querySelectorAll('.counter');
const speed = 200;

counters.forEach(counter => {
    const updateCount = () => {
        const target = +counter.getAttribute('data-target');
        const count = +counter.innerText;
        const inc = target / speed;

        if (count < target) {
            counter.innerText = Math.ceil(count + inc);
            setTimeout(updateCount, 20);
        } else {
            counter.innerText = target;
        }
    };
    updateCount();
});
</script>
</body>

</html>