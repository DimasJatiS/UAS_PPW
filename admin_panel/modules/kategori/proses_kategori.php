<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak.";
    redirect('../../../login.php');
}

$conn = connect_db();
$action = $_REQUEST['action'] ?? ''; // Bisa GET (untuk delete) atau POST (untuk create/update)

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'update')) {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $kategori_id = filter_var($_POST['kategori_id'] ?? null, FILTER_VALIDATE_INT); // Untuk update

    // Simpan data form ke session untuk repopulate jika ada error
    if ($action === 'create') {
        $_SESSION['form_data_kategori'] = $_POST;
    } else {
        $_SESSION['form_data_kategori_edit'] = $_POST;
    }

    // Validasi dasar
    if (empty($nama_kategori)) {
        if ($action === 'create') {
            $_SESSION['kategori_error_message'] = "Nama kategori wajib diisi.";
            redirect('tambah_kategori.php');
        } else {
            $_SESSION['kategori_error_message_edit'] = "Nama kategori wajib diisi.";
            redirect('edit_kategori.php?kategori_id=' . $kategori_id);
        }
    }

    // Cek duplikasi nama kategori (kecuali untuk kategori yang sedang diedit)
    $sql_check_duplikat = "SELECT kategori_id FROM kategoriproduk WHERE nama_kategori = ?";
    $params_check = [$nama_kategori];
    $types_check = "s";
    if ($action === 'update' && $kategori_id) {
        $sql_check_duplikat .= " AND kategori_id != ?";
        $params_check[] = $kategori_id;
        $types_check .= "i";
    }
    $stmt_check = $conn->prepare($sql_check_duplikat);
    $stmt_check->bind_param($types_check, ...$params_check);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $stmt_check->close();
        if ($action === 'create') {
            $_SESSION['kategori_error_message'] = "Nama kategori '$nama_kategori' sudah ada.";
            redirect('tambah_kategori.php');
        } else {
            $_SESSION['kategori_error_message_edit'] = "Nama kategori '$nama_kategori' sudah ada.";
            redirect('edit_kategori.php?kategori_id=' . $kategori_id);
        }
    }
    $stmt_check->close();


    if ($action === 'create') {
        $sql = "INSERT INTO kategoriproduk (nama_kategori, deskripsi, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['kategori_error_message'] = "Gagal menyiapkan statement: " . $conn->error;
            redirect('tambah_kategori.php');
        }
        $stmt->bind_param("ss", $nama_kategori, $deskripsi);
        if ($stmt->execute()) {
            $_SESSION['kategori_success_message'] = "Kategori berhasil ditambahkan!";
            unset($_SESSION['form_data_kategori']);
            redirect('index.php');
        } else {
            $_SESSION['kategori_error_message'] = "Gagal menambahkan kategori: " . $stmt->error;
            redirect('tambah_kategori.php');
        }
        $stmt->close();

    } elseif ($action === 'update') {
        if (!$kategori_id) {
            $_SESSION['kategori_error_message'] = "ID Kategori tidak valid untuk update.";
            redirect('index.php');
        }
        $sql = "UPDATE kategoriproduk SET nama_kategori = ?, deskripsi = ? WHERE kategori_id = ?";
        $stmt = $conn->prepare($sql);
         if (!$stmt) {
            $_SESSION['kategori_error_message_edit'] = "Gagal menyiapkan statement update: " . $conn->error;
            redirect('edit_kategori.php?kategori_id=' . $kategori_id);
        }
        $stmt->bind_param("ssi", $nama_kategori, $deskripsi, $kategori_id);
        if ($stmt->execute()) {
            $_SESSION['kategori_success_message'] = "Kategori berhasil diperbarui!";
            unset($_SESSION['form_data_kategori_edit']);
            redirect('index.php');
        } else {
            $_SESSION['kategori_error_message_edit'] = "Gagal memperbarui kategori: " . $stmt->error;
            redirect('edit_kategori.php?kategori_id=' . $kategori_id);
        }
        $stmt->close();
    }

} elseif ($action === 'delete' && isset($_GET['kategori_id'])) {
    $kategori_id = filter_var($_GET['kategori_id'], FILTER_VALIDATE_INT);
    if (!$kategori_id) {
        $_SESSION['kategori_error_message'] = "ID Kategori tidak valid untuk dihapus.";
        redirect('index.php');
    }

    // Periksa apakah kategori digunakan oleh produk
    $stmt_check_produk = $conn->prepare("SELECT COUNT(*) as count FROM produk WHERE kategori_id = ?");
    $stmt_check_produk->bind_param("i", $kategori_id);
    $stmt_check_produk->execute();
    $result_produk_count = $stmt_check_produk->get_result()->fetch_assoc()['count'];
    $stmt_check_produk->close();

    if ($result_produk_count > 0) {
        $_SESSION['kategori_error_message'] = "Kategori tidak dapat dihapus karena masih digunakan oleh ".$result_produk_count." produk. Harap pindahkan atau hapus produk terkait terlebih dahulu.";
        redirect('index.php');
    }

    $sql = "DELETE FROM kategoriproduk WHERE kategori_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
         $_SESSION['kategori_error_message'] = "Gagal menyiapkan statement delete: " . $conn->error;
    } else {
        $stmt->bind_param("i", $kategori_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $_SESSION['kategori_success_message'] = "Kategori berhasil dihapus!";
            } else {
                $_SESSION['kategori_error_message'] = "Kategori tidak ditemukan atau gagal dihapus.";
            }
        } else {
            $_SESSION['kategori_error_message'] = "Gagal menghapus kategori: " . $stmt->error;
        }
        $stmt->close();
    }
    redirect('index.php');

} else {
    $_SESSION['kategori_error_message'] = "Aksi tidak valid atau ID tidak ditemukan.";
    redirect('index.php');
}

$conn->close();
?>
