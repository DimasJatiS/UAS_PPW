<?php
session_start();
require_once 'db_connect.php'; // Pastikan ini mengarah ke file koneksi database Anda
$conn = connect_db(); // Fungsi untuk mendapatkan koneksi mysqli

// Logika untuk header (jumlah item keranjang, status login)
$cart_page_url = 'gallery_page.php?from_checkout=empty_cart&source=gallery_header';
$cart_item_count = 0;
$is_any_user_logged_in = isset($_SESSION['user']);
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? '';
$current_username = $_SESSION['user']['username'] ?? '';
$current_email = $_SESSION['user']['email'] ?? '';
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

// contact
$index_contact_error = $_SESSION['index_contact_error'] ?? '';
unset($_SESSION['index_contact_error']);

$index_contact_success = $_SESSION['index_contact_success'] ?? '';
unset($_SESSION['index_contact_success']);

$form_data_idx = $_SESSION['form_data_index_contact'] ?? [];
unset($_SESSION['form_data_index_contact']);

// Ambil semua kategori dari database untuk ditampilkan sebagai filter
$sql_kategori = "SELECT kategori_id, nama_kategori FROM kategoriproduk ORDER BY nama_kategori ASC";
$result_kategori = $conn->query($sql_kategori);
$kategori_list = [];
if ($result_kategori->num_rows > 0) {
    while($row_kat = $result_kategori->fetch_assoc()) {
        $kategori_list[] = $row_kat;
    }
}
// 2. Tentukan Kategori yang Sedang Aktif
$kategori_filter_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;

// 3. Query untuk Mengambil Produk
$sql_produk = "SELECT p.product_id, p.name AS nama_produk, p.harga, p.foto_produk, p.stok, k.nama_kategori
                FROM produk p
                LEFT JOIN kategoriproduk k ON p.kategori_id = k.kategori_id
                WHERE p.is_available = 1";

// Jika ada filter kategori, tambahkan kondisi WHERE
if ($kategori_filter_id > 0) {
    $sql_produk .= " AND p.kategori_id = ?";
}

$sql_produk .= " ORDER BY p.name ASC";
$stmt_produk = $conn->prepare($sql_produk);

if ($kategori_filter_id > 0) {
    $stmt_produk->bind_param("i", $kategori_filter_id);
}

$stmt_produk->execute();
$result_produk = $stmt_produk->get_result();

if ($is_any_user_logged_in && isset($_SESSION['user']['user_id'])) {
    $user_id_session = $_SESSION['user']['user_id'];
    $stmt_cart_count = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id_session);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        if ($row_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_count['total_items'] ?? 0);
        }
        $stmt_cart_count->close();
    }
}
if ($cart_item_count > 0) {
    $cart_page_url = 'checkout.php';
}

