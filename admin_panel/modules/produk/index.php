<?php
require_once '../../../db_connect.php'; // Path ke db_connect.php dari modules/produk/

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php'); // Redirect ke login di root
}

$conn = connect_db();
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'p.product_id';
$sort_order = $_GET['order'] ?? 'DESC';

// Validasi kolom sort untuk keamanan
$allowed_sort_columns = ['p.product_id', 'p.name', 'k.nama_kategori', 'p.harga', 'p.stok', 'p.is_available'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'p.product_id';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';


$sql = "SELECT p.product_id, p.name, p.harga, p.stok, p.is_available, p.foto_produk, k.nama_kategori
        FROM produk p
        LEFT JOIN kategoriproduk k ON p.kategori_id = k.kategori_id";

$params = [];
$types = "";

if (!empty($search_query)) {
    $sql .= " WHERE p.name LIKE ? OR k.nama_kategori LIKE ? OR p.deskripsi LIKE ?"; // Tambah pencarian deskripsi
    $search_param = "%" . $search_query . "%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$sql .= " ORDER BY $sort_by $sort_order";

// Implementasi Paginasi (Contoh sederhana)
$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Hitung total item untuk paginasi (tanpa ORDER BY dan LIMIT sementara)
$sql_total = "SELECT COUNT(p.product_id) as total
              FROM produk p
              LEFT JOIN kategoriproduk k ON p.kategori_id = k.kategori_id";
if (!empty($search_query)) {
    $sql_total .= " WHERE p.name LIKE ? OR k.nama_kategori LIKE ? OR p.deskripsi LIKE ?";
}

$stmt_total = $conn->prepare($sql_total);
if (!empty($params)) { // Gunakan params yang sama untuk search
    $stmt_total->bind_param($types, ...$params);
}
$stmt_total->execute();
$total_items = $stmt_total->get_result()->fetch_assoc()['total'];
$stmt_total->close();
$total_pages = ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;
$offset = ($current_page - 1) * $items_per_page;

$sql .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";


$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_produk = $stmt->get_result();
$produks = [];
while ($row = $result_produk->fetch_assoc()) {
    $produks[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manajemen Produk - Bloomarie Admin</title>
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
            <a href="index.php" class="active"><i class="fas fa-box-open"></i> Kelola Produk</a> 
            <a href="../kategori/index.php"><i class="fas fa-list-alt"></i> Kelola Kategori</a> 
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
                <h4 class="mb-0">Manajemen Produk</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="data-table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>Daftar Produk</h5>
                <a href="tambah_produk.php" class="btn btn-success btn-sm"><i class="fas fa-plus me-1"></i> Tambah Produk</a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama, kategori, atau deskripsi produk..." value="<?php echo sanitize_output($search_query); ?>">
                        <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="fas fa-search"></i> Cari</button>
                         <?php if (!empty($search_query)): ?>
                            <a href="index.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i> Reset</a>
                        <?php endif; ?>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Foto</th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=p.name&order=<?php echo ($sort_by == 'p.name' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Nama Produk <?php if($sort_by == 'p.name') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=k.nama_kategori&order=<?php echo ($sort_by == 'k.nama_kategori' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Kategori <?php if($sort_by == 'k.nama_kategori') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-end"><a href="?search=<?php echo urlencode($search_query); ?>&sort=p.harga&order=<?php echo ($sort_by == 'p.harga' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Harga <?php if($sort_by == 'p.harga') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center"><a href="?search=<?php echo urlencode($search_query); ?>&sort=p.stok&order=<?php echo ($sort_by == 'p.stok' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Stok <?php if($sort_by == 'p.stok') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center"><a href="?search=<?php echo urlencode($search_query); ?>&sort=p.is_available&order=<?php echo ($sort_by == 'p.is_available' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Status <?php if($sort_by == 'p.is_available') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($produks) > 0): ?>
                                <?php foreach ($produks as $produk): ?>
                                <tr>
                                    <td><?php echo $produk['product_id']; ?></td>
                                    <td>
                                        <?php if (!empty($produk['foto_produk'])): ?>
                                            <img src="../../../<?php echo sanitize_output($produk['foto_produk']); ?>" alt="<?php echo sanitize_output($produk['name']); ?>" class="img-thumbnail-small">
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo sanitize_output($produk['name']); ?></td>
                                    <td><?php echo sanitize_output($produk['nama_kategori'] ?? 'N/A'); ?></td>
                                    <td class="text-end">Rp <?php echo number_format($produk['harga'], 0, ',', '.'); ?></td>
                                    <td class="text-center"><?php echo $produk['stok']; ?></td>
                                    <td class="text-center">
                                        <?php if ($produk['is_available'] == 1): ?>
                                            <span class="badge bg-success">Tersedia</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Nonaktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <a href="edit_produk.php?product_id=<?php echo $produk['product_id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                        <a href="proses_produk.php?action=delete&product_id=<?php echo $produk['product_id']; ?>"
                                           class="btn btn-sm btn-danger" title="Hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus produk \'<?php echo htmlspecialchars(addslashes($produk['name']), ENT_QUOTES); ?>\'? Tindakan ini tidak dapat diurungkan.');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="8" class="text-center py-4 text-muted fst-italic">
                                    <?php if (!empty($search_query)): ?>
                                        Tidak ada produk ditemukan untuk pencarian "<?php echo sanitize_output($search_query); ?>".
                                    <?php else: ?>
                                        Belum ada produk. Silakan tambahkan produk baru.
                                    <?php endif; ?>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div> <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginasi Produk" class="mt-4 d-flex justify-content-center">
                    <ul class="pagination pagination-sm">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page - 1; ?>">Sebelumnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page + 1; ?>">Berikutnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Berikutnya</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            </div> </div> <div class="mt-4">
             <a href="../../admin_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
        </div>
    </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

