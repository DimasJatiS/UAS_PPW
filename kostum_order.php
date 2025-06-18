<<<<<<< HEAD
<?php
session_start(); // Atau pastikan sudah ada di db_connect.php di baris paling atas
require_once 'db_connect.php';

// Inisialisasi koneksi database
$conn = connect_db(); // Pastikan fungsi ini mengembalikan koneksi mysqli

// Pastikan pelanggan sudah login
if (!isCustomerLoggedIn()) { // Fungsi dari db_connect.php
    $_SESSION['login_message'] = "You have to log in to have a special orders.";
    // Simpan URL saat ini agar bisa kembali setelah login
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    redirect('login.php'); // Fungsi redirect dari db_connect.php
    exit();
}

// Inisialisasi variabel
$cart_item_count = 0;
$cart_page_url = 'gallery_page.php?from_checkout=empty_cart&source=index_header'; // Default

// Ambil jumlah item di keranjang jika user login (menggunakan mysqli)
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $user_id_session = (int)$_SESSION['user']['user_id']; // Pastikan integer
    $sql_cart_count = "SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?";
    $stmt_cart_count = $conn->prepare($sql_cart_count);

    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id_session);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        $row_cart_count = $result_cart_count->fetch_assoc();
        if ($row_cart_count && $row_cart_count['total_items'] > 0) {
            $cart_item_count = (int)$row_cart_count['total_items'];
        }
        $stmt_cart_count->close();
    } else {
        error_log("mysqli_prepare failed for cart count: " . $conn->error);
        // Biarkan $cart_item_count = 0 jika terjadi error
    }
}


if ($cart_item_count > 0) {
    $cart_page_url = 'checkout.php';
}

// Logika untuk header navigasi (konsisten dengan halaman publik lainnya)
$cart_page_url_header = 'checkout.php';
$cart_item_count_header = 0;
$is_user_logged_in_header = true; // Sudah pasti true di sini
$current_user_info = $_SESSION['user']; // Ambil info user dari session
$logout_url_header = "logout.php";

if (isset($current_user_info['user_id'])) {
    $user_id_session_header = (int)$current_user_info['user_id'];
    $stmt_cart_count_hd = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count_hd) {
        $stmt_cart_count_hd->bind_param("i", $user_id_session_header);
        $stmt_cart_count_hd->execute();
        $result_cart_count_hd = $stmt_cart_count_hd->get_result();
        if ($row_count_hd = $result_cart_count_hd->fetch_assoc()) {
            $cart_item_count_header = (int)($row_count_hd['total_items'] ?? 0);
        }
        $stmt_cart_count_hd->close();
    }
}
if ($cart_item_count_header > 0) {
    $cart_page_url_header = 'checkout.php';
}

// Ambil pesan error/sukses dan data form jika ada (dari proses_kostum_order.php)
$error_message = $_SESSION['kostum_order_error'] ?? '';
unset($_SESSION['kostum_order_error']);
$success_message = $_SESSION['kostum_order_success'] ?? '';
unset($_SESSION['kostum_order_success']);
$form_data_kostum = $_SESSION['form_data_kostum_request'] ?? []; // Sesuaikan nama session
unset($_SESSION['form_data_kostum_request']);

$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

// Cek apakah ada user yang login (apapun rolenya)
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $_SESSION['user']['username'] ?? '';
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? ''; // Pastikan ini ada di session
$current_email = $_SESSION['user']['email'] ?? ''; // Pastikan ini ada di session

