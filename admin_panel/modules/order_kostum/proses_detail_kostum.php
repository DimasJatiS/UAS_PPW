<?php
// Memulai sesi jika belum ada
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php');
}

$conn = connect_db();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
    $status_request = trim($_POST['status_request'] ?? '');
    $catatan_dari_toko = trim($_POST['catatan_dari_toko'] ?? '');
    
    $harga_produk = null;
    if (in_array($status_request, ['menunggu_verifikasi', 'menunggu_pembayaran'])) {
        $harga_produk = (float)($_POST['total_harga'] ?? 0);
    }


    if ($request_id <= 0) {
        $_SESSION['order_kostum_error_message'] = "ID Permintaan tidak valid.";
        redirect('index.php');
    }

    $allowed_statuses = ['diskusi', 'ditolak', 'dibatalkan', 'menunggu_pembayaran', 'menunggu_verifikasi', 'diproses', 'dikirim', 'selesai'];
    if (empty($status_request) || !in_array($status_request, $allowed_statuses)) {
        $_SESSION['order_kostum_error_message'] = "Status permintaan yang dipilih tidak valid.";
        redirect('detail_kostum.php?request_id=' . $request_id);
    }
    
    $conn->begin_transaction();

    try {

        if (in_array($status_request, ['menunggu_verifikasi', 'menunggu_pembayaran'])) {
            
            if ($harga_produk <= 0) {
                throw new Exception("Harga produk harus diisi dan lebih besar dari 0 untuk status ini.");
            }
            
            $total_final = $harga_produk; // Harga final adalah harga produk murni

            $stmt = $conn->prepare("UPDATE orderkostum SET status_request = ?, total_harga = ?, catatan_dari_toko = ? WHERE kostum_request_id = ?");
            if(!$stmt) throw new Exception("Prepare statement gagal: " . $conn->error);
            
            $stmt->bind_param("sdsi", $status_request, $total_final, $catatan_dari_toko, $request_id);
            
            $_SESSION['order_kostum_success_message'] = "Pesanan #K" . $request_id . " telah diupdate dengan total Rp " . number_format($total_final, 0, ',', '.');

        } else {
            // Kasus 2: Untuk semua update status lainnya (harga tidak diubah).
            $stmt = $conn->prepare("UPDATE orderkostum SET status_request = ?, catatan_dari_toko = ? WHERE kostum_request_id = ?");
            if(!$stmt) throw new Exception("Prepare statement gagal: " . $conn->error);
            
            $stmt->bind_param("ssi", $status_request, $catatan_dari_toko, $request_id);
            
            $_SESSION['order_kostum_success_message'] = "Status pesanan #K" . $request_id . " berhasil diperbarui menjadi '" . ucfirst(str_replace('_', ' ', $status_request)) . "'.";
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Gagal memperbarui data permintaan kostum: " . $stmt->error);
        }
        $stmt->close();

        $conn->commit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Gagal memproses update order kostum (ID: $request_id): " . $e->getMessage());
        $_SESSION['order_kostum_error_message'] = "Terjadi kesalahan: " . $e->getMessage();
    }
    
    $conn->close();
    redirect('detail_kostum.php?request_id=' . $request_id);

} else {
    redirect('../../admin_dashboard.php');
}
?>