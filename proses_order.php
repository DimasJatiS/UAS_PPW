<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "Your session has end. Please Re log in to continur.";
    $_SESSION['redirect_url'] = 'checkout.php';
    redirect('login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user']['user_id'];

    $nama_penerima = trim($_POST['nama_penerima_order'] ?? '');
    $nomor_telepon = trim($_POST['nomor_telepon_penerima_order'] ?? '');
    $alamat_lengkap = trim($_POST['alamat_lengkap_order'] ?? '');
    $custom_note = trim($_POST['custom_note'] ?? NULL);

    $_SESSION['form_data_checkout'] = $_POST;

    if (empty($nama_penerima) || empty($nomor_telepon) || empty($alamat_lengkap)) {
        $_SESSION['checkout_error'] = "Shipping data incomplete.";
        redirect('checkout.php');
    }
    if (!preg_match('/^[0-9]{10,15}$/', $nomor_telepon)) {
        $_SESSION['checkout_error'] = "Your phone number format is invalid.";
        redirect('checkout.php');
    }

    $sql_cart = "SELECT c.kuantitas, p.product_id, p.name, p.harga, p.stok FROM cart c JOIN produk p ON c.product_id = p.product_id WHERE c.user_id = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();

    if ($result_cart->num_rows == 0) {
        redirect('gallery_page.php');
    }

    $cart_items_for_order = [];
    $calculated_total_price = 0;
    $order_can_proceed = true;
    $stok_error_details = [];

    while ($item = $result_cart->fetch_assoc()) {
        if ((int)$item['kuantitas'] > (int)$item['stok']) {
            $order_can_proceed = false;
            $stok_error_details[] = sanitize_output($item['name']);
        }
        $cart_items_for_order[] = $item;
        // Total harga dihitung murni dari harga produk x kuantitas
        $calculated_total_price += ((float)$item['harga'] * (int)$item['kuantitas']);
    }
    $stmt_cart->close();

    if (!$order_can_proceed) {
        $_SESSION['checkout_error'] = "Stok tidak mencukupi untuk: " . implode(', ', $stok_error_details);
        redirect('checkout.php');
    }

    $conn->begin_transaction();
    try {
        $status_order = 'menunggu_pembayaran';
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, tanggal_order, total, nama_penerima_order, nomor_telepon_penerima_order, alamat_lengkap_order, custom_note, status) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?)");
        
        // Simpan total harga yang benar (tanpa ongkir) ke database
        $stmt_order->bind_param("idsssss", $user_id, $calculated_total_price, $nama_penerima, $nomor_telepon, $alamat_lengkap, $custom_note, $status_order);
        $stmt_order->execute();
        $new_order_id = $conn->insert_id;
        $stmt_order->close();

        if (!$new_order_id) throw new Exception("Gagal membuat order baru.");

        $stmt_detail = $conn->prepare("INSERT INTO orderdetails (order_id, product_id, kuantitas, price) VALUES (?, ?, ?, ?)");
        $stmt_update_stok = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE product_id = ? AND stok >= ?");

        foreach ($cart_items_for_order as $item) {
            $kuantitas_item = (int)$item['kuantitas'];
            $product_id_item = (int)$item['product_id'];
            $harga_item = (float)$item['harga'];
            
            $stmt_detail->bind_param("iiid", $new_order_id, $product_id_item, $kuantitas_item, $harga_item);
            $stmt_detail->execute();

            $stmt_update_stok->bind_param("iii", $kuantitas_item, $product_id_item, $kuantitas_item);
            $stmt_update_stok->execute();
            if($stmt_update_stok->affected_rows == 0) throw new Exception("Stok untuk ".sanitize_output($item['name'])." tidak mencukupi saat proses akhir.");
        }
        $stmt_detail->close();
        $stmt_update_stok->close();

        $stmt_delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt_delete_cart->bind_param("i", $user_id);
        $stmt_delete_cart->execute();
        $stmt_delete_cart->close();

        $conn->commit();

        unset($_SESSION['form_data_checkout']);
        
        // --- PEMBERSIHAN SESI ---
        $_SESSION['last_order_type'] = 'reguler';
        $_SESSION['last_order_id'] = $new_order_id;
        unset($_SESSION['last_request_id']); // Hapus session dari tipe pesanan lain

        redirect('order_success.php');
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Order processing failed (User ID: $user_id): " . $e->getMessage());
        $_SESSION['checkout_error'] = "Terjadi kesalahan: " . $e->getMessage();
        redirect('checkout.php');
        exit();
    }
} else {
    redirect('checkout.php');
}
?>
