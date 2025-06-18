<?php
// uas/admin_panel/templates/footer.php

// Tidak perlu require_once db_connect.php atau session_start() di sini.
// Asumsi semua tag pembuka sudah di-handle di admin_dashboard.php dan header.php.
?>
<footer style="text-align: center; margin-top: 20px; padding: 10px; background-color: #e9ecef; border-top: 1px solid #dee2e6;">
    <p>&copy; <?php echo date("Y"); ?> Bloomarie Admin Panel. All Rights Reserved.</p>
</footer>