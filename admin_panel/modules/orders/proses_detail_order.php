<?php
session_start();
require_once '../../../db_connect.php';

if (!isAdminLoggedIn() || $_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('../../../login.php');
}

$conn = connect_db();

$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
$status = trim($_POST['status'] ?? '');

// Validasi input
if ($order_id <= 0) {
    $_SESSION['order_error_message'] = "ID Pesanan tidak valid.";
    redirect('index.php');
}

$allowed_statuses = ['menunggu_pembayaran', 'menunggu_verifikasi', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];
if (empty($status) || !in_array($status, $allowed_statuses)) {
    $_SESSION['order_error_message'] = "Status yang dipilih tidak valid.";
    redirect('detail_order.php?order_id=' . $order_id);
}

// Update status di database
$stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        $_SESSION['order_success_message'] = "Status pesanan #" . $order_id . " berhasil diperbarui menjadi '" . str_replace('_', ' ', $status) . "'.";
    } else {
        $_SESSION['order_success_message'] = "Tidak ada perubahan pada status pesanan #" . $order_id . ".";
    }
} else {
    $_SESSION['order_error_message'] = "Gagal memperbarui status pesanan.";
}

$stmt->close();
$conn->close();

redirect('detail_order.php?order_id=' . $order_id);
?>