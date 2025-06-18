<?php
require_once '../../../db_connect.php';

// Pastikan hanya admin yang bisa mengakses
if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php');
}

$conn = connect_db();

// 1. Ambil ID pesan dari URL dan pastikan valid
$message_id = isset($_GET['message_id']) ? (int)$_GET['message_id'] : 0;
if ($message_id <= 0) {
    $_SESSION['kontak_error_message'] = "Permintaan tidak valid. ID Pesan tidak ditemukan.";
    redirect('index.php');
}

// 2. Update status pesan menjadi 'sudah dibaca' jika sebelumnya 'belum dibaca'
$stmt_update = $conn->prepare("UPDATE contact_messages SET status_baca = 'sudah dibaca' WHERE message_id = ? AND status_baca = 'belum dibaca'");
if ($stmt_update) {
    $stmt_update->bind_param("i", $message_id);
    $stmt_update->execute();
    $stmt_update->close();
} else {
    error_log("Gagal mempersiapkan statement update status pesan: " . $conn->error);
}

// 3. Ambil detail lengkap pesan dari database
$stmt_select = $conn->prepare("SELECT * FROM contact_messages WHERE message_id = ?");
if (!$stmt_select) {
    error_log("Gagal mempersiapkan statement select pesan: " . $conn->error);
    $_SESSION['kontak_error_message'] = "Terjadi kesalahan sistem saat mengambil data pesan.";
    redirect('index.php');
}

$stmt_select->bind_param("i", $message_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$message = $result->fetch_assoc();
$stmt_select->close();

// 4. Jika pesan tidak ditemukan, kembalikan ke halaman utama dengan error
if (!$message) {
    $_SESSION['kontak_error_message'] = "Pesan dengan ID #" . sanitize_output($message_id) . " tidak ditemukan.";
    redirect('index.php');
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Pesan: <?php echo sanitize_output($message['subjek_pesan']); ?> - Bloomarie Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/sidebar_style.css">
    <style>
        .message-details dl {
            margin-bottom: 0;
        }
        .message-details dt {
            font-weight: 600;
            color: #6c757d;
        }
        .message-details dd {
            margin-left: 0;
        }
        .message-body {
            white-space: pre-wrap; /* Mempertahankan format line break dan spasi */
            background-color: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1rem;
            border: 1px solid #dee2e6;
        }
    </style>
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
            <a href="../users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="../order_kostum/index.php"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
            <a href="index.php" class="active"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
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
                <h4 class="mb-0">Detail Pesan</h4>
                 <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-envelope-open-text me-2"></i>
                    <strong>Subjek:</strong> <?php echo sanitize_output($message['subjek_pesan'] ?: '(Tanpa Subjek)'); ?>
                </h5>
                <span class="text-muted">
                    <i class="fas fa-calendar-alt me-1"></i> <?php echo date('d M Y, H:i', strtotime($message['tanggal_kirim'])); ?>
                </span>
            </div>
            <div class="card-body message-details">
                <dl class="row mb-4">
                    <dt class="col-sm-3 col-lg-2">Nama Pengirim</dt>
                    <dd class="col-sm-9 col-lg-10"><?php echo sanitize_output($message['nama_pengirim']); ?>
                        <?php if($message['user_id']) echo '<span class="badge bg-success ms-2">Pengguna Terdaftar</span>'; ?>
                    </dd>

                    <dt class="col-sm-3 col-lg-2">Email Pengirim</dt>
                    <dd class="col-sm-9 col-lg-10">
                        <a href="mailto:<?php echo sanitize_output($message['email_pengirim']); ?>"><?php echo sanitize_output($message['email_pengirim']); ?></a>
                    </dd>
                </dl>

                <hr>

                <h6 class="mt-4 mb-3">Isi Pesan:</h6>
                <div class="message-body">
                    <?php echo nl2br(sanitize_output($message['isi_pesan'])); ?>
                </div>
            </div>
            <div class="card-footer text-end">
                 <a href="mailto:<?php echo sanitize_output($message['email_pengirim']); ?>?subject=Re: <?php echo sanitize_output($message['subjek_pesan']); ?>" class="btn btn-primary">
                    <i class="fas fa-reply me-1"></i> Balas via Email
                </a>
                <a href="proses_kontak_admin.php?action=delete&message_id=<?php echo $message['message_id']; ?>"
                   class="btn btn-danger"
                   onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini secara permanen?');">
                   <i class="fas fa-trash me-1"></i> Hapus
                </a>
            </div>
        </div>

        <div class="mt-4">
             <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Daftar Pesan</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>