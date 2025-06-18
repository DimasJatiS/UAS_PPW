<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak.";
    redirect('../../../login.php');
}
$conn = connect_db();

$kategori_id = $_GET['kategori_id'] ?? null;
if (!$kategori_id || !filter_var($kategori_id, FILTER_VALIDATE_INT)) {
    $_SESSION['kategori_error_message'] = "ID Kategori tidak valid.";
    redirect('index.php');
}

$stmt = $conn->prepare("SELECT * FROM kategoriproduk WHERE kategori_id = ?");
if (!$stmt) {
    error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    $_SESSION['kategori_error_message'] = "Gagal menyiapkan data kategori.";
    redirect('index.php');
}
$stmt->bind_param("i", $kategori_id);
$stmt->execute();
$result = $stmt->get_result();
$kategori = $result->fetch_assoc();
$stmt->close();

if (!$kategori) {
    $_SESSION['kategori_error_message'] = "Kategori tidak ditemukan.";
    redirect('index.php');
}

// Ambil data form jika ada error validasi sebelumnya, jika tidak gunakan data dari DB
$form_data = $_SESSION['form_data_kategori_edit'] ?? $kategori;
unset($_SESSION['form_data_kategori_edit']);
$error_message = $_SESSION['kategori_error_message_edit'] ?? '';
unset($_SESSION['kategori_error_message_edit']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori: <?php echo sanitize_output($kategori['nama_kategori']); ?> - Bloomarie Admin</title>
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
        <h1 class="site-title">BLOOMARIE</h1>
        <div class="view-site-link">
            <a href="../../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="../produk/index.php"><i class="fas fa-box-open"></i> Kelola Produk</a>
            <a href="index.php" class="active"><i class="fas fa-list-alt"></i> Kelola Kategori</a>
            <a href="../users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="../order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="../review/index.php"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        </div>
        <div class="view-site-link">
            <a href="../../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs Publik</a>
        </div>
        <div class="logout-link">
            <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Edit Kategori</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="data-table-card">
             <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Formulir Edit Kategori: <?php echo sanitize_output($kategori['nama_kategori']); ?></h5>
            </div>
            <div class="card-body admin-form">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form action="proses_kategori.php" method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="kategori_id" value="<?php echo $kategori['kategori_id']; ?>">
                    <div class="form-group mb-3">
                        <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nama_kategori" name="nama_kategori" value="<?php echo sanitize_output($form_data['nama_kategori'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi (Opsional)</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="4"><?php echo sanitize_output($form_data['deskripsi'] ?? ''); ?></textarea>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary-admin"><i class="fas fa-save me-2"></i> Simpan Perubahan</button>
                    <a href="index.php" class="btn btn-secondary-admin"><i class="fas fa-times me-2"></i> Batal</a>
                </form>
            </div>
        </div>
         <div class="mt-4">
             <a href="index.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Kategori</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
