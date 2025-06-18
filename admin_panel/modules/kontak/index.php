<?php
require_once '../../../db_connect.php';

if (!isAdminLoggedIn()) {
    $_SESSION['login_message'] = "Akses ditolak. Silakan login sebagai admin.";
    redirect('../../../login.php');
}

$conn = connect_db();

// --- Fungsi Bantuan (Konsisten dengan modul lain) ---
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
$filter_status_baca = $_GET['filter_status_baca'] ?? '';
$sort_by = $_GET['sort'] ?? 'tanggal_kirim';
$sort_order = $_GET['order'] ?? 'DESC';

$allowed_sort_columns = ['message_id', 'nama_pengirim', 'email_pengirim', 'subjek_pesan', 'tanggal_kirim', 'status_baca'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'tanggal_kirim';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$base_sql_select = "SELECT message_id, nama_pengirim, email_pengirim, subjek_pesan, isi_pesan, tanggal_kirim, status_baca, user_id FROM contact_messages";
$base_sql_count = "SELECT COUNT(*) as count FROM contact_messages";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_query)) {
    $search_param = "%" . $search_query . "%";
    $where_clauses[] = "(nama_pengirim LIKE ? OR email_pengirim LIKE ? OR subjek_pesan LIKE ? OR isi_pesan LIKE ?)";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $types .= "ssss";
}
if ($filter_status_baca === 'belum_dibaca' || $filter_status_baca === 'sudah_dibaca') {
    $where_clauses[] = "status_baca = ?";
    // Nilai di DB adalah 'belum dibaca' atau 'sudah dibaca' (dengan spasi)
    $params[] = str_replace('_', ' ', $filter_status_baca);
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
$messages = get_data_list($conn, $sql_select_final, $params, $types, $items_per_page, $offset);

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pesan Kontak - Bloomarie Admin</title>
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
                <h4 class="mb-0">Manajemen Pesan Kontak</h4>
                 <span class="navbar-text">
                    Login sebagai: <strong><?php echo sanitize_output($_SESSION['admin_nama_lengkap'] ?? ($_SESSION['admin_username'] ?? 'Admin')); ?></strong>
                </span>
            </div>
        </nav>

        <div class="data-table-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-envelope-open-text me-2"></i>Daftar Pesan Masuk</h5>
                </div>
            <div class="card-body">
                <?php if (isset($_SESSION['kontak_success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['kontak_success_message']); unset($_SESSION['kontak_success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['kontak_error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo sanitize_output($_SESSION['kontak_error_message']); unset($_SESSION['kontak_error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="GET" class="mb-4 row g-3 align-items-center">
                    <div class="col-md-7">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari pengirim, email, subjek, atau isi pesan..." value="<?php echo sanitize_output($search_query); ?>">
                    </div>
                    <div class="col-md-3">
                         <select name="filter_status_baca" class="form-select form-select-sm">
                            <option value="">Semua Status Baca</option>
                            <option value="belum_dibaca" <?php if($filter_status_baca == 'belum_dibaca') echo 'selected'; ?>>Belum Dibaca</option>
                            <option value="sudah_dibaca" <?php if($filter_status_baca == 'sudah_dibaca') echo 'selected'; ?>>Sudah Dibaca</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="fas fa-filter"></i> Filter/Cari</button>
                    </div>
                    <?php if (!empty($search_query) || !empty($filter_status_baca)): ?>
                        <div class="col-12">
                            <a href="index.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-times"></i> Reset Filter</a>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=nama_pengirim&order=<?php echo ($sort_by == 'nama_pengirim' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Pengirim <?php if($sort_by == 'nama_pengirim') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th>Email</th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=subjek_pesan&order=<?php echo ($sort_by == 'subjek_pesan' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Subjek <?php if($sort_by == 'subjek_pesan') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=tanggal_kirim&order=<?php echo ($sort_by == 'tanggal_kirim' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Tanggal <?php if($sort_by == 'tanggal_kirim') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center"><a href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=status_baca&order=<?php echo ($sort_by == 'status_baca' && $sort_order == 'ASC' ? 'DESC' : 'ASC'); ?>">Status <?php if($sort_by == 'status_baca') echo $sort_order == 'ASC' ? '<i class="fas fa-sort-up"></i>' : '<i class="fas fa-sort-down"></i>'; ?></a></th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($messages) > 0): ?>
                                <?php foreach ($messages as $msg): ?>
                                <tr class="<?php if($msg['status_baca'] == 'belum dibaca') echo 'fw-bold'; ?>">
                                    <td><?php echo $msg['message_id']; ?></td>
                                    <td><?php echo sanitize_output($msg['nama_pengirim']); ?> <?php if($msg['user_id']) echo '<i class="fas fa-user-check text-success ms-1" title="Dari Pengguna Terdaftar"></i>'; ?></td>
                                    <td><a href="mailto:<?php echo sanitize_output($msg['email_pengirim']); ?>"><?php echo sanitize_output($msg['email_pengirim']); ?></a></td>
                                    <td><?php echo sanitize_output(substr($msg['subjek_pesan'] ?: '(Tanpa Subjek)', 0, 50)) . (strlen($msg['subjek_pesan'] ?? '') > 50 ? '...' : ''); ?></td>
                                    <td><?php echo date('d M Y, H:i', strtotime($msg['tanggal_kirim'])); ?></td>
                                    <td class="text-center">
                                        <?php if ($msg['status_baca'] == 'belum dibaca'): ?>
                                            <span class="badge bg-warning text-dark">Belum Dibaca</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Sudah Dibaca</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center action-buttons">
                                        <a href="lihat_pesan.php?message_id=<?php echo $msg['message_id']; ?>" class="btn btn-sm btn-info" title="Lihat Pesan"><i class="fas fa-eye"></i></a>
                                        <a href="proses_kontak_admin.php?action=delete&message_id=<?php echo $msg['message_id']; ?>"
                                           class="btn btn-sm btn-danger" title="Hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-4 text-muted fst-italic">
                                     <?php if (!empty($search_query) || !empty($filter_status_baca)): ?>
                                        Tidak ada pesan ditemukan dengan filter saat ini.
                                    <?php else: ?>
                                        Tidak ada pesan masuk.
                                    <?php endif; ?>
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Paginasi Pesan" class="mt-4 d-flex justify-content-center">
                    <ul class="pagination pagination-sm">
                        <?php if ($current_page > 1): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page - 1; ?>">Sebelumnya</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Sebelumnya</span></li>
                        <?php endif; ?>
                        <?php for ($i = 1; $i <= $total_pages; $i++):
                            if ($i == 1 || $i == $total_pages || ($i >= $current_page - 2 && $i <= $current_page + 2)): ?>
                            <li class="page-item <?php if ($i == $current_page) echo 'active'; ?>">
                                <a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                            <?php elseif ($i == $current_page - 3 || $i == $current_page + 3): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($current_page < $total_pages): ?>
                            <li class="page-item"><a class="page-link" href="?search=<?php echo urlencode($search_query); ?>&filter_status_baca=<?php echo urlencode($filter_status_baca); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>&page=<?php echo $current_page + 1; ?>">Berikutnya</a></li>
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
