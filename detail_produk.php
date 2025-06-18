<?php
// SELALU MULAI DENGAN SESSION DAN REQUIRE
session_start();
require_once 'db_connect.php'; // Memuat semua fungsi helper (seperti isCustomerLoggedIn) dan koneksi
$conn = connect_db();

// ==================================================================
// BAGIAN 1: PENGAMBILAN SEMUA DATA YANG DIPERLUKAN
// ==================================================================

// 1. Validasi ID Produk dari URL
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if ($product_id <= 0) {
    redirect('gallery_page.php');
}

// 2. Ambil Detail Produk Utama
$stmt_prod = $conn->prepare("SELECT p.*, k.nama_kategori FROM produk p LEFT JOIN kategoriproduk k ON p.kategori_id = k.kategori_id WHERE p.product_id = ? AND p.is_available = 1");
$stmt_prod->bind_param("i", $product_id);
$stmt_prod->execute();
$product = $stmt_prod->get_result()->fetch_assoc();
$stmt_prod->close();

// Jika produk tidak ditemukan, arahkan kembali ke galeri
if (!$product) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'The product you search not found or unavailable.'];
    redirect('gallery_page.php');
}

// 3. Ambil Semua Ulasan untuk Produk Ini
$stmt_reviews = $conn->prepare("SELECT r.rating, r.comment, r.created_at, u.nama_lengkap, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmt_reviews->bind_param("i", $product_id);
$stmt_reviews->execute();
$reviews = $stmt_reviews->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_reviews->close();

// 4. Hitung Rata-rata Rating
$average_rating = 0;
if (count($reviews) > 0) {
    $total_rating = array_sum(array_column($reviews, 'rating'));
    $average_rating = round($total_rating / count($reviews), 1);
}

// 5. Periksa Apakah Pengguna Saat Ini Berhak Memberi Ulasan
$can_review = false;
if (isCustomerLoggedIn()) {
    $user_id = (int)$_SESSION['user']['user_id'];
    $stmt_check_purchase = $conn->prepare("SELECT COUNT(o.order_id) as total FROM orders o JOIN orderdetails od ON o.order_id = od.order_id WHERE o.user_id = ? AND od.product_id = ? AND o.status = 'selesai'");
    $stmt_check_purchase->bind_param("ii", $user_id, $product_id);
    $stmt_check_purchase->execute();
    $purchase_count = $stmt_check_purchase->get_result()->fetch_assoc()['total'];
    $stmt_check_purchase->close();
    if ($purchase_count > 0) {
        $can_review = true;
    }
}

// 6. Ambil Produk Terkait (Related Products)
$related_products = [];
if (!empty($product['kategori_id'])) {
    $stmt_related = $conn->prepare("SELECT * FROM produk WHERE kategori_id = ? AND product_id != ? AND is_available = 1 ORDER BY RAND() LIMIT 4");
    $stmt_related->bind_param("ii", $product['kategori_id'], $product_id);
    $stmt_related->execute();
    $result_related = $stmt_related->get_result();
    while ($row = $result_related->fetch_assoc()) {
        $related_products[] = $row;
    }
    $stmt_related->close();
}

// 7. Logika untuk Data Header (Disatukan di sini)
$cart_item_count = 0;
$is_any_user_logged_in = isCustomerLoggedIn();
$current_username = $_SESSION['user']['username'] ?? '';
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? '';
$current_email = $_SESSION['user']['email'] ?? '';
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

if ($is_any_user_logged_in) {
    $stmt_cart_count = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $_SESSION['user']['user_id']);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        if ($row_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_count['total_items'] ?? 0);
        }
        $stmt_cart_count->close();
    }
}

// Mengambil data email

