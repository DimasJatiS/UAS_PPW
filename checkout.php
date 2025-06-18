<?php
session_start();
require_once 'db_connect.php';

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "Anda harus login untuk melanjutkan ke checkout.";
    $_SESSION['redirect_url_after_login'] = 'checkout.php';
    redirect('login.php');
}

$conn = connect_db();
$user_id = (int)$_SESSION['user']['user_id'];

// --- Logika untuk Header Navigasi & Halaman ---
$cart_items = [];
$subtotal = 0;
$cart_item_count_header = 0;

$sql_cart = "SELECT c.cart_id, c.product_id, c.kuantitas, p.name, p.harga AS price, p.foto_produk, p.stok
             FROM cart c
             JOIN produk p ON c.product_id = p.product_id
             WHERE c.user_id = ?";
$stmt_cart = $conn->prepare($sql_cart);
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();
$can_checkout = true;

while ($row = $result_cart->fetch_assoc()) {
    if ($row['kuantitas'] > $row['stok']) {
        $can_checkout = false;
        $row['error_stok'] = "Kuantitas melebihi stok (" . $row['stok'] . ")";
    }
    $cart_items[] = $row;
    $subtotal += $row['price'] * $row['kuantitas'];
    $cart_item_count_header += $row['kuantitas'];
}
$stmt_cart->close();

// Jika keranjang kosong setelah memuat, arahkan ke galeri
if (empty($cart_items)) {
    $_SESSION['info_message'] = "Keranjang belanja Anda kosong.";
    redirect('gallery_page.php');
}

// Menyiapkan variabel untuk header
$current_user_info = $_SESSION['user'];
// Inisialisasi variabel
$cart_item_count = 0;
$cart_page_url = 'gallery_page.php?from_checkout=empty_cart&source=index_header'; // Default

// Ambil jumlah item di keranjang jika user login (menggunakan mysqli)
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $user_id_session = (int)$_SESSION['user']['user_id']; // Pastikan integer
    $sql_cart_count = "SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?";
    $stmt_cart_count = $conn->prepare($sql_cart_count);

    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id_session);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        $row_cart_count = $result_cart_count->fetch_assoc();
        if ($row_cart_count && $row_cart_count['total_items'] > 0) {
            $cart_item_count = (int)$row_cart_count['total_items'];
        }
        $stmt_cart_count->close();
    } else {
        error_log("mysqli_prepare failed for cart count: " . $conn->error);
        // Biarkan $cart_item_count = 0 jika terjadi error
    }
}


if ($cart_item_count > 0) {
    $cart_page_url = 'checkout.php';
}

// Logika untuk link login/logout dan register
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

// Cek apakah ada user yang login (apapun rolenya)
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $_SESSION['user']['username'] ?? '';
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? ''; // Pastikan ini ada di session
$current_email = $_SESSION['user']['email'] ?? ''; // Pastikan ini ada di session

// Ambil pesan error/sukses untuk form kontak di #contact-section
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

$total = $subtotal; // Total sekarang sama dengan subtotal

$conn->close();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bloomarie</title>
    <!-- Link ke CSS dan Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <!-- Disarankan untuk menyertakan Header Navigasi lengkap di sini -->
        <?php include 'includes/header.php'; ?>

        <section class="page-section py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title">Checkout</h1>
                </div>

                <?php // Tampilkan pesan error/info dari session
                if (isset($_SESSION['checkout_error'])): ?>
                    <div class="alert alert-danger"><?php echo sanitize_output($_SESSION['checkout_error']); unset($_SESSION['checkout_error']); ?></div>
                <?php endif; if (isset($_SESSION['cart_info_message'])): ?>
                    <div class="alert alert-info"><?php echo sanitize_output($_SESSION['cart_info_message']); unset($_SESSION['cart_info_message']); ?></div>
                <?php endif; ?>

                <form action="proses_order.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-5">
                        <!-- Kolom Detail Pengiriman -->
                        <div class="col-lg-7">
                            <h4 class="mb-3">Shipping Address</h4>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nama_penerima" class="form-label">Recipient's Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_penerima" name="nama_penerima_order" placeholder="Dimas" required>
                                    <div class="invalid-feedback">Recipient's name is required.</div>
                                </div>
                                <div class="col-12">
                                    <label for="nomor_telepon" class="form-label" >Phone Number (With Country Code)<span class="text-danger">*</span> </label>
                                    <input type="tel" class="form-control" id="nomor_telepon" name="nomor_telepon_penerima_order" required pattern="[0-9]{10,15}" placeholder="Ex: 628712387455">
                                    <div class="invalid-feedback">valid phone number is required. </div>
                                </div>
                                <div class="col-12">
                                    <label for="alamat_lengkap" class="form-label">Full Shipping Address <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap_order" rows="4" required placeholder="Jalan Babul Khairat No. 48"></textarea>
                                    <div class="invalid-feedback">Full shipping address is required. </div>
                                </div>
                                <div class="col-12">
                                    <label for="custom_note" class="form-label">Additional Notes (Optional)</label>
                                    <textarea class="form-control" id="custom_note" name="custom_note" rows="2" placeholder="E.g., Request for the love card..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Ringkasan Pesanan -->
                        <div class="col-lg-5">
                            <div class="card shadow-sm sticky-top" style="top: 100px; border-radius: var(--border-radius-lg);">
                                <div class="card-body p-4">
                                    <h4 class="d-flex justify-content-between align-items-center mb-3">
                                        <span>Order Summary</span>
                                        <span class="badge bg-primary rounded-pill"><?php echo $cart_item_count_header; ?></span>
                                    </h4>
                                    <ul class="list-group list-group-flush mb-3">
                                        <?php foreach ($cart_items as $item): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 <?php if(isset($item['error_stok'])) echo 'bg-danger-subtle'; ?>">
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo sanitize_output($item['foto_produk']); ?>" width="60" height="60" class="rounded me-3" style="object-fit: cover;">
                                                    <div>
                                                        <h6 class="my-0 small"><?php echo sanitize_output($item['name']); ?></h6>
                                                        <small class="text-muted">Jumlah: <?php echo $item['kuantitas']; ?></small>
                                                        <?php if(isset($item['error_stok'])): ?>
                                                            <div class="text-danger fw-bold small"><?php echo $item['error_stok']; ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="text-end">
                                                    <span class="text-muted">Rp <?php echo number_format($item['price'] * $item['kuantitas'], 0, ',', '.'); ?></span>
                                                    <!-- --- PERBAIKAN: Tombol Hapus --- -->
                                                    <a href="remove_from_cart.php?cart_id=<?php echo $item['cart_id']; ?>&return_url=checkout.php" class="btn btn-link text-danger p-0 ms-2" title="Hapus item" onclick="return confirm('Hapus item ini dari keranjang?')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </li>
                                        <?php endforeach; ?>
                                        <li class="list-group-item d-flex justify-content-between fs-5 bg-light">
                                            <span><strong>Total</strong></span>
                                            <strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong>
                                        </li>
                                    </ul>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-submit btn-lg" <?php if (!$can_checkout) echo 'disabled'; ?>>
                                            Proceed Order
                                        </button>
                                    </div>
                                    <?php if (!$can_checkout): ?>
                                        <p class="text-danger small mt-2 text-center">The quantity for some items exceeds our available stock. Please adjust the amount or remove the items to proceed.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </section>
        
        <footer class="actual-footer py-3 bg-light">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
</body>
</html>