<?php
require_once '../../db_connect.php';

if (!isAdminLoggedIn() || $_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('../../../login.php');
}
$conn = connect_db();

$payment_id = isset($_POST['payment_id']) ? (int)$_POST['payment_id'] : 0;
$action = $_POST['action'] ?? '';

if ($payment_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    redirect('index.php');
}

// Ambil detail pembayaran untuk mengetahui order_id atau request_id
$stmt_get = $conn->prepare("SELECT order_id, kostum_request_id FROM pembayaran WHERE payment_id = ?");
$stmt_get->bind_param("i", $payment_id);
$stmt_get->execute();
$payment = $stmt_get->get_result()->fetch_assoc();
$stmt_get->close();

if (!$payment) {
    $_SESSION['verification_message'] = "Data pembayaran tidak ditemukan.";
    redirect('index.php');
}

$order_id = $payment['order_id'];
$request_id = $payment['kostum_request_id'];
$new_status = '';
$message = '';

if ($action === 'approve') {
    $new_status = 'diproses';
    $message = "Pembayaran telah disetujui dan pesanan sedang diproses.";
} elseif ($action === 'reject') {
    // Jika ditolak, kembalikan statusnya agar pelanggan bisa bayar lagi
    $new_status = $order_id ? 'menunggu_pembayaran' : 'selesai_diskusi'; 
    $message = "Pembayaran ditolak. Silakan cek kembali data pembayaran Anda.";
}

// Update status di tabel yang sesuai
if ($order_id) {
    $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt_update->bind_param("si", $new_status, $order_id);
} elseif ($request_id) {
    $stmt_update = $conn->prepare("UPDATE orderkostum SET status_request = ? WHERE kostum_request_id = ?");
    $stmt_update->bind_param("si", $new_status, $request_id);
}

if (isset($stmt_update)) {
    $stmt_update->execute();
    $stmt_update->close();
    $_SESSION['verification_message'] = "Verifikasi berhasil. " . $message;
}

$conn->close();
redirect('index.php');
?>