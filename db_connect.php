<<<<<<< HEAD
<?php
// File: uas/db_connect.php

// --- Konfigurasi Dinamis untuk Database & BASE_URL ---

// Cek apakah skrip berjalan di server lokal (localhost)
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    
    // Konfigurasi untuk LOCALHOST (XAMPP Anda)
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'Dimas');
    define('DB_PASSWORD', 'LmdSrt6y7!');
    define('DB_NAME', 'uas');
    define('BASE_URL', 'http://localhost/uas/');

} else {

    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'u985354573_dimas');     // Username database Anda
    define('DB_PASSWORD', 'LmdSrt6y7!'); // Password database Anda
    define('DB_NAME', 'u985354573_Bloom');  
    
    // Gunakan https:// untuk keamanan dan ambil nama domain secara otomatis
    define('BASE_URL', 'https://bloomarie.trpl24.space/'); 
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fungsi koneksi database
function connect_db() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database Connection Error: " . $conn->connect_error);
        die("Tidak dapat terhubung ke layanan kami saat ini. Silakan coba lagi nanti.");
    }
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
    }
    return $conn;
}

// Fungsi untuk memeriksa apakah admin (superadmin atau admin biasa dari tabel users) sudah login
function isAdminLoggedIn() {
    return isset($_SESSION['admin_user_id']) &&
        isset($_SESSION['admin_role']) &&
        ($_SESSION['admin_role'] === 'superadmin' || $_SESSION['admin_role'] === 'admin');
}

// Fungsi untuk memeriksa apakah pengguna biasa (customer) sudah login
function isCustomerLoggedIn() {
    return isset($_SESSION['user']) &&
        isset($_SESSION['user']['role']) &&
        $_SESSION['user']['role'] === 'customer';
}

// Fungsi helper untuk redirect aman
function redirect($url) {
    // Untuk mencegah header injection, meskipun jarang untuk URL internal
    // $url = filter_var($url, FILTER_SANITIZE_URL); // Hati-hati, ini bisa merusak URL kompleks
    if (headers_sent()) {
        echo "<script>window.location.href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';</script>";
    } else {
        header("Location: " . $url);
    }
    exit();
}

// Fungsi helper untuk membersihkan input sebelum ditampilkan (XSS prevention)
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Fungsi helper untuk membersihkan input dari form (bukan untuk query SQL, itu pakai prepared statement)
function sanitize_form_input($data) {
    $data = trim($data);
    $data = stripslashes($data); // Hati-hati jika magic quotes aktif (seharusnya tidak di PHP modern)
    $data = htmlspecialchars($data, ENT_NOQUOTES); // ENT_NOQUOTES agar ' dan " tidak jadi &quot; jika mau disimpan apa adanya ke DB (tapi display tetap pakai full htmlspecialchars)
    return $data;
}
=======
<?php
// File: uas/db_connect.php

// --- Konfigurasi Dinamis untuk Database & BASE_URL ---

// Cek apakah skrip berjalan di server lokal (localhost)
if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['REMOTE_ADDR'] == '127.0.0.1') {
    
    // Konfigurasi untuk LOCALHOST (XAMPP Anda)
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'Dimas');
    define('DB_PASSWORD', 'LmdSrt6y7!');
    define('DB_NAME', 'uas');
    define('BASE_URL', 'http://localhost/uas/');

} else {

    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'u985354573_dimas');     // Username database Anda
    define('DB_PASSWORD', 'LmdSrt6y7!'); // Password database Anda
    define('DB_NAME', 'u985354573_Bloom');  
    
    // Gunakan https:// untuk keamanan dan ambil nama domain secara otomatis
    define('BASE_URL', 'https://bloomarie.trpl24.space/'); 
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fungsi koneksi database
function connect_db() {
    $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($conn->connect_error) {
        error_log("Database Connection Error: " . $conn->connect_error);
        die("Tidak dapat terhubung ke layanan kami saat ini. Silakan coba lagi nanti.");
    }
    if (!$conn->set_charset("utf8mb4")) {
        error_log("Error loading character set utf8mb4: " . $conn->error);
    }
    return $conn;
}

// Fungsi untuk memeriksa apakah admin (superadmin atau admin biasa dari tabel users) sudah login
function isAdminLoggedIn() {
    return isset($_SESSION['admin_user_id']) &&
        isset($_SESSION['admin_role']) &&
        ($_SESSION['admin_role'] === 'superadmin' || $_SESSION['admin_role'] === 'admin');
}

// Fungsi untuk memeriksa apakah pengguna biasa (customer) sudah login
function isCustomerLoggedIn() {
    return isset($_SESSION['user']) &&
        isset($_SESSION['user']['role']) &&
        $_SESSION['user']['role'] === 'customer';
}

// Fungsi helper untuk redirect aman
function redirect($url) {
    // Untuk mencegah header injection, meskipun jarang untuk URL internal
    // $url = filter_var($url, FILTER_SANITIZE_URL); // Hati-hati, ini bisa merusak URL kompleks
    if (headers_sent()) {
        echo "<script>window.location.href='" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "';</script>";
    } else {
        header("Location: " . $url);
    }
    exit();
}

// Fungsi helper untuk membersihkan input sebelum ditampilkan (XSS prevention)
function sanitize_output($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Fungsi helper untuk membersihkan input dari form (bukan untuk query SQL, itu pakai prepared statement)
function sanitize_form_input($data) {
    $data = trim($data);
    $data = stripslashes($data); // Hati-hati jika magic quotes aktif (seharusnya tidak di PHP modern)
    $data = htmlspecialchars($data, ENT_NOQUOTES); // ENT_NOQUOTES agar ' dan " tidak jadi &quot; jika mau disimpan apa adanya ke DB (tapi display tetap pakai full htmlspecialchars)
    return $data;
}
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
?>