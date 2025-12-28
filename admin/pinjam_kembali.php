<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

if (!isset($_GET['id'])) redirect('pinjam_aktif.php');
$id_peminjaman = clean_input($_GET['id']);

// 1. AMBIL DATA PEMINJAMAN
$sql = "SELECT p.*, a.Nama_Lengkap, a.No_Telepon, b.Judul, b.Cover_Buku, b.Id_Buku
        FROM peminjaman p
        JOIN anggota a ON p.Id_Anggota = a.Id_Anggota
        JOIN buku b ON p.Id_Buku = b.Id_Buku
        WHERE p.Id_Peminjaman = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_peminjaman);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "<script>alert('Data peminjaman tidak ditemukan!'); window.location='pinjam_aktif.php';</script>";
    exit;
}

// 2. PROSES PENGEMBALIAN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_kembali = $_POST['tgl_kembali'];
    $kondisi = $_POST['kondisi'];
    $denda_lain = str_replace('.', '', $_POST['denda_lain']); // Hapus titik ribuan
    $admin_id = $_SESSION['user_id'];
    
    // Hitung Keterlambatan
    $tgl_rencana = new DateTime($data['Tgl_Kembali_Rencana']);
    $tgl_aktual = new DateTime($tgl_kembali);
    $selisih = $tgl_rencana->diff($tgl_aktual);
    $hari_telat = ($tgl_aktual > $tgl_rencana) ? $selisih->days : 0;
    
    $tarif_denda = 1000; // Tarif per hari (Sesuaikan kebijakan)
    $total_denda_telat = $hari_telat * $tarif_denda;
    
    // Mulai Transaksi Database
    $conn->begin_transaction();
    
    try {
        // A. Update Tabel Peminjaman
        $status_akhir = ($kondisi == 'Hilang') ? 'Hilang' : 'Dikembalikan';
        $sql_update = "UPDATE peminjaman SET 
                        Tgl_Kembali_Aktual = ?, 
                        Status_Peminjaman = ?, 
                        Kondisi_Kembali = ?, 
                        Diterima_Oleh = ?, 
                        Updated_At = NOW() 
                       WHERE Id_Peminjaman = ?";
        $stmt_up = $conn->prepare($sql_update);
        $stmt_up->bind_param("sssss", $tgl_kembali, $status_akhir, $kondisi, $admin_id, $id_peminjaman);
        $stmt_up->execute();
        
        // B. Insert Denda (Jika Ada)
        // 1. Denda Keterlambatan
        if ($total_denda_telat > 0) {
            $id_denda1 = "DND-" . time() . rand(100,999);
            $sql_denda1 = "INSERT INTO denda (Id_Denda, Id_Peminjaman, Jenis_Denda, Jumlah_Hari_Terlambat, Tarif_Per_Hari, Total_Denda, Dibuat_Oleh) 
                           VALUES (?, ?, 'Keterlambatan', ?, ?, ?, ?)";
            $stmt_d1 = $conn->prepare($sql_denda1);
            $stmt_d1->bind_param("ssiids", $id_denda1, $id_peminjaman, $hari_telat, $tarif_denda, $total_denda_telat, $admin_id);
            $stmt_d1->execute();
        }
        
        // 2. Denda Kerusakan/Kehilangan
        if ($denda_lain > 0) {
            $id_denda2 = "DND-" . (time()+1) . rand(100,999);
            $jenis_denda2 = ($kondisi == 'Hilang') ? 'Kehilangan' : 'Kerusakan';
            // Kolom Biaya_Kerusakan atau Biaya_Kehilangan disesuaikan
            $col_biaya = ($jenis_denda2 == 'Kehilangan') ? 'Biaya_Kehilangan' : 'Biaya_Kerusakan';
            
            $sql_denda2 = "INSERT INTO denda (Id_Denda, Id_Peminjaman, Jenis_Denda, $col_biaya, Total_Denda, Dibuat_Oleh) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt_d2 = $conn->prepare($sql_denda2);
            $stmt_d2->bind_param("sssdds", $id_denda2, $id_peminjaman, $jenis_denda2, $denda_lain, $denda_lain, $admin_id);
            $stmt_d2->execute();
        }
        
        $conn->commit();
        echo "<script>alert('Buku berhasil dikembalikan!'); window.location='pinjam_riwayat.php';</script>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Terjadi kesalahan: " . $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-5 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-information-outline"></i> Info Peminjaman</h4>
                <hr>

                <div class="d-flex align-items-start mb-4">
                    <img src="<?php echo !empty($data['Cover_Buku']) ? '../uploads/buku/'.$data['Cover_Buku'] : 'images/no-cover.png'; ?>"
                        style="width:90px; height:130px; object-fit:cover; border-radius:5px; margin-right:15px; border:1px solid #ddd;">
                    <div>
                        <h5 class="font-weight-bold mb-1"><?php echo $data['Judul']; ?></h5>
                        <small class="text-muted d-block mb-1">ID Buku: <?php echo $data['Id_Buku']; ?></small>
                        <span class="badge badge-info">ID Transaksi: <?php echo $data['Id_Peminjaman']; ?></span>
                    </div>
                </div>

                <div class="form-group row mb-1">
                    <label class="col-sm-4 col-form-label text-muted">Peminjam</label>
                    <div class="col-sm-8 col-form-label font-weight-bold"><?php echo $data['Nama_Lengkap']; ?></div>
                </div>
                <div class="form-group row mb-1">
                    <label class="col-sm-4 col-form-label text-muted">Tgl Pinjam</label>
                    <div class="col-sm-8 col-form-label"><?php echo date('d M Y', strtotime($data['Tgl_Pinjam'])); ?>
                    </div>
                </div>
                <div class="form-group row mb-1">
                    <label class="col-sm-4 col-form-label text-muted">Jatuh Tempo</label>
                    <div class="col-sm-8 col-form-label text-danger font-weight-bold">
                        <?php echo date('d M Y', strtotime($data['Tgl_Kembali_Rencana'])); ?>
                        <input type="hidden" id="tgl_rencana" value="<?php echo $data['Tgl_Kembali_Rencana']; ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-7 grid-margin stretch-card">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h4 class="card-title text-success"><i class="mdi mdi-keyboard-return"></i> Proses Pengembalian</h4>

                <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" id="formKembali">

                    <div class="form-group">
                        <label class="font-weight-bold">Tanggal Dikembalikan</label>
                        <input type="date" name="tgl_kembali" id="tgl_kembali" class="form-control"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Kondisi Buku</label>
                        <select class="form-control" name="kondisi" id="kondisi" required>
                            <option value="Baik">✅ Baik (Normal)</option>
                            <option value="Rusak Ringan">⚠️ Rusak Ringan</option>
                            <option value="Rusak Berat">🛠️ Rusak Berat</option>
                            <option value="Hilang">❌ Hilang</option>
                        </select>
                    </div>

                    <div class="bg-light p-3 rounded mb-3">
                        <h6 class="text-muted mb-3">Rincian Biaya / Denda</h6>

                        <div class="d-flex justify-content-between mb-2">
                            <span>Keterlambatan (<span id="hari_telat">0</span> hari)</span>
                            <span class="font-weight-bold text-danger" id="info_denda_telat">Rp 0</span>
                        </div>

                        <div class="form-group mb-2" id="box_denda_lain" style="display:none;">
                            <label>Biaya Ganti Rugi (Kerusakan/Hilang)</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">Rp</span>
                                </div>
                                <input type="number" name="denda_lain" id="denda_lain" class="form-control"
                                    placeholder="0" min="0">
                            </div>
                            <small class="text-muted">*Isi sesuai harga buku atau biaya perbaikan</small>
                        </div>

                        <hr>
                        <div class="d-flex justify-content-between align-items-center">
                            <h5>Total Bayar</h5>
                            <h4 class="text-primary font-weight-bold" id="total_bayar">Rp 0</h4>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg btn-block"
                        onclick="return confirm('Proses pengembalian buku ini?');">
                        <i class="mdi mdi-check-circle"></i> Simpan Pengembalian
                    </button>
                    <a href="pinjam_aktif.php" class="btn btn-light btn-block mt-2">Batal</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
const tglRencanaStr = document.getElementById('tgl_rencana').value;
const inputTglKembali = document.getElementById('tgl_kembali');
const inputKondisi = document.getElementById('kondisi');
const inputDendaLain = document.getElementById('denda_lain');
const spanHariTelat = document.getElementById('hari_telat');
const spanDendaTelat = document.getElementById('info_denda_telat');
const boxDendaLain = document.getElementById('box_denda_lain');
const spanTotal = document.getElementById('total_bayar');

const TARIF_PER_HARI = 1000;

function hitungTotal() {
    // 1. Hitung Telat
    const tglRencana = new Date(tglRencanaStr);
    const tglKembali = new Date(inputTglKembali.value);

    // Hitung selisih waktu
    const diffTime = tglKembali - tglRencana;
    // Konversi ke hari (jika negatif berarti tidak telat)
    let hariTelat = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (hariTelat < 0) hariTelat = 0;

    const biayaTelat = hariTelat * TARIF_PER_HARI;

    // 2. Hitung Denda Lain
    let biayaLain = 0;
    if (inputKondisi.value !== 'Baik') {
        boxDendaLain.style.display = 'block';
        biayaLain = parseInt(inputDendaLain.value) || 0;
    } else {
        boxDendaLain.style.display = 'none';
        inputDendaLain.value = '';
    }

    // 3. Update UI
    spanHariTelat.innerText = hariTelat;
    spanDendaTelat.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(biayaTelat);

    const total = biayaTelat + biayaLain;
    spanTotal.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(total);
}

// Event Listeners
inputTglKembali.addEventListener('change', hitungTotal);
inputKondisi.addEventListener('change', hitungTotal);
inputDendaLain.addEventListener('input', hitungTotal);

// Run on load
hitungTotal();
</script>

<?php include 'includes/footer.php'; ?>