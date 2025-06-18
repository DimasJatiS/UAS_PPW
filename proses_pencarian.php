<?php
// File: proses_pencarian.php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

// 1. Ambil kata kunci pencarian
$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
// Ambil halaman asal pengguna untuk redirect kembali jika tidak ditemukan
$return_page = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Jika query kosong, redirect kembali dengan pesan error
if (empty($search_query)) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'Silakan masukkan kata kunci pencarian.'];
    redirect($return_page);
}

// 2. Cek kesamaan persis pada NAMA PRODUK
$stmt_product = $conn->prepare("SELECT product_id FROM produk WHERE name = ? AND is_available = 1");
$stmt_product->bind_param("s", $search_query);
$stmt_product->execute();
$result_product = $stmt_product->get_result();

if ($result_product->num_rows === 1) {
    // Jika ditemukan, langsung ke halaman detail produk
    $product = $result_product->fetch_assoc();
    $stmt_product->close();
    redirect('detail_produk.php?product_id=' . $product['product_id']);
}
$stmt_product->close();


// 3. Jika tidak ada produk, cek kesamaan persis pada NAMA KATEGORI
$stmt_category = $conn->prepare("SELECT kategori_id FROM kategoriproduk WHERE nama_kategori = ?");
$stmt_category->bind_param("s", $search_query);
$stmt_category->execute();
$result_category = $stmt_category->get_result();

if ($result_category->num_rows === 1) {
    // Jika ditemukan, langsung ke galeri yang sudah difilter
    $category = $result_category->fetch_assoc();
    $stmt_category->close();
    redirect('gallery_page.php?kategori_id=' . $category['kategori_id']);
}
$stmt_category->close();


// 4. Jika tidak ditemukan sama sekali, set flash message dan kembali
$_SESSION['flash_message'] = [
    'type' => 'danger', 
    'message' => "Produk atau kategori '" . sanitize_output($search_query) . "' tidak ditemukan."
];

$conn->close();
redirect($return_page);

?>