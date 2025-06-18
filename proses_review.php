<?php
// File: proses_review.php
session_start();
require_once 'db_connect.php';

// Hanya proses jika metode POST dan pengguna sudah login
if (!isCustomerLoggedIn() || $_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('index.php');
}

$conn = connect_db();

// Ambil data dari formulir
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$user_id = (int)$_SESSION['user']['user_id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validasi dasar
if ($product_id <= 0 || $rating < 1 || $rating > 5) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Terjadi kesalahan. Data ulasan tidak valid.'];
    redirect('detail_produk.php?product_id=' . $product_id);
}

// Validasi terpenting: Periksa lagi apakah pengguna benar-benar berhak memberi ulasan
// Cari order 'selesai' yang mengandung produk ini dan BELUM pernah di-review oleh user ini untuk produk ini.
$sql_check = "SELECT o.order_id
              FROM orders o
              JOIN orderdetails od ON o.order_id = od.order_id
              WHERE o.user_id = ? 
              AND od.product_id = ? 
              AND o.status = 'selesai'
              AND NOT EXISTS (
                  SELECT 1 FROM reviews r 
                  WHERE r.user_id = o.user_id 
                  AND r.product_id = od.product_id
                  AND r.order_id = o.order_id
              )
              LIMIT 1";

$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $user_id, $product_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$valid_order = $result_check->fetch_assoc();
$stmt_check->close();

if (!$valid_order) {
    $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'You can not review this product or you already reviewed one.'];
    redirect('detail_produk.php?product_id=' . $product_id);
}

$order_id_for_review = $valid_order['order_id'];

// Jika semua validasi lolos, masukkan ulasan ke database
$sql_insert = "INSERT INTO reviews (product_id, user_id, order_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iiiis", $product_id, $user_id, $order_id_for_review, $rating, $comment);

if ($stmt_insert->execute()) {
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Thank you! Your review has been send.'];
} else {
    // Error jika mencoba memasukkan duplikat (karena UNIQUE KEY di database)
    if ($conn->errno == 1062) {
        $_SESSION['flash_message'] = ['type' => 'warning', 'message' => 'You already reviewed this procut from this order.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Something happend when you review.'];
        error_log("Gagal menyimpan review: " . $stmt_insert->error);
    }
}

$stmt_insert->close();
$conn->close();

redirect('detail_produk.php?product_id=' . $product_id . '#reviews-section');
?>