// Ambil pesan error/sukses untuk form kontak di #contact-section
$current_email = $_SESSION['user']['email'] ?? ''; // Pastikan ini ada di session
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitize_output($product['name']); ?> - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <?php include 'includes/header.php'; ?>

        <!-- Notifikasi Flash Message -->
        <div class="container pt-4">
        <?php if (isset($_SESSION['flash_message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> text-center" role="alert">
                <?php echo sanitize_output($_SESSION['flash_message']['message']); unset($_SESSION['flash_message']); ?>
            </div>
        <?php endif; ?>
        </div>

        <section class="py-5">
            <div class="container px-4 px-lg-5 my-5">
                <div class="row gx-4 gx-lg-5 align-items-start">
                    <div class="col-md-6">
                        <img class="card-img-top mb-5 mb-md-0 shadow-strong" 
                             src="<?php echo sanitize_output(!empty($product['foto_produk']) ? $product['foto_produk'] : 'https://placehold.co/600x700/E0D8D1/7D6E63?text=Bloomarie'); ?>" 
                             alt="<?php echo sanitize_output($product['name']); ?>" 
                             style="border-radius: var(--border-radius-xl);">
                    </div>

                    <div class="col-md-6">
                        <?php if (!empty($product['nama_kategori'])): ?>
                            <div class="badge bg-light text-dark mb-2"><?php echo sanitize_output($product['nama_kategori']); ?></div>
                        <?php endif; ?>
                        <h1 class="display-5 fw-bolder"><?php echo sanitize_output($product['name']); ?></h1>
                        <div class="fs-4 mb-3">
                            <span>Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?></span>
                        </div>
                        
                        <!-- Tampilkan Rata-rata Rating -->
                        <div class="d-flex align-items-center small mb-3 rating-summary">
                            <div class="rating-stars text-warning">
                                <?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star <?php echo ($i > $average_rating) ? 'text-muted' : ''; ?>"></i><?php endfor; ?>
                            </div>
                            <a href="#reviews-section" class="text-muted ms-2">(<?php echo count($reviews); ?> Rating)</a>
                        </div>
                        
                        <p class="lead"><?php echo nl2br(sanitize_output($product['deskripsi'])); ?></p>
                        
                        <div class="d-flex align-items-center mb-4">
                            <span class="me-3">Stock: <strong><?php echo $product['stok']; ?></strong></span>
                        </div>

                        <div class="d-flex">
                            <?php if ($product['stok'] > 0): ?>
                                <a href="add_to_cart.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-submit btn-lg flex-shrink-0">
                                    <i class="fas fa-cart-plus me-1"></i> Add to Cart
                                </a>
                            <?php else: ?>
                                <button class="btn btn-outline-secondary btn-lg" disabled>Stock Empty</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="reviews-section" class="py-5 bg-light">
            <div class="container" style="max-width: 850px;">
                <h2 class="section-title text-center">Product Reviews</h2>
                <div class="title-underline mx-auto"></div>

                <?php if ($can_review): ?>
                <div class="card shadow-sm mb-5 review-form-card">
                    <div class="card-body p-4">
                        <h5 class="mb-3">Write Your Review</h5>
                        <form action="proses_review.php" method="POST">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <div class="mb-3">
                                <label class="form-label">Your Review:</label>
                                <div class="rating-stars-input">
                                    <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" title="Sempurna"></label>
                                    <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Bagus"></label>
                                    <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Cukup"></label>
                                    <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Kurang"></label>
                                    <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Buruk"></label>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Your Comment (Optional):</label>
                                <textarea name="comment" id="comment" class="form-control" rows="4" placeholder="Tell us your experience with this product..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-submit">Send Review</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <div class="reviews-list">
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-user-circle fa-3x text-light"></i>
                                </div>
                                <div class="ms-3 w-100">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold mb-0"><?php echo sanitize_output($review['nama_lengkap'] ?: $review['username']); ?></h6>
                                        <small class="text-muted"><?php echo date('d M Y', strtotime($review['created_at'])); ?></small>
                                    </div>
                                    <div class="rating-stars small text-warning my-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star <?php echo ($i > $review['rating']) ? 'text-muted' : ''; ?>"></i><?php endfor; ?>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(sanitize_output($review['comment'])); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-muted fst-italic py-4">There are no reviews for this product yet. Be the first!</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <?php if (!empty($related_products)): ?>
        <section class="py-5">
            <div class="container">
                <h2 class="section-title text-center">You May Also Like</h2>
                <div class="title-underline mx-auto"></div>
                <div class="row gx-4 gx-lg-5 row-cols-2 row-cols-md-3 row-cols-xl-4 justify-content-center">
                    <?php foreach ($related_products as $related): ?>
                    <div class="col mb-5 d-flex align-items-stretch">
                        <a href="detail_produk.php?product_id=<?php echo $related['product_id']; ?>" class="product-card-link">
                            <div class="product-card">
                                <div class="product-image-container">
                                    <img class="product-image" src="<?php echo sanitize_output(!empty($related['foto_produk']) ? $related['foto_produk'] : 'https://placehold.co/450x300/E0D8D1/7D6E63?text=Bloomarie'); ?>" alt="<?php echo sanitize_output($related['name']); ?>" />
                                </div>
                                <div class="product-info">
                                    <h5 class="product-name"><?php echo sanitize_output($related['name']); ?></h5>
                                    <p class="product-price">Rp <?php echo number_format($related['harga'], 0, ',', '.'); ?></p>
                                    <span class="btn btn-sm btn-submit-passive">Look the Detail</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
</body>
</html>
