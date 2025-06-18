<<<<<<< HEAD
<?php
session_start();
require_once 'db_connect.php'; 
$conn = connect_db();

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login untuk melihat riwayat pesanan.";
    $_SESSION['redirect_url_after_login'] = basename($_SERVER['PHP_SELF']);
    redirect('login.php');
}

$user_info = $_SESSION['user'];
$user_id = (int)$user_info['user_id'];

// --- Logika untuk Header Navigasi & Modal Kontak ---
$cart_item_count = 0;
$stmt_cart_count_hd = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
if ($stmt_cart_count_hd) {
    $stmt_cart_count_hd->bind_param("i", $user_id);
    $stmt_cart_count_hd->execute();
    $result_cart_count_hd = $stmt_cart_count_hd->get_result();
    if ($row_count_hd = $result_cart_count_hd->fetch_assoc()) {
        $cart_item_count = (int)($row_count_hd['total_items'] ?? 0);
    }
    $stmt_cart_count_hd->close();
}

// Menyiapkan variabel untuk modal kontak
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $user_info['username'] ?? '';
$current_fullname = $user_info['nama_lengkap'] ?? '';
$current_email = $user_info['email'] ?? '';

// --- PERBAIKAN UTAMA DI SINI: Menggunakan LEFT JOIN agar pesanan tetap muncul ---
$sql_orders = "SELECT 
                    o.order_id, o.tanggal_order, o.total, o.status,
                    od.kuantitas, od.price,
                    p.name as product_name, p.foto_produk
               FROM orders o
               LEFT JOIN orderdetails od ON o.order_id = od.order_id
               LEFT JOIN produk p ON od.product_id = p.product_id
               WHERE o.user_id = ? 
               ORDER BY o.tanggal_order DESC, o.order_id DESC";

$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$regular_orders_with_details = [];
while ($row = $result_orders->fetch_assoc()) {
    $order_id = $row['order_id'];
    if (!isset($regular_orders_with_details[$order_id])) {
        $regular_orders_with_details[$order_id] = [
            'tanggal_order' => $row['tanggal_order'],
            'total' => $row['total'],
            'status' => $row['status'],
            'items' => []
        ];
    }
    // Hanya tambahkan item jika detailnya ada (kuantitas tidak null karena LEFT JOIN)
    if ($row['kuantitas'] !== null) {
        $regular_orders_with_details[$order_id]['items'][] = [
            'product_name' => $row['product_name'], // Bisa jadi NULL jika produk dihapus
            'kuantitas' => $row['kuantitas'],
            'price' => $row['price'],
            'foto_produk' => $row['foto_produk'] // Bisa jadi NULL jika produk dihapus
        ];
    }
}
$stmt_orders->close();

// Ambil data pesanan kostum (sudah benar)
$sql_custom = "SELECT kostum_request_id, tanggal_request, status_request, deskripsi_request, total_harga FROM orderkostum WHERE user_id = ? ORDER BY tanggal_request DESC";
$stmt_custom = $conn->prepare($sql_custom);
$stmt_custom->bind_param("i", $user_id);
$stmt_custom->execute();
$result_custom = $stmt_custom->get_result();
$custom_orders = [];
while ($row = $result_custom->fetch_assoc()) {
    $custom_orders[] = $row;
}

// masuk keluar
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

