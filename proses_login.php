<?php
require_once 'db_connect.php'; // db_connect.php sudah memanggil session_start()

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email_input = sanitize_form_input($_POST['username_or_email'] ?? '');
    $password_input = $_POST['password'] ?? ''; // Password tidak di-sanitize karena akan diverifikasi dengan hash

    $_SESSION['form_data_login'] = ['input' => $username_or_email_input];

    if (empty($username_or_email_input) || empty($password_input)) {
        $_SESSION['login_error'] = "Username/Email dan password tidak boleh kosong.";
        redirect('login.php');
    }

    $conn = connect_db();
    $stmt = $conn->prepare("SELECT user_id, username, password, email, nama_lengkap, role FROM users WHERE email = ? OR username = ?");
    if (!$stmt) {
        error_log("Login DB prepare error: " . $conn->error);
        $_SESSION['login_error'] = "Terjadi kesalahan pada server. Silakan coba lagi.";
        redirect('login.php');
    }

    $stmt->bind_param("ss", $username_or_email_input, $username_or_email_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password_input, $user['password'])) {
            unset($_SESSION['form_data_login']);
            unset($_SESSION['login_error']);
            unset($_SESSION['login_message']);

            if ($user['role'] === 'superadmin' || $user['role'] === 'admin') {
                $_SESSION['admin_user_id'] = $user['user_id'];
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_nama_lengkap'] = $user['nama_lengkap']; // Simpan nama lengkap admin juga
                $_SESSION['admin_role'] = $user['role'];
                redirect('admin_panel/admin_dashboard.php');
            } elseif ($user['role'] === 'customer') {
                $_SESSION['user'] = [
                    'user_id'       => $user['user_id'],
                    'username'      => $user['username'],
                    'email'         => $user['email'],
                    'nama_lengkap'  => $user['nama_lengkap'],
                    'role'          => $user['role']
                ];
                if (isset($_SESSION['redirect_url'])) {
                $target_url = $_SESSION['redirect_url'];
                unset($_SESSION['redirect_url']);
                redirect($target_url);
            } else {
                redirect('index.php');
            }
            } else {
                $_SESSION['login_error'] = "Tipe akun tidak valid.";
                redirect('login.php');
            }
        }
    }

    $_SESSION['login_error'] = "Username/Email atau password salah.";
    if ($stmt) $stmt->close();
    if ($conn) $conn->close();
    redirect('login.php');

} else {
    redirect('login.php');
}
?>