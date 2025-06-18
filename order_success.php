<?php
session_start();
require_once 'db_connect.php'; // Menyediakan connect_db() dan helper

// Inisialisasi koneksi database
$conn = connect_db();

// Pastikan pengguna sudah login
if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) {
    redirect('index.php');
    exit();
}
$user_id_session = (int)$_SESSION['user']['user_id'];

// Ambil parameter dari GET dan SESSION
$order_type_from_get = $_GET['type'] ?? null;
$order_id_from_get = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;
$request_id_from_get = isset($_GET['request_id']) ? (int)$_GET['request_id'] : null;

$order_type_from_session = $_SESSION['last_order_type'] ?? null;
$order_id_from_session = isset($_SESSION['last_order_id']) ? (int)$_SESSION['last_order_id'] : null;
$request_id_from_session = isset($_SESSION['last_request_id']) ? (int)$_SESSION['last_request_id'] : null;

$final_order_type = null;
$final_order_id = null;     // Untuk ID pesanan reguler
$final_request_id = null;   // Untuk ID permintaan kostum

// Prioritaskan tipe dari GET jika ada
if ($order_type_from_get) {
    $final_order_type = $order_type_from_get;
    if ($final_order_type === 'reguler') {
        $final_order_id = $order_id_from_get ?? $order_id_from_session;
    } elseif ($final_order_type === 'kostum') {
        $final_request_id = $request_id_from_get ?? $request_id_from_session;
    }
} elseif ($order_id_from_get && !$request_id_from_get) {
    // Jika hanya order_id ada di GET (dan bukan request_id), asumsikan ini reguler
    $final_order_type = 'reguler';
    $final_order_id = $order_id_from_get;
} elseif ($request_id_from_get && !$order_id_from_get) {
    // Jika hanya request_id ada di GET (dan bukan order_id), asumsikan ini kostum
    $final_order_type = 'kostum';
    $final_request_id = $request_id_from_get;
} else {
    // Jika tidak ada petunjuk dari GET, gunakan data dari session
    $final_order_type = $order_type_from_session;
    if ($final_order_type === 'reguler') {
        $final_order_id = $order_id_from_session;
    } elseif ($final_order_type === 'kostum') {
        $final_request_id = $request_id_from_session;
    }
}

// Hapus variabel session setelah digunakan agar tidak tampil lagi jika halaman di-refresh
unset($_SESSION['last_order_type']);
unset($_SESSION['last_order_id']);
unset($_SESSION['last_request_id']);

$page_title = "Order Confirmation";
$success_message_title = "Your Order Succesfully Proceed!";
$order_display_details = [];
$is_valid_confirmation = false;

