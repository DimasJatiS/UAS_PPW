<!-- <?php
// uas/admin_panel/templates/sidebar.php

// Tidak perlu require_once db_connect.php di sini, sudah di admin_dashboard.php.
// Pastikan SITE_URL sudah didefinisikan di admin_dashboard.php (dari config.php)

// Fallback untuk SITE_URL jika belum didefinisikan (untuk development, jangan di produksi)
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/uas/'); // Ganti dengan URL dasar proyek Anda
}

// Pastikan $_SESSION['user'] sudah didefinisikan sebelum mengaksesnya
// Jika sidebar diakses terpisah atau sebelum admin_dashboard.php
if (!isset($_SESSION['user'])) {
    // Sebagai fallback, jika somehow session user tidak ada saat sidebar di-load,
    // (meskipun seharusnya tidak terjadi jika admin_dashboard.php diakses pertama)
    // bisa jadi pesan error atau redirect. Namun, untuk UI, kita bisa asumsikan ada.
}

?>
<div class="sidebar">
    <h3>Bloomarie Admin</h3>
    <ul>
        <?php
        // Dapatkan nama file saat ini dan parameter 'page' untuk menandai menu aktif
        $current_page = basename($_SERVER['PHP_SELF']); // e.g., "admin_dashboard.php"
        $page_param = $_GET['page'] ?? 'dashboard'; // Default ke 'dashboard' jika tidak ada param
        ?>

        <li>
            <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php" class="<?php echo ($current_page == 'admin_dashboard.php' && $page_param == 'dashboard') ? 'active' : ''; ?>">
                📊 Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=categories" class="<?php echo ($current_page == 'admin_dashboard.php' && $page_param == 'categories') ? 'active' : ''; ?>">
                🗂️ Kategori Produk
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=products" class="<?php echo ($current_page == 'admin_dashboard.php' && $page_param == 'products') ? 'active' : ''; ?>">
                💐 Produk (Buket)
            </a>
        </li>
        <li>
            <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=orders" class="<?php echo ($current_page == 'admin_dashboard.php' && $page_param == 'orders') ? 'active' : ''; ?>">
                🛒 Pesanan
            </a>
        </li>
        <?php
        // Hanya superadmin yang bisa kelola user
        if (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'superadmin'):
        ?>
        <li>
            <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=users" class="<?php echo ($current_page == 'admin_dashboard.php' && $page_param == 'users') ? 'active' : ''; ?>">
                👥 Pengguna
            </a>
        </li>
        <?php endif; ?>
        <li><a href="<?php echo SITE_URL; ?>logout.php">➡️ Logout</a></li>
    </ul>
</div> -->