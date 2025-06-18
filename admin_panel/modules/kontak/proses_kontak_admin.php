<?php
require_once '../../../db_connect.php';

// Pastikan hanya admin yang bisa mengakses
if (!isAdminLoggedIn()) {
    // Jika tidak ada session, mungkin akan ada error, jadi redirect saja
    redirect('../../../login.php');
}

// Hanya proses jika metodenya GET dan ada parameter yang diperlukan
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;

    if ($message_id <= 0) {
        $_SESSION['kontak_error_message'] = "Permintaan tidak valid. ID Pesan tidak ditemukan.";
        redirect('index.php');
    }

    $conn = connect_db();

    // Gunakan NOW() untuk mengisi waktu saat ini ke kolom deleted_at
    // Tambahkan "AND deleted_at IS NULL" untuk memastikan kita tidak menghapus ulang item yang sudah dihapus
    $sql = "UPDATE contact_messages SET deleted_at = NOW() WHERE message_id = ? AND deleted_at IS NULL";
    
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $message_id);
        
        if ($stmt->execute()) {
            // Cek apakah ada baris yang terpengaruh
            if ($stmt->affected_rows > 0) {
                $_SESSION['kontak_success_message'] = "Pesan berhasil dihapus.";
            } else {
                $_SESSION['kontak_error_message'] = "Gagal menghapus pesan. Pesan mungkin sudah dihapus atau tidak ditemukan.";
            }
        } else {
            $_SESSION['kontak_error_message'] = "Terjadi kesalahan saat mengeksekusi perintah hapus.";
            error_log("Execute failed (soft_delete): (" . $stmt->errno . ") " . $stmt->error);
        }
        $stmt->close();
    } else {
        $_SESSION['kontak_error_message'] = "Terjadi kesalahan pada sistem.";
        error_log("Prepare failed (soft_delete): (" . $conn->errno . ") " . $conn->error);
    }
    
    $conn->close();

} else {
    // Jika akses tidak valid, kembalikan ke index
    $_SESSION['kontak_error_message'] = "Aksi tidak diizinkan.";
}

// Redirect kembali ke halaman daftar pesan
redirect('index.php');
?>