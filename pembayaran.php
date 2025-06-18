<?php
session_start();
require_once 'db_connect.php';

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login untuk melakukan pembayaran.";
    redirect('login.php');
}

$conn = connect_db();
$user_id = (int)$_SESSION['user']['user_id'];

// --- Logika untuk Header Navigasi (Lengkap) ---
$cart_item_count_header = 0;
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $stmt_cart_count_hd = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count_hd) {
        $stmt_cart_count_hd->bind_param("i", $user_id);
        $stmt_cart_count_hd->execute();
        $result_cart_count_hd = $stmt_cart_count_hd->get_result();
        if ($row_count_hd = $result_cart_count_hd->fetch_assoc()) {
            $cart_item_count_header = (int)($row_count_hd['total_items'] ?? 0);
        }
        $stmt_cart_count_hd->close();
    }
}
$cart_page_url = ($cart_item_count_header > 0) ? 'checkout.php' : 'gallery_page.php';
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $_SESSION['user']['username'] ?? '';
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? '';
$current_email = $_SESSION['user']['email'] ?? '';


// --- Logika Inti Halaman Pembayaran ---
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
$payment_details = [];

if ($order_id > 0) {
    $stmt = $conn->prepare("SELECT order_id, total, status FROM orders WHERE order_id = ? AND user_id = ? AND status = 'menunggu_pembayaran'");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($order) {
        $payment_details = ['id_display' => '#' . $order['order_id'], 'raw_id' => $order['order_id'], 'total' => $order['total'], 'type' => 'regular'];
    }
} elseif ($request_id > 0) {
    $stmt = $conn->prepare("SELECT kostum_request_id, total_harga, status_request FROM orderkostum WHERE kostum_request_id = ? AND user_id = ? AND (status_request = 'selesai_diskusi' OR status_request = 'menunggu_pembayaran')");
    $stmt->bind_param("ii", $request_id, $user_id);
    $stmt->execute();
    $order_custom = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($order_custom) {
        $payment_details = ['id_display' => '#K' . $order_custom['kostum_request_id'], 'raw_id' => $order_custom['kostum_request_id'], 'total' => $order_custom['total_harga'], 'type' => 'custom'];
    }
}

if (empty($payment_details)) {
    $_SESSION['info_message'] = "Pesanan tidak valid, tidak ditemukan, sudah dibayar, atau sudah dibatalkan.";
    redirect('riwayat_pesanan.php');
}

$total_bayar = $payment_details['total'];
$conn->close();

$error_message = $_SESSION['payment_error'] ?? ''; unset($_SESSION['payment_error']);
$form_data_payment = $_SESSION['form_data_payment'] ?? []; unset($_SESSION['form_data_payment']);