// Ambil pesan error/sukses untuk form kontak di #contact-section
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Khusus - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <?php include 'includes/header.php'; ?>

        <section class="custom-order-section py-5">
            <div class="container" style="max-width: 800px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2.8rem, 8vw, 5rem); line-height:1.1;">Custom Orders</h1>
                    <p class="lead" style="color: var(--text-medium); font-family: var(--secondary-font);">
                        Have a special bouquet in mind? Tell us your idea!
                    </p>
                </div>

                <div class="card shadow-lg" style="border-radius: var(--border-radius-xl); border: none; background-color: var(--bg-section-alt);">
                    <div class="card-body p-4 p-md-5">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert" style="border-radius: var(--border-radius-md);">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert" style="border-radius: var(--border-radius-md);">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo sanitize_output($success_message); ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="proses_kostum_order.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="deskripsi_request" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Describe Your Desired Bouquet <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="deskripsi_request" name="deskripsi_request" rows="6" placeholder="Example: A bouquet of red and white roses for a 5th wedding anniversary, with a romantic and elegant feel, plus a custom greeting card and a gold satin ribbon."  required minlength="20"><?php echo sanitize_output($form_data_kostum['deskripsi_request'] ?? ''); ?></textarea>
                                <div class="invalid-feedback">Please describe your desired bouquet in detail (minimum 20 characters).</div>
                            </div>
                            <div class="mb-4">
                                <label for="budget_estimasi" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Estimated Budget (IDR) (Optional)</label>
                                <input type="number" class="form-control" id="budget_estimasi" name="budget_estimasi" placeholder="Contoh: 500000" value="<?php echo sanitize_output($form_data_kostum['budget_estimasi'] ?? ''); ?>" step="10000" min="0">
                                <small class="form-text" style="color: var(--text-light);">Leave blank if you'd like us to provide a recommendation based on your description.</small>
                            </div>
                            <div class="mb-4">
                                <label for="referensi_gambar_file" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Upload a Reference Image (Optional)</label>
                                <input type="file" class="form-control" id="referensi_gambar_file" name="referensi_gambar_file" accept="image/jpeg, image/png, image/gif">
                                <small class="form-text" style="color: var(--text-light);">Allowed formats: JPG, PNG, GIF. Maximum size: 2MB.</small>
                                <input type="hidden" name="existing_referensi_gambar_path" value="<?php echo sanitize_output($form_data_kostum['referensi_gambar_url'] ?? ''); ?>">
                                <?php if (!empty($form_data_kostum['referensi_gambar_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo sanitize_output($form_data_kostum['referensi_gambar_url']); ?>" alt="Referensi Sebelumnya" style="max-width: 100px; max-height: 100px; border-radius: var(--border-radius-sm); margin-right: 10px;">
                                        <small style="color: var(--text-medium);">Current image: <?php echo basename(sanitize_output($form_data_kostum['referensi_gambar_url'])); ?></small><br>
                                        <small class="text-info">Upload a new file to replace this one.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-4 pt-2">
                                <button type="submit" class="btn btn-submit btn-lg px-5"><i class="fas fa-paper-plane me-2"></i> Send Request</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                    <a href="gallery_page.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-images me-1"></i> Discover Our Gallery</a>
                </div>
            </div>
        </section>

        <footer class="actual-footer py-3">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div> <!-- #page-wrapper -->

    <!-- Modal Kontak (Sama seperti di index.php) -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel" style="font-family: var(--primary-font); color: var(--text-dark);">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlertsKostumPage" class="mb-3"></div>
                    <form id="contactModalFormKostumPage">
                        <input type="hidden" name="source_page" value="modal_contact_ajax_kostum_page">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim_kostum_page" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control contact-input" id="modal_nama_pengirim_kostum_page" name="nama_pengirim" required
                                       value="<?php echo sanitize_output($current_fullname_header ?: $current_username_header); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim_kostum_page" class="form-label">Email Anda <span class="text-danger">*</span></label>
                                <input type="email" class="form-control contact-input" id="modal_email_pengirim_kostum_page" name="email_pengirim" required
                                       value="<?php echo sanitize_output($current_email_header); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan_kostum_page" class="form-label">Subjek</label>
                            <input type="text" class="form-control contact-input" id="modal_subjek_pesan_kostum_page" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan_kostum_page" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control contact-input" id="modal_isi_pesan_kostum_page" name="isi_pesan" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-submit">Kirim Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_server.js"></script>
    
    <script>
        // Script untuk toggle search input (sama seperti index.php, pastikan ID unik)
        document.addEventListener('DOMContentLoaded', function() {
            const searchIconToggleKostumPage = document.getElementById('searchIconToggleKostumPage');
            const searchInputKostumPage = document.getElementById('searchInputKostumPage');
            const searchFormKostumPage = document.getElementById('searchFormKostumPage');
            let searchVisibleKostumPage = false;

            if(searchIconToggleKostumPage && searchInputKostumPage && searchFormKostumPage){
                searchIconToggleKostumPage.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!searchVisibleKostumPage) {
                        searchInputKostumPage.style.display = 'inline-block';
                        setTimeout(() => { searchInputKostumPage.style.width = '200px'; }, 0);
                        searchInputKostumPage.focus();
                        searchVisibleKostumPage = true;
                    } else { /* ... (logika toggle search sama) ... */
                        if (searchInputKostumPage.value.trim() !== '') { searchFormKostumPage.submit(); }
                        else { searchInputKostumPage.style.width = '0'; setTimeout(() => { searchInputKostumPage.style.display = 'none'; }, 300); searchVisibleKostumPage = false; }
                    }
                });
                searchInputKostumPage.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { searchFormKostumPage.submit(); } }
                });
                document.addEventListener('click', function(event) {
                    if (searchVisibleKostumPage && !searchFormKostumPage.contains(event.target) && event.target !== searchIconToggleKostumPage && !searchIconToggleKostumPage.contains(event.target)) {
                        searchInputKostumPage.style.width = '0'; setTimeout(() => { searchInputKostumPage.style.display = 'none'; }, 300); searchVisibleKostumPage = false;
                    }
                });
            }

            // Bootstrap form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // AJAX untuk Contact Modal (sama seperti index.php, pastikan ID unik)
            const contactModalElKostumPage = document.getElementById('contactModal');
            if (contactModalElKostumPage) {
                const contactModalFormKostumPage = document.getElementById('contactModalFormKostumPage');
                const contactModalAlertsKostumPage = document.getElementById('contactModalAlertsKostumPage');
                if (contactModalFormKostumPage) {
                    contactModalFormKostumPage.addEventListener('submit', function(event) { /* ... (logika AJAX modal sama) ... */
                        event.preventDefault(); contactModalAlertsKostumPage.innerHTML = '';
                        const formData = new FormData(contactModalFormKostumPage);
                        const submitButton = contactModalFormKostumPage.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true; submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';
                        fetch('proses_kontak.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(response => response.json()).then(data => {
                            if (data.status === 'success') {
                                contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                                contactModalFormKostumPage.reset();
                                <?php if ($is_user_logged_in_header): ?>
                                const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname_header ?: $current_username_header)); ?>";
                                const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email_header)); ?>";
                                if(document.getElementById('modal_nama_pengirim_kostum_page')) document.getElementById('modal_nama_pengirim_kostum_page').value = defaultNameModal;
                                if(document.getElementById('modal_email_pengirim_kostum_page')) document.getElementById('modal_email_pengirim_kostum_page').value = defaultEmailModal;
                                <?php endif; ?>
                                setTimeout(() => { const modalInstance = bootstrap.Modal.getInstance(contactModalElKostumPage); if (modalInstance) modalInstance.hide(); contactModalAlertsKostumPage.innerHTML = ''; }, 3000);
                            } else {
                                let errMsg = data.message || 'Terjadi kesalahan.'; if (data.errors && typeof data.errors === 'object') { errMsg += '<ul class="mt-2 mb-0 text-start">'; for (const field in data.errors) { errMsg += `<li>${data.errors[field]}</li>`; } errMsg += '</ul>'; }
                                contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${errMsg} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                            }
                        }).catch(error => {
                            contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">Tidak dapat terhubung ke server. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                        }).finally(() => { submitButton.disabled = false; submitButton.innerHTML = originalButtonText; });
                    });
                }
                contactModalElKostumPage.addEventListener('hidden.bs.modal', function () { if (contactModalAlertsKostumPage) contactModalAlertsKostumPage.innerHTML = ''; });
            }
        });
    </script>
