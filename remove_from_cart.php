<?php
session_start();
require_once 'db_connect.php';

// Panggil fungsi connect_db() untuk menginisialisasi variabel $conn
$conn = connect_db();

// Jika pengguna tidak login, hentikan proses
if (!isCustomerLoggedIn()) {
    // Sebaiknya tidak melakukan apa-apa atau redirect ke login
    header("Location: login.php");
    exit();
}

// Tentukan halaman kembali (default ke checkout.php)
$return_url = $_GET['return_url'] ?? 'checkout.php';
// Validasi sederhana untuk return_url agar aman
if (!in_array($return_url, ['checkout.php', 'gallery_page.php'])) {
    $return_url = 'checkout.php';
}


if (isset($_GET['cart_id'])) {
    $cart_id_to_remove = (int)$_GET['cart_id'];
    $user_id = (int)$_SESSION['user']['user_id'];

    // Pastikan koneksi berhasil
    if (!$conn) {
        header("Location: " . $return_url . "?error=db_connection_failed");
        exit();
    }

    // Baris 14 yang diperbaiki: $conn sekarang sudah ada
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");

    if (!$stmt) {
        error_log("Gagal prepare statement untuk hapus item: " . $conn->error);
        header("Location: " . $return_url . "?error=remove_failed_db");
        exit();
    }
    
    $stmt->bind_param("ii", $cart_id_to_remove, $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // Item berhasil dihapus
            $_SESSION['cart_info_message'] = "Item succesfully deleted from cart.";
            header("Location: " . $return_url);
            exit();
        } else {
            // Item tidak ditemukan di keranjang pengguna atau sudah dihapus
            $_SESSION['cart_error_message'] = "Failed to delete. Item not found in your cart.";
            header("Location: " . $return_url);
            exit();
        }
    } else {
        error_log("Gagal eksekusi statement untuk hapus item: " . $stmt->error);
        $_SESSION['cart_error_message'] = "Something went wrong while deleting item.";
        header("Location: " . $return_url);
        exit();
    }
    $stmt->close();
    $conn->close();
} else {
    $_SESSION['cart_error_message'] = "Request Invalid.";
    header("Location: " . $return_url);
    exit();
}
?>
