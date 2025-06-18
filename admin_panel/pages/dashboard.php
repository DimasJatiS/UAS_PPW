<?php
// uas/admin_panel/pages/dashboard.php

// Pastikan $pdo sudah tersedia dari admin_panel/config.php
if (!isset($pdo)) { // Baris ini bisa diaktifkan kembali jika perlu
    die('Koneksi database tidak tersedia di dashboard.php.');
}

// Global variables from admin_panel/index.php
global $admin_username, $admin_role;

// --- Ambil Data untuk Ringkasan Dashboard ---

// 1. Total Produk
$total_products = 0;
try {
    // Nama tabel 'Produk' di query Anda sudah sesuai dengan skema 'produk' (MySQL case-insensitive by default on Windows, but good to match case)
    $stmt = $pdo->query("SELECT COUNT(*) FROM produk"); // Skema: produk
    $total_products = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total products: " . $e->getMessage());
}

// 2. Total Pesanan
$total_orders = 0;
try {
    // Nama tabel 'Orders' di query sudah sesuai dengan skema 'orders'
    $stmt = $pdo->query("SELECT COUNT(*) FROM orders"); // Skema: orders
    $total_orders = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total orders: " . $e->getMessage());
}

// 3. Total Pengguna (Customer + Admin/Superadmin)
$total_users = 0;
try {
    // Nama tabel 'Users' di query sudah sesuai dengan skema 'users'
    $stmt = $pdo->query("SELECT COUNT(*) FROM users"); // Skema: users
    $total_users = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total users: " . $e->getMessage());
}

// 4. Pesanan Terbaru
$latest_orders = [];
try {
    // Penyesuaian nama kolom:
    // o.total_price -> o.total
    // o.order_status -> o.status
    // o.created_at -> o.tanggal_order (atau biarkan created_at jika ada di tabel Orders, skema hanya menunjukkan tanggal_order)
    // Untuk konsistensi, kita asumsikan Anda ingin menggunakan tanggal_order dari skema.
    // Jika tabel 'orders' Anda memiliki kolom 'created_at' yang berbeda, sesuaikan.
    $stmt = $pdo->query("SELECT o.order_id, u.username, o.total AS total_price, o.status AS order_status, o.tanggal_order AS created_at
                        FROM orders o JOIN users u ON o.user_id = u.user_id
                        ORDER BY o.tanggal_order DESC LIMIT 5"); // Skema: orders, users. Kolom: total, status, tanggal_order
    $latest_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest orders: " . $e->getMessage());
}

// 5. Produk dengan Stok Rendah
$low_stock_products = [];
try {
    // Query sudah sesuai: tabel 'Produk' (skema 'produk'), kolom 'stok'
    $stmt = $pdo->query("SELECT product_id, name, stok FROM produk WHERE stok < 10 ORDER BY stok ASC LIMIT 5"); // Skema: produk
    $low_stock_products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching low stock products: " . $e->getMessage());
}

// 6. Review Terbaru
$latest_reviews = [];
try {
    // Penyesuaian nama tabel: 'Review' -> 'reviews'
    // Penyesuaian nama kolom: r.created_at -> r.review_date
    $stmt = $pdo->query("SELECT r.review_id, u.username, p.name AS product_name, r.rating, r.comment, r.review_date AS created_at
                        FROM reviews r JOIN users u ON r.user_id = u.user_id JOIN produk p ON r.product_id = p.product_id
                        ORDER BY r.review_date DESC LIMIT 3"); // Skema: reviews, users, produk. Kolom: review_date
    $latest_reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest reviews: " . $e->getMessage());
}

// 7. Pesanan Kustom Terbaru (dari tabel contact_messages)
$latest_custom_orders = [];
try {
    // Query menggunakan 'contacts', skema menunjukkan 'contact_messages'.
    // Kolom 'created_at' di query, skema 'tanggal_kirim'.
    // Kolom 'contact_id' di HTML, skema 'message_id'.
    // Kita akan alias agar sesuai dengan penggunaan di HTML.
    $stmt = $pdo->query("SELECT message_id AS contact_id, nama_pengirim, email_pengirim, subjek_pesan, isi_pesan, tanggal_kirim AS created_at
                        FROM contact_messages ORDER BY tanggal_kirim DESC LIMIT 3"); // Skema: contact_messages. Kolom: message_id, tanggal_kirim
    $latest_custom_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest custom orders from contact_messages: " . $e->getMessage());
    $latest_custom_orders = [];
}
?>

<div class="content-block mt-4">
    <h3>Pesanan Kustom / Kontak Terbaru</h3>
    <?php if (empty($latest_custom_orders)): ?>
        <p>Belum ada pesan kustom atau kontak terbaru.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th> <th>Nama Pengirim</th>
                    <th>Email</th>
                    <th>Subjek</th>
                    <th>Pesan</th>
                    <th>Tanggal</th> <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_custom_orders as $message): ?>
                <tr>
                    <td><?php echo htmlspecialchars($message['contact_id']); ?></td>
                    <td><?php echo htmlspecialchars($message['nama_pengirim']); ?></td>
                    <td><?php echo htmlspecialchars($message['email_pengirim']); ?></td>
                    <td><?php echo htmlspecialchars($message['subjek_pesan'] ?: '-'); ?></td>
                    <td><?php echo htmlspecialchars(mb_strimwidth($message['isi_pesan'], 0, 50, "...")); ?></td>
                    <td><?php echo date('d M Y, H:i', strtotime($message['created_at'])); ?></td>
                    <td><a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewMessageModal" data-message='<?php echo json_encode($message); ?>'>Lihat</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>