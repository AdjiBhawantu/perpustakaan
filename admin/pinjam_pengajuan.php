<?php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id'])) redirect('../login.php');

$pending = ambil_pengajuan('Menunggu');
$semua = ambil_pengajuan('Semua');

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12 grid-margin stretch-card">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="card-title text-primary"><i class="mdi mdi-inbox-arrow-down mr-2"></i>Daftar Pengajuan
                    Peminjaman</h4>

                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active font-weight-bold" id="pending-tab" data-toggle="tab" href="#pending"
                            role="tab">
                            ⏳ Menunggu Konfirmasi
                            <?php if($pending->num_rows > 0): ?>
                            <span class="badge badge-danger ml-2"><?php echo $pending->num_rows; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link font-weight-bold" id="history-tab" data-toggle="tab" href="#history"
                            role="tab">📜 Riwayat Lengkap</a>
                    </li>
                </ul>

                <div class="tab-content pt-4" id="myTabContent">

                    <div class="tab-pane fade show active" id="pending" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>ID Request</th>
                                        <th>Anggota</th>
                                        <th>Buku</th>
                                        <th>Durasi</th>
                                        <th>Menunggu</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($pending->num_rows > 0): ?>
                                    <?php while($row = $pending->fetch_assoc()): ?>
                                    <?php 
                                                // Highlight jika menunggu > 3 hari
                                                $bg_class = ($row['Hari_Menunggu'] > 3) ? 'style="background-color: #fff5f5; border-left: 4px solid #fc8181;"' : '';
                                                $text_class = ($row['Hari_Menunggu'] > 3) ? 'text-danger font-weight-bold' : 'text-muted';
                                            ?>
                                    <tr <?php echo $bg_class; ?>>
                                        <td>
                                            <strong><?php echo $row['Id_Pengajuan']; ?></strong><br>
                                            <small
                                                class="text-muted"><?php echo date('d M Y', strtotime($row['Tgl_Pengajuan'])); ?></small>
                                        </td>
                                        <td>
                                            <?php echo $row['Nama_Anggota']; ?><br>
                                            <small class="text-muted"><?php echo $row['Id_Anggota']; ?></small>
                                        </td>
                                        <td><?php echo $row['Judul_Buku']; ?></td>
                                        <td>
                                            <?php echo date('d/m', strtotime($row['Tgl_Pinjam_Diinginkan'])); ?> s.d
                                            <?php echo date('d/m', strtotime($row['Tgl_Kembali_Diinginkan'])); ?>
                                            <br><small class="text-primary">(<?php echo $row['Durasi_Hari']; ?>
                                                Hari)</small>
                                        </td>
                                        <td class="<?php echo $text_class; ?>">
                                            <?php echo $row['Hari_Menunggu']; ?> Hari
                                            <?php if($row['Hari_Menunggu'] > 3) echo '<i class="mdi mdi-alert-circle"></i>'; ?>
                                        </td>
                                        <td>
                                            <a href="pinjam_proses.php?id=<?php echo $row['Id_Pengajuan']; ?>"
                                                class="btn btn-primary btn-sm btn-icon-text">
                                                <i class="mdi mdi-gavel btn-icon-prepend"></i> Proses
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">Tidak ada pengajuan baru.
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="history" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Anggota</th>
                                        <th>Buku</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($h = $semua->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $h['Id_Pengajuan']; ?></td>
                                        <td><?php echo $h['Nama_Lengkap']; ?></td>
                                        <td><?php echo $h['Judul']; ?></td>
                                        <td>
                                            <?php 
                                                if($h['Status_Pengajuan'] == 'Disetujui') echo '<span class="badge badge-success">Disetujui</span>';
                                                elseif($h['Status_Pengajuan'] == 'Ditolak') echo '<span class="badge badge-danger">Ditolak</span>';
                                                elseif($h['Status_Pengajuan'] == 'Menunggu') echo '<span class="badge badge-warning">Menunggu</span>';
                                                else echo '<span class="badge badge-secondary">Dibatalkan</span>';
                                                ?>
                                        </td>
                                        <td>
                                            <a href="pinjam_proses.php?id=<?php echo $h['Id_Pengajuan']; ?>"
                                                class="btn btn-light btn-sm"><i class="mdi mdi-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>