// untuk pesan singkat
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri Produk - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<link rel="icon" href="assets/favicon.ico">
<body class="gallery-page-body">

    <div id="page-wrapper">
        
        <?php include 'includes/header.php'; ?>
        
        <section class="gallery-header-section text-center py-5" style="background-color: var(--accent-soft-pink);">
            <div class="container">
                <h1 class="bloomarie-title gallery-main-title-text">Gallery</h1>
                <p class="gallery-subtitle-text lead" style="color: var(--text-medium); font-family: var(--secondary-font);">
                    Find the perfect bouquet for every special moment.
                </p>
            </div>
            <?php include 'includes/flash_message.php'; ?>
        </section>

        <section class="gallery-content-section py-5">
            <div class="container">
                <div class="category-filter-nav text-center mb-5">
                    <a href="gallery_page.php" class="btn btn-outline-secondary <?php if ($kategori_filter_id == 0) echo 'active'; ?>">All products</a>
                    <?php foreach ($kategori_list as $kategori): ?>
                        <a href="gallery_page.php?kategori_id=<?php echo $kategori['kategori_id']; ?>" 
                        class="btn btn-outline-secondary <?php if ($kategori_filter_id == $kategori['kategori_id']) echo 'active'; ?>">
                            <?php echo htmlspecialchars($kategori['nama_kategori']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($_GET['item_added'])): ?>
                    <div class="alert alert-success text-center" role="alert">
                        "<?php echo sanitize_output(urldecode($_GET['item_added'])); ?>"  was successfully added to your cart!
                    <?php endif; ?>
                    <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center" role="alert">
                        <?php
                        if ($_GET['error'] == 'insufficient_stock') {
                            // Sanitize the product name from the URL to prevent XSS attacks
                            $productName = isset($_GET['product_name']) ? htmlspecialchars($_GET['product_name'], ENT_QUOTES, 'UTF-8') : 'the product';

                            // Display the message in English
                            echo "Sorry, there is not enough stock for <strong>" . $productName . "</strong> to fulfill your request.";
                        }
                        // You can add other error cases here. For example:
                        elseif ($_GET['error'] == 'invalid_input') {
                            echo "The quantity provided is invalid. Please enter a valid number.";
                        }
                        ?>
                    </div>
                <?php endif; ?>
                
                <div class="row mt-4 justify-content-center g-4">
                    <?php
                    // Cek apakah ada filter kategori dari URL
                    $kategori_filter_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
                    
                    $sql_produk = "SELECT p.product_id, p.name AS nama_produk, p.harga, p.foto_produk, p.deskripsi, p.stok, k.nama_kategori
                                    FROM produk p
                                    LEFT JOIN kategoriproduk k ON p.kategori_id = k.kategori_id
                                    WHERE p.is_available = 1";

                    // Jika ada filter ID kategori, tambahkan ke query
                    if ($kategori_filter_id > 0) {
                        $sql_produk .= " AND p.kategori_id = " . $kategori_filter_id;
                    }

                    $sql_produk .= " ORDER BY p.name ASC";
                    $result_produk = $conn->query($sql_produk);
                    
                    if ($result_produk && $result_produk->num_rows > 0) {
                        while ($product = $result_produk->fetch_assoc()) :
                            $bg_class = 'default-bg';
                            $kategori_slug = strtolower(str_replace(' ', '', $product['nama_kategori'] ?? 'default'));
                            if (in_array($kategori_slug.'-bg', ['rose-bg', 'hydrangea-bg', 'mixflowers-bg', 'tulip-bg', 'orchid-bg'])) {
                                $bg_class = $kategori_slug . '-bg';
                            }
                    ?>
                            <div class="col-sm-6 col-md-4 col-lg-4 d-flex align-items-stretch">
                                <a href="detail_produk.php?product_id=<?php echo $product['product_id']; ?>" class="product-card-link">
                                    <div class="product-card w-100">
                                        <img src="<?php echo sanitize_output(!empty($product['foto_produk']) ? $product['foto_produk'] : 'https://placehold.co/453x454/E0D8D1/7D6E63?text=Bloomarie'); ?>"
                                        alt="<?php echo sanitize_output($product['nama_produk']); ?>" class="product-image">
                                        <div class="product-info <?php echo $bg_class; ?>">
                                            <h3 class="product-name"><?php echo sanitize_output($product['nama_produk']); ?></h3>
                                            <p class="product-price" ...>
                                                Rp <?php echo number_format($product['harga'], 0, ',', '.'); ?>
                                            </p>
                                            <small class="mb-2 d-block" ...>Stock: <?php echo $product['stok']; ?></small>

                                            <span class="btn btn-sm btn-submit-passive">
                                                View Details
                                            </span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <?php
                        endwhile;
                    } else {
                        echo "<div class='col-12 text-center py-5'><p class='text-muted fst-italic'>There are no products available in the gallery at this time.</p></div>";
                    }
                    $conn->close();
                    ?>
                </div>
                <div class="row mt-5 pt-4">
                    <div class="col-12">
                        <div class="custom-order-banner">
                            <div class="icon-wrapper mb-3">
                                <i class="fas fa-paint-brush fa-3x"></i>
                            </div>
                            <h3 class="fw-bolder">Dreaming of a Custom Bouquet?</h3>
                            <p class="lead" style="font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                                Don't hesitate to bring your dream bouquet to life. Our team here to craft a unique arrangement, just for you.
                            </p>
                            <a href="kostum_order.php" class="btn btn-dark btn-lg mt-3">
                                <i class="fas fa-cut me-2"></i>Create a Custom Order Now
                            </a>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <a href="index.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left me-2"></i> Return to Homepage</a>
                </div>
            </div>
        </section>
        
        <section id="contact-section" class="customize-contact-section py-5 section-custom-order-effect">
            <div class="container">
                <div class="row align-items-center justify-content-center mt-4">
                    <div class="col-lg-4 text-center mb-4 mb-lg-0 contact-image-container">
                        <img src="assets/custom-red.jpg" alt="Customize Flower Arrangement" class="img-fluid contact-image-custom">
                    </div>
                    <div class="col-lg-6 offset-lg-1">
                        <div class="contact-form-and-links-container form-with-bg-icon p-4 p-md-5">
                            <h4 class="text-center mb-4" style="font-family: 'Cooper Black'; font-size: 2rem;">Send Us Messages</h4>
                            <?php if ($index_contact_error): ?><div class="alert alert-danger" role="alert"><?php echo $index_contact_error; ?></div><?php endif; ?>
                            <?php if ($index_contact_success): ?><div class="alert alert-success" role="alert"><?php echo htmlspecialchars($index_contact_success); ?></div><?php endif; ?>

                            <form id="indexContactForm" action="proses_kontak.php" method="POST" class="mb-4">
                                <input type="hidden" name="source_page" value="index_form_submit">
                                <?php if ($is_any_user_logged_in && ($current_fullname || $current_username)): ?>
                                    <input type="hidden" name="nama_pengirim" value="<?php echo htmlspecialchars($current_fullname ?: $current_username); ?>">
                                <?php endif; ?>
                                <input type="hidden" name="subjek_pesan" value="Pesan dari Halaman Utama (Customize Section)">
                                <div class="mb-3">
                                    <input type="email" name="email_pengirim" class="form-control form-control-lg contact-input contact-input-email" placeholder="Your e-mail" required value="<?php echo htmlspecialchars($form_data_idx['email_pengirim'] ?? $default_email_idx); ?>">
                                </div>
                                <div class="mb-4 message-field-wrapper">
                                    <textarea class="form-control form-control-lg contact-input contact-input-message" name="isi_pesan" rows="4" placeholder="Message" required><?php echo htmlspecialchars($form_data_idx['isi_pesan'] ?? ''); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-submit btn-lg">Submit Message!</button>
                            </form>
                            <nav class="nav justify-content-center flex-wrap mb-3 footer-nav-links">
                                <a class="nav-link footer-link" href="index.php">Home</a>
                                <a class="nav-link footer-link" href="about.php">About</a>
                                <a class="nav-link footer-link" href="gallery_page.php">Gallery</a>
                                <a class="nav-link footer-link" href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact</a>
                            </nav>
                            <div class="social-icons text-center">
                                <a href="https://www.facebook.com/profile.php?id=100006681615415" target="_blank" class="social-icon-link mx-2"><i class="fab fa-facebook-f"></i></a>
                                <a href="https://www.threads.com/@dimaassatriaa1" target="_blank" class="social-icon-link mx-2"><i class="fab fa-threads"></i></a>
                                <a href="https://www.instagram.com/dimaassatriaa1" target="_blank" class="social-icon-link mx-2"><i class="fab fa-instagram"></i></a>
                                <a href="https://www.linkedin.com/in/dimas-jati-satria-26a794221/" target="_blank" class="social-icon-link mx-2"><i class="fab fa-linkedin"></i></a>
                                <a href="https://www.youtube.com/channel/UChZ_qhLgmlOOPfN8LJeAByw" target="_blank" class="social-icon-link mx-2"><i class="fab fa-youtube"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <footer class="actual-footer py-3 bg-light">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title internal-page-header" id="contactModalLabel" style="margin-bottom: 0;">Contact Us</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                        <input type="hidden" name="source_page" value="modal_contact_ajax">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control contact-input" id="modal_nama_pengirim" name="nama_pengirim" required
                                       value="<?php echo $is_any_user_logged_in ? htmlspecialchars($current_fullname ?: $current_username) : ''; ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control contact-input" id="modal_email_pengirim" name="email_pengirim" required
                                       value="<?php echo $is_any_user_logged_in ? htmlspecialchars($current_email) : ''; ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan" class="form-label">Subject</label>
                            <input type="text" class="form-control contact-input" id="modal_subjek_pesan" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">The Messages <span class="text-danger">*</span></label>
                            <textarea class="form-control contact-input" id="modal_isi_pesan" name="isi_pesan" rows="4" style="border-radius: 20px !important;" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-submit">Send Messages</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        <?php if ($is_any_user_logged_in): ?>
            const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname ?: $current_username)); ?>";                        
            const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email)); ?>";
            if(document.getElementById('modal_nama_pengirim')) document.getElementById('modal_nama_pengirim').value = defaultNameModal;                                
            if(document.getElementById('modal_email_pengirim')) document.getElementById('modal_email_pengirim').value = defaultEmailModal;
        <?php endif; ?>
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>

</body>
</html>