<?php
// uas/admin_panel/modules/orders/custom_orders_list.php

// Pastikan $pdo sudah tersedia dari admin_panel/config.php
if (!isset($pdo)) {
    die('Koneksi database tidak tersedia.');
}

$message = '';
// Logika untuk menandai pesan sebagai "dibaca" atau menghapus (jika ada)
// Contoh: Menghapus pesan kontak
if (isset($_GET['action']) && $_GET['action'] == 'delete_contact' && isset($_GET['id'])) {
    $contact_id_to_delete = intval($_GET['id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM contacts WHERE contact_id = ?"); // Asumsi nama tabel adalah 'contacts'
        $stmt->execute([$contact_id_to_delete]);
        set_flash_message("Pesan kontak berhasil dihapus.", "success");
        header("Location: " . SITE_URL . "admin_panel/index.php?page=custom_orders");
        exit();
    } catch (PDOException $e) {
        set_flash_message("Gagal menghapus pesan kontak: " . $e->getMessage(), "danger");
        header("Location: " . SITE_URL . "admin_panel/index.php?page=custom_orders");
        exit();
    }
}


// Ambil semua pesan kontak (pesanan kustom dianggap sebagai pesan kontak di sini)
try {
    $stmt = $pdo->query("SELECT contact_id, nama_pengirim, email_pengirim, subjek_pesan, isi_pesan, created_at FROM contacts ORDER BY created_at DESC");
    $custom_orders = $stmt->fetchAll();
} catch (PDOException $e) {
    set_flash_message("Error mengambil daftar pesan kustom: " . $e->getMessage(), "danger");
    $custom_orders = [];
}
?>

<div class="content-block">
    <h3>Daftar Pesan Kustom / Kontak</h3>
    <?php display_flash_message(); // Tampilkan pesan sukses/error dari session ?>

    <?php if (empty($custom_orders)): ?>
        <p>Belum ada pesan kustom atau kontak yang masuk.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nama Pengirim</th>
                    <th>Email</th>
                    <th>Subjek</th>
                    <th>Pesan Singkat</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($custom_orders as $order): ?>
                <tr>
                    <td data-label="ID"><?php echo htmlspecialchars($order['contact_id']); ?></td>
                    <td data-label="Nama Pengirim"><?php echo htmlspecialchars($order['nama_pengirim']); ?></td>
                    <td data-label="Email"><?php echo htmlspecialchars($order['email_pengirim']); ?></td>
                    <td data-label="Subjek"><?php echo htmlspecialchars($order['subjek_pesan'] ?: '-'); ?></td>
                    <td data-label="Pesan"><?php echo htmlspecialchars(mb_strimwidth($order['isi_pesan'], 0, 70, "...")); ?></td>
                    <td data-label="Tanggal"><?php echo date('d M Y, H:i', strtotime($order['created_at'])); ?></td>
                    <td data-label="Aksi">
                        <a href="#" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewMessageModal" data-message='<?php echo json_encode($order); ?>'>Lihat</a>
                        <a href="<?php echo SITE_URL; ?>admin_panel/index.php?page=custom_orders&action=delete_contact&id=<?php echo $order['contact_id']; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Apakah Anda yakin ingin menghapus pesan ini?');">Hapus</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="modal fade" id="viewMessageModal" tabindex="-1" aria-labelledby="viewMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg message-details-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewMessageModalLabel">Detail Pesan Kustom</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Dari:</strong> <span id="modalSenderName"></span> (<span id="modalSenderEmail"></span>)</p>
                <p><strong>Subjek:</strong> <span id="modalSubject"></span></p>
                <p><strong>Pesan:</strong></p>
                <p id="modalMessageContent"></p>
                <p><small><strong>Dikirim pada:</strong> <span id="modalSentAt"></span></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var viewMessageModal = document.getElementById('viewMessageModal');
    if (viewMessageModal) {
        viewMessageModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget; // Button that triggered the modal
            var messageData = JSON.parse(button.getAttribute('data-message'));

            var modalSenderName = viewMessageModal.querySelector('#modalSenderName');
            var modalSenderEmail = viewMessageModal.querySelector('#modalSenderEmail');
            var modalSubject = viewMessageModal.querySelector('#modalSubject');
            var modalMessageContent = viewMessageModal.querySelector('#modalMessageContent');
            var modalSentAt = viewMessageModal.querySelector('#modalSentAt');

            modalSenderName.textContent = messageData.nama_pengirim;
            modalSenderEmail.textContent = messageData.email_pengirim;
            modalSubject.textContent = messageData.subjek_pesan || 'Tidak Ada Subjek';
            modalMessageContent.textContent = messageData.isi_pesan;
            modalSentAt.textContent = new Date(messageData.created_at).toLocaleString('id-ID', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
        });
    }
});
</script>