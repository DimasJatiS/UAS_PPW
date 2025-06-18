<?php
// File: uas/cart_handler.php
session_start();
require_once 'db_connect.php'; // Pastikan path ini benar dari root



// Cek apakah pengguna adalah pelanggan yang sudah login
// if (isset($_SESSION['admin_user_id'])) {
    
//     // Admin tidak punya keranjang belanja. Siapkan pesan info.
//     $_SESSION['flash_message'] = [
//         'type' => 'info', // Tipe 'info' (biru) atau 'danger' (merah)
//         'message' => 'Fitur keranjang belanja hanya untuk pelanggan. Anda sedang login sebagai admin.'
//     ];

//     // Arahkan admin kembali ke halaman yang relevan (misalnya halaman utama atau dashboard admin)
//     // dan hentikan eksekusi skrip.
//     redirect('index.php');

// } 
// JIKA BUKAN ADMIN, LANJUTKAN LOGIKA LAMA
if (isCustomerLoggedIn()) {
    
    // Logika untuk pelanggan yang sudah login (TIDAK ADA PERUBAHAN)
    $conn = connect_db();
    $user_id = (int)$_SESSION['user']['user_id'];
    
    $stmt = $conn->prepare("SELECT SUM(kuantitas) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart_count = (int)$stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $conn->close();

    if ($cart_count > 0) {
        redirect('checkout.php');
    } else {
        $_SESSION['flash_message'] = [
            'type' => 'info',
            'message' => 'Your shopping cart is empty. Please select a product first.'
        ];
        redirect('gallery_page.php');
    }

} else {
    
    // Logika untuk pengunjung yang belum login (TIDAK ADA PERUBAHAN)
    $_SESSION['flash_message'] = [
        'type' => 'warning',
        'message' => 'You must login first to view your shopping cart.'
    ];
    
    $_SESSION['redirect_url'] = 'gallery_page.php'; 
    
    redirect('gallery_page.php');
}
?>
