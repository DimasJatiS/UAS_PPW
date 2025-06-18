<<<<<<< HEAD
<?php
require_once 'db_connect.php'; // Untuk memulai sesi jika belum dan fungsi redirect

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Redirect ke halaman utama atau login
redirect('index.php?status=logged_out');
=======
<?php
require_once 'db_connect.php'; // Untuk memulai sesi jika belum dan fungsi redirect

// Hapus semua variabel sesi
$_SESSION = array();

// Hapus cookie sesi jika ada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi
session_destroy();

// Redirect ke halaman utama atau login
redirect('index.php?status=logged_out');
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
?>