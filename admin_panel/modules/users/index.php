<?php
require_once '../../../db_connect.php'; // Path ke db_connect.php

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php');
}

$conn = connect_db();

// --- Fungsi Bantuan (Salin dari modul kontak/produk) ---
function get_count($conn, $sql, $params = [], $types = "") {
    $stmt = $conn->prepare($sql);
    if (!$stmt) { error_log("Prepare failed (get_count): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql); return 0; }
    if (!empty($params) && !empty($types)) { if (!$stmt->bind_param($types, ...$params)) { error_log("Binding parameters failed (get_count): (" . $stmt->errno . ") " . $stmt->error); $stmt->close(); return 0; } }
    if (!$stmt->execute()) { error_log("Execute failed (get_count): (" . $stmt->errno . ") " . $stmt->error); $stmt->close(); return 0; }
    $result = $stmt->get_result(); $row = $result->fetch_assoc(); $stmt->close();
    return $row ? (int)$row['count'] : 0;
}
function get_data_list($conn, $sql, $params = [], $types = "", $limit = 10, $offset = 0) {
    $sql_with_limit = $sql . " LIMIT ? OFFSET ?";
    $params_with_limit = array_merge($params, [$limit, $offset]);
    $types_with_limit = $types . "ii";
    $stmt = $conn->prepare($sql_with_limit);
    if (!$stmt) { error_log("Prepare failed (get_data_list): (" . $conn->errno . ") " . $conn->error . " SQL: " . $sql_with_limit); return []; }
    if (!empty($params_with_limit) && !empty($types_with_limit)) { if (!$stmt->bind_param($types_with_limit, ...$params_with_limit)) { error_log("Binding parameters failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error); $stmt->close(); return []; } }
    if (!$stmt->execute()) { error_log("Execute failed (get_data_list): (" . $stmt->errno . ") " . $stmt->error); $stmt->close(); return []; }
    $result = $stmt->get_result(); $data = [];
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
    $stmt->close(); return $data;
}
// --- End Fungsi Bantuan ---


$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'user_id';
$sort_order = $_GET['order'] ?? 'DESC';

$allowed_sort_columns = ['user_id', 'username', 'nama_lengkap', 'email', 'role', 'created_at'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'user_id';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$base_sql_select = "SELECT user_id, username, nama_lengkap, email, role, created_at FROM users";
$base_sql_count = "SELECT COUNT(*) as count FROM users";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $where_clauses[] = "(username LIKE ? OR nama_lengkap LIKE ? OR email LIKE ?)";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
    $types .= "sss";
}

$sql_where = "";
if (!empty($where_clauses)) {
    $sql_where = " WHERE " . implode(" AND ", $where_clauses);
}

$sql_count_final = $base_sql_count . $sql_where;
$total_items = get_count($conn, $sql_count_final, $params, $types);

$items_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max(1, $current_page); 
$total_pages = ceil($total_items / $items_per_page);
$current_page = min($current_page, $total_pages > 0 ? $total_pages : 1); 
$offset = ($current_page - 1) * $items_per_page;

$sql_select_final = $base_sql_select . $sql_where . " ORDER BY $sort_by $sort_order";
$users = get_data_list($conn, $sql_select_final, $params, $types, $items_per_page, $offset);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Bloomarie Admin</title>
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
        <nav class="navbar navbar-expand-lg admin-navbar mb-4">
            <div class="container-fluid">
                <h4 class="mb-0">Manajemen Pengguna</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="data-table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Daftar Pengguna</h5>
                <a href="tambah_user.php" class="btn btn-success btn-sm"><i class="fas fa-user-plus me-1"></i> Tambah Pengguna</a>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['user_success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['user_success_message']); unset($_SESSION['user_success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['user_error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['user_error_message']); unset($_SESSION['user_error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="mb-4">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari username, nama, atau email..." value="<?php echo sanitize_output($search_query); ?>">
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
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=username&order=<?php echo ($sort_by == 'username' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Username <?php if($sort_by == 'username') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=nama_lengkap&order=<?php echo ($sort_by == 'nama_lengkap' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Nama Lengkap <?php if($sort_by == 'nama_lengkap') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=email&order=<?php echo ($sort_by == 'email' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Email <?php if($sort_by == 'email') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=role&order=<?php echo ($sort_by == 'role' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Role <?php if($sort_by == 'role') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&sort=created_at&order=<?php echo ($sort_by == 'created_at' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Tanggal Daftar <?php if($sort_by == 'created_at') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo sanitize_output($user['username']); ?></td>
                                    <td><?php echo sanitize_output($user['nama_lengkap'] ?? 'N/A'); ?></td>
                                    <td><?php echo sanitize_output($user['email']); ?></td>
                                    <td>
                                        <?php
                                        $role_class = 'secondary';
                                        if ($user['role'] == 'superadmin') $role_class = 'danger';
                                        else if ($user['role'] == 'admin') $role_class = 'warning text-dark';
                                        else if ($user['role'] == 'customer') $role_class = 'info text-dark';
                                        ?>
                                        <span class="badge bg-<?php echo $role_class; ?>"><?php echo ucfirst(sanitize_output($user['role'])); ?></span>
                                    </td>
                                    <td><?php echo date('d M Y, H:i', strtotime($user['created_at'])); ?></td>
                                    <td class="text-center action-buttons">
                                        <a href="edit_user.php?user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary" title="Edit"><i class="fas fa-user-edit"></i></a>
                                        <?php if ($user['role'] !== 'superadmin' && $_SESSION['admin_user_id'] != $user['user_id']): ?>
                                        <a href="proses_user.php?action=delete&user_id=<?php echo $user['user_id']; ?>"
                                           class="btn btn-sm btn-danger" title="Hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pengguna \'<?php echo htmlspecialchars(addslashes($user['username']), ENT_QUOTES); ?>\'?');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted fst-italic">
                                    <?php if (!empty($search_query)): ?>
                                        Tidak ada pengguna ditemukan untuk pencarian "<?php echo sanitize_output($search_query); ?>".
                                    <?php else: ?>
                                        Belum ada pengguna terdaftar.
                                    <?php endif; ?>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginasi Pengguna" class="mt-4 d-flex justify-content-center">
                    <ul class="pagination pagination-sm">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page - 1; ?>">Sebelumnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page + 1; ?>">Berikutnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Berikutnya</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            </div>
        </div>
         <div class="mt-4">
             <a href="../../admin_dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
