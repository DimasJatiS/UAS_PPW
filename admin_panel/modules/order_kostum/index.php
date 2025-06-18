<?php
require_once '../../../db_connect.php'; // Path ke db_connect.php

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php');
}

$conn = connect_db();

// --- Fungsi Bantuan (Sama seperti sebelumnya) ---
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
$filter_status_request = $_GET['filter_status_request'] ?? '';
$sort_by = $_GET['sort'] ?? 'ok.tanggal_request';
$sort_order = $_GET['order'] ?? 'DESC';

$allowed_sort_columns = ['ok.kostum_request_id', 'u.username', 'ok.tanggal_request', 'ok.budget_estimasi', 'ok.status_request'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'ok.tanggal_request';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$base_sql_select = "SELECT ok.kostum_request_id, ok.user_id, u.username as customer_username, u.nama_lengkap as customer_fullname, 
                           ok.deskripsi_request, ok.budget_estimasi, ok.referensi_gambar_url, 
                           ok.status_request, ok.tanggal_request, ok.catatan_dari_toko
                    FROM orderkostum ok
                    LEFT JOIN users u ON ok.user_id = u.user_id";
$base_sql_count = "SELECT COUNT(ok.kostum_request_id) as count
                   FROM orderkostum ok
                   LEFT JOIN users u ON ok.user_id = u.user_id";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $where_clauses[] = "(CAST(ok.kostum_request_id AS CHAR) LIKE ? OR u.username LIKE ? OR u.nama_lengkap LIKE ? OR ok.deskripsi_request LIKE ?)";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}
if (!empty($filter_status_request)) {
    $where_clauses[] = "ok.status_request = ?";
    $params[] = $filter_status_request;
    $types .= "s";
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
$custom_orders = get_data_list($conn, $sql_select_final, $params, $types, $items_per_page, $offset);

$custom_order_statuses = [];
$result_statuses = $conn->query("SELECT DISTINCT status_request FROM orderkostum ORDER BY status_request ASC");
if($result_statuses) {
    while($row = $result_statuses->fetch_assoc()) {
        $custom_order_statuses[] = $row['status_request'];
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesanan Kostum - Bloomarie Admin</title>
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
            <a href="../users/index.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="../orders/index.php"><i class="fas fa-shopping-cart"></i> Pesanan Reguler</a>
            <a href="index.php" class="active"><i class="fas fa-paint-brush"></i> Pesanan Kostum</a>
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
                <h4 class="mb-0">Manajemen Pesanan Kostum</h4>
                <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="data-table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-paint-brush me-2"></i>Daftar Permintaan Pesanan Kostum</h5>
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['order_kostum_success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['order_kostum_success_message']); unset($_SESSION['order_kostum_success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['order_kostum_error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['order_kostum_error_message']); unset($_SESSION['order_kostum_error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="mb-4 row g-3 align-items-center">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari ID, Pelanggan, Deskripsi..." value="<?php echo sanitize_output($search_query); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="filter_status_request" class="form-select form-select-sm">
                            <option value="">Semua Status Permintaan</option>
                            <?php foreach($custom_order_statuses as $status_val): ?>
                                <option value="<?php echo $status_val; ?>" <?php if($filter_status_request == $status_val) echo 'selected'; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', sanitize_output($status_val))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="fas fa-filter"></i> Filter/Cari</button>
                    </div>
                    <?php if (!empty($search_query) || !empty($filter_status_request)): ?>
                        <div class="col-12">
                            <a href="index.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i> Reset Filter</a>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=ok.kostum_request_id&order=<?php echo ($sort_by == 'ok.kostum_request_id' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">ID <?php if($sort_by == 'ok.kostum_request_id') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=u.username&order=<?php echo ($sort_by == 'u.username' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Pelanggan <?php if($sort_by == 'u.username') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th>Deskripsi Singkat</th>
                                <th class="text-end"><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=ok.budget_estimasi&order=<?php echo ($sort_by == 'ok.budget_estimasi' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Budget (Est.) <?php if($sort_by == 'ok.budget_estimasi') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=ok.tanggal_request&order=<?php echo ($sort_by == 'ok.tanggal_request' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Tgl Permintaan <?php if($sort_by == 'ok.tanggal_request') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center"><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=ok.status_request&order=<?php echo ($sort_by == 'ok.status_request' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Status <?php if($sort_by == 'ok.status_request') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($custom_orders) > 0): ?>
                                <?php foreach ($custom_orders as $co): ?>
                                <tr>
                                    <td>#K<?php echo $co['kostum_request_id']; ?></td>
                                    <td><?php echo sanitize_output($co['customer_username'] ?? ($co['customer_fullname'] ?? 'N/A')); ?></td>
                                    <td><?php echo sanitize_output(substr($co['deskripsi_request'], 0, 70)) . (strlen($co['deskripsi_request']) > 70 ? '...' : ''); ?></td>
                                    <td class="text-end">Rp <?php echo $co['budget_estimasi'] ? number_format($co['budget_estimasi'], 0, ',', '.') : '-'; ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($co['tanggal_request'])); ?></td>
                                    <td class="text-center">
                                        <?php
                                        $status_text = ucfirst(str_replace('_', ' ', $co['status_request']));
                                        $badge_class = 'bg-secondary'; // Default
                                        if (in_array($co['status_request'], ['menunggu_konfirmasi_awal', 'diskusi'])) $badge_class = 'bg-warning text-dark';
                                        elseif ($co['status_request'] === 'diterima') $badge_class = 'bg-success';
                                        elseif ($co['status_request'] === 'ditolak' || $co['status_request'] === 'dibatalkan') $badge_class = 'bg-danger';
                                        elseif ($co['status_request'] === 'selesai_diskusi') $badge_class = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo sanitize_output($status_text); ?></span>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <a href="detail_kostum.php?request_id=<?php echo $co['kostum_request_id']; ?>" class="btn btn-sm btn-info" title="Lihat Detail & Kelola"><i class="fas fa-eye"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted fst-italic">
                                    <?php if (!empty($search_query) || !empty($filter_status_request)): ?>
                                        Tidak ada permintaan pesanan kostum ditemukan dengan filter saat ini.
                                    <?php else: ?>
                                        Belum ada permintaan pesanan kostum.
                                    <?php endif; ?>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginasi Pesanan Kostum" class="mt-4 d-flex justify-content-center">
                     <ul class="pagination pagination-sm">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page - 1; ?>">Sebelumnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_request=<?php echo urlencode($filter_status_request); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page + 1; ?>">Berikutnya</a></li>
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