$stmt_custom->close();
$conn->close();

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
    <title>Galeri Produk - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        .order-detail-row { background-color: #fdfdfc; }
        .order-detail-row .list-group-item { border: none; background-color: transparent; }
        .table > :not(caption) > * > * { box-shadow: none; }
        .btn-toggle-details { border-radius: 50px; font-size: 0.8rem; }
    </style>
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <?php include 'includes/header.php'; ?>

        <section class="page-section py-5">
            <div class="container" style="max-width: 950px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2.5rem, 8vw, 5rem); line-height:1.1;">My Account</h1>
                    <p class="lead" style="color: var(--text-medium);">
                        Hello, <strong><?php echo sanitize_output($user_info['nama_lengkap'] ?? $user_info['username']); ?></strong>!
                    </p>
                </div>

                <?php if (isset($_SESSION['info_message'])): ?>
                    <div class="alert alert-info text-center" role="alert"><?php echo sanitize_output($_SESSION['info_message']); unset($_SESSION['info_message']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success text-center" role="alert"><?php echo sanitize_output($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <ul class="nav nav-tabs justify-content-center mb-4" id="myOrderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="regular-orders-tab" data-bs-toggle="tab" data-bs-target="#regular-orders-pane" type="button" role="tab">Regular Order</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="custom-orders-tab" data-bs-toggle="tab" data-bs-target="#custom-orders-pane" type="button" role="tab">Custom Order</button>
                    </li>
                </ul>

                <div class="tab-content" id="myOrderTabsContent">
                    <div class="tab-pane fade show active" id="regular-orders-pane" role="tabpanel">
                        <div class="card shadow-sm" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($regular_orders_with_details) > 0): ?>
                                                <?php foreach ($regular_orders_with_details as $order_id => $order): ?>
                                                <tr class="align-middle">
                                                    <td><strong>#<?php echo $order_id; ?></strong></td>
                                                    <td><?php echo date('d M Y', strtotime($order['tanggal_order'])); ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                                    <td class="text-center">
                                                        <?php
                                                        $status_text = ucfirst(str_replace('_', ' ', $order['status']));
                                                        $badge_class = 'bg-secondary';
                                                        if ($order['status'] === 'menunggu_pembayaran') $badge_class = 'bg-warning text-dark';
                                                        elseif ($order['status'] === 'menunggu_verifikasi') $badge_class = 'bg-primary';
                                                        elseif ($order['status'] === 'diproses') $badge_class = 'bg-info text-dark';
                                                        elseif ($order['status'] === 'dikirim') $badge_class = 'bg-primary';
                                                        elseif ($order['status'] === 'selesai') $badge_class = 'bg-success';
                                                        elseif ($order['status'] === 'dibatalkan') $badge_class = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo sanitize_output($status_text); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($order['status'] === 'menunggu_pembayaran'): ?>
                                                            <a href="pembayaran.php?order_id=<?php echo $order_id; ?>" class="btn btn-sm btn-success">Proceed</a>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-outline-secondary btn-toggle-details" type="button" data-bs-toggle="collapse" data-bs-target="#order-detail-<?php echo $order_id; ?>" aria-expanded="false">
                                                            Detail's <i class="fas fa-chevron-down ms-1"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="order-detail-row">
                                                    <td colspan="5" class="p-0" style="border:none;">
                                                        <div class="collapse" id="order-detail-<?php echo $order_id; ?>">
                                                            <div class="p-3">
                                                                <h6 class="mb-3">Ordered Item #<?php echo $order_id; ?>:</h6>
                                                                <ul class="list-group list-group-flush">
                                                                <?php if (!empty($order['items'])): ?>
                                                                    <?php foreach ($order['items'] as $item): ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?php echo sanitize_output(!empty($item['foto_produk']) ? $item['foto_produk'] : 'https://placehold.co/50x50/EAE0DA/7D6E63?text=N/A'); ?>" 
                                                                                 alt="<?php echo sanitize_output($item['product_name'] ?? 'Produk Dihapus'); ?>" width="50" height="50" class="me-3 rounded" style="object-fit: cover;">
                                                                            <div>
                                                                                <div><?php echo sanitize_output($item['product_name'] ?? 'Produk Telah Dihapus'); ?></div>
                                                                                <small class="text-muted"><?php echo $item['kuantitas']; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></small>
                                                                            </div>
                                                                        </div>
                                                                        <span class="text-muted">Rp <?php echo number_format($item['price'] * $item['kuantitas'], 0, ',', '.'); ?></span>
                                                                    </li>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <li class="list-group-item text-muted fst-italic">Item's detailed not available</li>
                                                                <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted fst-italic">You don't have any regular order history yet.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="custom-orders-pane" role="tabpanel">
                         <div class="card shadow-sm" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr><th>Request ID</th><th>Date</th><th>Total</th><th class="text-center">Status</th><th class="text-center">Action</th></tr>
                                        </thead>
                                        <tbody>
                                             <?php if (count($custom_orders) > 0): ?>
                                                <?php foreach ($custom_orders as $order): ?>
                                                <tr>
                                                    <td><strong>#K<?php echo $order['kostum_request_id']; ?></strong></td>
                                                    <td><?php echo date('d M Y', strtotime($order['tanggal_request'])); ?></td>
                                                    <td><?php echo ($order['total_harga'] > 0) ? 'Rp ' . number_format($order['total_harga'], 0, ',', '.') : '<em class="text-muted">Menunggu harga</em>'; ?></td>
                                                    <td class="text-center">
                                                        <?php
                                                        $status_text_custom = ucfirst(str_replace('_', ' ', $order['status_request']));
                                                        $badge_class_custom = 'bg-secondary';
                                                        if (in_array($order['status_request'], [ 'diskusi'])) $badge_class_custom = 'bg-warning text-dark';
                                                        elseif ($order['status_request'] === 'menunggu_pembayaran') $badge_class_custom = 'bg-info text-dark';
                                                        elseif (in_array($order['status_request'], ['menunggu_verifikasi'])) $badge_class_custom = 'bg-primary';
                                                        elseif (in_array($order['status_request'], ['ditolak', 'dibatalkan'])) $badge_class_custom = 'bg-danger';
                                                        elseif (in_array($order['status_request'], ['diproses', 'dikirim', 'selesai'])) $badge_class_custom = 'bg-success';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class_custom; ?>"><?php echo sanitize_output($status_text_custom); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($order['status_request'] === 'menunggu_pembayaran'): ?>
                                                            <a href="pembayaran.php?request_id=<?php echo $order['kostum_request_id']; ?>" class="btn btn-sm btn-success">Proceed</a>
                                                        <?php endif; ?>
                                                        <a href="diskusi_kostum.php?request_id=<?php echo $order['kostum_request_id']; ?>" class="btn btn-sm btn-outline-primary">Discussion</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted fst-italic"></td>You don't have any custom order history yet.</tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="gallery_page.php" class="btn btn-submit">Start Shopping</a>
                </div>
            </div>
        </section>
        
        <footer class="actual-footer py-3">
             <div class="container text-center"><p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p></div>
        </footer>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                        <input type="hidden" name="source_page" value="modal_contact_ajax">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_nama_pengirim" name="nama_pengirim" required
                                       value="<?php echo sanitize_output($current_fullname ?: $current_username); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email Anda <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="modal_email_pengirim" name="email_pengirim" required
                                       value="<?php echo sanitize_output($current_email); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan" class="form-label">Subjek</label>
                            <input type="text" class="form-control" id="modal_subjek_pesan" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="modal_isi_pesan" name="isi_pesan" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-submit">Kirim Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchIconToggle = document.getElementById('searchIconToggle');
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            let searchVisible = false;

            if(searchIconToggle && searchInput && searchForm){
                searchIconToggle.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!searchVisible) {
                        searchInput.style.display = 'inline-block';
                        setTimeout(() => { searchInput.style.width = '200px'; }, 0);
                        searchInput.focus();
                        searchVisible = true;
                    } else {
                        if (searchInput.value.trim() !== '') {
                            searchForm.submit();
                        } else {
                            searchInput.style.width = '0';
                            setTimeout(() => { searchInput.style.display = 'none'; }, 300);
                            searchVisible = false;
                        }
                    }
                });
            }

            const contactModalEl = document.getElementById('contactModal');
            if (contactModalEl) {
                const contactModalForm = document.getElementById('contactModalForm');
                const contactModalAlerts = document.getElementById('contactModalAlerts');

                if (contactModalForm) {
                    contactModalForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        contactModalAlerts.innerHTML = '';

                        const formData = new FormData(contactModalForm);
                        const submitButton = contactModalForm.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';

                        fetch('proses_kontak.php', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                contactModalAlerts.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                                contactModalForm.reset();
                                <?php if ($is_any_user_logged_in): ?>
                                const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname ?: $current_username)); ?>";
                                const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email)); ?>";
                                if(document.getElementById('modal_nama_pengirim')) document.getElementById('modal_nama_pengirim').value = defaultNameModal;
                                if(document.getElementById('modal_email_pengirim')) document.getElementById('modal_email_pengirim').value = defaultEmailModal;
                                <?php endif; ?>
                                setTimeout(() => {
                                    const modalInstance = bootstrap.Modal.getInstance(contactModalEl);
                                    if (modalInstance) modalInstance.hide();
                                    contactModalAlerts.innerHTML = '';
                                }, 3000);
                            } else {
                                let errorMessage = data.message || 'Terjadi kesalahan.';
                                contactModalAlerts.innerHTML = `<div class="alert alert-danger">${errorMessage}</div>`;
                            }
                        })
                        .catch(error => {
                            contactModalAlerts.innerHTML = `<div class="alert alert-danger">Tidak dapat terhubung ke server.</div>`;
                            console.error('Error:', error);
                        })
                        .finally(() => {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonText;
                        });
                    });
                }
            }
        });
    </script>
