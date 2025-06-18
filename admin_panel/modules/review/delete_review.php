<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}
$conn = connect_db();

if (isset($_GET['review_id'])) {
    $review_id = (int)$_GET['review_id'];

    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->bind_param("i", $review_id);

    if ($stmt->execute()) {
        $_SESSION['review_success_message'] = "Ulasan berhasil dihapus.";
    } else {
        $_SESSION['review_error_message'] = "Gagal menghapus ulasan.";
    }
    $stmt->close();
} else {
    $_SESSION['review_error_message'] = "ID Ulasan tidak valid.";
}

$conn->close();
redirect('index.php');
?>