<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}
$conn = connect_db();

$user_id = $_GET['user_id'] ?? null;
if (!$user_id || !filter_var($user_id, FILTER_VALIDATE_INT)) {
    $_SESSION['user_error_message'] = "ID User tidak valid.";
    redirect('index.php');
}

$stmt = $conn->prepare("SELECT user_id, nama_lengkap, username, email, role FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['user_error_message'] = "User tidak ditemukan.";
    redirect('index.php');
}

$form_data = $_SESSION['form_data_user_edit'] ?? $user;
unset($_SESSION['form_data_user_edit']);
$error_message = $_SESSION['user_error_message_edit'] ?? '';
unset($_SESSION['user_error_message_edit']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit User: <?php echo sanitize_output($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div class="sidebar">
        <h1 class="site-title">BLOOMARIE DASHBOARD</h1>
        <div class="view-site-link">
            <a href="../../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../../pembayaran/index.php"><i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
            <a href="../produk/index.php"><i class="fas fa-box-open"></i> Kelola Produk</a>
            <a href="../kategori/index.php"><i class="fas fa-list-alt"></i> Kelola Kategori</a>
            <a href="index.php" class="active"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="../order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="#"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
            <a href="../../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a>
        </div>
        <div class="logout-link">
            <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="data-table-card">
            <div class="card-header">
                <h5 class="mb-0">Edit User: <?php echo sanitize_output($user['username']); ?></h5>
            </div>
            <div class="card-body admin-form">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="proses_user.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo sanitize_output($form_data['nama_lengkap'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo sanitize_output($form_data['username'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo sanitize_output($form_data['email'] ?? ''); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select name="role" id="role" class="form-select" required>
                                <option value="customer" <?php echo (($form_data['role'] ?? '') === 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="admin" <?php echo (($form_data['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="superadmin" <?php echo (($form_data['role'] ?? '') === 'superadmin') ? 'selected' : ''; ?>>Superadmin</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password Baru</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <small class="form-text text-muted">Kosongkan jika tidak ingin mengubah password.</small>
                        </div>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary-admin">Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-secondary-admin">Batal</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>