</body>
=======
<?php
session_start();
require_once 'db_connect.php'; 
$conn = connect_db();

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login untuk melihat riwayat pesanan.";
    $_SESSION['redirect_url_after_login'] = basename($_SERVER['PHP_SELF']);
    redirect('login.php');
}

$user_info = $_SESSION['user'];
$user_id = (int)$user_info['user_id'];

// --- Logika untuk Header Navigasi & Modal Kontak ---
$cart_item_count = 0;
$stmt_cart_count_hd = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
if ($stmt_cart_count_hd) {
    $stmt_cart_count_hd->bind_param("i", $user_id);
    $stmt_cart_count_hd->execute();
    $result_cart_count_hd = $stmt_cart_count_hd->get_result();
    if ($row_count_hd = $result_cart_count_hd->fetch_assoc()) {
        $cart_item_count = (int)($row_count_hd['total_items'] ?? 0);
    }
    $stmt_cart_count_hd->close();
}

// Menyiapkan variabel untuk modal kontak
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $user_info['username'] ?? '';
$current_fullname = $user_info['nama_lengkap'] ?? '';
$current_email = $user_info['email'] ?? '';

// --- PERBAIKAN UTAMA DI SINI: Menggunakan LEFT JOIN agar pesanan tetap muncul ---
$sql_orders = "SELECT 
                    o.order_id, o.tanggal_order, o.total, o.status,
                    od.kuantitas, od.price,
                    p.name as product_name, p.foto_produk
               FROM orders o
               LEFT JOIN orderdetails od ON o.order_id = od.order_id
               LEFT JOIN produk p ON od.product_id = p.product_id
               WHERE o.user_id = ? 
               ORDER BY o.tanggal_order DESC, o.order_id DESC";

