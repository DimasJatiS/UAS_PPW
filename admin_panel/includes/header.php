<?php
    session_start();
?>

<header class="container-fluid sticky-top bg-white shadow-sm py-3">
            <div class="container">
                <nav class="d-flex justify-content-between align-items-center">
                    <div class="nav-search">
                        <form action="search_result.php" method="GET" class="d-flex align-items-center" id="searchForm">
                            <input class="form-control me-2" type="search" name="query" placeholder="Cari produk" aria-label="Search" id="searchInput" style="display: none; width: 0; transition: width 0.3s ease-in-out;">
                            <a href="#" class="text-dark" id="searchIconToggle" title="Cari"><i class="fas fa-search fa-2x"></i></a>
                        </form>
                    </div>

                    <div class="nav-links-center d-flex align-items-center">
                        <a href="gallery_page.php" class="nav-item-custom mx-3 mx-lg-4">Gallery</a>
                        <a href="index.php" class="logo-link mx-3 mx-lg-4">
                            <img src="https://placehold.co/115x136/D9D9D9/333?text=LOGO" alt="Bloomarie Logo" class="main-logo">
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
                                    <li><a class="dropdown-item" href="riwayat_pesanan.php">Riwayat Pesanan</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $logout_url; ?>">Logout</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?php echo $login_url; ?>">Login</a></li>
                                    <li><a class="dropdown-item" href="<?php echo $register_url; ?>">Register</a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="nav-cart">
                            <a href="<?php echo $cart_page_url; ?>" class="text-dark position-relative" title="Keranjang Belanja">
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