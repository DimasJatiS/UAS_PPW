<?php
require_once 'db_connect.php'; // Menyediakan connect_db() dan fungsi helper
$conn = connect_db();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $_SESSION['form_data_register'] = [
        'nama_lengkap' => $nama_lengkap,
        'username' => $username,
        'email' => $email
    ];

    // Validasi dasar
    if (empty($nama_lengkap) || empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['register_error'] = "Semua field yang ditandai * wajib diisi.";
        redirect('register.php');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_error'] = "Invalid Email Format.";
        redirect('register.php');
    }
    if (strlen($password) < 6) { // Contoh validasi panjang password
        $_SESSION['register_error'] = "Password must be at least 6 characters.";
        redirect('register.php');
    }
    if ($password !== $confirm_password) {
        $_SESSION['register_error'] = "Password and confirm password do not match.";
        redirect('register.php');
    }

    // Cek apakah username atau email sudah ada
    $stmt_check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
    if ($stmt_check) {
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['register_error'] = "Username or email already registered.";
            $stmt_check->close();
            redirect('register.php');
        }
        $stmt_check->close();
    } else {
        error_log("Register check statement preparation failed: " . $conn->error);
        $_SESSION['register_error'] = "An error occurred on the server. Please try again later.";
        redirect('register.php');
    }


    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $role = 'customer'; // Default role untuk registrasi baru

    // Insert user baru ke database
    $stmt_insert = $conn->prepare("INSERT INTO users (username, password, email, role, nama_lengkap, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt_insert) {
        $stmt_insert->bind_param("sssss", $username, $hashed_password, $email, $role, $nama_lengkap);
        if ($stmt_insert->execute()) {
            unset($_SESSION['form_data_register']); // Hapus data form dari session jika sukses
            $_SESSION['register_success'] = "Registration successful! Please login.";
            redirect('login.php');
        } else {
            error_log("Register insert execution failed: " . $stmt_insert->error);
            $_SESSION['register_error'] = "Failed to register. Please try again.";
            redirect('register.php');
        }
        $stmt_insert->close();
    } else {
        error_log("Register insert statement preparation failed: " . $conn->error);
        $_SESSION['register_error'] = "An error occurred on the server. Please try again later (prep).";
        redirect('register.php');
    }
} else {
    redirect('register.php'); // Jika diakses langsung tanpa POST
}
$conn->close();
?>