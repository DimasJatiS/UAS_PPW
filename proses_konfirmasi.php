<<<<<<< HEAD
<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

// 1. Keamanan Awal: Pastikan pengguna login dan metode adalah POST
if (!isCustomerLoggedIn() || $_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('login.php');
}

// 2. Ambil Data dari Form
$user_id = (int)$_SESSION['user']['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : null;
$bank_pengirim = trim($_POST['bank_pengirim'] ?? '');
$nama_rekening_pengirim = trim($_POST['nama_rekening_pengirim'] ?? '');
$jumlah_transfer = (float)($_POST['jumlah_transfer'] ?? 0);
$tanggal_transfer = trim($_POST['tanggal_transfer'] ?? '');

// Simpan data form untuk diisi kembali jika ada error
$_SESSION['form_data_payment'] = $_POST;

// 3. Validasi Input Form
$errors = [];
if (empty($bank_pengirim)) $errors[] = "Bank pengirim wajib diisi.";
if (empty($nama_rekening_pengirim)) $errors[] = "Nama pemilik rekening wajib diisi.";
if ($jumlah_transfer <= 0) $errors[] = "Jumlah transfer tidak valid.";
if (empty($tanggal_transfer)) $errors[] = "Tanggal transfer wajib diisi.";

// Cek apakah ada file yang diunggah
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] != UPLOAD_ERR_OK) {
    $errors[] = "Bukti pembayaran wajib diunggah.";
}

// --- 4. Logika Upload File (Meniru proses_kostum_order.php) ---
$path_for_db = null; // Path yang akan disimpan ke database

if (empty($errors)) { // Hanya proses jika validasi awal lolos
    
    $file_tmp_path = $_FILES['bukti_pembayaran']['tmp_name'];
    $file_name_original = basename($_FILES['bukti_pembayaran']['name']);
    $file_size = $_FILES['bukti_pembayaran']['size'];
    $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_file_size = 4 * 1024 * 1024; // 4MB

    if (!in_array($file_ext, $allowed_extensions)) {
        $errors[] = "Format file tidak diizinkan (JPG, JPEG, PNG, PDF).";
    } elseif ($file_size > $max_file_size) {
        $errors[] = "Ukuran file terlalu besar (Maks 4MB).";
    }

    if (empty($errors)) {
        // -- INI ADALAH LOGIKA PATH YANG DIAMBIL DARI FILE ANDA YANG SUDAH BENAR --
        $project_root_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/'; 
        $upload_subdir = 'uploads/bukti_pembayaran/';
        $destination_directory = $project_root_path . $upload_subdir;

        if (!is_dir($destination_directory)) {
            if (!mkdir($destination_directory, 0755, true)) {
                $errors[] = "Terjadi kesalahan internal (dir). Hubungi administrator.";
                error_log("Gagal membuat direktori upload: " . $destination_directory);
            }
        }

        if (empty($errors)) {
            $new_filename = 'bukti_' . ($order_id ?? $request_id) . '_' . time() . '.' . $file_ext;
            $target_file = $destination_directory . $new_filename;

            if (move_uploaded_file($file_tmp_path, $target_file)) {
                $path_for_db = $upload_subdir . $new_filename; // Sukses! Path untuk database sudah siap.
            } else {
                $errors[] = "Gagal memindahkan file bukti pembayaran. Periksa izin folder.";
                error_log("Gagal memindahkan file ke: " . $target_file);
            }
        }
    }
}
// --- Akhir Logika Upload File ---


// 5. Redirect Jika Ada Error Apapun (dari validasi form atau upload)
if (!empty($errors)) {
    $_SESSION['payment_error'] = implode('<br>', $errors);
    $redirect_url = 'pembayaran.php?';
    if ($order_id) $redirect_url .= 'order_id=' . $order_id;
    if ($request_id) $redirect_url .= 'request_id=' . $request_id;
    redirect($redirect_url);
}

