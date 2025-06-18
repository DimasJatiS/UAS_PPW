<<<<<<< HEAD
<?php
// uas/admin_panel/config.php

// Pastikan session sudah dimulai di file utama (admin_panel/index.php)
// session_start(); // Tidak perlu di sini, sudah di index.php

// URL Dasar Proyek (root)
define('SITE_URL', 'http://localhost/uas/'); // Ganti dengan URL dasar proyek Anda

// Path ke folder upload
define('UPLOAD_DIR_RELATIVE', 'uploads/products/'); // Relatif dari root proyek (uas/)
define('UPLOAD_DIR_FULL_PATH', dirname(__DIR__) . '/' . UPLOAD_DIR_RELATIVE); // Full path untuk move_uploaded_file

// Kredensial Database Admin (jika admin panel menggunakan kredensial berbeda, jika tidak, pakai db_connect.php)
// Saya akan menggunakan db_connect.php di root proyek untuk konsistensi, jadi kredensial ini tidak perlu di sini.

// --- Fungsi-fungsi Pembantu untuk Admin Panel ---

// Fungsi koneksi PDO untuk Admin Panel (jika berbeda dari db_connect.php atau untuk konsistensi PDO)
// Jika Anda sudah memiliki db_connect.php yang mengembalikan PDO, gunakan itu.
// Saya akan asumsikan Anda ingin menggunakan PDO di admin panel untuk kemudahan.
// function get_pdo_connection() {
//     $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4';
//     $options = [
//         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//         PDO::ATTR_EMULATE_PREPARES   => false,
//     ];
//     try {
//         $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
//         return $pdo;
//     } catch (PDOException $e) {
//         error_log("PDO Connection Error: " . $e->getMessage());
//         die("Koneksi database admin gagal. Silakan coba lagi nanti.");
//     }
// }

// Fungsi untuk memeriksa apakah user yang login adalah admin atau superadmin
function require_login() {
    if (!isset($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'superadmin' && ($_SESSION['user']['role'] ?? '') !== 'admin')) {
        $_SESSION['login_error'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

// Fungsi untuk memeriksa apakah user adalah superadmin
function is_superadmin($username_or_user_array) {
    if (is_array($username_or_user_array)) {
        return ($username_or_user_array['role'] ?? '') === 'superadmin';
    }
    // Jika hanya username yang diberikan, asumsikan username superadmin adalah 'superadmin'
    // Atau jika Anda ingin lebih robust, Anda bisa melakukan query ke DB
    // Namun, cara terbaik adalah selalu melewati array user dari sesi
    return ($_SESSION['user']['role'] ?? '') === 'superadmin'; // Cek dari sesi
}

// Fungsi untuk menampilkan pesan flash (success/error)
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type']; // success, danger, warning, info
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}

// Fungsi untuk mengatur pesan flash
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Inisialisasi koneksi PDO di awal (untuk modules)
=======
<?php
// uas/admin_panel/config.php

// Pastikan session sudah dimulai di file utama (admin_panel/index.php)
// session_start(); // Tidak perlu di sini, sudah di index.php

// URL Dasar Proyek (root)
define('SITE_URL', 'http://localhost/uas/'); // Ganti dengan URL dasar proyek Anda

// Path ke folder upload
define('UPLOAD_DIR_RELATIVE', 'uploads/products/'); // Relatif dari root proyek (uas/)
define('UPLOAD_DIR_FULL_PATH', dirname(__DIR__) . '/' . UPLOAD_DIR_RELATIVE); // Full path untuk move_uploaded_file

// Kredensial Database Admin (jika admin panel menggunakan kredensial berbeda, jika tidak, pakai db_connect.php)
// Saya akan menggunakan db_connect.php di root proyek untuk konsistensi, jadi kredensial ini tidak perlu di sini.

// --- Fungsi-fungsi Pembantu untuk Admin Panel ---

// Fungsi koneksi PDO untuk Admin Panel (jika berbeda dari db_connect.php atau untuk konsistensi PDO)
// Jika Anda sudah memiliki db_connect.php yang mengembalikan PDO, gunakan itu.
// Saya akan asumsikan Anda ingin menggunakan PDO di admin panel untuk kemudahan.
// function get_pdo_connection() {
//     $dsn = 'mysql:host=' . DB_SERVER . ';dbname=' . DB_NAME . ';charset=utf8mb4';
//     $options = [
//         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//         PDO::ATTR_EMULATE_PREPARES   => false,
//     ];
//     try {
//         $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
//         return $pdo;
//     } catch (PDOException $e) {
//         error_log("PDO Connection Error: " . $e->getMessage());
//         die("Koneksi database admin gagal. Silakan coba lagi nanti.");
//     }
// }

// Fungsi untuk memeriksa apakah user yang login adalah admin atau superadmin
function require_login() {
    if (!isset($_SESSION['user']) || (($_SESSION['user']['role'] ?? '') !== 'superadmin' && ($_SESSION['user']['role'] ?? '') !== 'admin')) {
        $_SESSION['login_error'] = "Anda harus login sebagai admin untuk mengakses halaman ini.";
        header("Location: " . SITE_URL . "login.php");
        exit();
    }
}

// Fungsi untuk memeriksa apakah user adalah superadmin
function is_superadmin($username_or_user_array) {
    if (is_array($username_or_user_array)) {
        return ($username_or_user_array['role'] ?? '') === 'superadmin';
    }
    // Jika hanya username yang diberikan, asumsikan username superadmin adalah 'superadmin'
    // Atau jika Anda ingin lebih robust, Anda bisa melakukan query ke DB
    // Namun, cara terbaik adalah selalu melewati array user dari sesi
    return ($_SESSION['user']['role'] ?? '') === 'superadmin'; // Cek dari sesi
}

// Fungsi untuk menampilkan pesan flash (success/error)
function display_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message']['message'];
        $type = $_SESSION['flash_message']['type']; // success, danger, warning, info
        echo '<div class="alert alert-' . htmlspecialchars($type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($message);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['flash_message']);
    }
}

// Fungsi untuk mengatur pesan flash
function set_flash_message($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Inisialisasi koneksi PDO di awal (untuk modules)
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
// $pdo = get_pdo_connection();