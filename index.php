<<<<<<< HEAD
<?php
session_start();
require_once 'db_connect.php'; // Pastikan ini menginisialisasi $conn dengan mysqli

// Jika db_connect.php hanya mendefinisikan fungsi connect_db(), panggil di sini.
// Jika $conn sudah global setelah include, baris ini tidak perlu.
if (!isset($conn) || !$conn) { // Cek apakah $conn ada dan valid
    $conn = connect_db(); // Panggil fungsi untuk mendapatkan objek koneksi mysqli
}

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLOOMARIE - Landing Page</title>
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
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/flash_message.php'; ?>

        <section id="hero-section" class="hero-section text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="hero-title-background my-4">
                <h1 class="bloomarie-title">BLOOMARIE</h1>
            </div>
            <img src="assets/hero.jpg" alt="Main Banner" class="img-fluid hero-banner-image">
        </section>

        <section id="featured-product-section" class="featured-product-section py-5">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> text-center" role="alert">
                    <?php 
                        echo sanitize_output($_SESSION['flash_message']['message']); 
                        unset($_SESSION['flash_message']); // Hapus pesan setelah ditampilkan
                    ?>
                </div>
            <?php endif; ?>
            <div class="container text-center">
                <h2 class="section-title">Featured Product</h2>
                <div class="title-underline">_______</div>
                <div class="row mt-5 justify-content-center g-4">
                    <?php
                    $featured_products = [];
                    // Pastikan $conn ada dan merupakan objek koneksi yang valid
                    if (isset($conn) && $conn) {
                        $sql_featured = "SELECT p.product_id, p.name, p.harga, p.stok, p.foto_produk, k.nama_kategori
                                            FROM produk p
                                            JOIN kategoriproduk k ON p.kategori_id = k.kategori_id
                                            WHERE p.is_available = 1
                                            ORDER BY RAND() LIMIT 3";
                        $result_featured = $conn->query($sql_featured); // Menggunakan mysqli query

                        if ($result_featured && $result_featured->num_rows > 0) {
                            while($product_row = $result_featured->fetch_assoc()){
                                $featured_products[] = $product_row;
                            }
                        } elseif ($result_featured === false) {
                            error_log("mysqli query failed for featured products: " . $conn->error);
                        }
                         // $result_featured->close(); // Jika menggunakan query() dan mengambil semua hasil, bisa ditutup di sini atau biarkan PHP yg handle
                    } else {
                        echo "<p class='col-12'>Koneksi database gagal, tidak dapat memuat produk unggulan.</p>";
                    }


                    if (!empty($featured_products)) {
                        foreach ($featured_products as $product) {
                    ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="product-card">

                                    <a href="detail_produk.php?product_id=<?php echo $product['product_id']; ?>" class="product-image-link">
                                        <div class="product-image-wrapper">
                                            <img src="<?php echo htmlspecialchars(!empty($product['foto_produk']) ? $product['foto_produk'] : 'https://placehold.co/453x454/cccccc/333?text=No+Image'); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                    </a>

                                    <div class="product-info">
                                        <div class="product-details">
                                            <h3 class="product-name">
                                                <a href="detail_produk.php?product_id=<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                            </h3>
                                        </div>
                                    </div> </div> </div> <?php
                        } // Akhir dari foreach
                    } elseif (isset($conn) && $conn) {
                        // Pesan jika tidak ada produk unggulan
                        echo "<p class='col-12 text-center'>Produk unggulan akan segera hadir!</p>";
                    }
                    ?>
                </div>
            </div>
        </section>

        <section id="gallery-section" class="discover-gallery-section py-5 overflow-hidden">
            <div class="container text-center">
                <div class="gallery-title-wrapper position-relative d-inline-block mx-auto">
                    <img src="assets/discover.png" alt="Gallery title background" class="gallery-title-bg-image"><a href="gallery_page.php" class="text-decoration-none gallery-title-link">
                        <h2 class="gallery-title display-1">Discover Our Gallery</h2>
                    </a>
                </div>
                <div class="row align-items-center mt-5 text-start">
                    <div class="col-lg-5 text-center text-lg-start mb-4 mb-lg-0">
                        <h3 class="gallery-subtitle mb-4">A Note from Bloomarie</h3>
                        <p class="gallery-text">
                            Each bouquet is a special tale, crafted for the people you cherish.<br>
                            Choice blooms, arranged with heartfelt dedication, create the perfect bouquet to express your love, longing, and hope.
                        </p>
                    </div>
                    <div class="col-lg-7">
                        <img src="assets/discover-2.jpg" alt="Gallery Image" class="img-fluid rounded-4 shadow">
                    </div>
                </div>
            </div>
        </section>
    
        <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>

</body>
=======
<?php
session_start();
require_once 'db_connect.php'; // Pastikan ini menginisialisasi $conn dengan mysqli

