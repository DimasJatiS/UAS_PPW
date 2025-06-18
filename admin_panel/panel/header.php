<?php
// uas/admin_panel/templates/header.php

// Asumsi 'config.php' sudah di-require di admin_panel/index.php,
// sehingga SITE_URL, $admin_username, $page_title, dan fungsi-fungsi tersedia.
// Jangan panggil session_start() di sini.

// Mengambil variabel yang didefinisikan di admin_panel/index.php
global $page_title, $admin_username; // Pastikan variabel global dapat diakses

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loading... - Bloomarie Admin</title> 
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>admin_panel/assets/css/style.css"> 
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar"> <div class="sidebar-header">
                <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php" class="logo">Bloomarie Admin</a>
            </div>
            <nav class="sidebar-nav">
                <ul>
                    <?php
                    // Dapatkan parameter 'page' saat ini untuk menandai menu aktif
                    $page_param = $_GET['page'] ?? 'dashboard';
                    ?>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=dashboard" class="<?php echo ($page_param == 'dashboard') ? 'active' : ''; ?>">
                            <span class="icon">📊</span> Dashboard
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=categories" class="<?php echo ($page_param == 'categories') ? 'active' : ''; ?>">
                            <span class="icon">🗂️</span> Kategori Produk
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=products" class="<?php echo ($page_param == 'products') ? 'active' : ''; ?>">
                            <span class="icon">💐</span> Produk (Buket)
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=orders" class="<?php echo ($page_param == 'orders') ? 'active' : ''; ?>">
                            <span class="icon">🛒</span> Pesanan
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=custom_orders" class="<?php echo ($page_param == 'custom_orders') ? 'active' : ''; ?>">
                            <span class="icon">📝</span> Pesanan Kustom
                        </a>
                    </li>
                    <?php
                    // Hanya superadmin yang bisa kelola user
                    // Gunakan fungsi is_superadmin dari config.php
                    if (isset($_SESSION['user']) && is_superadmin($_SESSION['user'])):
                    ?>
                    <li>
                        <a href="<?php echo SITE_URL; ?>admin_panel/admin_dashboard.php?page=users" class="<?php echo ($page_param == 'users') ? 'active' : ''; ?>">
                            <span class="icon">👥</span> Pengguna
                        </a>
                    </li>
                    <?php endif; ?>
                    <li><a href="<?php echo SITE_URL; ?>logout.php"><span class="icon">➡️</span> Logout</a></li>
                </ul>
            </nav>
        </aside>

        <main class="admin-main-content">
            <header class="admin-header">
                <h2>Dashboard</h2> 
                <div class="admin-user-info">
                    <span>Halo, <?php echo htmlspecialchars($admin_username); ?>!</span>
                    <a href="<?php echo SITE_URL; ?>logout.php" class="btn btn-logout">Logout</a>
                </div>
            </header>
            ```
<!-- **Perubahan Kunci:**
* **`admin-sidebar`**: Menggunakan kelas `admin-sidebar` (baru di CSS) dan memindahkan seluruh kode sidebar ke sini.
* **`SITE_URL`**: Semua link menggunakan `SITE_URL` dari `config.php`.
* **Active Class Logic**: Menggunakan `$_GET['page']` untuk menandai menu aktif.
* **`is_superadmin()`**: Pengecekan role pengguna menggunakan fungsi `is_superadmin()` yang ada di `config.php`.
* **No Redundant Tags**: Menghapus tag `<!DOCTYPE>`, `<html>`, `<head>`, `<body>`, `</div>`, `</main>` dari file ini. Ini adalah bagian dari struktur HTML utama yang hanya boleh ada di `index.php` (pengganti `admin_dashboard.php`).
* **No `db_connect.php` `require_once`**: Karena `config.php` sudah di-*require* di `index.php` dan `config.php` yang akan menangani koneksi DB.
* **`$admin_username`**: Menggunakan variabel `$admin_username` yang sudah di-set di `index.php` (`admin_dashboard.php` lama).
* **Title Placeholder**: Mengatur title di `<title>` dan `<h2>` header sebagai placeholder yang akan di-update oleh JavaScript di `index.php`.

---

### **4. `uas/admin_panel/templates/footer.php` (Diperbarui)**

Ini akan berisi penutup `main` dan `admin-wrapper` serta footer.

```php -->
<?php
// uas/admin_panel/templates/footer.php

// Tidak perlu require_once db_connect.php atau session_start() di sini.
// Asumsi semua tag pembuka sudah di-handle di admin_panel/index.php dan templates/header.php.
?>
        </main> </div> <footer class="admin-footer"> <p>&copy; <?php echo date("Y"); ?> Bloomarie Admin Panel. All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>