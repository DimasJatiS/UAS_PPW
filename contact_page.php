<?php
session_start();
require_once 'db_connect.php'; // Menyediakan fungsi connect_db() dan helper

// Panggil fungsi connect_db() untuk menginisialisasi variabel $conn
$conn = connect_db();

// Logika header (jumlah item keranjang, status login)
$cart_page_url = 'gallery_page.php?from_checkout=empty_cart&source=contact_header';
$cart_item_count = 0;
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $user_id_session = $_SESSION['user']['user_id'];
    // Baris 10 yang diperbaiki: $conn sekarang sudah ada
    $stmt_cart_count = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id_session);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        if ($row_count = $result_cart_count->fetch_assoc()) {
            $cart_item_count = (int)($row_count['total_items'] ?? 0);
        }
        $stmt_cart_count->close();
    } else {
        error_log("Gagal prepare statement untuk cart count di contact_page.php: " . $conn->error);
    }
}
if ($cart_item_count > 0) $cart_page_url = 'checkout.php';


$error_message = $_SESSION['contact_error'] ?? '';
unset($_SESSION['contact_error']);
$success_message = $_SESSION['contact_success'] ?? '';
unset($_SESSION['contact_success']);
$form_data = $_SESSION['form_data_contact'] ?? [];
unset($_SESSION['form_data_contact']);

// Pre-fill email and name if user is logged in
$default_name = '';
$default_email = '';
if (isset($_SESSION['user'])) {
    $default_name = $_SESSION['user']['nama_lengkap'] ?? ($_SESSION['user']['username'] ?? '');
    $default_email = $_SESSION['user']['email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="id">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hubungi Kami - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body class="internal-page-body d-flex flex-column" style="min-height: 100vh;">

    <header class="container-fluid sticky-top bg-white shadow-sm py-3">
        <div class="container">
            <nav class="d-flex justify-content-between align-items-center">
                <div class="nav-search">
                    <form action="search_results.php" method="GET" class="d-flex align-items-center" id="searchForm">
                        <input class="form-control me-2" type="search" name="query" placeholder="Cari produk..." aria-label="Search" id="searchInput" style="display: none; width: 0; transition: width 0.3s ease-in-out;">
                        <a href="#" class="text-dark" id="searchIconToggle" title="Cari"><i class="fas fa-search fa-2x"></i></a>
                        <button type="submit" style="display:none;">Search</button>
                    </form>
                </div>
                <div class="nav-links-center d-flex align-items-center">
                    <a href="gallery_page.php" class="nav-item-custom mx-3 mx-lg-4">Gallery</a>
                    <a href="index.php" class="logo-link mx-3 mx-lg-4">
                        <img src="https://placehold.co/115x136/E0D8D1/4A3F35?text=LOGO" alt="Bloomarie Logo" class="main-logo">
                    </a>
                    <a href="contact_page.php" class="nav-item-custom mx-3 mx-lg-4 active">Contact</a>
                </div>
                <div class="nav-user-cart d-flex align-items-center">
                    <div class="nav-item dropdown me-3">
                        <a class="nav-link dropdown-toggle text-dark p-0" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Akun Pengguna"><i class="fas fa-user fa-2x"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if (isset($_SESSION['user'])): ?>
                                <li><h6 class="dropdown-header">Halo, <?php echo htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username']); ?></h6></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="login.php">Login</a></li>
                                <li><a class="dropdown-item" href="register.php">Register</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <div class="nav-cart">
                        <a href="<?php echo $cart_page_url; ?>" class="text-dark position-relative" title="Keranjang Belanja"><i class="fas fa-shopping-cart fa-2x"></i>
                            <?php if ($cart_item_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?php echo $cart_item_count; ?><span class="visually-hidden">item</span></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <div class="page-container form-focus my-auto"> <!-- my-auto untuk memusatkan secara vertikal -->
        <h1 class="internal-page-title" style="font-family=var(--primary-font)">BLOOMARIE</h1>
        <h2 class="internal-page-header">Hubungi Kami</h2>
        <p class="mb-4">Ada pertanyaan atau masukan? Jangan ragu untuk menghubungi kami melalui formulir di bawah ini.</p>

        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error_message; // Sudah di-sanitize jika perlu di proses_kontak ?></div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form action="proses_kontak.php" method="POST" class="text-start needs-validation" novalidate>
            <input type="hidden" name="source_page" value="contact_page">
            <div class="mb-3">
                <label for="nama_pengirim" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_pengirim" name="nama_pengirim" placeholder="Nama Lengkap" required value="<?php echo htmlspecialchars($form_data['nama_pengirim'] ?? $default_name); ?>">
                <div class="invalid-feedback">Nama Anda wajib diisi.</div>
            </div>
            <div class="mb-3">
                <label for="email_pengirim" class="form-label">Email Anda <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email_pengirim" name="email_pengirim" placeholder="contoh@email.com" required value="<?php echo htmlspecialchars($form_data['email_pengirim'] ?? $default_email); ?>">
                <div class="invalid-feedback">Format email tidak valid.</div>
            </div>
            <div class="mb-3">
                <label for="subjek_pesan" class="form-label">Subjek (Opsional)</label>
                <input type="text" class="form-control" id="subjek_pesan" name="subjek_pesan" placeholder="Subjek Pesan Anda" value="<?php echo htmlspecialchars($form_data['subjek_pesan'] ?? ''); ?>">
            </div>
            <div class="mb-4">
                <label for="isi_pesan" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                <textarea class="form-control" id="isi_pesan" name="isi_pesan" rows="5" placeholder="Tuliskan pesan Anda di sini..." required><?php echo htmlspecialchars($form_data['isi_pesan'] ?? ''); ?></textarea>
                <div class="invalid-feedback">Pesan tidak boleh kosong.</div>
            </div>
            <div class="text-center">
                <button type="submit" class="btn btn-submit btn-lg">Kirim Pesan</button>
            </div>
        </form>
        <div class="mt-4 nav-links-internal text-center">
            <a href="index.php">Kembali ke Beranda</a> |
            <a href="gallery_page.php">Lihat Galeri</a>
        </div>
    </div>

    <footer class="actual-footer py-3 bg-light">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Script untuk toggle search input (sama seperti di index.php)
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
                searchInput.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        if (this.value.trim() !== '') { searchForm.submit(); }
                    }
                });
                document.addEventListener('click', function(event) {
                    if (searchVisible && !searchForm.contains(event.target) && event.target !== searchIconToggle && !searchIconToggle.contains(event.target)) {
                        searchInput.style.width = '0';
                        setTimeout(() => { searchInput.style.display = 'none'; }, 300);
                        searchVisible = false;
                    }
                });
            }

            // Bootstrap form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>
