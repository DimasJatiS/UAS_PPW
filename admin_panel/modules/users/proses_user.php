<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}

$conn = connect_db();
$action = $_REQUEST['action'] ?? '';

// --- LOGIKA UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
    $user_id = filter_var($_POST['user_id'] ?? 0, FILTER_VALIDATE_INT);
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? 'customer');
    $password = $_POST['password'] ?? '';

    $_SESSION['form_data_user_edit'] = $_POST;

    // Validasi
    if (!$user_id || empty($nama_lengkap) || empty($username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['user_error_message_edit'] = "Data tidak lengkap atau tidak valid.";
        redirect('edit_user.php?user_id=' . $user_id);
    }
    if (!in_array($role, ['customer', 'admin', 'superadmin'])) {
        $_SESSION['user_error_message_edit'] = "Role tidak valid.";
        redirect('edit_user.php?user_id=' . $user_id);
    }

    // Update password HANYA JIKA diisi
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET nama_lengkap=?, username=?, email=?, role=?, password=? WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssi", $nama_lengkap, $username, $email, $role, $hashed_password, $user_id);
    } else {
        // Jika password kosong, jangan update password
        $sql = "UPDATE users SET nama_lengkap=?, username=?, email=?, role=? WHERE user_id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $nama_lengkap, $username, $email, $role, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['user_success_message'] = "Data user berhasil diperbarui!";
        unset($_SESSION['form_data_user_edit']);
        redirect('index.php');
    } else {
        $_SESSION['user_error_message_edit'] = "Gagal memperbarui data user. Error: " . $stmt->error;
        redirect('edit_user.php?user_id=' . $user_id);
    }
    $stmt->close();

// --- LOGIKA SOFT DELETE (DEACTIVATE/ACTIVATE) ---
} elseif (($action === 'deactivate' || $action === 'activate') && isset($_GET['user_id'])) {
    $user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);
    if (!$user_id) {
        $_SESSION['user_error_message'] = "ID User tidak valid.";
        redirect('index.php');
    }

    $new_status = ($action === 'deactivate') ? 0 : 1;
    $action_text = ($action === 'deactivate') ? 'dinonaktifkan' : 'diaktifkan';

    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $new_status, $user_id);
    if ($stmt->execute()) {
        $_SESSION['user_success_message'] = "User berhasil " . $action_text . ".";
    } else {
        $_SESSION['user_error_message'] = "Gagal mengubah status user.";
    }
    $stmt->close();
    redirect('index.php');

} else {
    $_SESSION['user_error_message'] = "Aksi tidak valid.";
    redirect('index.php');
}

$conn->close();
?>