if (!function_exists('sanitize_output')) {
    function sanitize_output($data) { return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Pesanan <?php echo sanitize_output($payment_details['id_display']); ?> - Bloomarie</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <? include 'includes/header.php'; ?>

        <section class="page-section py-5">
            <div class="container" style="max-width: 850px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title">Payment</h1>
                    <p class="lead">Order ID <?php echo sanitize_output($payment_details['id_display']); ?></p>
                </div>
                <?php if (isset($_SESSION['admin_role'])): // Cara sederhana memeriksa apakah admin yang login ?>
                    <div class="alert alert-warning text-center" role="alert">
                        <i class="fas fa-user-shield me-2"></i>
                        Anda login sebagai Admin. 
                        <a href="admin_panel/pages/pembayaran/index.php" class="alert-link fw-bold">Buka Halaman Verifikasi Pembayaran</a>.
                    </div>
                <?php endif; ?>
                <div class="row g-4 justify-content-center">
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-4">
                                <h4 class="mb-3">Instruction & Details</h4>
                                <ul class="list-group list-group-flush mb-3">
                                    <li class="list-group-item d-flex justify-content-between px-0 fs-5 fw-bold">
                                        <span>Total Bill</span>
                                        <span>Rp <?php echo number_format($total_bayar, 0, ',', '.'); ?></span>
                                    </li>
                                </ul>
                                <p>Please transfer the total amount of the bill to the following account:</p>
                                <div class="alert alert-info">
                                    <strong>Bank Mandiri:</strong> 1080025991739<br>
                                    <strong>Account Name:</strong> Dimas
                                </div>
                                <p class="mt-3 text-muted" style="font-size: 0.9rem;">After that, please fill in and upload proof of transfer in the confirmation form on the page.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card shadow-sm h-100" style="border-radius: var(--border-radius-lg);">
                            <div class="card-body p-4">
                                <h4 class="mb-3">Confirmation Form</h4>
                                <?php if ($error_message): ?>
                                    <div class="alert alert-danger" role="alert"><?php echo $error_message; ?></div>
                                <?php endif; ?>
                                <form action="proses_konfirmasi.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <?php if ($payment_details['type'] === 'regular'): ?>
                                        <input type="hidden" name="order_id" value="<?php echo $payment_details['raw_id']; ?>">
                                    <?php elseif ($payment_details['type'] === 'custom'): ?>
                                        <input type="hidden" name="request_id" value="<?php echo $payment_details['raw_id']; ?>">
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="jumlah_transfer" class="form-label">Transfer Amount</label>
                                        <input type="number" class="form-control" id="jumlah_transfer" name="jumlah_transfer" value="<?php echo $total_bayar; ?>" readonly required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bank_pengirim" class="form-label">Transfer from Bank <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="bank_pengirim" name="bank_pengirim" value="<?php echo sanitize_output($form_data_payment['bank_pengirim'] ?? ''); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="nama_rekening" class="form-label">Account Owner Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nama_rekening" name="nama_rekening_pengirim" value="<?php echo sanitize_output($form_data_payment['nama_rekening_pengirim'] ?? ''); ?>" required>
                                    </div>
                                     <div class="mb-3">
                                        <label for="tanggal_transfer" class="form-label">Transfer Date<span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="tanggal_transfer" name="tanggal_transfer" value="<?php echo sanitize_output($form_data_payment['tanggal_transfer'] ?? date('Y-m-d')); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="bukti_transfer" class="form-label">Upload of Proof Transfer <span class="text-danger">*</span></label>
                                        <input type="file" class="form-control" id="bukti_transfer" name="bukti_pembayaran" accept="image/jpeg, image/png, image/pdf" required>
                                        <small class="form-text text-muted">Max. 3MB.</small>
                                    </div>
                                    <div class="d-grid mt-4">
                                        <button type="submit" class="btn btn-submit">Send Confirmation</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="riwayat_pesanan.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back to Order History</a>
                </div>
            </div>
        </section>
        
        <footer class="actual-footer py-3 bg-light">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div> <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                         <input type="hidden" name="source_page" value="modal_contact_ajax_pembayaran">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Nama Anda</label>
                                <input type="text" class="form-control" id="modal_nama_pengirim" name="nama_pengirim" required value="<?php echo sanitize_output($current_fullname ?: $current_username); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email Anda</label>
                                <input type="email" class="form-control" id="modal_email_pengirim" name="email_pengirim" required value="<?php echo sanitize_output($current_email); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan" class="form-label">Subjek</label>
                            <input type="text" class="form-control" id="modal_subjek_pesan" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">Pesan Anda</label>
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
        // Script untuk toggle search, validasi, dan AJAX modal
        document.addEventListener('DOMContentLoaded', function() {
            // Logika toggle search
            const searchIconToggle = document.getElementById('searchIconToggle');
            const searchInput = document.getElementById('searchInput');
            if(searchIconToggle && searchInput){
                searchIconToggle.addEventListener('click', function(e){
                    e.preventDefault();
                    searchInput.style.display = 'inline-block';
                    setTimeout(() => { searchInput.style.width = '200px'; }, 0);
                    searchInput.focus();
                });
            }

            // Logika AJAX modal kontak
            const contactModalEl = document.getElementById('contactModal');
            if (contactModalEl) {
                const contactModalForm = document.getElementById('contactModalForm');
                const contactModalAlerts = document.getElementById('contactModalAlerts');
                contactModalForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    // ... (Salin logika fetch AJAX dari file index.php ke sini) ...
                    // Contoh singkat:
                    const formData = new FormData(contactModalForm);
                    fetch('proses_kontak.php', { method: 'POST', body: formData, headers: {'X-Requested-With': 'XMLHttpRequest'} })
                    .then(response => response.json())
                    .then(data => {
                        if(data.status === 'success'){
                             contactModalAlerts.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                             setTimeout(() => bootstrap.Modal.getInstance(contactModalEl).hide(), 2000);
                        } else {
                             contactModalAlerts.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                        }
                    })
                    .catch(error => console.error('Error:', error));
                });
            }
        });
    </script>
</body>
</html>