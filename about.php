<?php
// ==================================================================
// BAGIAN 1: PONDASI TEKNIS (PERBAIKAN)
// ==================================================================

// Memanggil koneksi database & memulai sesi. Ini WAJIB ada di paling atas.
require_once 'db_connect.php'; 
$conn = connect_db();

// Inisialisasi variabel default
$cart_item_count = 0;
$is_any_user_logged_in = isCustomerLoggedIn();
$current_username = '';
$current_fullname = '';
$current_email = '';
$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

// Jika ada pelanggan yang login, ambil data spesifiknya
if ($is_any_user_logged_in) {
    $user_id = (int)$_SESSION['user']['user_id'];
    $current_username = $_SESSION['user']['username'];
    $current_fullname = $_SESSION['user']['nama_lengkap'];
    $current_email = $_SESSION['user']['email'];

    // Ambil jumlah item di keranjang
    $stmt_cart_count = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        if ($row_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_count['total_items'] ?? 0);
        }
        $stmt_cart_count->close();
    }
}

// Ambil pesan error/sukses untuk form kontak di #contact-section
$current_email = $_SESSION['user']['email'] ?? ''; // Pastikan ini ada di session
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

$conn->close();
// Inisialisasi variabel untuk layout zigzag
$i = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tentang Bloomarie - Kisah Kami</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">

</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">

    <?php
    include 'includes/header.php'; 
    ?>

    <main class="page-section-container">
        <section class="py-5">
            <div class="container">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2.5rem, 8vw, 5rem); line-height:1.1;">About Bloomarie</h1>
                    <p class="lead" style="color: var(--text-medium);">Every Flower Tells a Story</p>
                </div>

                <div class="row gx-lg-5 align-items-center mb-5 pb-5">
                    <div class="col-md-6">
                        <h2 class="section-title mb-3">Our Story: Born from Love</h2>
                        <p>Welcome to the world of Bloomarie, a place where every floral arrangement is not only crafted by hand, but also grown with heart. We believe that flowers are the most beautiful way to deliver a message, celebrate a moment, and bring a little piece of nature’s magic into your life.</p>
                        <p>Bloomarie was born from a deep and profound love for flowers. It’s more than a business; it’s the extension of a simple hobby that blossomed into a passion. It all began in a small backyard garden, where I spent my time nurturing all kinds of flowers—from classic roses to charming <em>wildflowers</em>.</p>
                    </div>
                    <div class="col-md-6">
                        <img src="assets/garden.jpg" alt="Bloomarie Flowers Garden" class="img-fluid rounded-xl shadow-strong">
                    </div>
                </div>

                <div class="row gx-lg-5 align-items-center mb-5 pb-5 flex-md-row-reverse">
                    <div class="col-md-6">
                        <h2 class="section-title mb-3">From Our Garden, Directly to You</h2>
                        <p>What makes Bloomarie special is the source of all the beauty we offer. We are not just another flower shop. Every single stem you receive comes directly from our private garden. This is a "garden-to-hand" model that we proudly practice.</p>
                    </div>
                    <div class="col-md-6">
                        <img src="assets/florist.jpg" alt="Bloomarie Fresh Flower" class="img-fluid rounded-xl shadow-strong">
                    </div>
                </div>

                <section class="py-5 text-center bg-light rounded-xl mb-5">
                    <div class="container">
                        <h2 class="section-title mb-4">Our Philosophy</h2>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="icon-wrapper mb-3 text-accent-primary"><i class="fas fa-seedling fa-3x"></i></div>
                                <h5 class="fw-bold">Peak Freshness</h5>
                                <p class="text-medium">Flowers are picked on the day of delivery to guarantee maximum freshness and longevity when they arrive in your hands.</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="icon-wrapper mb-3 text-accent-primary"><i class="fas fa-palette fa-3x"></i></div>
                                <h5 class="fw-bold">personal Design</h5>
                                <p class="text-medium">Every bouquet is a work of art, specially arranged to reflect the uniqueness of each season and order.</p>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="icon-wrapper mb-3 text-accent-primary"><i class="fas fa-leaf fa-3x"></i></div>
                                <h5 class="fw-bold">Eco-Conscious</h5>
                                <p class="text-medium">By growing our own flowers, we minimize our carbon footprint and care for each plant using natural methods.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="row justify-content-center text-center mb-5 pb-5">
                    <div class="col-lg-8">
                        <img src="assets/the man.JPG" alt="Foto Profil Pendiri Bloomarie" class="img-fluid shadow-medium mb-4 founder-profile-image">
                        <h2 class="section-title">The Man</h2>
                        <p class="lead">Hello, I'm <strong> Dimas</strong>, the founder, gardener, and floral designer behind Bloomarie. For me, flowers are a way to communicate without words.</p>
                        <p class="text-medium">Simple mission: to translate your feelings into a beautiful and meaningful floral arrangement. Every order is an honor for me.</p>
                    </div>
                </div>

                <div class="row justify-content-center mb-5">
                    <div class="col-lg-10">
                        <h2 class="section-title text-center mb-4">What They Say</h2>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            <div class="col">
                                <div class="card h-100 shadow-soft border-0 rounded-lg p-3">
                                    <div class="card-body text-center">
                                        <div class="text-warning mb-2">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </div>
                                        <p class="card-text fst-italic">"The bouquet was absolutely beautiful and the flowers were so fresh! You can really see it was arranged with love. Thank you, Bloomarie!"</p>
                                        <p class="card-text mt-3"><strong class="text-dark">Coco</strong></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card h-100 shadow-soft border-0 rounded-lg p-3">
                                    <div class="card-body text-center">
                                        <div class="text-warning mb-2">
                                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                        </div>
                                        <p class="card-text fst-italic">"A very personal service. I was able to request a color palette and the result exceeded my expectations. I will definitely order again."</p>
                                        <p class="card-text mt-3"><strong class="text-dark">Caca</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="custom-order-banner">
                    <div class="icon-wrapper mb-3"><i class="fas fa-paint-brush fa-3x"></i></div>
                    <h3 class="fw-bolder">Ready to Find Your Perfect Bouquet?</h3>
                    <p class="lead" style="font-size: 1.1rem; max-width: 600px; margin-left: auto; margin-right: auto;">
                        Let Bloomarie help you tell your story through flowers.
                    </p>
                    <a href="gallery_page.php" class="btn btn-dark btn-lg mt-3">
                        <i class="fas fa-th-large me-2"></i>Discover our Gallery
                    </a>
                </div>
            </div>
        </section>
    </main>

    <?php
    include 'includes/footer.php'; // Sertakan footer
    ?>

    </div> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
</body>
</html>