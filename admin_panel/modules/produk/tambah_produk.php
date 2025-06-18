<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak.";
    redirect('../../../login.php');
}
$conn = connect_db();

// Ambil daftar kategori untuk dropdown
$kategori_options = '';
$result_kategori = $conn->query("SELECT kategori_id, nama_kategori FROM kategoriproduk ORDER BY nama_kategori ASC");
if ($result_kategori && $result_kategori->num_rows > 0) {
    while ($row = $result_kategori->fetch_assoc()) {
        $kategori_options .= "<option value=\"" . $row['kategori_id'] . "\">" . sanitize_output($row['nama_kategori']) . "</option>";
    }
}

// Logika untuk repopulate form jika ada error
$form_data = $_SESSION['form_data_produk'] ?? [];
unset($_SESSION['form_data_produk']);
$error_message = $_SESSION['error_message_produk'] ?? '';
unset($_SESSION['error_message_produk']);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk Baru - Bloomarie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/sidebar_style.css">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>

<div class="sidebar">
    <h1 class="site-title">BLOOMARIE</h1>
    <div class="view-sitek-link">
        <a href="../../admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="../../pages/pembayaran/index.php"><i class="fas fa-check-circle"></i> Verifikasi Pembayaran</a>
        <a href="index.php" class="active"><i class="fas fa-box-open"></i> Kelola Produk</a>
        <a href="../kategori/index.php"><i class="fas fa-list-alt"></i> Kelola Kategori</a>
        <a href="../users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
        <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
        <a href="../order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
        <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
        <a href="../review/index.php"><i class="fas fa-star"></i> Ulasan Produk</a>
    </div>
    <div class="view-site-link">
        <a href="../../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs</a>
    </div>
    <div class="logout-link">
        <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
</div>

<div class="main-content">
    <nav class="navbar navbar-expand-lg admin-navbar mb-4">
        <div class="container-fluid">
            <h4 class="mb-0">Tambah Produk Baru</h4>
            <span class="navbar-text">
                Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? 'Admin'); ?></strong>
            </span>
        </div>
    </nav>

    <div class="data-table-card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Formulir Produk Baru</h5>
        </div>
        <div class="card-body admin-form">
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <form action="proses_produk.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="mb-3">
                    <label for="name" class="form-label">Nama Produk <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize_output($form_data['name'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo sanitize_output($form_data['deskripsi'] ?? ''); ?></textarea>
                </div>
                 <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="harga" class="form-label">Harga (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="harga" name="harga" value="<?php echo sanitize_output($form_data['harga'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stok" class="form-label">Stok <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="stok" name="stok" value="<?php echo sanitize_output($form_data['stok'] ?? '0'); ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="kategori_id" class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                        <option value="">Pilih Kategori...</option>
                        <?php echo $kategori_options; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="foto_produk" class="form-label">Foto Produk</label>
                    <input type="file" class="form-control" id="foto_produk" name="foto_produk" accept="image/jpeg, image/png, image/gif">
                    <small class="form-text text-muted">Format: JPG, PNG, GIF. Maks 2MB.</small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="is_available" name="is_available" value="1" checked>
                    <label class="form-check-label" for="is_available">Tersedia untuk dijual</label>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i> Simpan Produk</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i> Batal</a>
            </form>
        </div>
    </div>
     <div class="mt-4">
        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Produk</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>