<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../../db_connect.php'; 

// Pastikan hanya admin yang bisa akses
if (!isAdminLoggedIn()) {
    redirect('../../../login.php');
}
$conn = connect_db();

// Logika Pencarian
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql_reviews = "SELECT r.review_id, r.rating, r.comment, r.created_at, u.username, p.name as product_name 
                FROM reviews r 
                JOIN users u ON r.user_id = u.user_id 
                JOIN produk p ON r.product_id = p.product_id";

if (!empty($search_query)) {
    $sql_reviews .= " WHERE u.username LIKE ? OR p.name LIKE ? OR r.comment LIKE ?";
}
$sql_reviews .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql_reviews);
if (!empty($search_query)) {
    $search_term = "%" . $search_query . "%";
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
}
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Ulasan Produk - Bloomarie Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,100..700&family=Inter:wght@300;400;500;600&family=Cooper+Black&family=Luxurious+Script&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../../assets/css/sidebar_style.css">
        <link rel="stylesheet" href="../../assets/css/style.css">
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
            <a href="../kontak/index.php"><i class="fas fa-envelope-open-text"></i> Pesan Kontak</a>
            <a href="index.php" class="active"><i class="fas fa-star"></i> Ulasan Produk</a>
            <a href="#"><i class="fas fa-cogs"></i> Pengaturan Situs</a>
        </div>
        <div class="view-site-link"> <a href="../../../index.php" target="_blank"><i class="fas fa-external-link-alt"></i> Lihat Situs</a> </div>
        <div class="logout-link"> <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a> </div>
    </div>

    <div class="main-content">
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Kelola Ulasan Produk</h4>
            </div>
        </nav>
        
        <?php if (isset($_SESSION['review_success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['review_success_message']; unset($_SESSION['review_success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['review_error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['review_error_message']; unset($_SESSION['review_error_message']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Daftar Semua Ulasan</span>
                <form action="index.php" method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control form-control-sm me-2" placeholder="Cari ulasan..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-sm btn-primary-admin">Cari</button>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Produk</th>
                                <th>Pengguna</th>
                                <th>Rating</th>
                                <th>Komentar</th>
                                <th>Tanggal</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($reviews) > 0): ?>
                                <?php foreach ($reviews as $review): ?>
                                <tr>
                                    <td><?php echo $review['review_id']; ?></td>
                                    <td><?php echo htmlspecialchars($review['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($review['username']); ?></td>
                                    <td>
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?><i class="fas fa-star <?php echo ($i > $review['rating']) ? 'text-muted' : ''; ?>"></i><?php endfor; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($review['comment'], 0, 50)); ?><?php echo strlen($review['comment']) > 50 ? '...' : ''; ?></td>
                                    <td><?php echo date('d M Y', strtotime($review['created_at'])); ?></td>
                                    <td class="text-center">
                                        <a href="delete_review.php?review_id=<?php echo $review['review_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus ulasan ini secara permanen?');">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Tidak ada ulasan yang ditemukan.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>