$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();

$regular_orders_with_details = [];
while ($row = $result_orders->fetch_assoc()) {
    $order_id = $row['order_id'];
    if (!isset($regular_orders_with_details[$order_id])) {
        $regular_orders_with_details[$order_id] = [
            'tanggal_order' => $row['tanggal_order'],
            'total' => $row['total'],
            'status' => $row['status'],
            'items' => []
        ];
    }
    // Hanya tambahkan item jika detailnya ada (kuantitas tidak null karena LEFT JOIN)
    if ($row['kuantitas'] !== null) {
        $regular_orders_with_details[$order_id]['items'][] = [
            'product_name' => $row['product_name'], // Bisa jadi NULL jika produk dihapus
            'kuantitas' => $row['kuantitas'],
            'price' => $row['price'],
            'foto_produk' => $row['foto_produk'] // Bisa jadi NULL jika produk dihapus
        ];
    }
}
$stmt_orders->close();

// Ambil data pesanan kostum (sudah benar)
$sql_custom = "SELECT kostum_request_id, tanggal_request, status_request, deskripsi_request, total_harga FROM orderkostum WHERE user_id = ? ORDER BY tanggal_request DESC";
$stmt_custom = $conn->prepare($sql_custom);
$stmt_custom->bind_param("i", $user_id);
$stmt_custom->execute();
$result_custom = $stmt_custom->get_result();
$custom_orders = [];
while ($row = $result_custom->fetch_assoc()) {
    $custom_orders[] = $row;
}

