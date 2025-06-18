<?php
session_start();
require_once 'db_connect.php'; // Memuat definisi fungsi connect_db() dan helper lainnya

// Pastikan $conn diinisialisasi dengan memanggil fungsi connect_db()
$conn = connect_db();

if (!isset($_SESSION['user']) || !isset($_SESSION['user']['user_id'])) { // Periksa juga user_id untuk keamanan
    // Simpan halaman tujuan jika user belum login dan ingin menambahkan ke keranjang
    $_SESSION['redirect_url'] = "gallery_page.php?product_id=" . ($_GET['product_id'] ?? ''); // Atau halaman detail produk jika ada
    $_SESSION['login_message'] = "You must login to add items to cart.";
    header("Location: login.php"); // Arahkan ke halaman login
    exit();
}

if (isset($_GET['product_id'])) {
    $product_id = (int)$_GET['product_id'];
    $user_id = (int)$_SESSION['user']['user_id'];
    $kuantitas_to_add = 1; // Default tambah 1 item

    // 1. Dapatkan detail produk dan stok
    // Pastikan $conn adalah objek mysqli yang valid sebelum memanggil prepare
    if (!$conn) {
        // Seharusnya sudah ditangani oleh die() di connect_db(), tapi sebagai pengaman tambahan
        header("Location: gallery_page.php?error=db_connection_failed");
        exit();
    }

    $stmt_prod = $conn->prepare("SELECT name, stok FROM produk WHERE product_id = ? AND is_available = 1");
    if (!$stmt_prod) {
        error_log("Prepare statement produk gagal: " . $conn->error);
        header("Location: gallery_page.php?error=statement_failed");
        exit();
    }
    $stmt_prod->bind_param("i", $product_id);
    if(!$stmt_prod->execute()){
        error_log("Eksekusi statement produk gagal: " . $stmt_prod->error);
        $stmt_prod->close();
        header("Location: gallery_page.php?error=execution_failed");
        exit();
    }
    $result_prod = $stmt_prod->get_result(); // Ini baris 16 di file asli Anda

    if ($product = $result_prod->fetch_assoc()) {
        $nama_produk = $product['name'];
        $stok_produk = (int)$product['stok'];
        $stmt_prod->close();

        // 2. Cek apakah item sudah ada di keranjang pengguna
        $stmt_cart = $conn->prepare("SELECT cart_id, kuantitas FROM cart WHERE user_id = ? AND product_id = ?");
        if (!$stmt_cart) {
            error_log("Prepare statement keranjang gagal: " . $conn->error);
            header("Location: gallery_page.php?error=statement_failed_cart");
            exit();
        }
        $stmt_cart->bind_param("ii", $user_id, $product_id);
        if(!$stmt_cart->execute()){
            error_log("Eksekusi statement keranjang gagal: " . $stmt_cart->error);
            $stmt_cart->close();
            header("Location: gallery_page.php?error=execution_failed_cart");
            exit();
        }
        $result_cart = $stmt_cart->get_result();
        $cart_item = $result_cart->fetch_assoc();
        $stmt_cart->close();

        $kuantitas_di_cart = $cart_item ? (int)$cart_item['kuantitas'] : 0;

        // 3. Cek stok
        if ($stok_produk < ($kuantitas_di_cart + $kuantitas_to_add)) {
            header("Location: gallery_page.php?error=insufficient_stock&product_name=" . urlencode($nama_produk));
            exit();
        }

        // 4. Update atau Insert ke tabel cart
        if ($cart_item) { // Item sudah ada, update kuantitas
            $new_kuantitas = $cart_item['kuantitas'] + $kuantitas_to_add;
            $stmt_update_cart = $conn->prepare("UPDATE cart SET kuantitas = ? WHERE cart_id = ?");
            if (!$stmt_update_cart) { /* ... error handling ... */ header("Location: gallery_page.php?error=update_prep_failed"); exit(); }
            $stmt_update_cart->bind_param("ii", $new_kuantitas, $cart_item['cart_id']);
            if(!$stmt_update_cart->execute()){ /* ... error handling ... */ $stmt_update_cart->close(); header("Location: gallery_page.php?error=update_exec_failed"); exit(); }
            $stmt_update_cart->close();
        } else { // Item belum ada, insert baru
            $stmt_insert_cart = $conn->prepare("INSERT INTO cart (user_id, product_id, kuantitas, added_at) VALUES (?, ?, ?, NOW())");
            if (!$stmt_insert_cart) { /* ... error handling ... */ header("Location: gallery_page.php?error=insert_prep_failed"); exit(); }
            $stmt_insert_cart->bind_param("iii", $user_id, $product_id, $kuantitas_to_add);
            if(!$stmt_insert_cart->execute()){ /* ... error handling ... */  $stmt_insert_cart->close(); header("Location: gallery_page.php?error=insert_exec_failed"); exit(); }
            $stmt_insert_cart->close();
        }
        $_SESSION['cart_success_message'] = "Produk '" . sanitize_output($nama_produk) . "' berhasil ditambahkan ke keranjang!";
        header("Location: gallery_page.php?item_added=" . urlencode($nama_produk)); // Arahkan kembali ke galeri atau halaman produk
        exit();

    } else { // Produk tidak ditemukan atau tidak tersedia
        if(isset($stmt_prod)) $stmt_prod->close();
        header("Location: gallery_page.php?error=product_not_found");
        exit();
    }
    // $conn->close(); // Koneksi akan ditutup otomatis di akhir skrip, atau jika diperlukan di sini

} else {
    header("Location: gallery_page.php?error=invalid_request");
    exit();
}
?>
