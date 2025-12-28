<?php
include 'includes/header_member.php';
// --- LOGIKA NOTIFIKASI & DETEKSI TANGGAL ---
$notifikasi = [];
$kirim_email = false;

// Cek buku yang sedang dipinjam
$sql_cek = "SELECT b.Judul, p.Tgl_Kembali_Rencana, DATEDIFF(p.Tgl_Kembali_Rencana, CURRENT_DATE()) as Sisa_Hari 
            FROM peminjaman p 
            JOIN buku b ON p.Id_Buku = b.Id_Buku 
            WHERE p.Id_Anggota = '$id_anggota' AND p.Status_Peminjaman = 'Dipinjam'";
$res_cek = $conn->query($sql_cek);

if ($res_cek->num_rows > 0) {
    while ($row = $res_cek->fetch_assoc()) {
        $sisa = $row['Sisa_Hari'];
        $judul = $row['Judul'];
        
        // Kondisi 1: Terlambat (Sisa hari minus)
        if ($sisa < 0) {
            $telat = abs($sisa);
            $notifikasi[] = [
                'type' => 'danger',
                'msg' => "🚨 <b>TERLAMBAT!</b> Buku <i>'$judul'</i> sudah lewat jatuh tempo $telat hari. Segera kembalikan!"
            ];
            $kirim_email = true; // Trigger email
        }
        // Kondisi 2: Jatuh Tempo Hari Ini (Sisa 0)
        elseif ($sisa == 0) {
            $notifikasi[] = [
                'type' => 'warning',
                'msg' => "⚠️ <b>HARI INI!</b> Batas pengembalian buku <i>'$judul'</i> adalah hari ini."
            ];
            $kirim_email = true; // Trigger email
        }
        // Kondisi 3: Besok (Sisa 1)
        elseif ($sisa == 1) {
            $notifikasi[] = [
                'type' => 'warning',
                'msg' => "⏰ <b>PENGINGAT:</b> Besok adalah batas pengembalian buku <i>'$judul'</i>."
            ];
        }
    }
}

// --- SIMULASI KIRIM EMAIL (BACKEND LOGIC) ---
if ($kirim_email) {
    // Di sini Anda bisa menggunakan PHPMailer
    $email_status = "<div class='alert alert-success'><i class='fa fa-envelope'></i> Notifikasi pengingat telah dikirim ke email: <b>$email_anggota</b></div>";
}
?>

<div class="container" style="min-height: 600px;">

    <div class="section-header" style="margin-top: 40px; margin-bottom: 30px;">
        <h2 style="color: #2d3748;">📚 Status Peminjaman Saya</h2>
        <p style="color: #718096;">Pantau buku yang sedang Anda pinjam dan riwayat pengembalian.</p>
    </div>

    <div class="notification-area">
        <?php 
        if (isset($email_status)) echo $email_status;
        
        foreach ($notifikasi as $notif) {
            echo "<div class='alert alert-{$notif['type']}'>{$notif['msg']}</div>";
        }
        ?>
    </div>

    <div class="card"
        style="background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background: #3182ce; color: white;">
                    <tr>
                        <th style="padding: 15px; text-align: left;">Buku</th>
                        <th style="padding: 15px;">Tanggal Pinjam</th>
                        <th style="padding: 15px;">Jatuh Tempo</th>
                        <th style="padding: 15px;">Status</th>
                        <th style="padding: 15px;">Denda</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // PERBAIKAN QUERY: Menambahkan Subquery untuk mengambil total Denda dari tabel denda
                    $sql = "SELECT p.*, b.Judul, b.Cover_Buku,
                            (SELECT SUM(Total_Denda) FROM denda WHERE Id_Peminjaman = p.Id_Peminjaman) as Denda
                            FROM peminjaman p 
                            JOIN buku b ON p.Id_Buku = b.Id_Buku 
                            WHERE p.Id_Anggota = '$id_anggota' 
                            ORDER BY p.Tgl_Pinjam DESC";
                            
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Cek Status Waktu
                            $tgl_kembali = strtotime($row['Tgl_Kembali_Rencana']);
                            $now = time();
                            
                            // Hitung telat hanya jika status masih Dipinjam
                            $hari_telat = 0;
                            if($row['Status_Peminjaman'] == 'Dipinjam') {
                                $selisih = $now - $tgl_kembali;
                                $hari_telat = floor($selisih / (60 * 60 * 24));
                            }
                            
                            // Badge Status
                            $status_badge = "";
                            if ($row['Status_Peminjaman'] == 'Dipinjam') {
                                if ($hari_telat > 0) {
                                    $status_badge = "<span class='badge badge-danger'>Terlambat $hari_telat Hari</span>";
                                } else {
                                    $status_badge = "<span class='badge badge-info'>Sedang Dipinjam</span>";
                                }
                            } elseif ($row['Status_Peminjaman'] == 'Dikembalikan') {
                                $status_badge = "<span class='badge badge-success'>Selesai</span>";
                            } else {
                                $status_badge = "<span class='badge badge-warning'>{$row['Status_Peminjaman']}</span>";
                            }

                            // Gambar
                            $cover = !empty($row['Cover_Buku']) ? "uploads/buku/".$row['Cover_Buku'] : "assets/images/no-cover.png";
                            
                            // Ambil nilai denda, default 0 jika null
                            $nilai_denda = $row['Denda'] ?? 0;
                            ?>
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 15px;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <img src="<?php echo $cover; ?>"
                                    style="width: 50px; height: 75px; object-fit: cover; border-radius: 5px;">
                                <div>
                                    <div style="font-weight: bold; color: #2d3748;"><?php echo $row['Judul']; ?></div>
                                    <small style="color: #718096;">ID: <?php echo $row['Id_Peminjaman']; ?></small>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 15px; text-align: center;">
                            <?php echo date('d M Y', strtotime($row['Tgl_Pinjam'])); ?></td>
                        <td style="padding: 15px; text-align: center; font-weight: bold;">
                            <?php echo date('d M Y', strtotime($row['Tgl_Kembali_Rencana'])); ?>
                        </td>
                        <td style="padding: 15px; text-align: center;"><?php echo $status_badge; ?></td>
                        <td style="padding: 15px; text-align: center;">
                            <?php 
                                    if ($nilai_denda > 0) {
                                        echo "<span style='color: #c53030; font-weight: bold;'>Rp " . number_format($nilai_denda, 0, ',', '.') . "</span>";
                                    } else {
                                        echo "-";
                                    }
                                    ?>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='5' style='padding: 30px; text-align: center; color: #718096;'>Belum ada riwayat peminjaman. <a href='index.php#catalog'>Pinjam buku sekarang!</a></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer_member.php'; ?>