</body>
</html>
=======
<?php
session_start(); // Atau pastikan sudah ada di db_connect.php di baris paling atas
require_once 'db_connect.php';

// Inisialisasi koneksi database
$conn = connect_db(); // Pastikan fungsi ini mengembalikan koneksi mysqli

// Pastikan pelanggan sudah login
if (!isCustomerLoggedIn()) { // Fungsi dari db_connect.php
    $_SESSION['login_message'] = "You have to log in to have a special orders.";
    // Simpan URL saat ini agar bisa kembali setelah login
    $_SESSION['redirect_url'] = basename($_SERVER['PHP_SELF']);
    redirect('login.php'); // Fungsi redirect dari db_connect.php
    exit();
}

// Inisialisasi variabel
$cart_item_count = 0;
$cart_page_url = 'gallery_page.php?from_checkout=empty_cart&source=index_header'; // Default

// Ambil jumlah item di keranjang jika user login (menggunakan mysqli)
if (isset($_SESSION['user']) && isset($_SESSION['user']['user_id'])) {
    $user_id_session = (int)$_SESSION['user']['user_id']; // Pastikan integer
    $sql_cart_count = "SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?";
    $stmt_cart_count = $conn->prepare($sql_cart_count);

    if ($stmt_cart_count) {
        $stmt_cart_count->bind_param("i", $user_id_session);
        $stmt_cart_count->execute();
        $result_cart_count = $stmt_cart_count->get_result();
        $row_cart_count = $result_cart_count->fetch_assoc();
        if ($row_cart_count && $row_cart_count['total_items'] > 0) {
            $cart_item_count = (int)$row_cart_count['total_items'];
        }
        $stmt_cart_count->close();
    } else {
        error_log("mysqli_prepare failed for cart count: " . $conn->error);
        // Biarkan $cart_item_count = 0 jika terjadi error
    }
}


