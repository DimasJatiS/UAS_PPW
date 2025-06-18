<?php
// uas/admin_panel/pages/dashboard.php

// Pastikan $pdo sudah tersedia dari admin_panel/config.php
// if (!isset($pdo)) {
//     die('Koneksi database tidak tersedia.');
// }

// Global variables from admin_panel/index.php
global $admin_username, $admin_role;

// --- Ambil Data untuk Ringkasan Dashboard ---

// 1. Total Produk
$total_products = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Produk");
    $total_products = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total products: " . $e->getMessage());
}

// 2. Total Pesanan
$total_orders = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Orders");
    $total_orders = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total orders: " . $e->getMessage());
}

// 3. Total Pengguna (Customer + Admin/Superadmin)
$total_users = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM Users");
    $total_users = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Error fetching total users: " . $e->getMessage());
}

// 4. Pesanan Terbaru
$latest_orders = [];
try {
    $stmt = $pdo->query("SELECT o.order_id, u.username, o.total_price, o.order_status, o.created_at
                        FROM Orders o JOIN Users u ON o.user_id = u.user_id
                        ORDER BY o.created_at DESC LIMIT 5");
    $latest_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest orders: " . $e->getMessage());
}

// 5. Produk dengan Stok Rendah
$low_stock_products = [];
try {
    $stmt = $pdo->query("SELECT product_id, name, stok FROM Produk WHERE stok < 10 ORDER BY stok ASC LIMIT 5"); // Batas stok 10
    $low_stock_products = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching low stock products: " . $e->getMessage());
}

// 6. Review Terbaru (jika ada tabel Review)
$latest_reviews = [];
try {
    $stmt = $pdo->query("SELECT r.review_id, u.username, p.name AS product_name, r.rating, r.comment, r.created_at
                        FROM Review r JOIN Users u ON r.user_id = u.user_id JOIN Produk p ON r.product_id = p.product_id
                        ORDER BY r.created_at DESC LIMIT 3");
    $latest_reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest reviews: " . $e->getMessage());
}

// 7. Pesanan Kustom Terbaru (jika ada tabel CustomOrders, diasumsikan dari form kontak atau kostum_order.php)
$latest_custom_orders = [];
try {
    // Asumsi tabel CustomOrders memiliki kolom: custom_order_id, user_id (opsional),
    // customer_name, customer_email, details, status, created_at
    // Jika tidak ada tabel CustomOrders, Anda bisa menampilkan dari tabel contacts jika pesan kustom disimpan di sana
    $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC LIMIT 3"); // Menggunakan tabel 'contacts' dari proses_kontak.php
    $latest_custom_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching latest custom orders from contacts: " . $e->getMessage());
    $latest_custom_orders = [];
}
?>

<div class="content-block">
    <h3>Halo, <?php echo htmlspecialchars($admin_username); ?>!</h3>
    <p>Selamat datang di dashboard admin Bloomarie. Anda login sebagai: <span class="badge badge-superadmin"><?php echo htmlspecialchars(ucfirst($admin_role)); ?></span></p>

    <div class="dashboard-summary row">
        <div class="col-md-4 mb-3">
            <div class="card text-center p-3">
                <div class="card-body">
                    <h4>Total Produk</h4>
                    <p class="display-4 text-primary"><?php echo $total_products; ?></p>
                    <a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=products" class="btn btn-sm btn-secondary">Lihat Produk</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center p-3">
                <div class="card-body">
                    <h4>Total Pesanan</h4>
                    <p class="display-4 text-info"><?php echo $total_orders; ?></p>
                    <a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=orders" class="btn btn-sm btn-secondary">Lihat Pesanan</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card text-center p-3">
                <div class="card-body">
                    <h4>Total Pengguna</h4>
                    <p class="display-4 text-success"><?php echo $total_users; ?></p>
                    <a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=users" class="btn btn-sm btn-secondary">Lihat Pengguna</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="content-block mt-4">
    <h3>Pesanan Terbaru</h3>
    <?php if (empty($latest_orders)): ?>
        <p>Belum ada pesanan terbaru.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID Pesanan</th>
                    <th>Pelanggan</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($latest_orders as $order): ?>
                <tr>
                    <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td><?php echo htmlspecialchars($order['username']); ?></td>
                    <td>Rp <?php echo number_format($order['total_price'], 0, ',', '.'); ?></td>
                    <td><span class="badge status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                    <td><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                    <td><a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=orders&action=view&id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-info">Detail</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="content-block mt-4">
    <h3>Produk Stok Rendah</h3>
    <?php if (empty($low_stock_products)): ?>
        <p>Semua produk memiliki stok yang cukup.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID Produk</th>
                    <th>Nama Produk</th>
                    <th>Stok</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><span class="badge bg-danger"><?php echo htmlspecialchars($product['stok']); ?></span></td>
                    <td><a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=products&action=edit&id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-warning">Edit Stok</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="content-block mt-4">
    <h3>Pesan Kustom / Kontak Terbaru</h3>
    <?php if (empty($latest_custom_orders)): ?>
        <p>Belum ada pesan kustom atau kontak terbaru.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Pengirim</th>
                    <th>Email</th>
                    <th>Subjek</th>
                    <th>Pesan</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
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

<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMessageModalLabel">Detail Pesan Kustom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Dari:</strong> <span id="modalSenderName"></span> (<span id="modalSenderEmail"></span>)</p>
                <p><strong>Subjek:</strong> <span id="modalSubject"></span></p>
                <p><strong>Pesan:</strong></p>
                <p id="modalMessageContent"></p>
                <p><small><strong>Dikirim pada:</strong> <span id="modalSentAt"></span></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewMessageModal = document.getElementById('viewMessageModal');
    if (viewMessageModal) {
        viewMessageModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var messageData = JSON.parse(button.getAttribute('data-message'));

            var modalSenderName = viewMessageModal.querySelector('#modalSenderName');
            var modalSenderEmail = viewMessageModal.querySelector('#modalSenderEmail');
            var modalSubject = viewMessageModal.querySelector('#modalSubject');
            var modalMessageContent = viewMessageModal.querySelector('#modalMessageContent');
            var modalSentAt = viewMessageModal.querySelector('#modalSentAt');

            modalSenderName.textContent = messageData.nama_pengirim;
            modalSenderEmail.textContent = messageData.email_pengirim;
            modalSubject.textContent = messageData.subjek_pesan || 'Tidak Ada Subjek';
            modalMessageContent.textContent = messageData.isi_pesan;
            modalSentAt.textContent = new Date(messageData.created_at).toLocaleString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        });
    }
});
</script>