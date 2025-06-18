<?php
session_start();
require_once 'db_connect.php';
$conn = connect_db();

if (!isCustomerLoggedIn()) {
    $_SESSION['login_message'] = "You must be logged in to view discussion details..";
    redirect('login.php');
}

$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if ($request_id <= 0) {
    redirect('riwayat_pesanan.php');
}

// Mengambil semua data dari tabel orderkostum
$sql = "SELECT * FROM orderkostum WHERE kostum_request_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $request_id, $_SESSION['user']['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$request = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$request) {
    $_SESSION['info_message'] = "Costume order request not found or not yours.";
    redirect('riwayat_pesanan.php');
}


// Dummy function jika belum ada
if (!function_exists('sanitize_output')) {
    function sanitize_output($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
// Definisikan BASE_URL jika belum ada (misalnya di db_connect.php)
if (!defined('BASE_URL')) {
    // Sesuaikan dengan URL root proyek Anda
    define('BASE_URL', '/'); 
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesanan Kostum #K<?php echo $request['kostum_request_id']; ?> - Bloomarie</title>
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
        <section class="page-section py-5">
            <div class="container" style="max-width: 800px;">
                <div class="text-center mb-5">
                    <h1 class="bloomarie-title" style="font-size: clamp(2rem, 7vw, 4rem);">Special Order Status</h1>
                    <p class="lead" style="color: var(--text-medium);">
                        Request Number #K<?php echo $request['kostum_request_id']; ?>
                    </p>
                </div>

                <div class="card shadow-sm" style="border-radius: var(--border-radius-lg);">
                    <div class="card-body p-4 p-md-5">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <h5 class="mb-3" style="font-family: var(--primary-font);">Your Request Details</h5>
                                <dl class="row">
                                    <dt class="col-sm-5">Date:</dt>
                                    <dd class="col-sm-7"><?php echo date('d M Y, H:i', strtotime($request['tanggal_request'])); ?></dd>
                                    <dt class="col-sm-5">Budget (Est.):</dt>
                                    <dd class="col-sm-7">Rp <?php echo $request['budget_estimasi'] ? number_format($request['budget_estimasi'], 0, ',', '.') : 'N/A'; ?></dd>
                                    <dt class="col-12 mt-2">Description:</dt>
                                    <dd class="col-12"><small class="d-block p-2 bg-light" style="border-radius:var(--border-radius-sm);"><?php echo nl2br(sanitize_output($request['deskripsi_request'])); ?></small></dd>
                                    <?php if (!empty($request['referensi_gambar_url'])): ?>
                                        <dt class="col-12 mt-2">Reference Picture:</dt>
                                        <dd class="col-12">
                                            <a href="<?php echo sanitize_output($request['referensi_gambar_url']); ?>" target="_blank">
                                                <img src="<?php echo sanitize_output($request['referensi_gambar_url']); ?>" style="max-width:100px; border-radius:var(--border-radius-sm);" alt="Gambar Referensi">
                                            </a>
                                        </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                            <div class="col-md-6" style="border-left: 1px solid var(--border-color);">
                                <h5 class="mb-3" style="font-family: var(--primary-font);">Update from Bloomarie</h5>
                                <dl class="row">
                                    <dt class="col-sm-5">Current Status:</dt>
                                    <dd class="col-sm-7">
                                        <?php
                                        $status_text = ucfirst(str_replace('_', ' ', $request['status_request']));
                                        $badge_class = 'bg-secondary';
                                        if (in_array($request['status_request'], ['menunggu_konfirmasi_awal', 'diskusi'])) $badge_class = 'bg-warning text-dark';
                                        elseif (in_array($request['status_request'], ['diterima', 'selesai_diskusi'])) $badge_class = 'bg-info text-dark';
                                        elseif (in_array($request['status_request'], ['ditolak', 'dibatalkan'])) $badge_class = 'bg-danger';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo sanitize_output($status_text); ?></span>
                                    </dd>
                                    
                                    <?php if (!empty($request['total_harga']) && $request['total_harga'] > 0): ?>
                                    <dt class="col-sm-5 mt-3">Final Cost:</dt>
                                    <dd class="col-sm-7 mt-3">
                                        <strong style="color: var(--primary-color);">Rp <?php echo number_format($request['total_harga'], 0, ',', '.'); ?></strong>
                                    </dd>
                                    <?php endif; ?>

                                    <dt class="col-12 mt-3">Notes from Our Team:</dt>
                                    <dd class="col-12">
                                        <?php if (!empty($request['catatan_dari_toko'])): ?>
                                            <p class="p-3 bg-light" style="border-radius:var(--border-radius-sm);"><?php echo nl2br(sanitize_output($request['catatan_dari_toko'])); ?></p>
                                        <?php else: ?>
                                            <p class="text-muted fst-italic">There are no records from our team yet.</p>
                                        <?php endif; ?>
                                    </dd>
                                </dl>
                                <p class="mt-3" style="font-size: 0.9rem;">Have further questions? <a href="#" data-bs-toggle="modal" data-bs-target="#contactModal">Contact Us</a> by including your request number ID.</p>
                            </div>
                        </div>
                    </div>
                </div>

                 <div class="mt-4 text-center">
                    <a href="riwayat_pesanan.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> back to Purchased History</a>
                </div>
            </div>
        </section>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactModalLabel">Contact Us</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="contactModalAlerts" class="mb-3"></div>
                    <form id="contactModalForm">
                        <input type="hidden" name="source_page" value="modal_contact_ajax_diskusi_page">
                        <input type="hidden" name="subjek_pesan" value="Pertanyaan mengenai Pesanan Khusus #K<?php echo $request['kostum_request_id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="modal_nama_pengirim" class="form-label">Your Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="modal_nama_pengirim" name="nama_pengirim" required value="<?php echo sanitize_output($_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="modal_email_pengirim" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="modal_email_pengirim" name="email_pengirim" required value="<?php echo sanitize_output($_SESSION['user']['email']); ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="modal_isi_pesan" class="form-label">your Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="modal_isi_pesan" name="isi_pesan" rows="4" required></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-submit">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/custom.js"></script>
    <script src="assets/js/live_search.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const contactModalEl = document.getElementById('contactModal');
        if (contactModalEl) {
            const contactModalForm = document.getElementById('contactModalForm');
            const contactModalAlerts = document.getElementById('contactModalAlerts');
            if (contactModalForm) {
                contactModalForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    contactModalAlerts.innerHTML = '';
                    const formData = new FormData(contactModalForm);
                    const submitButton = contactModalForm.querySelector('button[type="submit"]');
                    const originalButtonText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mengirim...';
                    
                    fetch('proses_kontak.php', { method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }})
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            contactModalAlerts.innerHTML = `<div class="alert alert-success">${data.message}</div>`;
                            contactModalForm.reset();
                            // Reset field yang sudah terisi otomatis
                            document.getElementById('modal_nama_pengirim').value = "<?php echo htmlspecialchars(addslashes($_SESSION['user']['nama_lengkap'] ?? $_SESSION['user']['username'])); ?>";
                            document.getElementById('modal_email_pengirim').value = "<?php echo htmlspecialchars(addslashes($_SESSION['user']['email'])); ?>";
                            setTimeout(() => { 
                                const modal = bootstrap.Modal.getInstance(contactModalEl);
                                if (modal) {
                                    modal.hide();
                                }
                                contactModalAlerts.innerHTML = ''; 
                            }, 3000);
                        } else {
                            let errMsg = data.message || 'Terjadi kesalahan.';
                            if (data.errors) {
                                errMsg += '<ul>';
                                for (const field in data.errors) { errMsg += `<li>${data.errors[field]}</li>`; }
                                errMsg += '</ul>';
                            }
                            contactModalAlerts.innerHTML = `<div class="alert alert-danger">${errMsg}</div>`;
                        }
                    })
                    .catch(error => {
                        contactModalAlerts.innerHTML = `<div class="alert alert-danger">Tidak dapat terhubung ke server.</div>`;
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalButtonText;
                    });
                });
            }
        }
    });
    </script>
</body>
</html>