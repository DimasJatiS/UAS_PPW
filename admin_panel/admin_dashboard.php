<<<<<<< HEAD
<?php
// admin_panel/admin_dashboard.php
require_once '../db_connect.php'; // Akses db_connect.php di folder root

// 🔒 Pastikan hanya admin yang bisa akses
if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
    redirect('../login.php'); // Arahkan ke login jika bukan admin
}

$conn = connect_db(); // Buat koneksi database mysqli

// --- Fungsi Bantuan untuk Ambil Data ---
/**
 * Mengambil jumlah baris dari query SQL.
 * @param mysqli $conn Objek koneksi mysqli.
 * @param string $sql Kueri SQL SELECT COUNT(*).
 * @param array $params Array parameter untuk prepared statement.
 * @param string $types String tipe data untuk bind_param (mis. "is").
 * @return int Jumlah baris.
 */
function get_count($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (get_count): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
        return 0;
    }
    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Binding parameters failed (get_count): (" . $stmt->errno . ") " . $stmt->error);
            $stmt->close();
            return 0;
        }
    }
    if (!$stmt->execute()) {
        error_log("Execute failed (get_count): (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['count'] : 0;
}

/**
 * Mengambil daftar data dari query SQL dengan limit.
 * @param mysqli $conn Objek koneksi mysqli.
 * @param string $sql Kueri SQL SELECT.
 * @param array $params Array parameter untuk prepared statement.
 * @param string $types String tipe data untuk bind_param.
 * @param int $limit Batas jumlah data yang diambil.
 * @return array Daftar data.
 */
function get_data_list($conn, $sql, $params = [], $types = "", $limit = 5) {
    if (stripos($sql, 'LIMIT') === false) { // Tambahkan LIMIT jika belum ada
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (get_data_list): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
        return [];
    }
    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Binding parameters failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error);
            $stmt->close();
            return [];
        }
    }
    if (!$stmt->execute()) {
        error_log("Execute failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// --- Ambil Data untuk Dashboard ---

// 👤 Pengguna
$total_users = get_count($conn, "SELECT COUNT(*) as count FROM users");
$customer_users = get_count($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$admin_users = get_count($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin' OR role = 'superadmin'");
$recent_users = get_data_list($conn, "SELECT user_id, username, email, role, nama_lengkap, created_at FROM users ORDER BY created_at DESC");

// 🛍️ Produk
$total_products = get_count($conn, "SELECT COUNT(*) as count FROM produk");
$available_products = get_count($conn, "SELECT COUNT(*) as count FROM produk WHERE is_available = 1 AND stok > 0");
$outofstock_products = get_count($conn, "SELECT COUNT(*) as count FROM produk WHERE stok = 0 OR is_available = 0");
$low_stock_limit = 5; // Tentukan batas stok rendah
$low_stock_products = get_data_list($conn, "SELECT product_id, name, stok, harga FROM produk WHERE is_available = 1 AND stok > 0 AND stok < ? ORDER BY stok ASC", [$low_stock_limit], "i");

// 🛒 Pesanan Reguler (tabel 'orders')
$total_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders");
$pending_payment_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'menunggu_pembayaran'");
$processing_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'diproses'");
$recent_pending_orders = get_data_list($conn,
    "SELECT o.order_id, u.username as customer_name, o.tanggal_order, o.total, o.status
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.status IN ('menunggu_pembayaran', 'diproses')
        ORDER BY o.tanggal_order DESC"
);

// 🎨 Pesanan Kostum (tabel 'orderkostum')
$total_custom_orders = get_count($conn, "SELECT COUNT(*) as count FROM orderkostum");
$pending_custom_orders_count = get_count($conn, "SELECT COUNT(*) as count FROM orderkostum WHERE status_request IN ('menunggu_konfirmasi_awal', 'diskusi')");
$recent_custom_orders = get_data_list($conn,
    "SELECT ok.kostum_request_id, u.username as customer_name, ok.tanggal_request, ok.status_request, ok.budget_estimasi
        FROM orderkostum ok
        LEFT JOIN users u ON ok.user_id = u.user_id
        WHERE ok.status_request IN ('menunggu_konfirmasi_awal', 'diskusi')
        ORDER BY ok.tanggal_request DESC"
    );

// ✉️ Pesan Kontak
$total_messages = get_count($conn, "SELECT COUNT(*) as count FROM contact_messages");
$unread_messages = get_count($conn, "SELECT COUNT(*) as count FROM contact_messages WHERE status_baca = 'belum dibaca'");
$recent_unread_messages = get_data_list($conn, "SELECT message_id, nama_pengirim, subjek_pesan, tanggal_kirim FROM contact_messages WHERE status_baca = 'belum dibaca' ORDER BY tanggal_kirim DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sidebar_style.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<link rel="icon" href="assets/favicon.ico">
<body>
    <div class="sidebar">
        <h1 class="site-title">BLOOMARIE DASHBOARD</h1>
        <div class="view-site-link">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="pembayaran/index.php" target="_parent"> <i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
            <a href="modules/produk/index.php" target="_parent"><i class="fas fa-external-link-alt"></i> Kelola Produk</a>
            <a href="modules/kategori/index.php" target="_parent"> <i class="fas fa-list-alt"></i> Kelola Kategori</a>
            <a href="modules/users/index.php" target="_parent"> <i class="fas fa-external-link-alt"></i> Kelola Pengguna</a>
            <a href="modules/orders/index.php" target="_parent"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="modules/order_kostum/index.php" target="_parent"> <i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="modules/kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="#"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
            <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a>
        </div>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Dashboard Admin</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                    (<?php echo sanitize_output($_SESSION['admin_role'] ?? ''); ?>)
                </span>
            </div>
        </nav>

        <!-- Ringkasan Statistik -->
        <div class="row mb-4 g-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card users">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Total Pengguna</h5>
                            <p class="card-text"><?php echo $total_users; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="card-footer"><small>Pelanggan: <?php echo $customer_users; ?>, Admin: <?php echo $admin_users; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card products">
                     <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Total Produk</h5>
                            <p class="card-text"><?php echo $total_products; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-box-open"></i></div>
                    </div>
                     <div class="card-footer"><small>Tersedia: <?php echo $available_products; ?>, Habis/Nonaktif: <?php echo $outofstock_products; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card orders">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Pesanan Reguler</h5>
                            <p class="card-text"><?php echo $total_orders; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="card-footer"><small>Pending Bayar: <?php echo $pending_payment_orders; ?>, Diproses: <?php echo $processing_orders; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card messages">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Pesan Belum Dibaca</h5>
                            <p class="card-text"><?php echo $unread_messages; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-envelope"></i></div>
                    </div>
                    <div class="card-footer"><small>Total Pesan: <?php echo $total_messages; ?></small></div>
                </div>
            </div>
        </div>

        <!-- Daftar Data Penting -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-circle text-danger me-2"></i>Produk Stok Rendah (< <?php echo $low_stock_limit; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_products)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>Nama Produk</th><th>Stok</th><th>Harga</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($low_stock_products as $p): ?>
                                    <tr>
                                        <td><?php echo sanitize_output($p['name']); ?></td>
                                        <td><span class="badge bg-danger"><?php echo $p['stok']; ?></span></td>
                                        <td>Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                                        <td><a href="modules/produk/edit_produk.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">🎉 Tidak ada produk dengan stok rendah saat ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools text-info me-2"></i>Permintaan Pesanan Kostum Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_custom_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>ID</th><th>Pelanggan</th><th>Budget (Est.)</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($recent_custom_orders as $co): ?>
                                    <tr>
                                        <td>#K<?php echo $co['kostum_request_id']; ?></td>
                                        <td><?php echo sanitize_output($co['customer_name'] ?? 'N/A'); ?></td>
                                        <td>Rp <?php echo $co['budget_estimasi'] ? number_format($co['budget_estimasi'], 0, ',', '.') : '-'; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $co['status_request'])); ?></span></td>
                                        <td><a href="modules/order_kostum/index.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-comments me-1"></i>Diskusi</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">✨ Tidak ada permintaan pesanan kostum baru.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus text-success me-2"></i>Pengguna Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Bergabung</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['user_id']; ?></td>
                                        <td><?php echo sanitize_output($u['username']); ?></td>
                                        <td><?php echo sanitize_output($u['nama_lengkap'] ?? 'N/A'); ?></td>
                                        <td><?php echo sanitize_output($u['email']); ?></td>
                                        <td><span class="badge" style="background-color: <?php echo ($u['role'] === 'admin' || $u['role'] === 'superadmin') ? 'var(--accent-color)' : '#6c757d'; ?>; color: white;"><?php echo ucfirst(sanitize_output($u['role'])); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                        <td><a href="modules/users/index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-user-edit me-1"></i>Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">Belum ada pengguna terdaftar.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- .main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
=======
<?php
// admin_panel/admin_dashboard.php
require_once '../db_connect.php'; // Akses db_connect.php di folder root

// 🔒 Pastikan hanya admin yang bisa akses
if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
    redirect('../login.php'); // Arahkan ke login jika bukan admin
}

$conn = connect_db(); // Buat koneksi database mysqli

// --- Fungsi Bantuan untuk Ambil Data ---
/**
 * Mengambil jumlah baris dari query SQL.
 * @param mysqli $conn Objek koneksi mysqli.
 * @param string $sql Kueri SQL SELECT COUNT(*).
 * @param array $params Array parameter untuk prepared statement.
 * @param string $types String tipe data untuk bind_param (mis. "is").
 * @return int Jumlah baris.
 */
function get_count($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (get_count): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
        return 0;
    }
    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Binding parameters failed (get_count): (" . $stmt->errno . ") " . $stmt->error);
            $stmt->close();
            return 0;
        }
    }
    if (!$stmt->execute()) {
        error_log("Execute failed (get_count): (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        return 0;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? (int)$row['count'] : 0;
}

/**
 * Mengambil daftar data dari query SQL dengan limit.
 * @param mysqli $conn Objek koneksi mysqli.
 * @param string $sql Kueri SQL SELECT.
 * @param array $params Array parameter untuk prepared statement.
 * @param string $types String tipe data untuk bind_param.
 * @param int $limit Batas jumlah data yang diambil.
 * @return array Daftar data.
 */
function get_data_list($conn, $sql, $params = [], $types = "", $limit = 5) {
    if (stripos($sql, 'LIMIT') === false) { // Tambahkan LIMIT jika belum ada
        $sql .= " LIMIT " . (int)$limit;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed (get_data_list): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
        return [];
    }
    if (!empty($params) && !empty($types)) {
        if (!$stmt->bind_param($types, ...$params)) {
            error_log("Binding parameters failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error);
            $stmt->close();
            return [];
        }
    }
    if (!$stmt->execute()) {
        error_log("Execute failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error);
        $stmt->close();
        return [];
    }
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// --- Ambil Data untuk Dashboard ---

// 👤 Pengguna
$total_users = get_count($conn, "SELECT COUNT(*) as count FROM users");
$customer_users = get_count($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'customer'");
$admin_users = get_count($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin' OR role = 'superadmin'");
$recent_users = get_data_list($conn, "SELECT user_id, username, email, role, nama_lengkap, created_at FROM users ORDER BY created_at DESC");

// 🛍️ Produk
$total_products = get_count($conn, "SELECT COUNT(*) as count FROM produk");
$available_products = get_count($conn, "SELECT COUNT(*) as count FROM produk WHERE is_available = 1 AND stok > 0");
$outofstock_products = get_count($conn, "SELECT COUNT(*) as count FROM produk WHERE stok = 0 OR is_available = 0");
$low_stock_limit = 5; // Tentukan batas stok rendah
$low_stock_products = get_data_list($conn, "SELECT product_id, name, stok, harga FROM produk WHERE is_available = 1 AND stok > 0 AND stok < ? ORDER BY stok ASC", [$low_stock_limit], "i");

// 🛒 Pesanan Reguler (tabel 'orders')
$total_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders");
$pending_payment_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'menunggu_pembayaran'");
$processing_orders = get_count($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'diproses'");
$recent_pending_orders = get_data_list($conn,
    "SELECT o.order_id, u.username as customer_name, o.tanggal_order, o.total, o.status
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.user_id
        WHERE o.status IN ('menunggu_pembayaran', 'diproses')
        ORDER BY o.tanggal_order DESC"
);

// 🎨 Pesanan Kostum (tabel 'orderkostum')
$total_custom_orders = get_count($conn, "SELECT COUNT(*) as count FROM orderkostum");
$pending_custom_orders_count = get_count($conn, "SELECT COUNT(*) as count FROM orderkostum WHERE status_request IN ('menunggu_konfirmasi_awal', 'diskusi')");
$recent_custom_orders = get_data_list($conn,
    "SELECT ok.kostum_request_id, u.username as customer_name, ok.tanggal_request, ok.status_request, ok.budget_estimasi
        FROM orderkostum ok
        LEFT JOIN users u ON ok.user_id = u.user_id
        WHERE ok.status_request IN ('menunggu_konfirmasi_awal', 'diskusi')
        ORDER BY ok.tanggal_request DESC"
    );

// ✉️ Pesan Kontak
$total_messages = get_count($conn, "SELECT COUNT(*) as count FROM contact_messages");
$unread_messages = get_count($conn, "SELECT COUNT(*) as count FROM contact_messages WHERE status_baca = 'belum dibaca'");
$recent_unread_messages = get_data_list($conn, "SELECT message_id, nama_pengirim, subjek_pesan, tanggal_kirim FROM contact_messages WHERE status_baca = 'belum dibaca' ORDER BY tanggal_kirim DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/sidebar_style.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<link rel="icon" href="assets/favicon.ico">
<body>
    <div class="sidebar">
        <h1 class="site-title">BLOOMARIE DASHBOARD</h1>
        <div class="view-site-link">
            <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="pembayaran/index.php" target="_parent"> <i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
            <a href="modules/produk/index.php" target="_parent"><i class="fas fa-external-link-alt"></i> Kelola Produk</a>
            <a href="modules/kategori/index.php" target="_parent"> <i class="fas fa-list-alt"></i> Kelola Kategori</a>
            <a href="modules/users/index.php" target="_parent"> <i class="fas fa-external-link-alt"></i> Kelola Pengguna</a>
            <a href="modules/orders/index.php" target="_parent"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="modules/order_kostum/index.php" target="_parent"> <i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="modules/kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="#"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
            <a href="../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a>
        </div>
        <div class="logout-link">
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Dashboard Admin</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                    (<?php echo sanitize_output($_SESSION['admin_role'] ?? ''); ?>)
                </span>
            </div>
        </nav>

        <!-- Ringkasan Statistik -->
        <div class="row mb-4 g-4">
            <div class="col-xl-3 col-md-6">
                <div class="stat-card users">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Total Pengguna</h5>
                            <p class="card-text"><?php echo $total_users; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="card-footer"><small>Pelanggan: <?php echo $customer_users; ?>, Admin: <?php echo $admin_users; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card products">
                     <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Total Produk</h5>
                            <p class="card-text"><?php echo $total_products; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-box-open"></i></div>
                    </div>
                     <div class="card-footer"><small>Tersedia: <?php echo $available_products; ?>, Habis/Nonaktif: <?php echo $outofstock_products; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card orders">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Pesanan Reguler</h5>
                            <p class="card-text"><?php echo $total_orders; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-shopping-cart"></i></div>
                    </div>
                    <div class="card-footer"><small>Pending Bayar: <?php echo $pending_payment_orders; ?>, Diproses: <?php echo $processing_orders; ?></small></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="stat-card messages">
                    <div class="card-body">
                        <div class="stat-content">
                            <h5 class="card-title">Pesan Belum Dibaca</h5>
                            <p class="card-text"><?php echo $unread_messages; ?></p>
                        </div>
                        <div class="card-icon"><i class="fas fa-envelope"></i></div>
                    </div>
                    <div class="card-footer"><small>Total Pesan: <?php echo $total_messages; ?></small></div>
                </div>
            </div>
        </div>

        <!-- Daftar Data Penting -->
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-exclamation-circle text-danger me-2"></i>Produk Stok Rendah (< <?php echo $low_stock_limit; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($low_stock_products)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>Nama Produk</th><th>Stok</th><th>Harga</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($low_stock_products as $p): ?>
                                    <tr>
                                        <td><?php echo sanitize_output($p['name']); ?></td>
                                        <td><span class="badge bg-danger"><?php echo $p['stok']; ?></span></td>
                                        <td>Rp <?php echo number_format($p['harga'], 0, ',', '.'); ?></td>
                                        <td><a href="modules/produk/edit_produk.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit me-1"></i>Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">🎉 Tidak ada produk dengan stok rendah saat ini.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-tools text-info me-2"></i>Permintaan Pesanan Kostum Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_custom_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead><tr><th>ID</th><th>Pelanggan</th><th>Budget (Est.)</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                <?php foreach ($recent_custom_orders as $co): ?>
                                    <tr>
                                        <td>#K<?php echo $co['kostum_request_id']; ?></td>
                                        <td><?php echo sanitize_output($co['customer_name'] ?? 'N/A'); ?></td>
                                        <td>Rp <?php echo $co['budget_estimasi'] ? number_format($co['budget_estimasi'], 0, ',', '.') : '-'; ?></td>
                                        <td><span class="badge bg-secondary"><?php echo ucfirst(str_replace('_', ' ', $co['status_request'])); ?></span></td>
                                        <td><a href="modules/order_kostum/index.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-comments me-1"></i>Diskusi</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">✨ Tidak ada permintaan pesanan kostum baru.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="data-table-card">
                    <div class="card-header">
                        <h5><i class="fas fa-user-plus text-success me-2"></i>Pengguna Terbaru</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_users)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Bergabung</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent_users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['user_id']; ?></td>
                                        <td><?php echo sanitize_output($u['username']); ?></td>
                                        <td><?php echo sanitize_output($u['nama_lengkap'] ?? 'N/A'); ?></td>
                                        <td><?php echo sanitize_output($u['email']); ?></td>
                                        <td><span class="badge" style="background-color: <?php echo ($u['role'] === 'admin' || $u['role'] === 'superadmin') ? 'var(--accent-color)' : '#6c757d'; ?>; color: white;"><?php echo ucfirst(sanitize_output($u['role'])); ?></span></td>
                                        <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                                        <td><a href="modules/users/index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-user-edit me-1"></i>Edit</a></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="p-3 text-muted fst-italic">Belum ada pengguna terdaftar.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div> <!-- .main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
