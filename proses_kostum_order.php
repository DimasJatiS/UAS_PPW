<<<<<<< HEAD
<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

// Pastikan pelanggan sudah login
if (!isCustomerLoggedIn()) {
    $_SESSION['kostum_order_error'] = "You must logged in to send a custom order request.";
    $_SESSION['redirect_url_after_login'] = 'kostum_order.php'; // Simpan halaman tujuan
    redirect('login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user']['user_id'];
    $deskripsi_request = trim($_POST['deskripsi_request'] ?? '');
    $budget_estimasi_str = trim($_POST['budget_estimasi'] ?? '');
    $existing_referensi_gambar_path = trim($_POST['existing_referensi_gambar_path'] ?? null);
    $referensi_gambar_final_path = $existing_referensi_gambar_path; // Default ke gambar yang sudah ada

    // Simpan data form awal ke session untuk pre-fill jika ada error
    $_SESSION['form_data_kostum_request'] = [
        'deskripsi_request' => $deskripsi_request,
        'budget_estimasi' => $budget_estimasi_str,
        'referensi_gambar_url' => $existing_referensi_gambar_path
    ];

    // Validasi
    if (empty($deskripsi_request)) {
        $_SESSION['kostum_order_error'] = "Your Description can't be blank.";
        redirect('kostum_order.php');
    }
    if (strlen($deskripsi_request) < 20) {
        $_SESSION['kostum_order_error'] = "Your Description is too short (At Least 20 Characters).";
        redirect('kostum_order.php');
    }

    $budget_estimasi_for_db = null;
    if (!empty($budget_estimasi_str)) {
        if (!is_numeric($budget_estimasi_str) || (float)$budget_estimasi_str < 0) {
            $_SESSION['kostum_order_error'] = "Budget estimation must be positive or zero.";
            redirect('kostum_order.php');
        }
        $budget_estimasi_for_db = (float)$budget_estimasi_str;
    }

    // --- Logika Upload File ---
    $upload_error_msg = '';
    // Default ke path lama jika ada, atau NULL jika tidak ada
    $referensi_gambar_final_path = $existing_referensi_gambar_path ?? NULL; 
    
    if (isset($_FILES['referensi_gambar_file']) && $_FILES['referensi_gambar_file']['error'] == UPLOAD_ERR_OK) {
        
        // Validasi file (ukuran, tipe) - tidak ada perubahan
        $file_tmp_path = $_FILES['referensi_gambar_file']['tmp_name'];
        $file_name_original = basename($_FILES['referensi_gambar_file']['name']);
        $file_size = $_FILES['referensi_gambar_file']['size'];
        $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
    
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 4 * 1024 * 1024; // 4MB
    
        $image_info = @getimagesize($file_tmp_path);
        if (!$image_info) {
            $upload_error_msg = "Invalid upload file picture.";
        } else {
            $mime_type = $image_info['mime'];
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    
            if (!in_array($file_ext, $allowed_extensions) || !in_array($mime_type, $allowed_mime_types)) {
                $upload_error_msg = "The file format is not allowed (only JPG, JPEG, PNG, GIF).";
            } elseif ($file_size > $max_file_size) {
                $upload_error_msg = "The file size is too large (Maximum 4MB).";
            }
        }
    
        if (empty($upload_error_msg)) {
            // =========================================================================
            // BAGIAN PALING PENTING ADA DI SINI: PENENTUAN PATH
            // =========================================================================
    
            // 1. Definisikan path absolut ke direktori root website Anda di server.
            // $_SERVER['DOCUMENT_ROOT'] biasanya mengarah ke /home/user/public_html atau sejenisnya.
            $project_root_path = $_SERVER['DOCUMENT_ROOT'] . '/';
    
            // 2. Tentukan subdirektori untuk upload.
            $upload_subdir = 'uploads/custom_orders/';
    
            // 3. Gabungkan menjadi path tujuan fisik di server.
            $destination_directory = $project_root_path . $upload_subdir;
    
            // 4. Buat direktorinya jika belum ada (sangat penting di hosting).
            if (!is_dir($destination_directory)) {
                // mkdir akan mencoba membuat folder, 0755 adalah izin folder yang umum.
                if (!mkdir($destination_directory, 0755, true)) {
                     $upload_error_msg = "Gagal membuat direktori upload di server.";
                     error_log("Gagal membuat direktori: " . $destination_directory);
                }
            }
            
            if (empty($upload_error_msg)) {
                // 5. Buat nama file yang unik.
                $safe_original_name = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($file_name_original, PATHINFO_FILENAME));
                if (empty($safe_original_name)) $safe_original_name = "ref_img";
                $new_file_name = 'customreq_' . $user_id . '_' . time() . '_' . $safe_original_name . '.' . $file_ext;
                
                // 6. Path lengkap tempat file akan disimpan di server.
                $target_file_path = $destination_directory . $new_file_name;
    
                // Pindahkan file yang di-upload ke lokasi tujuan
                if (move_uploaded_file($file_tmp_path, $target_file_path)) {
                    
                    // 7. Simpan HANYA path relatifnya ke database untuk digabungkan dengan BASE_URL.
                    $path_for_db = $upload_subdir . $new_file_name; 
                    $referensi_gambar_final_path = $path_for_db; 
                    $_SESSION['form_data_kostum_request']['referensi_gambar_url'] = $referensi_gambar_final_path;
    
                } else {
                    $upload_error_msg = "failed to move the file. Please check the folder permissions on your hosting.";
                    error_log("Gagal memindahkan file ke: " . $target_file_path);
                }
            }
        }
    } elseif (isset($_FILES['referensi_gambar_file']) && $_FILES['referensi_gambar_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_error_msg = "Terjadi kesalahan saat mengunggah file (Kode: " . $_FILES['referensi_gambar_file']['error'] . ").";
    }
    
    if (!empty($upload_error_msg)) {
        $_SESSION['kostum_order_error'] = $upload_error_msg;
        redirect('kostum_order.php');
    }

    // Jika tidak ada file baru diupload & tidak ada file lama, path jadi NULL
    if (!isset($_FILES['referensi_gambar_file']) || $_FILES['referensi_gambar_file']['error'] == UPLOAD_ERR_NO_FILE) {
        if(empty($existing_referensi_gambar_path)) { // Jika dari awal memang tidak ada gambar
             $referensi_gambar_final_path = NULL;
        }
        // Jika ada existing_referensi_gambar_path dan tidak ada upload baru, $referensi_gambar_final_path sudah di-set ke existing.
    }


    $status_request = 'menunggu_konfirmasi_awal';

    $stmt = $conn->prepare("INSERT INTO orderkostum (user_id, deskripsi_request, budget_estimasi, referensi_gambar_url, status_request, tanggal_request) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isdss", $user_id, $deskripsi_request, $budget_estimasi_for_db, $referensi_gambar_final_path, $status_request);
        if ($stmt->execute()) {
            $last_request_id = $conn->insert_id;
            unset($_SESSION['form_data_kostum_request']);
            
            // Set session untuk halaman sukses generik
            $_SESSION['last_order_type'] = 'kostum';
            $_SESSION['last_request_id'] = $last_request_id;
            
            // Hapus pesan sukses lama jika ada, akan digantikan oleh halaman order_success
            // unset($_SESSION['kostum_order_success']); 
            redirect('order_success.php'); // Arahkan ke halaman sukses yang sama
            exit();
        } else {
            error_log("MySQLi Execute Error (orderkostum): " . $stmt->error);
            $_SESSION['kostum_order_error'] = "Failed to save the request. Please try again..";
            redirect('kostum_order.php');
        }
        $stmt->close();
    } else {
        error_log("MySQLi Prepare Error (orderkostum): " . $conn->error);
        $_SESSION['kostum_order_error'] = "An error occurred on the server. Please try again later..";
        redirect('kostum_order.php');
    }
    $conn->close();

} else {
    redirect('index.php');
}
?>
=======
<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

