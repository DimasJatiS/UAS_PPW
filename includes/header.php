
<?php
// Jika file header Anda belum memiliki session_start(), tambahkan di atas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bloomarie</title> <meta name="base-url" content="<?php echo BASE_URL; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo time(); ?>">
    </head>
<link rel="icon" href="assets/favicon.ico">
<header class="container-fluid sticky-top bg-white shadow-sm py-3">
    <div class="container">
        <nav class="d-flex justify-content-between align-items-center">
            <div class="nav-search search-container">
                <form action="gallery_page.php" method="GET" class="d-flex align-items-center" id="searchForm">
                    <input class="form-control me-2" type="search" id="liveSearchInput" name="search_query" placeholder="Search for a Bouquet..." aria-label="Search" style="display: none; width: 0;">
                    <a href="#" class="text-dark" id="searchIconToggle" title="Cari">
                        <i class="fas fa-search"></i>
                    </a>
                    <!--<button type="submit"></button> -->
                    </form>
                <div id="searchResultsDropdown" class="search-results-dropdown"></div>
            </div>

            <div class="nav-links-center d-flex align-items-center">
                <a href="gallery_page.php" class="nav-item-custom mx-3 mx-lg-4">Gallery</a>
                    <a href="index.php" class="logo-link mx-3 mx-lg-4">
                        <img src="assets/Asset 1.png" alt="Bloomarie Logo" class="main-logo">
                    </a>
                <a href="#" data-bs-toggle="modal" data-bs-target="#contactModal" class="nav-item-custom mx-3 mx-lg-4">Contact</a>
            </div>

            <div class="nav-user-cart d-flex align-items-center">
                <div class="nav-item dropdown me-3">
                    <a class="nav-link dropdown-toggle text-dark p-0" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" title="Akun Pengguna">
                        <i class="fas fa-user fa-2x"></i>
                    </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <?php if ($is_any_user_logged_in): ?>
                                <li><h6 class="dropdown-header">Halo, <?php echo htmlspecialchars($current_fullname ?: $current_username); ?></h6></li>
                                <?php if ($current_email): ?>
                                    <li><span class="dropdown-item-text fst-italic"><?php echo htmlspecialchars($current_email); ?></span></li>
                                <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="riwayat_pesanan.php">Purchase History</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $logout_url; ?>">Logout</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo $login_url; ?>">Login</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $register_url; ?>">Register</a></li>
                                <?php endif; ?>
                        </ul>
                </div>
                <div class="nav-cart">
                    <a href="cart_handler.php" class="text-dark position-relative" title="Keranjang Belanja">
                        <i class="fas fa-shopping-cart fa-2x"></i>
                        <?php if ($cart_item_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_item_count; ?>
                                <span class="visually-hidden">item di keranjang</span>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </nav>
    </div>
</header>