// masuk keluar
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

$stmt_custom->close();
$conn->close();

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
    <title>Galeri Produk - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css"> 
    <style>
        .order-detail-row { background-color: #fdfdfc; }
        .order-detail-row .list-group-item { border: none; background-color: transparent; }
        .table > :not(caption) > * > * { box-shadow: none; }
        .btn-toggle-details { border-radius: 50px; font-size: 0.8rem; }
    </style>
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <?php include 'includes/header.php'; ?>

        <section class="page-section py-5">
            <div class="container" style="max-width: 950px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2.5rem, 8vw, 5rem); line-height:1.1;">My Account</h1>
                    <p class="lead" style="color: var(--text-medium);">
                        Hello, <strong><?php echo sanitize_output($user_info['nama_lengkap'] ?? $user_info['username']); ?></strong>!
                    </p>
                </div>

                <?php if (isset($_SESSION['info_message'])): ?>
                    <div class="alert alert-info text-center" role="alert"><?php echo sanitize_output($_SESSION['info_message']); unset($_SESSION['info_message']); ?></div>
                <?php endif; ?>
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success text-center" role="alert"><?php echo sanitize_output($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <ul class="nav nav-tabs justify-content-center mb-4" id="myOrderTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="regular-orders-tab" data-bs-toggle="tab" data-bs-target="#regular-orders-pane" type="button" role="tab">Regular Order</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="custom-orders-tab" data-bs-toggle="tab" data-bs-target="#custom-orders-pane" type="button" role="tab">Custom Order</button>
                    </li>
                </ul>

                <div class="tab-content" id="myOrderTabsContent">
                    <div class="tab-pane fade show active" id="regular-orders-pane" role="tabpanel">
                        <div class="card shadow-sm" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Date</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($regular_orders_with_details) > 0): ?>
                                                <?php foreach ($regular_orders_with_details as $order_id => $order): ?>
                                                <tr class="align-middle">
                                                    <td><strong>#<?php echo $order_id; ?></strong></td>
                                                    <td><?php echo date('d M Y', strtotime($order['tanggal_order'])); ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($order['total'], 0, ',', '.'); ?></td>
                                                    <td class="text-center">
                                                        <?php
                                                        $status_text = ucfirst(str_replace('_', ' ', $order['status']));
                                                        $badge_class = 'bg-secondary';
                                                        if ($order['status'] === 'menunggu_pembayaran') $badge_class = 'bg-warning text-dark';
                                                        elseif ($order['status'] === 'menunggu_verifikasi') $badge_class = 'bg-primary';
                                                        elseif ($order['status'] === 'diproses') $badge_class = 'bg-info text-dark';
                                                        elseif ($order['status'] === 'dikirim') $badge_class = 'bg-primary';
                                                        elseif ($order['status'] === 'selesai') $badge_class = 'bg-success';
                                                        elseif ($order['status'] === 'dibatalkan') $badge_class = 'bg-danger';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class; ?>"><?php echo sanitize_output($status_text); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($order['status'] === 'menunggu_pembayaran'): ?>
                                                            <a href="pembayaran.php?order_id=<?php echo $order_id; ?>" class="btn btn-sm btn-success">Proceed</a>
                                                        <?php endif; ?>
                                                        <button class="btn btn-sm btn-outline-secondary btn-toggle-details" type="button" data-bs-toggle="collapse" data-bs-target="#order-detail-<?php echo $order_id; ?>" aria-expanded="false">
                                                            Detail's <i class="fas fa-chevron-down ms-1"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <tr class="order-detail-row">
                                                    <td colspan="5" class="p-0" style="border:none;">
                                                        <div class="collapse" id="order-detail-<?php echo $order_id; ?>">
                                                            <div class="p-3">
                                                                <h6 class="mb-3">Ordered Item #<?php echo $order_id; ?>:</h6>
                                                                <ul class="list-group list-group-flush">
                                                                <?php if (!empty($order['items'])): ?>
                                                                    <?php foreach ($order['items'] as $item): ?>
                                                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                        <div class="d-flex align-items-center">
                                                                            <img src="<?php echo sanitize_output(!empty($item['foto_produk']) ? $item['foto_produk'] : 'https://placehold.co/50x50/EAE0DA/7D6E63?text=N/A'); ?>" 
                                                                                 alt="<?php echo sanitize_output($item['product_name'] ?? 'Produk Dihapus'); ?>" width="50" height="50" class="me-3 rounded" style="object-fit: cover;">
                                                                            <div>
                                                                                <div><?php echo sanitize_output($item['product_name'] ?? 'Produk Telah Dihapus'); ?></div>
                                                                                <small class="text-muted"><?php echo $item['kuantitas']; ?> x Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></small>
                                                                            </div>
                                                                        </div>
                                                                        <span class="text-muted">Rp <?php echo number_format($item['price'] * $item['kuantitas'], 0, ',', '.'); ?></span>
                                                                    </li>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <li class="list-group-item text-muted fst-italic">Item's detailed not available</li>
                                                                <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted fst-italic">You don't have any regular order history yet.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="custom-orders-pane" role="tabpanel">
                         <div class="card shadow-sm" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead>
                                            <tr><th>Request ID</th><th>Date</th><th>Total</th><th class="text-center">Status</th><th class="text-center">Action</th></tr>
                                        </thead>
                                        <tbody>
                                             <?php if (count($custom_orders) > 0): ?>
                                                <?php foreach ($custom_orders as $order): ?>
                                                <tr>
                                                    <td><strong>#K<?php echo $order['kostum_request_id']; ?></strong></td>
                                                    <td><?php echo date('d M Y', strtotime($order['tanggal_request'])); ?></td>
                                                    <td><?php echo ($order['total_harga'] > 0) ? 'Rp ' . number_format($order['total_harga'], 0, ',', '.') : '<em class="text-muted">Menunggu harga</em>'; ?></td>
                                                    <td class="text-center">
                                                        <?php
                                                        $status_text_custom = ucfirst(str_replace('_', ' ', $order['status_request']));
                                                        $badge_class_custom = 'bg-secondary';
                                                        if (in_array($order['status_request'], [ 'diskusi'])) $badge_class_custom = 'bg-warning text-dark';
                                                        elseif ($order['status_request'] === 'menunggu_pembayaran') $badge_class_custom = 'bg-info text-dark';
                                                        elseif (in_array($order['status_request'], ['menunggu_verifikasi'])) $badge_class_custom = 'bg-primary';
                                                        elseif (in_array($order['status_request'], ['ditolak', 'dibatalkan'])) $badge_class_custom = 'bg-danger';
                                                        elseif (in_array($order['status_request'], ['diproses', 'dikirim', 'selesai'])) $badge_class_custom = 'bg-success';
                                                        ?>
                                                        <span class="badge <?php echo $badge_class_custom; ?>"><?php echo sanitize_output($status_text_custom); ?></span>
                                                    </td>
                                                    <td class="text-center">
                                                        <?php if ($order['status_request'] === 'menunggu_pembayaran'): ?>
                                                            <a href="pembayaran.php?request_id=<?php echo $order['kostum_request_id']; ?>" class="btn btn-sm btn-success">Proceed</a>
                                                        <?php endif; ?>
                                                        <a href="diskusi_kostum.php?request_id=<?php echo $order['kostum_request_id']; ?>" class="btn btn-sm btn-outline-primary">Discussion</a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center py-4 text-muted fst-italic"></td>You don't have any custom order history yet.</tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="gallery_page.php" class="btn btn-submit">Start Shopping</a>
                </div>
            </div>
        </section>
        
        <footer class="actual-footer py-3">
             <div class="container text-center"><p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p></div>
        </footer>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                        <input type="hidden" name="source_page" value="modal_contact_ajax">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_nama_pengirim" name="nama_pengirim" required
                                       value="<?php echo sanitize_output($current_fullname ?: $current_username); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email Anda <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="modal_email_pengirim" name="email_pengirim" required
                                       value="<?php echo sanitize_output($current_email); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan" class="form-label">Subjek</label>
                            <input type="text" class="form-control" id="modal_subjek_pesan" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="modal_isi_pesan" name="isi_pesan" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-submit">Kirim Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchIconToggle = document.getElementById('searchIconToggle');
            const searchInput = document.getElementById('searchInput');
            const searchForm = document.getElementById('searchForm');
            let searchVisible = false;

            if(searchIconToggle && searchInput && searchForm){
                searchIconToggle.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!searchVisible) {
                        searchInput.style.display = 'inline-block';
                        setTimeout(() => { searchInput.style.width = '200px'; }, 0);
                        searchInput.focus();
                        searchVisible = true;
                    } else {
                        if (searchInput.value.trim() !== '') {
                            searchForm.submit();
                        } else {
                            searchInput.style.width = '0';
                            setTimeout(() => { searchInput.style.display = 'none'; }, 300);
                            searchVisible = false;
                        }
                    }
                });
            }

            const contactModalEl = document.getElementById('contactModal');
            if (contactModalEl) {
                const contactModalForm = document.getElementById('contactModalForm');
                const contactModalAlerts = document.getElementById('contactModalAlerts');

                if (contactModalForm) {
                    contactModalForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        contactModalAlerts.innerHTML = '';

                        const formData = new FormData(contactModalForm);
                        const submitButton = contactModalForm.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';

                        fetch('proses_kontak.php', {
                            method: 'POST',
                            body: formData,
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                contactModalAlerts.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                                contactModalForm.reset();
                                <?php if ($is_any_user_logged_in): ?>
                                const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname ?: $current_username)); ?>";
                                const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email)); ?>";
                                if(document.getElementById('modal_nama_pengirim')) document.getElementById('modal_nama_pengirim').value = defaultNameModal;
                                if(document.getElementById('modal_email_pengirim')) document.getElementById('modal_email_pengirim').value = defaultEmailModal;
                                <?php endif; ?>
                                setTimeout(() => {
                                    const modalInstance = bootstrap.Modal.getInstance(contactModalEl);
                                    if (modalInstance) modalInstance.hide();
                                    contactModalAlerts.innerHTML = '';
                                }, 3000);
                            } else {
                                let errorMessage = data.message || 'Terjadi kesalahan.';
                                contactModalAlerts.innerHTML = `<div class="alert alert-danger">${errorMessage}</div>`;
                            }
                        })
                        .catch(error => {
                            contactModalAlerts.innerHTML = `<div class="alert alert-danger">Tidak dapat terhubung ke server.</div>`;
                            console.error('Error:', error);
                        })
                        .finally(() => {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalButtonText;
                        });
                    });
                }
            }
        });
    </script>
</body>
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
</html>