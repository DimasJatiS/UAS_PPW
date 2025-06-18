<?php
// uas/admin_panel/modules/orders/custom_orders_list.php

// Pastikan $pdo sudah tersedia dari admin_panel/config.php
if (!isset($pdo)) {
    die('Koneksi database tidak tersedia.');
}

$message = '';
// Logika untuk menghapus pesan kontak
if (isset($_GET['action']) && $_GET['action'] == 'delete_contact' && isset($_GET['id'])) {
    $contact_id_to_delete = intval($_GET['id']);
    try {
        // Tabel 'contacts' -> 'contact_messages'
        // Kolom 'contact_id' -> 'message_id'
        $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE message_id = ?"); // Skema: contact_messages. Kolom: message_id
        $stmt->execute([$contact_id_to_delete]);
        set_flash_message("Pesan kontak berhasil dihapus.", "success");
        header("Location: " . SITE_URL . "admin_panel/index.php?page=custom_orders"); // Menggunakan SITE_URL dari config.php
        exit();
    } catch (PDOException $e) {
        set_flash_message("Gagal menghapus pesan kontak: " . $e->getMessage(), "danger");
        header("Location: " . SITE_URL . "admin_panel/index.php?page=custom_orders");
        exit();
    }
}


// Ambil semua pesan kontak (pesanan kustom dianggap sebagai pesan kontak di sini)
try {
    // Tabel 'contacts' -> 'contact_messages'
    // Kolom 'contact_id' -> 'message_id' (alias sebagai contact_id)
    // Kolom 'created_at' -> 'tanggal_kirim' (alias sebagai created_at)
    $stmt = $pdo->query("SELECT message_id AS contact_id, nama_pengirim, email_pengirim, subjek_pesan, isi_pesan, tanggal_kirim AS created_at 
                        FROM contact_messages ORDER BY tanggal_kirim DESC"); // Skema: contact_messages
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
                    <th>ID</th> <th>Nama Pengirim</th>
                    <th>Email</th>
                    <th>Subjek</th>
                    <th>Pesan Singkat</th>
                    <th>Tanggal</th> <th>Aksi</th>
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