if ($final_order_type === 'reguler' && $final_order_id) {
    $stmt_order = $conn->prepare("SELECT order_id, total, tanggal_order, status, nama_penerima_order 
                                  FROM orders 
                                  WHERE order_id = ? AND user_id = ?");
    if ($stmt_order) {
        $stmt_order->bind_param("ii", $final_order_id, $user_id_session);
        $stmt_order->execute();
        $result_order = $stmt_order->get_result();
        $details = $result_order->fetch_assoc();
        $stmt_order->close();

        if ($details) {
            $is_valid_confirmation = true;
            $page_title = "Pesanan Reguler Berhasil - Bloomarie";
            $success_message_title = "Your Regular Order Succed to Process!";
            $order_display_details = [
                "Order Number (ID)" => "#" . sanitize_output($details['order_id']),
                "Order Date" => date('d F Y, H:i', strtotime($details['tanggal_order'])),
                "Recepient's Name" => sanitize_output($details['nama_penerima_order']),
                "Total Amount of Payment" => "Rp " . number_format($details['total'], 0, ',', '.'),
                "Order Status" => ucfirst(str_replace('_', ' ', sanitize_output($details['status'])))
            ];
        }
    } else {
        error_log("Gagal prepare statement untuk order reguler: " . $conn->error);
    }
} elseif ($final_order_type === 'kostum' && $final_request_id) {
    $stmt_kostum = $conn->prepare("SELECT kostum_request_id, tanggal_request, status_request, deskripsi_request 
                                   FROM orderkostum 
                                   WHERE kostum_request_id = ? AND user_id = ?");
    if ($stmt_kostum) {
        $stmt_kostum->bind_param("ii", $final_request_id, $user_id_session);
        $stmt_kostum->execute();
        $result_kostum = $stmt_kostum->get_result();
        $details = $result_kostum->fetch_assoc();
        $stmt_kostum->close();

        if ($details) {
            $is_valid_confirmation = true;
            $page_title = "Permintaan Pesanan Kostum Terkirim - Bloomarie";
            $success_message_title = "Your Custom Order Succed to Process!";
            $order_display_details = [
                "Order Number" => "#K" . sanitize_output($details['kostum_request_id']),
                "Order Date" => date('d F Y, H:i', strtotime($details['tanggal_request'])),
                "Order Status" => ucfirst(str_replace('_', ' ', sanitize_output($details['status_request'])))
            ];
        }
    } else {
        error_log("Gagal prepare statement untuk order kostum: " . $conn->error);
    }
}

if (!$is_valid_confirmation) {
    $_SESSION['error_message'] = "Invalid Order Confirmation or Session has Expired.";
    redirect('index.php');
    exit();
}

// $conn->close(); // Koneksi akan ditutup otomatis atau di akhir skrip db_connect.php

// Logika untuk header (disalin dari file checkout.php/gallery_page.php)
$cart_page_url_header = 'checkout.php';
$cart_item_count_header = 0; // Setelah order berhasil, keranjang seharusnya kosong
$is_user_logged_in_header = true;
$current_fullname_header = $_SESSION['user']['nama_lengkap'] ?? '';
$current_username_header = $_SESSION['user']['username'] ?? '';
$current_email_header = $_SESSION['user']['email'] ?? '';
$logout_url_header = "logout.php";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
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
        <header class="container-fluid sticky-top bg-white shadow-sm py-3">
            <div class="container">
                <nav class="d-flex justify-content-between align-items-center">
                    <div class="nav-search">
                        <form action="search_results.php" method="GET" class="d-flex align-items-center" id="searchFormSuccess">
                            <input class="form-control me-2" type="search" name="query" placeholder="Cari produk..." aria-label="Search" id="searchInputSuccess" style="display: none; width: 0;">
                            <a href="#" class="text-dark" id="searchIconToggleSuccess" title="Cari">
                                <i class="fas fa-search fa-2x"></i>
                            </a>
                            <button type="submit" style="display:none;">Search</button>
                        </form>
                    </div>
                    <div class="nav-links-center d-flex align-items-center">
                        <a href="gallery_page.php" class="nav-item-custom mx-3 mx-lg-4">Gallery</a>
                        <a href="index.php#hero-section" class="logo-link mx-3 mx-lg-4">
                            <img src="https://placehold.co/115x136/E0D8D1/4A3F35?text=LOGO" alt="Bloomarie Logo" class="main-logo">
                        </a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#contactModal" class="nav-item-custom mx-3 mx-lg-4">Contact</a>
                    </div>
                    <div class="nav-user-cart d-flex align-items-center">
                        <div class="nav-item dropdown me-3">
                            <a class="nav-link dropdown-toggle text-dark p-0" href="#" id="userDropdownSuccess" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Akun Pengguna">
                                <i class="fas fa-user fa-2x"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdownSuccess">
                                <li><h6 class="dropdown-header">Halo, <?php echo sanitize_output($current_fullname_header ?: $current_username_header); ?></h6></li>
                                <?php if ($current_email_header): ?>
                                    <li><span class="dropdown-item-text fst-italic"><?php echo sanitize_output($current_email_header); ?></span></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo $logout_url_header; ?>">Logout</a></li>
                            </ul>
                        </div>
                        <div class="nav-cart">
                            <a href="checkout.php" class="text-dark position-relative" title="Keranjang Belanja">
                                <i class="fas fa-shopping-cart fa-2x"></i>
                                <?php if ($cart_item_count_header > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        <?php echo $cart_item_count_header; ?>
                                        <span class="visually-hidden">Item in Cart</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </nav>
            </div>
        </header>

        <section class="order-success-section py-5 text-center">
            <div class="container" style="max-width: 700px;">
                 <div class="card shadow-sm" style="border-radius: var(--border-radius-xl); border: 1px solid var(--border-color); background-color: var(--bg-section-alt); padding: 2.5rem;">
                    <div class="mb-4">
                        <i class="fas fa-check-circle fa-5x" style="color: var(--accent-green-leaf, #28a745);"></i>
                    </div>
                    <h1 class="bloomarie-title mb-3" style="font-size: clamp(2rem, 6vw, 3.5rem); line-height:1.1;"><?php echo sanitize_output($success_message_title); ?></h1>
                    
                    <p class="lead" style="color: var(--text-medium);">
                        Thank you, <strong><?php echo sanitize_output($_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username']); ?></strong>, has trusted your special moment to Bloomarie.
                    </p>

                    <?php if (!empty($order_display_details)): ?>
                        <div class="card my-4 text-start" style="border-radius: var(--border-radius-md); border-color: var(--border-color);">
                            <div class="card-header" style="background-color: var(--accent-soft-pink); border-bottom: 1px solid var(--border-color);">
                                <h5 class="mb-0" style="font-family: var(--primary-font); color: var(--text-dark); font-weight:500;">Detail Confirmation</h5>
                            </div>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($order_display_details as $label => $value): ?>
                                    <li class="list-group-item d-flex justify-content-between">
                                        <span style="color: var(--text-medium);"><?php echo $label; ?>:</span>
                                        <strong style="color: var(--text-dark);"><?php echo $value; ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($final_order_type === 'reguler'): ?>
                        <p class="mt-4" style="font-size: 0.95rem; color: var(--text-medium);">
                            We will send further information regarding payment and delivery via your email
                            (<?php echo sanitize_output($_SESSION['user']['email'] ?? '...'); ?>).
                            You can also view your ordered item on your account.
                        </p>
                    <?php elseif ($final_order_type === 'kostum'): ?>
                        <p class="mt-4" style="font-size: 0.95rem; color: var(--text-medium);">
                            Our team will reviw your requst soon and will respond to you via email or phone number listed for further discussion.
                        </p>
                    <?php endif; ?>

                    <div class="mt-5 d-flex flex-column flex-sm-row justify-content-center">
                        <a href="gallery_page.php" class="btn btn-submit mb-2 mb-sm-0 me-sm-2"><i class="fas fa-shopping-bag me-2"></i> Continue Shopping</a>
                        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-home me-2"></i> back to Home</a>
                        <a href="riwayat_pesanan.php" class="btn btn-outline-info ms-sm-2 mt-2 mt-sm-0"><i class="fas fa-history me-2"></i> View Order History</a>
                        <!-- Tambahkan link ke histori pesanan jika ada -->
                        <!-- <a href="histori_pesanan.php" class="btn btn-outline-info ms-sm-2 mt-2 mt-sm-0"><i class="fas fa-history me-2"></i> Lihat Riwayat Pesanan</a> -->
                    </div>
                </div>
            </div>
        </section>

        <footer class="actual-footer py-3">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div> <!-- #page-wrapper -->

    <!-- Modal Kontak (Sama seperti di index.php) -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel" style="font-family: var(--primary-font); color: var(--text-dark);">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlertsSuccess" class="mb-3"></div>
                    <form id="contactModalFormSuccess">
                        <input type="hidden" name="source_page" value="modal_contact_ajax_order_success">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim_success" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control contact-input" id="modal_nama_pengirim_success" name="nama_pengirim" required
                                       value="<?php echo sanitize_output($current_fullname_header ?: $current_username_header); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim_success" class="form-label">Email Anda <span class="text-danger">*</span></label>
                                <input type="email" class="form-control contact-input" id="modal_email_pengirim_success" name="email_pengirim" required
                                       value="<?php echo sanitize_output($current_email_header); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan_success" class="form-label">Subjek</label>
                            <input type="text" class="form-control contact-input" id="modal_subjek_pesan_success" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan_success" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control contact-input" id="modal_isi_pesan_success" name="isi_pesan" rows="4" required></textarea>
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
        // Script untuk toggle search input (pastikan ID unik jika perlu)
        document.addEventListener('DOMContentLoaded', function() {
            const searchIconToggleSuccess = document.getElementById('searchIconToggleSuccess');
            const searchInputSuccess = document.getElementById('searchInputSuccess');
            const searchFormSuccess = document.getElementById('searchFormSuccess');
            let searchVisibleSuccess = false;

            if(searchIconToggleSuccess && searchInputSuccess && searchFormSuccess){
                searchIconToggleSuccess.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!searchVisibleSuccess) {
                        searchInputSuccess.style.display = 'inline-block';
                        setTimeout(() => { searchInputSuccess.style.width = '200px'; }, 0);
                        searchInputSuccess.focus();
                        searchVisibleSuccess = true;
                    } else {
                        if (searchInputSuccess.value.trim() !== '') { searchFormSuccess.submit(); }
                        else { searchInputSuccess.style.width = '0'; setTimeout(() => { searchInputSuccess.style.display = 'none'; }, 300); searchVisibleSuccess = false; }
                    }
                });
                 searchInputSuccess.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { searchFormSuccess.submit(); } }
                });
                document.addEventListener('click', function(event) {
                    if (searchVisibleSuccess && !searchFormSuccess.contains(event.target) && event.target !== searchIconToggleSuccess && !searchIconToggleSuccess.contains(event.target)) {
                        searchInputSuccess.style.width = '0'; setTimeout(() => { searchInputSuccess.style.display = 'none'; }, 300); searchVisibleSuccess = false;
                    }
                });
            }

            // AJAX untuk Contact Modal (sama seperti index.php, pastikan ID unik)
            const contactModalElSuccess = document.getElementById('contactModal');
            if (contactModalElSuccess) {
                const contactModalFormSuccess = document.getElementById('contactModalFormSuccess');
                const contactModalAlertsSuccess = document.getElementById('contactModalAlertsSuccess');
                if (contactModalFormSuccess) {
                    contactModalFormSuccess.addEventListener('submit', function(event) {
                        event.preventDefault(); contactModalAlertsSuccess.innerHTML = '';
                        const formData = new FormData(contactModalFormSuccess);
                        const submitButton = contactModalFormSuccess.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true; submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';
                        fetch('proses_kontak.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(response => response.json()).then(data => {
                            if (data.status === 'success') {
                                contactModalAlertsSuccess.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                                contactModalFormSuccess.reset();
                                <?php if ($is_user_logged_in_header): ?>
                                const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname_header ?: $current_username_header)); ?>";
                                const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email_header)); ?>";
                                if(document.getElementById('modal_nama_pengirim_success')) document.getElementById('modal_nama_pengirim_success').value = defaultNameModal;
                                if(document.getElementById('modal_email_pengirim_success')) document.getElementById('modal_email_pengirim_success').value = defaultEmailModal;
                                <?php endif; ?>
                                setTimeout(() => { const modalInstance = bootstrap.Modal.getInstance(contactModalElSuccess); if (modalInstance) modalInstance.hide(); contactModalAlertsSuccess.innerHTML = ''; }, 3000);
                            } else {
                                let errMsg = data.message || 'Terjadi kesalahan.'; if (data.errors && typeof data.errors === 'object') { errMsg += '<ul class="mt-2 mb-0 text-start">'; for (const field in data.errors) { errMsg += `<li>${data.errors[field]}</li>`; } errMsg += '</ul>'; }
                                contactModalAlertsSuccess.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${errMsg} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                            }
                        }).catch(error => {
                            contactModalAlertsSuccess.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">Tidak dapat terhubung ke server. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                        }).finally(() => { submitButton.disabled = false; submitButton.innerHTML = originalButtonText; });
                    });
                }
                contactModalElSuccess.addEventListener('hidden.bs.modal', function () { if (contactModalAlertsSuccess) contactModalAlertsSuccess.innerHTML = ''; });
            }
        });
    </script>
</body>
</html>