if ($cart_item_count > 0) {
    $cart_page_url = 'checkout.php';
}

// Logika untuk header navigasi (konsisten dengan halaman publik lainnya)
$cart_page_url_header = 'checkout.php';
$cart_item_count_header = 0;
$is_user_logged_in_header = true; // Sudah pasti true di sini
$current_user_info = $_SESSION['user']; // Ambil info user dari session
$logout_url_header = "logout.php";

if (isset($current_user_info['user_id'])) {
    $user_id_session_header = (int)$current_user_info['user_id'];
    $stmt_cart_count_hd = $conn->prepare("SELECT SUM(kuantitas) as total_items FROM cart WHERE user_id = ?");
    if ($stmt_cart_count_hd) {
        $stmt_cart_count_hd->bind_param("i", $user_id_session_header);
        $stmt_cart_count_hd->execute();
        $result_cart_count_hd = $stmt_cart_count_hd->get_result();
        if ($row_count_hd = $result_cart_count_hd->fetch_assoc()) {
            $cart_item_count_header = (int)($row_count_hd['total_items'] ?? 0);
        }
        $stmt_cart_count_hd->close();
    }
}
if ($cart_item_count_header > 0) {
    $cart_page_url_header = 'checkout.php';
}

// Ambil pesan error/sukses dan data form jika ada (dari proses_kostum_order.php)
$error_message = $_SESSION['kostum_order_error'] ?? '';
unset($_SESSION['kostum_order_error']);
$success_message = $_SESSION['kostum_order_success'] ?? '';
unset($_SESSION['kostum_order_success']);
$form_data_kostum = $_SESSION['form_data_kostum_request'] ?? []; // Sesuaikan nama session
unset($_SESSION['form_data_kostum_request']);

$login_url = "login.php";
$register_url = "register.php";
$logout_url = "logout.php";

// Cek apakah ada user yang login (apapun rolenya)
$is_any_user_logged_in = isset($_SESSION['user']);
$current_username = $_SESSION['user']['username'] ?? '';
$current_fullname = $_SESSION['user']['nama_lengkap'] ?? ''; // Pastikan ini ada di session
$current_email = $_SESSION['user']['email'] ?? ''; // Pastikan ini ada di session