// Jika db_connect.php hanya mendefinisikan fungsi connect_db(), panggil di sini.
// Jika $conn sudah global setelah include, baris ini tidak perlu.
if (!isset($conn) || !$conn) { // Cek apakah $conn ada dan valid
    $conn = connect_db(); // Panggil fungsi untuk mendapatkan objek koneksi mysqli
}

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

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLOOMARIE - Landing Page</title>
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
        <?php include 'includes/header.php'; ?>
        <?php include 'includes/flash_message.php'; ?>

        <section id="hero-section" class="hero-section text-center d-flex flex-column justify-content-center align-items-center" style="min-height: 80vh;">
            <div class="hero-title-background my-4">
                <h1 class="bloomarie-title">BLOOMARIE</h1>
            </div>
            <img src="assets/hero.jpg" alt="Main Banner" class="img-fluid hero-banner-image">
        </section>

        <section id="featured-product-section" class="featured-product-section py-5">
            <?php if (isset($_SESSION['flash_message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['flash_message']['type']; ?> text-center" role="alert">
                    <?php 
                        echo sanitize_output($_SESSION['flash_message']['message']); 
                        unset($_SESSION['flash_message']); // Hapus pesan setelah ditampilkan
                    ?>
                </div>
            <?php endif; ?>
            <div class="container text-center">
                <h2 class="section-title">Featured Product</h2>
                <div class="title-underline">_______</div>
                <div class="row mt-5 justify-content-center g-4">
                    <?php
                    $featured_products = [];
                    // Pastikan $conn ada dan merupakan objek koneksi yang valid
                    if (isset($conn) && $conn) {
                        $sql_featured = "SELECT p.product_id, p.name, p.harga, p.stok, p.foto_produk, k.nama_kategori
                                            FROM produk p
                                            JOIN kategoriproduk k ON p.kategori_id = k.kategori_id
                                            WHERE p.is_available = 1
                                            ORDER BY RAND() LIMIT 3";
                        $result_featured = $conn->query($sql_featured); // Menggunakan mysqli query

                        if ($result_featured && $result_featured->num_rows > 0) {
                            while($product_row = $result_featured->fetch_assoc()){
                                $featured_products[] = $product_row;
                            }
                        } elseif ($result_featured === false) {
                            error_log("mysqli query failed for featured products: " . $conn->error);
                        }
                         // $result_featured->close(); // Jika menggunakan query() dan mengambil semua hasil, bisa ditutup di sini atau biarkan PHP yg handle
                    } else {
                        echo "<p class='col-12'>Koneksi database gagal, tidak dapat memuat produk unggulan.</p>";
                    }


                    if (!empty($featured_products)) {
                        foreach ($featured_products as $product) {
                    ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="product-card">

                                    <a href="detail_produk.php?product_id=<?php echo $product['product_id']; ?>" class="product-image-link">
                                        <div class="product-image-wrapper">
                                            <img src="<?php echo htmlspecialchars(!empty($product['foto_produk']) ? $product['foto_produk'] : 'https://placehold.co/453x454/cccccc/333?text=No+Image'); ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                        </div>
                                    </a>

                                    <div class="product-info">
                                        <div class="product-details">
                                            <h3 class="product-name">
                                                <a href="detail_produk.php?product_id=<?php echo $product['product_id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                                            </h3>
                                        </div>
                                    </div> </div> </div> <?php
                        } // Akhir dari foreach
                    } elseif (isset($conn) && $conn) {
                        // Pesan jika tidak ada produk unggulan
                        echo "<p class='col-12 text-center'>Produk unggulan akan segera hadir!</p>";
                    }
                    ?>
                </div>
            </div>
        </section>

        <section id="gallery-section" class="discover-gallery-section py-5 overflow-hidden">
            <div class="container text-center">
                <div class="gallery-title-wrapper position-relative d-inline-block mx-auto">
                    <img src="assets/discover.png" alt="Gallery title background" class="gallery-title-bg-image"><a href="gallery_page.php" class="text-decoration-none gallery-title-link">
                        <h2 class="gallery-title display-1">Discover Our Gallery</h2>
                    </a>
                </div>
                <div class="row align-items-center mt-5 text-start">
                    <div class="col-lg-5 text-center text-lg-start mb-4 mb-lg-0">
                        <h3 class="gallery-subtitle mb-4">A Note from Bloomarie</h3>
                        <p class="gallery-text">
                            Each bouquet is a special tale, crafted for the people you cherish.<br>
                            Choice blooms, arranged with heartfelt dedication, create the perfect bouquet to express your love, longing, and hope.
                        </p>
                    </div>
                    <div class="col-lg-7">
                        <img src="assets/discover-2.jpg" alt="Gallery Image" class="img-fluid rounded-4 shadow">
                    </div>
                </div>
            </div>
        </section>
    
        <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>

</body>
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
</html>