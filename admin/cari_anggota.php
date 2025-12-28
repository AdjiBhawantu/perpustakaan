<?php
// FILE: admin/cari_anggota.php
require_once 'includes/functions.php';

// Cek Login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    redirect('../login.php');
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card shadow-sm mb-4" style="border: none; border-radius: 15px;">
            <div class="card-body">
                <h4 class="card-title text-primary mb-4">
                    <i class="mdi mdi-account-search mr-2"></i>Pencarian Anggota Cepat
                </h4>

                <div class="form-group">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0"
                                style="border-radius: 10px 0 0 10px;">
                                <i class="mdi mdi-magnify text-primary" style="font-size: 1.2rem;"></i>
                            </span>
                        </div>
                        <input type="text" id="keyword" class="form-control border-left-0"
                            placeholder="Ketik Nama, Username, atau ID Anggota..."
                            style="height: 50px; font-size: 1.1rem; border-radius: 0 10px 10px 0;" autocomplete="off">
                    </div>
                    <small class="text-muted ml-2">Hasil akan muncul otomatis saat Anda mengetik...</small>
                </div>

                <div id="loading" class="text-center py-4" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Mencari data...</p>
                </div>

                <div id="hasil-pencarian">
                    <div class="text-center py-5 text-muted">
                        <i class="mdi mdi-keyboard-variant display-4"></i>
                        <p class="mt-2">Silakan ketik kata kunci untuk mencari anggota.</p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {

    // Saat mengetik di kolom pencarian
    $('#keyword').on('keyup', function() {
        var keyword = $(this).val();

        if (keyword.length > 0) {
            // Tampilkan loading, sembunyikan hasil lama
            $('#loading').show();
            $('#hasil-pencarian').hide();

            $.ajax({
                url: 'ajax_cari.php', // File pemroses di bawah
                method: 'POST',
                data: {
                    keyword: keyword
                },
                success: function(response) {
                    // Sembunyikan loading, tampilkan hasil baru
                    $('#loading').hide();
                    $('#hasil-pencarian').html(response).fadeIn();
                },
                error: function() {
                    $('#loading').hide();
                    $('#hasil-pencarian').html(
                        '<p class="text-danger text-center">Terjadi kesalahan koneksi.</p>'
                        ).show();
                }
            });
        } else {
            // Jika input kosong, kembalikan ke tampilan awal
            $('#loading').hide();
            $('#hasil-pencarian').html(`
                <div class="text-center py-5 text-muted">
                    <i class="mdi mdi-keyboard-variant display-4"></i>
                    <p class="mt-2">Silakan ketik kata kunci untuk mencari anggota.</p>
                </div>
            `).show();
        }
    });

});
</script>

<?php include 'includes/footer.php'; ?>