// Pastikan pelanggan sudah login
if (!isCustomerLoggedIn()) {
    $_SESSION['kostum_order_error'] = "You must logged in to send a custom order request.";
    $_SESSION['redirect_url_after_login'] = 'kostum_order.php'; // Simpan halaman tujuan
    redirect('login.php');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = (int)$_SESSION['user']['user_id'];
    $deskripsi_request = trim($_POST['deskripsi_request'] ?? '');
    $budget_estimasi_str = trim($_POST['budget_estimasi'] ?? '');
    $existing_referensi_gambar_path = trim($_POST['existing_referensi_gambar_path'] ?? null);
    $referensi_gambar_final_path = $existing_referensi_gambar_path; // Default ke gambar yang sudah ada

    // Simpan data form awal ke session untuk pre-fill jika ada error
    $_SESSION['form_data_kostum_request'] = [
        'deskripsi_request' => $deskripsi_request,
        'budget_estimasi' => $budget_estimasi_str,
        'referensi_gambar_url' => $existing_referensi_gambar_path
    ];

    // Validasi
    if (empty($deskripsi_request)) {
        $_SESSION['kostum_order_error'] = "Your Description can't be blank.";
        redirect('kostum_order.php');
    }
    if (strlen($deskripsi_request) < 20) {
        $_SESSION['kostum_order_error'] = "Your Description is too short (At Least 20 Characters).";
        redirect('kostum_order.php');
    }

    $budget_estimasi_for_db = null;
    if (!empty($budget_estimasi_str)) {
        if (!is_numeric($budget_estimasi_str) || (float)$budget_estimasi_str < 0) {
            $_SESSION['kostum_order_error'] = "Budget estimation must be positive or zero.";
            redirect('kostum_order.php');
        }
        $budget_estimasi_for_db = (float)$budget_estimasi_str;
    }

    // --- Logika Upload File ---
    $upload_error_msg = '';
    // Default ke path lama jika ada, atau NULL jika tidak ada
    $referensi_gambar_final_path = $existing_referensi_gambar_path ?? NULL; 
    
    if (isset($_FILES['referensi_gambar_file']) && $_FILES['referensi_gambar_file']['error'] == UPLOAD_ERR_OK) {
        
        // Validasi file (ukuran, tipe) - tidak ada perubahan
        $file_tmp_path = $_FILES['referensi_gambar_file']['tmp_name'];
        $file_name_original = basename($_FILES['referensi_gambar_file']['name']);
        $file_size = $_FILES['referensi_gambar_file']['size'];
        $file_ext = strtolower(pathinfo($file_name_original, PATHINFO_EXTENSION));
    
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $max_file_size = 4 * 1024 * 1024; // 4MB
    
        $image_info = @getimagesize($file_tmp_path);
        if (!$image_info) {
            $upload_error_msg = "Invalid upload file picture.";
        } else {
            $mime_type = $image_info['mime'];
            $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
    
            if (!in_array($file_ext, $allowed_extensions) || !in_array($mime_type, $allowed_mime_types)) {
                $upload_error_msg = "The file format is not allowed (only JPG, JPEG, PNG, GIF).";
            } elseif ($file_size > $max_file_size) {
                $upload_error_msg = "The file size is too large (Maximum 4MB).";
            }
        }
    
        if (empty($upload_error_msg)) {
            // =========================================================================
            // BAGIAN PALING PENTING ADA DI SINI: PENENTUAN PATH
            // =========================================================================
    
            // 1. Definisikan path absolut ke direktori root website Anda di server.
            // $_SERVER['DOCUMENT_ROOT'] biasanya mengarah ke /home/user/public_html atau sejenisnya.
            $project_root_path = $_SERVER['DOCUMENT_ROOT'] . '/';
    
            // 2. Tentukan subdirektori untuk upload.
            $upload_subdir = 'uploads/custom_orders/';
    
            // 3. Gabungkan menjadi path tujuan fisik di server.
            $destination_directory = $project_root_path . $upload_subdir;
    
            // 4. Buat direktorinya jika belum ada (sangat penting di hosting).
            if (!is_dir($destination_directory)) {
                // mkdir akan mencoba membuat folder, 0755 adalah izin folder yang umum.
                if (!mkdir($destination_directory, 0755, true)) {
                     $upload_error_msg = "Gagal membuat direktori upload di server.";
                     error_log("Gagal membuat direktori: " . $destination_directory);
                }
            }
            
            if (empty($upload_error_msg)) {
                // 5. Buat nama file yang unik.
                $safe_original_name = preg_replace('/[^A-Za-z0-9_.-]/', '_', pathinfo($file_name_original, PATHINFO_FILENAME));
                if (empty($safe_original_name)) $safe_original_name = "ref_img";
                $new_file_name = 'customreq_' . $user_id . '_' . time() . '_' . $safe_original_name . '.' . $file_ext;
                
                // 6. Path lengkap tempat file akan disimpan di server.
                $target_file_path = $destination_directory . $new_file_name;
    
                // Pindahkan file yang di-upload ke lokasi tujuan
                if (move_uploaded_file($file_tmp_path, $target_file_path)) {
                    
                    // 7. Simpan HANYA path relatifnya ke database untuk digabungkan dengan BASE_URL.
                    $path_for_db = $upload_subdir . $new_file_name; 
                    $referensi_gambar_final_path = $path_for_db; 
                    $_SESSION['form_data_kostum_request']['referensi_gambar_url'] = $referensi_gambar_final_path;
    
                } else {
                    $upload_error_msg = "failed to move the file. Please check the folder permissions on your hosting.";
                    error_log("Gagal memindahkan file ke: " . $target_file_path);
                }
            }
        }
    } elseif (isset($_FILES['referensi_gambar_file']) && $_FILES['referensi_gambar_file']['error'] != UPLOAD_ERR_NO_FILE) {
        $upload_error_msg = "Terjadi kesalahan saat mengunggah file (Kode: " . $_FILES['referensi_gambar_file']['error'] . ").";
    }
    
    if (!empty($upload_error_msg)) {
        $_SESSION['kostum_order_error'] = $upload_error_msg;
        redirect('kostum_order.php');
    }

    // Jika tidak ada file baru diupload & tidak ada file lama, path jadi NULL
    if (!isset($_FILES['referensi_gambar_file']) || $_FILES['referensi_gambar_file']['error'] == UPLOAD_ERR_NO_FILE) {
        if(empty($existing_referensi_gambar_path)) { // Jika dari awal memang tidak ada gambar
             $referensi_gambar_final_path = NULL;
        }
        // Jika ada existing_referensi_gambar_path dan tidak ada upload baru, $referensi_gambar_final_path sudah di-set ke existing.
    }


    $status_request = 'menunggu_konfirmasi_awal';

    $stmt = $conn->prepare("INSERT INTO orderkostum (user_id, deskripsi_request, budget_estimasi, referensi_gambar_url, status_request, tanggal_request) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("isdss", $user_id, $deskripsi_request, $budget_estimasi_for_db, $referensi_gambar_final_path, $status_request);
        if ($stmt->execute()) {
            $last_request_id = $conn->insert_id;
            unset($_SESSION['form_data_kostum_request']);
            
            // Set session untuk halaman sukses generik
            $_SESSION['last_order_type'] = 'kostum';
            $_SESSION['last_request_id'] = $last_request_id;
            
            // Hapus pesan sukses lama jika ada, akan digantikan oleh halaman order_success
            // unset($_SESSION['kostum_order_success']); 
            redirect('order_success.php'); // Arahkan ke halaman sukses yang sama
            exit();
        } else {
            error_log("MySQLi Execute Error (orderkostum): " . $stmt->error);
            $_SESSION['kostum_order_error'] = "Failed to save the request. Please try again..";
            redirect('kostum_order.php');
        }
        $stmt->close();
    } else {
        error_log("MySQLi Prepare Error (orderkostum): " . $conn->error);
        $_SESSION['kostum_order_error'] = "An error occurred on the server. Please try again later..";
        redirect('kostum_order.php');
    }
    $conn->close();

} else {
    redirect('index.php');
}
?>
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