// Ambil pesan error/sukses untuk form kontak di #contact-section
$index_contact_error = $_SESSION['index_contact_error'] ?? ''; unset($_SESSION['index_contact_error']);
$index_contact_success = $_SESSION['index_contact_success'] ?? ''; unset($_SESSION['index_contact_success']);
$form_data_idx = $_SESSION['form_data_index_contact'] ?? []; unset($_SESSION['form_data_index_contact']);
$default_email_idx = $current_email;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Khusus - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<link rel="icon" href="assets/favicon.ico">
<body>
    <div id="page-wrapper">
        <?php include 'includes/header.php'; ?>

        <section class="custom-order-section py-5">
            <div class="container" style="max-width: 800px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2.8rem, 8vw, 5rem); line-height:1.1;">Custom Orders</h1>
                    <p class="lead" style="color: var(--text-medium); font-family: var(--secondary-font);">
                        Have a special bouquet in mind? Tell us your idea!
                    </p>
                </div>

                <div class="card shadow-lg" style="border-radius: var(--border-radius-xl); border: none; background-color: var(--bg-section-alt);">
                    <div class="card-body p-4 p-md-5">
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger d-flex align-items-center" role="alert" style="border-radius: var(--border-radius-md);">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <div><?php echo $error_message; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success d-flex align-items-center" role="alert" style="border-radius: var(--border-radius-md);">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?php echo sanitize_output($success_message); ?></div>
                            </div>
                        <?php endif; ?>

                        <form action="proses_kostum_order.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="deskripsi_request" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Describe Your Desired Bouquet <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="deskripsi_request" name="deskripsi_request" rows="6" placeholder="Example: A bouquet of red and white roses for a 5th wedding anniversary, with a romantic and elegant feel, plus a custom greeting card and a gold satin ribbon."  required minlength="20"><?php echo sanitize_output($form_data_kostum['deskripsi_request'] ?? ''); ?></textarea>
                                <div class="invalid-feedback">Please describe your desired bouquet in detail (minimum 20 characters).</div>
                            </div>
                            <div class="mb-4">
                                <label for="budget_estimasi" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Estimated Budget (IDR) (Optional)</label>
                                <input type="number" class="form-control" id="budget_estimasi" name="budget_estimasi" placeholder="Contoh: 500000" value="<?php echo sanitize_output($form_data_kostum['budget_estimasi'] ?? ''); ?>" step="10000" min="0">
                                <small class="form-text" style="color: var(--text-light);">Leave blank if you'd like us to provide a recommendation based on your description.</small>
                            </div>
                            <div class="mb-4">
                                <label for="referensi_gambar_file" class="form-label" style="font-weight: 500; color: var(--text-dark); font-size: 1.1rem;">Upload a Reference Image (Optional)</label>
                                <input type="file" class="form-control" id="referensi_gambar_file" name="referensi_gambar_file" accept="image/jpeg, image/png, image/gif">
                                <small class="form-text" style="color: var(--text-light);">Allowed formats: JPG, PNG, GIF. Maximum size: 2MB.</small>
                                <input type="hidden" name="existing_referensi_gambar_path" value="<?php echo sanitize_output($form_data_kostum['referensi_gambar_url'] ?? ''); ?>">
                                <?php if (!empty($form_data_kostum['referensi_gambar_url'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo sanitize_output($form_data_kostum['referensi_gambar_url']); ?>" alt="Referensi Sebelumnya" style="max-width: 100px; max-height: 100px; border-radius: var(--border-radius-sm); margin-right: 10px;">
                                        <small style="color: var(--text-medium);">Current image: <?php echo basename(sanitize_output($form_data_kostum['referensi_gambar_url'])); ?></small><br>
                                        <small class="text-info">Upload a new file to replace this one.</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-center mt-4 pt-2">
                                <button type="submit" class="btn btn-submit btn-lg px-5"><i class="fas fa-paper-plane me-2"></i> Send Request</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="mt-5 text-center">
                    <a href="index.php" class="btn btn-outline-secondary btn-sm me-2"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
                    <a href="gallery_page.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-images me-1"></i> Discover Our Gallery</a>
                </div>
            </div>
        </section>

        <footer class="actual-footer py-3">
            <div class="container text-center">
                <p class="text-muted mb-0">&copy; <?php echo date("Y"); ?> BLOOMARIE. All Rights Reserved.</p>
            </div>
        </footer>
    </div> <!-- #page-wrapper -->

    <!-- Modal Kontak (Sama seperti di index.php) -->
    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
         <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel" style="font-family: var(--primary-font); color: var(--text-dark);">Hubungi Kami</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlertsKostumPage" class="mb-3"></div>
                    <form id="contactModalFormKostumPage">
                        <input type="hidden" name="source_page" value="modal_contact_ajax_kostum_page">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim_kostum_page" class="form-label">Nama Anda <span class="text-danger">*</span></label>
                                <input type="text" class="form-control contact-input" id="modal_nama_pengirim_kostum_page" name="nama_pengirim" required
                                       value="<?php echo sanitize_output($current_fullname_header ?: $current_username_header); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim_kostum_page" class="form-label">Email Anda <span class="text-danger">*</span></label>
                                <input type="email" class="form-control contact-input" id="modal_email_pengirim_kostum_page" name="email_pengirim" required
                                       value="<?php echo sanitize_output($current_email_header); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_subjek_pesan_kostum_page" class="form-label">Subjek</label>
                            <input type="text" class="form-control contact-input" id="modal_subjek_pesan_kostum_page" name="subjek_pesan">
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan_kostum_page" class="form-label">Pesan Anda <span class="text-danger">*</span></label>
                            <textarea class="form-control contact-input" id="modal_isi_pesan_kostum_page" name="isi_pesan" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-submit">Kirim Pesan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_server.js"></script>
    
    <script>
        // Script untuk toggle search input (sama seperti index.php, pastikan ID unik)
        document.addEventListener('DOMContentLoaded', function() {
            const searchIconToggleKostumPage = document.getElementById('searchIconToggleKostumPage');
            const searchInputKostumPage = document.getElementById('searchInputKostumPage');
            const searchFormKostumPage = document.getElementById('searchFormKostumPage');
            let searchVisibleKostumPage = false;

            if(searchIconToggleKostumPage && searchInputKostumPage && searchFormKostumPage){
                searchIconToggleKostumPage.addEventListener('click', function(event) {
                    event.preventDefault();
                    if (!searchVisibleKostumPage) {
                        searchInputKostumPage.style.display = 'inline-block';
                        setTimeout(() => { searchInputKostumPage.style.width = '200px'; }, 0);
                        searchInputKostumPage.focus();
                        searchVisibleKostumPage = true;
                    } else { /* ... (logika toggle search sama) ... */
                        if (searchInputKostumPage.value.trim() !== '') { searchFormKostumPage.submit(); }
                        else { searchInputKostumPage.style.width = '0'; setTimeout(() => { searchInputKostumPage.style.display = 'none'; }, 300); searchVisibleKostumPage = false; }
                    }
                });
                searchInputKostumPage.addEventListener('keypress', function(event) {
                    if (event.key === 'Enter') { event.preventDefault(); if (this.value.trim() !== '') { searchFormKostumPage.submit(); } }
                });
                document.addEventListener('click', function(event) {
                    if (searchVisibleKostumPage && !searchFormKostumPage.contains(event.target) && event.target !== searchIconToggleKostumPage && !searchIconToggleKostumPage.contains(event.target)) {
                        searchInputKostumPage.style.width = '0'; setTimeout(() => { searchInputKostumPage.style.display = 'none'; }, 300); searchVisibleKostumPage = false;
                    }
                });
            }

            // Bootstrap form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });

            // AJAX untuk Contact Modal (sama seperti index.php, pastikan ID unik)
            const contactModalElKostumPage = document.getElementById('contactModal');
            if (contactModalElKostumPage) {
                const contactModalFormKostumPage = document.getElementById('contactModalFormKostumPage');
                const contactModalAlertsKostumPage = document.getElementById('contactModalAlertsKostumPage');
                if (contactModalFormKostumPage) {
                    contactModalFormKostumPage.addEventListener('submit', function(event) { /* ... (logika AJAX modal sama) ... */
                        event.preventDefault(); contactModalAlertsKostumPage.innerHTML = '';
                        const formData = new FormData(contactModalFormKostumPage);
                        const submitButton = contactModalFormKostumPage.querySelector('button[type="submit"]');
                        const originalButtonText = submitButton.innerHTML;
                        submitButton.disabled = true; submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Mengirim...';
                        fetch('proses_kontak.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                        .then(response => response.json()).then(data => {
                            if (data.status === 'success') {
                                contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-success alert-dismissible fade show" role="alert">${data.message} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                                contactModalFormKostumPage.reset();
                                <?php if ($is_user_logged_in_header): ?>
                                const defaultNameModal = "<?php echo htmlspecialchars(addslashes($current_fullname_header ?: $current_username_header)); ?>";
                                const defaultEmailModal = "<?php echo htmlspecialchars(addslashes($current_email_header)); ?>";
                                if(document.getElementById('modal_nama_pengirim_kostum_page')) document.getElementById('modal_nama_pengirim_kostum_page').value = defaultNameModal;
                                if(document.getElementById('modal_email_pengirim_kostum_page')) document.getElementById('modal_email_pengirim_kostum_page').value = defaultEmailModal;
                                <?php endif; ?>
                                setTimeout(() => { const modalInstance = bootstrap.Modal.getInstance(contactModalElKostumPage); if (modalInstance) modalInstance.hide(); contactModalAlertsKostumPage.innerHTML = ''; }, 3000);
                            } else {
                                let errMsg = data.message || 'Terjadi kesalahan.'; if (data.errors && typeof data.errors === 'object') { errMsg += '<ul class="mt-2 mb-0 text-start">'; for (const field in data.errors) { errMsg += `<li>${data.errors[field]}</li>`; } errMsg += '</ul>'; }
                                contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">${errMsg} <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                            }
                        }).catch(error => {
                            contactModalAlertsKostumPage.innerHTML = `<div class="alert alert-danger alert-dismissible fade show" role="alert">Tidak dapat terhubung ke server. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>`;
                        }).finally(() => { submitButton.disabled = false; submitButton.innerHTML = originalButtonText; });
                    });
                }
                contactModalElKostumPage.addEventListener('hidden.bs.modal', function () { if (contactModalAlertsKostumPage) contactModalAlertsKostumPage.innerHTML = ''; });
            }
        });
    </script>
</body>
</html>
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
