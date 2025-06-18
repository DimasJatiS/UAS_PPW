<?php
// Memulai sesi jika belum ada. Pastikan ini ada di db_connect.php atau di sini.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../db_connect.php'; 

if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}
$conn = connect_db();

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if ($request_id <= 0) {
    $_SESSION['order_kostum_error_message'] = "ID Permintaan tidak valid.";
    redirect('index.php');
}

// Mengambil semua data dari database
$sql = "SELECT ok.*, u.username, u.nama_lengkap, u.email 
        FROM orderkostum ok 
        LEFT JOIN users u ON ok.user_id = u.user_id 
        WHERE ok.kostum_request_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $request_id);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$request) {
    $_SESSION['order_kostum_error_message'] = "Permintaan pesanan kostum tidak ditemukan.";
    redirect('index.php');
}

// Opsi status yang tersedia untuk diubah oleh admin
$status_options = ['diskusi', 'ditolak', 'dibatalkan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'diproses', 'dikirim', 'selesai'];

if (!function_exists('sanitize_output')) {
    function sanitize_output($data) {
        return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan Kostum #K<?php echo $request['kostum_request_id']; ?> - Bloomarie Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar_style.css">
        <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .detail-card { border-radius: var(--border-radius-lg); box-shadow: var(--card-shadow); }
        .detail-label { font-weight: 600; color: var(--text-light); }
        .referensi-img { max-width: 100%; height: auto; border-radius: var(--border-radius-md); border: 1px solid var(--border-color); }
    </style>
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div class="sidebar">
        <h1 class="site-title">BLOOMARIE DASHBOARD</h1>
        <div class="view-site-link">
            <a href="../../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../../pembayaran/index.php"><i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
            <a href="../produk/index.php"><i class="fas fa-box-open"></i> Kelola Produk</a>
            <a href="../kategori/index.php"><i class="fas fa-list-alt"></i> Kelola Kategori</a>
            <a href="../users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="index.php" class="active"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="../review/index.php"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        </div>
        <div class="view-site-link"> <a href="../../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a> </div>
        <div class="logout-link"> <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a> </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Detail Pesanan Kostum #K<?php echo $request['kostum_request_id']; ?></h4>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i> Kembali</a>
            </div>
        </nav>
        
        <?php if (isset($_SESSION['order_kostum_success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['order_kostum_success_message']; unset($_SESSION['order_kostum_success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['order_kostum_error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['order_kostum_error_message']; unset($_SESSION['order_kostum_error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card detail-card">
                    <div class="card-body p-4">
                        <h5 class="mb-4">Detail Permintaan</h5>
                        <dl class="row">
                            <dt class="col-sm-4 detail-label">Pelanggan:</dt>
                            <dd class="col-sm-8"><?php echo sanitize_output($request['nama_lengkap'] ?? $request['username']); ?></dd>
                            <dt class="col-sm-4 detail-label">Email:</dt>
                            <dd class="col-sm-8"><a href="mailto:<?php echo sanitize_output($request['email']); ?>"><?php echo sanitize_output($request['email']); ?></a></dd>
                            <dt class="col-sm-4 detail-label">Tanggal Permintaan:</dt>
                            <dd class="col-sm-8"><?php echo date('d F Y, H:i', strtotime($request['tanggal_request'])); ?></dd>
                            <dt class="col-sm-4 detail-label">Estimasi Budget:</dt>
                            <dd class="col-sm-8">Rp <?php echo $request['budget_estimasi'] ? number_format($request['budget_estimasi'], 0, ',', '.') : '-'; ?></dd>
                            <dt class="col-sm-4 detail-label">Total Harga Final:</dt>
                            <dd class="col-sm-8 fw-bold text-success">Rp <?php echo $request['total_harga'] ? number_format($request['total_harga'], 0, ',', '.') : '<em class="text-muted">Belum ditetapkan</em>'; ?></dd>
                            <dt class="col-sm-12 mt-3 detail-label">Deskripsi dari Pelanggan:</dt>
                            <dd class="col-sm-12"><p class="p-3 bg-light" style="border-radius: var(--border-radius-md);"><?php echo nl2br(sanitize_output($request['deskripsi_request'])); ?></p></dd>
                            <?php if (!empty($request['referensi_gambar_url'])): ?>
                                <dt class="col-sm-12 mt-3 detail-label">Gambar Referensi:</dt>
                                <dd class="col-sm-12">
                                    <a href="<?php echo BASE_URL . sanitize_output($request['referensi_gambar_url']); ?>" target="_blank">
                                        <img src="<?php echo BASE_URL . sanitize_output($request['referensi_gambar_url']); ?>" class="referensi-img" alt="Gambar Referensi">
                                    </a>
                                </dd>
                            <?php endif; ?>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card detail-card">
                     <div class="card-body p-4 admin-form">
                        <h5 class="mb-3">Kelola Permintaan</h5>
                        <form action="proses_detail_kostum.php" method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $request['kostum_request_id']; ?>">
                            
                             <div class="form-group mb-3">
                                <label for="status_request" class="form-label">Ubah Status Permintaan</label>
                                <select class="form-select" id="status_request" name="status_request">
                                    <?php foreach ($status_options as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php if ($request['status_request'] == $status) echo 'selected'; ?>>
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3" id="harga_final_container" style="display: none;">
                                <label for="total_harga" class="form-label">Harga Produk Jadi</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="total_harga" id="total_harga" value="0" min="0" step="10000">
                                </div>
                            </div>

                            <div class="form-group mb-3">
                                <label for="catatan_dari_toko" class="form-label">Catatan dari Toko (Untuk Pelanggan)</label>
                                <textarea class="form-control" name="catatan_dari_toko" id="catatan_dari_toko" rows="6"><?php echo sanitize_output($request['catatan_dari_toko']); ?></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-admin"><i class="fas fa-sync-alt me-2"></i> Update Permintaan</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript untuk menampilkan/menyembunyikan field harga secara dinamis
        document.addEventListener('DOMContentLoaded', function() {
            const statusSelect = document.getElementById('status_request');
            const hargaContainer = document.getElementById('harga_final_container');
            const hargaInput = document.getElementById('total_harga');

            function toggleHargaField() {
                // Tampilkan field harga HANYA jika status yang dipilih adalah 'selesai_diskusi'
                if (statusSelect.value === 'menunggu_pembayaran') {
                    hargaContainer.style.display = 'block';
                    hargaInput.required = true; // Jadikan wajib diisi
                } else {
                    hargaContainer.style.display = 'none';
                    hargaInput.required = false; // Jadikan tidak wajib diisi
                }
            }

            // Panggil fungsi saat halaman dimuat untuk memeriksa status awal
            toggleHargaField();

            // Panggil fungsi setiap kali nilai dropdown status berubah
            statusSelect.addEventListener('change', toggleHargaField);
        });
    </script>
</body>
</html>