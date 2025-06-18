<?php
// Memulai sesi untuk mengakses variabel $_SESSION
session_start();
require_once '../../db_connect.php';

if (!isAdminLoggedIn()) {
    redirect('../../login.php');
}
$conn = connect_db();

// --- PERBAIKAN 1: Query SQL yang lebih akurat dan aman ---
$sql = "SELECT 
            p.payment_id,
            p.order_id,
            p.kostum_request_id,
            p.bank_pengirim,
            p.nama_rekening_pengirim,
            p.jumlah_transfer,
            p.tanggal_transfer,
            p.bukti_pembayaran_url,
            p.tanggal_konfirmasi,
            u.nama_lengkap,
            u.username
        FROM pembayaran p
        LEFT JOIN orders o ON p.order_id = o.order_id
        LEFT JOIN orderkostum ok ON p.kostum_request_id = ok.kostum_request_id
        LEFT JOIN users u ON u.user_id = COALESCE(o.user_id, ok.user_id)
        WHERE 
            (p.order_id IS NOT NULL AND o.status = 'menunggu_verifikasi') 
            OR 
            (p.kostum_request_id IS NOT NULL AND ok.status_request = 'menunggu_verifikasi')
        ORDER BY p.tanggal_konfirmasi ASC";

$result = $conn->query($sql);
$payments_to_verify = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();

// Dummy function jika belum ada
if (!function_exists('sanitize_output')) {
    function sanitize_output($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Pembayaran - Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/sidebar_style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
<div class="sidebar">
    <h1 class="site-title">BLOOMARIE DASHBOARD</h1>
    <div class="view-site-link">
        <a href="../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="index.php" class="active"><i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
        <a href="../modules/produk/index.php"><i class="fas fa-box-open"></i> Kelola Produk</a>
        <a href="../modules/kategori/index.php"><i class="fas fa-list-alt"></i> Kelola Kategori</a>
        <a href="../modules/users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
        <a href="../modules/orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
        <a href="../modules/order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
        <a href="../modules/kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
        <a href="#"><i class="fas fa-star"></i> Ulasan Produk</a>
        <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        <a href="../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a>
    </div>
    <div class="logout-link">
        <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>
<div class="main-content">
    <nav class="navbar navbar-expand-lg admin-navbar mb-4">
        <div class="container-fluid">
            <h4 class="mb-0">Verifikasi Pembayaran</h4>
        </div>
    </nav>
    <div class="container-fluid">
        <?php if (isset($_SESSION['verification_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['verification_message']; unset($_SESSION['verification_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID Pesanan</th>
                                <th>Pelanggan</th>
                                <th>Tgl. Konfirmasi</th>
                                <th>Jumlah Transfer</th>
                                <th>Pengirim</th>
                                <th>Bukti</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($payments_to_verify) > 0): ?>
                                <?php foreach ($payments_to_verify as $payment): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $payment['order_id'] ? '#' . $payment['order_id'] : '#K' . $payment['kostum_request_id']; ?></strong>
                                    </td>
                                    <td><?php echo sanitize_output($payment['nama_lengkap'] ?? $payment['username']); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($payment['tanggal_konfirmasi'])); ?></td>
                                    <td>Rp <?php echo number_format($payment['jumlah_transfer'], 0, ',', '.'); ?></td>
                                    <td><?php echo sanitize_output($payment['nama_rekening_pengirim']) . '<br><small class="text-muted">' . sanitize_output($payment['bank_pengirim']) . '</small>'; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL . sanitize_output($payment['bukti_pembayaran_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info">Lihat Bukti</a>
                                    </td>
                                    <td>
                                        <form action="proses_verifikasi.php" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menyetujui pembayaran ini?');">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Setujui</button>
                                        </form>
                                        <form action="proses_verifikasi.php" method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin menolak pembayaran ini?');">
                                            <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Tolak</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted p-4">Tidak ada pembayaran yang perlu diverifikasi saat ini.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>