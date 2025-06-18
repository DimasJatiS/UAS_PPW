<?php
// uas/admin_panel/index.php

// Pastikan error reporting diaktifkan untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Mulai sesi PHP (harus paling awal)
session_start();

// Memuat konfigurasi admin dan fungsi-fungsi penting
require_once 'config.php'; // Ini akan me-require db_connect.php melalui get_pdo_connection()

// Memastikan admin sudah login
require_login(); 

// Router sederhana berdasarkan parameter 'page'
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// Ambil informasi user dari sesi untuk ditampilkan di header/sidebar
$admin_username = $_SESSION['user']['username'] ?? 'Admin';
$admin_role = $_SESSION['user']['role'] ?? 'N/A';

// --- Bagian HTML Utama ---
// Header, sidebar, dan pembuka tag HTML utama akan dimuat dari templates/header.php
// $page_title akan di-set di dalam switch statement, lalu di-update via JS di akhir.
include_once 'templates/header.php'; 

echo '<div class="admin-page-content">'; // Memulai konten area

// Tampilkan pesan flash jika ada
display_flash_message();

// Memuat konten modul berdasarkan parameter 'page'
switch ($page) {
    case 'products':
        $page_title = 'Manajemen Produk';
        include_once 'modules/products/list.php';
        break;
    case 'add_product':
        $page_title = 'Tambah Produk Baru';
        include_once 'modules/products/add.php';
        break;
    case 'edit_product': // Tambahkan ini jika Anda punya fitur edit
        $page_title = 'Edit Produk';
        include_once 'modules/products/edit.php'; // Anda perlu membuat file ini
        break;
    case 'categories':
        $page_title = 'Manajemen Kategori';
        include_once 'modules/categories/list.php';
        break;
    case 'add_category':
        $page_title = 'Tambah Kategori Baru';
        include_once 'modules/categories/add.php';
        break;
    case 'users':
        $page_title = 'Manajemen Pengguna';
        // Hanya superadmin yang bisa kelola user
        if (is_superadmin($_SESSION['user'])) { // Pengecekan role menggunakan fungsi
            include_once 'modules/users/list.php';
        } else {
            include_once 'pages/access_denied.php';
        }
        break;
    case 'view_user': 
        $page_title = 'Detail Pengguna';
        if (is_superadmin($_SESSION['user'])) {
            include_once 'modules/users/view.php';
        } else {
            include_once 'pages/access_denied.php';
        }
        break;
    case 'orders':
        $page_title = 'Manajemen Pesanan';
        include_once 'modules/orders/list.php';
        break;
    case 'custom_orders': // Kasus baru untuk custom order
        $page_title = 'Manajemen Pesanan Kustom';
        include_once 'modules/orders/custom_orders_list.php';
        break;
    case 'dashboard':
    default:
        $page_title = 'Dashboard Utama';
        include_once 'pages/dashboard.php'; // Konten dashboard utama di file terpisah
        break;
}

echo '</div>'; // Menutup div.admin-page-content

// Perbarui judul halaman dan header di HTML menggunakan JavaScript
echo "<script>document.title = '" . htmlspecialchars($page_title) . " - Bloomarie Admin';</script>";
echo "<script>document.querySelector('.admin-header h2').textContent = '" . htmlspecialchars($page_title) . "';</script>";

// Footer dan penutup tag HTML akan dimuat dari templates/footer.php
include_once 'templates/header.php';
?>