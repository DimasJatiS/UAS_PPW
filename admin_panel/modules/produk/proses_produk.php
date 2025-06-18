<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak.";
    redirect('../../../login.php'); // Redirect ke login jika bukan admin
}

$conn = connect_db();
$action = $_REQUEST['action'] ?? ''; // Bisa GET (untuk delete) atau POST (untuk create/update)

// --- Fungsi Upload Foto ---
function handle_upload_foto($file_input_name, $existing_foto_path = '') {
    $upload_dir = '../../../uploads/produk/'; // Pastikan folder ini ada dan writable relatif dari root web
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == UPLOAD_ERR_OK) {
        $tmp_name = $_FILES[$file_input_name]['tmp_name'];
        // Buat nama file unik untuk menghindari penimpaan
        $file_extension = strtolower(pathinfo($_FILES[$file_input_name]['name'], PATHINFO_EXTENSION));
        $safe_filename = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($_FILES[$file_input_name]['name'], PATHINFO_FILENAME));
        $new_file_name = time() . '_' . $safe_filename . '.' . $file_extension;
        $target_file = $upload_dir . $new_file_name;

        // Validasi tipe dan ukuran file
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($tmp_name);
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error_message_produk'] = "Format file foto tidak valid (hanya JPG, PNG, GIF).";
            return false; // Kembalikan false jika error
        }
        if ($_FILES[$file_input_name]['size'] > 2 * 1024 * 1024) { // Maks 2MB
            $_SESSION['error_message_produk'] = "Ukuran file foto terlalu besar (maksimal 2MB).";
            return false;
        }

        if (move_uploaded_file($tmp_name, $target_file)) {
            // Hapus foto lama jika ada dan foto baru berhasil diupload
            if (!empty($existing_foto_path) && file_exists('../../../' . $existing_foto_path)) {
                unlink('../../../' . $existing_foto_path);
            }
            return 'uploads/produk/' . $new_file_name; // Kembalikan path relatif dari root web
        } else {
            $_SESSION['error_message_produk'] = "Gagal mengupload foto produk.";
            return false;
        }
    } elseif (!empty($existing_foto_path) && $action === 'update' && (!isset($_FILES[$file_input_name]) || $_FILES[$file_input_name]['error'] == UPLOAD_ERR_NO_FILE)) {
        // Jika update dan tidak ada file baru diupload, pertahankan foto lama
        return $existing_foto_path;
    }
    return null; // Tidak ada file diupload atau error (selain yang sudah di-handle)
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'update')) {
    // Ambil data dari form
    $name = trim($_POST['name'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $harga = filter_var($_POST['harga'] ?? 0, FILTER_VALIDATE_FLOAT);
    $stok = filter_var($_POST['stok'] ?? 0, FILTER_VALIDATE_INT);
    $kategori_id = filter_var($_POST['kategori_id'] ?? null, FILTER_VALIDATE_INT);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Simpan data form ke session untuk repopulate jika ada error
    $_SESSION['form_data_produk'] = $_POST;


    // Validasi dasar
    if (empty($name) || $harga === false || $stok === false || $kategori_id === null) {
        $_SESSION['error_message_produk'] = "Nama, harga, stok, dan kategori wajib diisi dengan benar.";
        redirect($action === 'create' ? 'tambah_produk.php' : 'edit_produk.php?product_id=' . ($_POST['product_id'] ?? ''));
    }

    $foto_produk_path = handle_upload_foto('foto_produk', $_POST['existing_foto_produk'] ?? '');
    if ($foto_produk_path === false) { // Error saat upload
        redirect($action === 'create' ? 'tambah_produk.php' : 'edit_produk.php?product_id=' . ($_POST['product_id'] ?? ''));
    }


    if ($action === 'create') {
        $sql = "INSERT INTO produk (name, deskripsi, harga, stok, kategori_id, foto_produk, is_available, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
             $_SESSION['error_message_produk'] = "Prepare statement gagal: " . $conn->error;
        } else {
            $stmt->bind_param("ssdiisi", $name, $deskripsi, $harga, $stok, $kategori_id, $foto_produk_path, $is_available);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Produk berhasil ditambahkan!";
                unset($_SESSION['form_data_produk']);
                redirect('index.php');
            } else {
                $_SESSION['error_message_produk'] = "Gagal menambahkan produk: " . $stmt->error;
            }
            $stmt->close();
        }
    } elseif ($action === 'update') {
        $product_id = filter_var($_POST['product_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$product_id) {
            $_SESSION['error_message_produk'] = "ID Produk tidak valid untuk update.";
            redirect('index.php');
        }

        if ($foto_produk_path !== null) { // Ada foto baru atau foto lama dipertahankan
            $sql = "UPDATE produk SET name=?, deskripsi=?, harga=?, stok=?, kategori_id=?, foto_produk=?, is_available=? WHERE product_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssdiisii", $name, $deskripsi, $harga, $stok, $kategori_id, $foto_produk_path, $is_available, $product_id);
        } else { // Tidak ada perubahan foto (misalnya, foto dihapus dan tidak diupload baru)
            $sql = "UPDATE produk SET name=?, deskripsi=?, harga=?, stok=?, kategori_id=?, is_available=? WHERE product_id=?";
            $stmt = $conn->prepare($sql);
            // Jika $foto_produk_path adalah null dan user tidak ingin menghapus foto, Anda harus handle ini
            // Untuk skenario sederhana: jika foto dikosongkan, maka di-set null di DB
            // Atau, jika Anda punya opsi "hapus foto", handle di sini
            $stmt->bind_param("ssdiiii", $name, $deskripsi, $harga, $stok, $kategori_id, $is_available, $product_id);
        }


        if (!$stmt) {
             $_SESSION['error_message_produk'] = "Prepare statement update gagal: " . $conn->error;
        } else {
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Produk berhasil diperbarui!";
                unset($_SESSION['form_data_produk']);
                redirect('index.php');
            } else {
                $_SESSION['error_message_produk'] = "Gagal memperbarui produk: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    // Jika sampai sini, ada error yang belum tertangani, redirect kembali
    redirect($action === 'create' ? 'tambah_produk.php' : 'edit_produk.php?product_id=' . ($_POST['product_id'] ?? ''));


} elseif ($action === 'delete' && isset($_GET['product_id'])) {
    $product_id = filter_var($_GET['product_id'], FILTER_VALIDATE_INT);
    if (!$product_id) {
        $_SESSION['error_message'] = "ID Produk tidak valid untuk dinonaktifkan.";
        redirect('index.php');
    }

    $sql = "UPDATE produk SET is_available = 0 WHERE product_id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['error_message'] = "Prepare statement gagal: " . $conn->error;
    } else {
        $stmt->bind_param("i", $product_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['success_message'] = "Produk berhasil dinonaktifkan dan disembunyikan dari halaman publik.";
            } else {
                $_SESSION['error_message'] = "Produk tidak ditemukan atau sudah nonaktif.";
            }
        } else {
            $_SESSION['error_message'] = "Gagal menonaktifkan produk: " . $stmt->error;
        }
        $stmt->close();
    }
    redirect('index.php');

} else {
    $_SESSION['error_message'] = "Aksi tidak valid.";
    redirect('index.php');
}

$conn->close();
?>