<?php
session_start();
// 1. Sertakan file koneksi database dan buat koneksi
require_once 'db_connect.php'; // Asumsi menggunakan db_connect dari admin_panel
$conn = connect_db(); // Panggil fungsi untuk mendapatkan objek koneksi

// Debugging tambahan (opsional, hapus setelahnya)
if (!$conn) {
    // Fungsi connect_db() sudah memiliki die() jika gagal,
    // tapi ini sebagai pengaman tambahan jika fungsi diubah.
    // Simpan error ke log atau kirim response JSON error jika ini adalah endpoint AJAX.
    error_log("Koneksi database GAGAL di proses_kontak.php.");
    // Jika ini adalah endpoint AJAX, kirim response JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Kesalahan internal server. Gagal terhubung ke database.']);
        exit();
    } else {
        // Untuk submit form tradisional, bisa redirect atau die()
        die("Sistem kami sedang mengalami gangguan. Mohon coba beberapa saat lagi.");
    }
}


// Cek apakah ini request AJAX dari modal kontak
$is_ajax_request = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax_request) {
    header('Content-Type: application/json');
}

$response = ['status' => 'error', 'message' => 'Terjadi kesalahan yang tidak diketahui.'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $source_page_value = $_POST['source_page'] ?? 'unknown'; // Untuk redirect jika bukan AJAX

    $email_pengirim = trim($_POST['email_pengirim'] ?? '');
    $isi_pesan = trim($_POST['isi_pesan'] ?? '');
    $nama_pengirim = trim($_POST['nama_pengirim'] ?? '');
    $subjek_pesan = trim($_POST['subjek_pesan'] ?? NULL);

    // Validasi Dasar
    if ($source_page_value === 'modal_contact_ajax' || $source_page_value === 'contact_page') { // Nama wajib untuk modal/halaman kontak
        if (empty($nama_pengirim)) {
            $errors['nama_pengirim'] = "Name Cannot be empty.";
        }
    }
    if (empty($email_pengirim)) {
        $errors['email_pengirim'] = "Email cannot be empty.";
    } elseif (!filter_var($email_pengirim, FILTER_VALIDATE_EMAIL)) {
        $errors['email_pengirim'] = "Format email tidak valid.";
    }
    if (empty($isi_pesan)) {
        $errors['isi_pesan'] = "Message cannot be empty.";
    }

    if (!empty($errors)) {
        $response['message'] = "Please correct the data you entered:";
        $response['errors'] = $errors;
        if ($is_ajax_request) {
            echo json_encode($response);
        } else {
            // Untuk submit tradisional, simpan error ke session dan redirect
            $_SESSION[$source_page_value == 'contact_page' ? 'contact_error' : 'index_contact_error'] = implode("<br>", array_values($errors));
            $_SESSION[$source_page_value == 'contact_page' ? 'form_data_contact' : 'form_data_index_contact'] = $_POST;
            header("Location: " . ($source_page_value == 'contact_page' ? 'contact_page.php' : 'index.php#contact-section'));
        }
        exit();
    }

    $user_id_to_insert = NULL;
    if (isset($_SESSION['user']['user_id'])) {
        $user_id_to_insert = $_SESSION['user']['user_id'];
    }

    if (empty($nama_pengirim) && isset($_SESSION['user']['nama_lengkap'])) {
        $nama_pengirim = $_SESSION['user']['nama_lengkap'];
    } elseif (empty($nama_pengirim) && isset($_SESSION['user']['username'])) {
        $nama_pengirim = $_SESSION['user']['username'];
    } elseif (empty($nama_pengirim)) {
        $nama_pengirim = "Pengunjung";
    }

    if (empty($subjek_pesan) && $source_page_value === 'index_form_submit') {
        $subjek_pesan = "Pesan dari Halaman Utama (Customize Section)";
    } elseif (empty($subjek_pesan) && $source_page_value === 'modal_contact_ajax') {
        $subjek_pesan = "Pesan dari Kontak Modal";
    }


    // Baris ke-48 kemungkinan ada di sekitar sini saat $conn->prepare() dipanggil
    $stmt = $conn->prepare("INSERT INTO contact_messages (nama_pengirim, email_pengirim, subjek_pesan, isi_pesan, user_id, tanggal_kirim) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param("ssssi", $nama_pengirim, $email_pengirim, $subjek_pesan, $isi_pesan, $user_id_to_insert);

        if ($stmt->execute()) {
            $response['status'] = 'success';
            $response['message'] = 'Your message has been sent successfully! Thank you.';
            if (!$is_ajax_request) {
                $_SESSION[$source_page_value == 'contact_page' ? 'contact_success' : 'index_contact_success'] = $response['message'];
                unset($_SESSION[$source_page_value == 'contact_page' ? 'form_data_contact' : 'form_data_index_contact']);
            }
        } else {
            $response['message'] = "Failed to send message to database. Please try again..";
            error_log("Contact form DB execute error: " . $stmt->error);
            if (!$is_ajax_request) $_SESSION[$source_page_value == 'contact_page' ? 'contact_error' : 'index_contact_error'] = $response['message'];
        }
        $stmt->close();
    } else {
        $response['message'] = "Failed to prepare database statement. Please try again later..";
        error_log("Contact form DB prepare error: " . $conn->error);
        if (!$is_ajax_request) $_SESSION[$source_page_value == 'contact_page' ? 'contact_error' : 'index_contact_error'] = $response['message'];
    }
    $conn->close();

    if ($is_ajax_request) {
        echo json_encode($response);
    } else {
        header("Location: " . ($source_page_value == 'contact_page' ? 'contact_page.php' : 'index.php#contact-section'));
    }
    exit();

} else {
    $response['message'] = "Metode request tidak valid.";
    if ($is_ajax_request) {
        echo json_encode($response);
    } else {
        header("Location: index.php"); // Redirect default jika bukan POST dan bukan AJAX
    }
    exit();
}
?>