// 6. Simpan ke Database (menggunakan transaksi)
$conn->begin_transaction();
try {
    // Simpan data pembayaran
    $stmt_payment = $conn->prepare(
        "INSERT INTO pembayaran (order_id, kostum_request_id, bank_pengirim, nama_rekening_pengirim, jumlah_transfer, tanggal_transfer, bukti_pembayaran_url, tanggal_konfirmasi) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt_payment->bind_param("iissdss", $order_id, $request_id, $bank_pengirim, $nama_rekening_pengirim, $jumlah_transfer, $tanggal_transfer, $path_for_db);
    
    if (!$stmt_payment->execute()) {
        throw new Exception("Gagal menyimpan data konfirmasi: " . $stmt_payment->error);
    }
    $stmt_payment->close();

    // Update status pesanan terkait
    if ($order_id) {
        $stmt_update = $conn->prepare("UPDATE orders SET status = 'menunggu_verifikasi' WHERE order_id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $order_id, $user_id);
    } elseif ($request_id) {
        $stmt_update = $conn->prepare("UPDATE orderkostum SET status_request = 'menunggu_verifikasi' WHERE kostum_request_id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $request_id, $user_id);
    }
    
    if (isset($stmt_update)) {
        if (!$stmt_update->execute()) {
            throw new Exception("Gagal memperbarui status pesanan: " . $stmt_update->error);
        }
        $stmt_update->close();
    }

    // Jika semua query berhasil, commit transaksi
    $conn->commit();

    // Berhasil, bersihkan data form dan arahkan ke halaman sukses
    unset($_SESSION['form_data_payment']);
    $_SESSION['success_message'] = "Konfirmasi pembayaran telah berhasil dikirim. Tim kami akan segera memverifikasi pembayaran Anda.";
    redirect('riwayat_pesanan.php');

} catch (Exception $e) {
    // Jika ada kegagalan, batalkan semua perubahan
    $conn->rollback();
    
    // Hapus file yang sudah terlanjur di-upload jika ada
    if (isset($target_file) && file_exists($target_file)) {
        @unlink($target_file);
    }

    // Catat error teknis dan beri pesan ke pengguna
    error_log("Payment confirmation failed: " . $e->getMessage());
    $_SESSION['payment_error'] = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
    
    $redirect_url = 'pembayaran.php?';
    if ($order_id) $redirect_url .= 'order_id=' . $order_id;
    if ($request_id) $redirect_url .= 'request_id=' . $request_id;
    redirect($redirect_url);
}

$conn->close();
=======
<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

// 1. Keamanan Awal: Pastikan pengguna login dan metode adalah POST
if (!isCustomerLoggedIn() || $_SERVER["REQUEST_METHOD"] != "POST") {
    redirect('login.php');
}

// 2. Ambil Data dari Form
$user_id = (int)$_SESSION['user']['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : null;
$bank_pengirim = trim($_POST['bank_pengirim'] ?? '');
$nama_rekening_pengirim = trim($_POST['nama_rekening_pengirim'] ?? '');
$jumlah_transfer = (float)($_POST['jumlah_transfer'] ?? 0);
$tanggal_transfer = trim($_POST['tanggal_transfer'] ?? '');

// Simpan data form untuk diisi kembali jika ada error
$_SESSION['form_data_payment'] = $_POST;

// 3. Validasi Input Form
$errors = [];
if (empty($bank_pengirim)) $errors[] = "Bank pengirim wajib diisi.";
if (empty($nama_rekening_pengirim)) $errors[] = "Nama pemilik rekening wajib diisi.";
if ($jumlah_transfer <= 0) $errors[] = "Jumlah transfer tidak valid.";
if (empty($tanggal_transfer)) $errors[] = "Tanggal transfer wajib diisi.";

// Cek apakah ada file yang diunggah
if (!isset($_FILES['bukti_pembayaran']) || $_FILES['bukti_pembayaran']['error'] != UPLOAD_ERR_OK) {
    $errors[] = "Bukti pembayaran wajib diunggah.";
}

// --- 4. Logika Upload File (Meniru proses_kostum_order.php) ---
$path_for_db = null; // Path yang akan disimpan ke database

if (empty($errors)) { // Hanya proses jika validasi awal lolos
    
    $file_tmp_path = $_FILES['bukti_pembayaran']['tmp_name'];
    $file_name_original = basename($_FILES['bukti_pembayaran']['name']);
    $file_size = $_FILES['bukti_pembayaran']['size'];
    $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
    $max_file_size = 4 * 1024 * 1024; // 4MB

    if (!in_array($file_ext, $allowed_extensions)) {
        $errors[] = "Format file tidak diizinkan (JPG, JPEG, PNG, PDF).";
    } elseif ($file_size > $max_file_size) {
        $errors[] = "Ukuran file terlalu besar (Maks 4MB).";
    }

    if (empty($errors)) {
        // -- INI ADALAH LOGIKA PATH YANG DIAMBIL DARI FILE ANDA YANG SUDAH BENAR --
        $project_root_path = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/'; 
        $upload_subdir = 'uploads/bukti_pembayaran/';
        $destination_directory = $project_root_path . $upload_subdir;

        if (!is_dir($destination_directory)) {
            if (!mkdir($destination_directory, 0755, true)) {
                $errors[] = "Terjadi kesalahan internal (dir). Hubungi administrator.";
                error_log("Gagal membuat direktori upload: " . $destination_directory);
            }
        }

        if (empty($errors)) {
            $new_filename = 'bukti_' . ($order_id ?? $request_id) . '_' . time() . '.' . $file_ext;
            $target_file = $destination_directory . $new_filename;

            if (move_uploaded_file($file_tmp_path, $target_file)) {
                $path_for_db = $upload_subdir . $new_filename; // Sukses! Path untuk database sudah siap.
            } else {
                $errors[] = "Gagal memindahkan file bukti pembayaran. Periksa izin folder.";
                error_log("Gagal memindahkan file ke: " . $target_file);
            }
        }
    }
}
// --- Akhir Logika Upload File ---


// 5. Redirect Jika Ada Error Apapun (dari validasi form atau upload)
if (!empty($errors)) {
    $_SESSION['payment_error'] = implode('<br>', $errors);
    $redirect_url = 'pembayaran.php?';
    if ($order_id) $redirect_url .= 'order_id=' . $order_id;
    if ($request_id) $redirect_url .= 'request_id=' . $request_id;
    redirect($redirect_url);
}

// 6. Simpan ke Database (menggunakan transaksi)
$conn->begin_transaction();
try {
    // Simpan data pembayaran
    $stmt_payment = $conn->prepare(
        "INSERT INTO pembayaran (order_id, kostum_request_id, bank_pengirim, nama_rekening_pengirim, jumlah_transfer, tanggal_transfer, bukti_pembayaran_url, tanggal_konfirmasi) 
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $stmt_payment->bind_param("iissdss", $order_id, $request_id, $bank_pengirim, $nama_rekening_pengirim, $jumlah_transfer, $tanggal_transfer, $path_for_db);
    
    if (!$stmt_payment->execute()) {
        throw new Exception("Gagal menyimpan data konfirmasi: " . $stmt_payment->error);
    }
    $stmt_payment->close();

    // Update status pesanan terkait
    if ($order_id) {
        $stmt_update = $conn->prepare("UPDATE orders SET status = 'menunggu_verifikasi' WHERE order_id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $order_id, $user_id);
    } elseif ($request_id) {
        $stmt_update = $conn->prepare("UPDATE orderkostum SET status_request = 'menunggu_verifikasi' WHERE kostum_request_id = ? AND user_id = ?");
        $stmt_update->bind_param("ii", $request_id, $user_id);
    }
    
    if (isset($stmt_update)) {
        if (!$stmt_update->execute()) {
            throw new Exception("Gagal memperbarui status pesanan: " . $stmt_update->error);
        }
        $stmt_update->close();
    }

    // Jika semua query berhasil, commit transaksi
    $conn->commit();

    // Berhasil, bersihkan data form dan arahkan ke halaman sukses
    unset($_SESSION['form_data_payment']);
    $_SESSION['success_message'] = "Konfirmasi pembayaran telah berhasil dikirim. Tim kami akan segera memverifikasi pembayaran Anda.";
    redirect('riwayat_pesanan.php');

} catch (Exception $e) {
    // Jika ada kegagalan, batalkan semua perubahan
    $conn->rollback();
    
    // Hapus file yang sudah terlanjur di-upload jika ada
    if (isset($target_file) && file_exists($target_file)) {
        @unlink($target_file);
    }

    // Catat error teknis dan beri pesan ke pengguna
    error_log("Payment confirmation failed: " . $e->getMessage());
    $_SESSION['payment_error'] = "Terjadi kesalahan pada sistem. Silakan coba lagi.";
    
    $redirect_url = 'pembayaran.php?';
    if ($order_id) $redirect_url .= 'order_id=' . $order_id;
    if ($request_id) $redirect_url .= 'request_id=' . $request_id;
    redirect($redirect_url);
}

$conn->close();
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
?>