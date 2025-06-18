<?php
require 'db_connect.php';
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$product_id = $_POST['product_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

if ($rating < 1 || $rating > 5 || empty($comment)) {
    header("Location: detail_produk.php?id_produk=$product_id&review=invalid");
    exit;
}

// Simpan review ke tabel
$stmt = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);
$stmt->execute();
$stmt->close();

header("Location: detail_produk.php?id_produk=$product_id&review=success");
exit;
?>
