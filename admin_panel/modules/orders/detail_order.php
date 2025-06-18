<?php
session_start();
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}
$conn = connect_db();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    $_SESSION['order_error_message'] = "ID Pesanan tidak valid.";
    redirect('index.php');
}

// --- Query Utama untuk Mengambil Detail Pesanan, Pelanggan, dan Alamat ---
$sql_order = "SELECT o.*, u.username, u.nama_lengkap, u.email 
              FROM orders o
              JOIN users u ON o.user_id = u.user_id
              WHERE o.order_id = ?";
$stmt_order = $conn->prepare($sql_order);
$stmt_order->bind_param("i", $order_id);
$stmt_order->execute();
$result_order = $stmt_order->get_result();
$order = $result_order->fetch_assoc();
$stmt_order->close();

if (!$order) {
    $_SESSION['order_error_message'] = "Pesanan tidak ditemukan.";
    redirect('index.php');
}

// --- Query untuk Mengambil Item-item dalam Pesanan ---
$sql_items = "SELECT od.kuantitas, od.price, p.name as product_name, p.foto_produk
              FROM orderdetails od
              JOIN produk p ON od.product_id = p.product_id
              WHERE od.order_id = ?";
$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order_id);
$stmt_items->execute();
$result_items = $stmt_items->get_result();
$order_items = $result_items->fetch_all(MYSQLI_ASSOC);
$stmt_items->close();

// --- Query untuk Mengambil Informasi Pembayaran jika Ada ---
$sql_payment = "SELECT * FROM pembayaran WHERE order_id = ? ORDER BY tanggal_konfirmasi DESC LIMIT 1";
$stmt_payment = $conn->prepare($sql_payment);
$stmt_payment->bind_param("i", $order_id);
$stmt_payment->execute();
$result_payment = $stmt_payment->get_result();
$payment_info = $result_payment->fetch_assoc();
$stmt_payment->close();

$conn->close();

$status_options = ['menunggu_pembayaran', 'menunggu_verifikasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

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
    <title>Detail Pesanan #<?php echo $order['order_id']; ?> - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
            <a href="index.php" class="active"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="../order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="../review/index.php"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Detail Pesanan #<?php echo $order['order_id']; ?></h4>
                <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Pesanan</a>
            </div>
        </nav>

        <div class="container-fluid">
            <?php if (isset($_SESSION['order_success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['order_success_message']; unset($_SESSION['order_success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card detail-card">
                        <div class="card-body p-4">
                            <h5 class="mb-4">Detail Pesanan & Pengiriman</h5>
                            
                            <dl class="row mb-4">
                                <dt class="col-sm-3 detail-label">Pelanggan:</dt>
                                <dd class="col-sm-9"><?php echo sanitize_output($order['nama_lengkap'] ?? $order['username']); ?></dd>
                                
                                <dt class="col-sm-3 detail-label">Email:</dt>
                                <dd class="col-sm-9"><a href="mailto:<?php echo sanitize_output($order['email']); ?>"><?php echo sanitize_output($order['email']); ?></a></dd>

                                <dt class="col-sm-3 detail-label">Penerima:</dt>
                                <dd class="col-sm-9"><?php echo sanitize_output($order['nama_penerima_order']); ?></dd>
                                
                                <dt class="col-sm-3 detail-label">Telepon:</dt>
                                <dd class="col-sm-9"><?php echo sanitize_output($order['nomor_telepon_penerima_order']); ?></dd>
                                
                                <dt class="col-sm-3 detail-label">Alamat:</dt>
                                <dd class="col-sm-9"><?php echo nl2br(sanitize_output($order['alamat_lengkap_order'])); ?></dd>
                            </dl>

                            <hr>
                            
                            <h6 class="mb-3">Item yang Dipesan</h6>
                            <ul class="list-group list-group-flush">
                            <?php foreach ($order_items as $item): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                                    <div class="d-flex align-items-center">
                                        <img src="../../../<?php echo sanitize_output($item['foto_produk']); ?>" width="60" height="60" class="rounded me-3" style="object-fit: cover;">
                                        <div>
                                            <div><?php echo sanitize_output($item['product_name']); ?></div>
                                            <small class="text-muted"><?php echo $item['kuantitas']; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></small>
                                        </div>
                                    </div>
                                    <span class="fw-bold">Rp <?php echo number_format($item['price'] * $item['kuantitas'], 0, ',', '.'); ?></span>
                                </li>
                            <?php endforeach; ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center fw-bold fs-5 px-0 mt-2">
                                    <span>Total Pesanan</span>
                                    <span>Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card detail-card">
                        <div class="card-body p-4 admin-form">
                            <h5 class="mb-3">Kelola Pesanan</h5>
                            <form action="proses_detail_order.php" method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Ubah Status Pesanan</label>
                                    <select name="status" id="status" class="form-select">
                                        <?php foreach($status_options as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php if ($order['status'] == $status) echo 'selected'; ?>>
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary-admin">Update Status</button>
                                </div>
                            </form>

                            <hr>

                            <h6 class="mb-3">Informasi Pembayaran</h6>
                            <?php if ($payment_info): ?>
                                <p class="mb-1"><strong class="detail-label">Bank:</strong><br><?php echo sanitize_output($payment_info['bank_pengirim']); ?></p>
                                <p class="mb-1"><strong class="detail-label">Pengirim:</strong><br><?php echo sanitize_output($payment_info['nama_rekening_pengirim']); ?></p>
                                <p class="mb-1"><strong class="detail-label">Jumlah:</strong><br>Rp <?php echo number_format($payment_info['jumlah_transfer'], 0, ',', '.'); ?></p>
                                <p class="mb-3"><strong class="detail-label">Tgl. Transfer:</strong><br><?php echo date('d F Y', strtotime($payment_info['tanggal_transfer'])); ?></p>
                                <div class="d-grid">
                                    <a href="../../../<?php echo sanitize_output($payment_info['bukti_pembayaran_url']); ?>" target="_blank" class="btn btn-outline-info">
                                        <i class="fas fa-receipt me-2"></i>Lihat Bukti Pembayaran
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted fst-italic">Pelanggan belum melakukan konfirmasi pembayaran.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>