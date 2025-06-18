<?php
session_start();
// login.php (atau proses_login.php)
require_once 'db_connect.php'; // Menyediakan connect_db() dan fungsi helper
$conn = connect_db();

$error_message = $_SESSION['login_error'] ?? ''; // Ambil error dari redirect checkout
unset($_SESSION['login_error']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password tidak boleh kosong.";
    } else {
        // Gunakan prepared statement untuk keamanan
        $stmt = $conn->prepare("SELECT user_id, username, password, role, nama_lengkap, email FROM users WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                // Login sukses
                session_regenerate_id(true); // Regenerate session ID untuk keamanan

                if ($user['role'] === 'superadmin' || $user['role'] === 'admin') {
                    // Set session untuk admin/superadmin
                    $_SESSION['admin_user_id'] = $user['user_id'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_role'] = $user['role'];
                    $_SESSION['admin_nama_lengkap'] = $user['nama_lengkap']; // Opsional
                    // Kosongkan session user customer jika ada
                    unset($_SESSION['user']);
                    redirect('admin_panel/admin_dashboard.php');
                } else { // Diasumsikan customer
                    // Set session untuk customer
                    $_SESSION['user'] = [
                        'user_id' => $user['user_id'],
                        'username' => $user['username'],
                        'role' => $user['role'],
                        'nama_lengkap' => $user['nama_lengkap'],
                        'email' => $user['email']
                    ];
                    // Kosongkan session admin jika ada
                    unset($_SESSION['admin_user_id']);
                    unset($_SESSION['admin_username']);
                    unset($_SESSION['admin_role']);
                    unset($_SESSION['admin_nama_lengkap']);

                    if (isset($_SESSION['redirect_to_checkout']) && $_SESSION['redirect_to_checkout']) {
                        unset($_SESSION['redirect_to_checkout']);
                        redirect('checkout.php');
                    } else {
                        redirect('index.php');
                    }
                }
            } else {
                $error_message = "Wrong Username atau password.";
            }
        } else {
            error_log("Login statement preparation failed: " . $conn->error);
            $error_message = "There was a problem trying to log in. Please try again.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bloomarie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montagu+Slab:opsz,wght@16..144,200;16..144,400&family=Inter:wght@300&family=Luxurious+Script&family=Cooper+Black&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
</head>
<link rel="icon" href="assets/favicon.ico">
<body class="internal-page-body d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="page-container form-focus text-center">
        <h1 class="internal-page-title">BLOOMARIE</h1>
        <h2 class="internal-page-header">Account Login</h2>

        <?php
            include 'includes/flash_message.php'; 
        ?>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?php echo sanitize_output($error_message); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['login_message'])): ?>
            <div class="alert alert-info" role="alert"><?php echo sanitize_output($_SESSION['login_message']); unset($_SESSION['login_message']); ?></div>
        <?php endif; ?>


        <form action="login.php" method="POST" class="text-start mt-4">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? sanitize_output($_POST['username']) : ''; ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-submit w-100 mt-3">Login</button>
        </form>

        <p class="mt-3 nav-links-internal">Don't have an account yet? <a href="register.php">Register here</a></p>
        <p class="mt-2 nav-links-internal"><a href="index.php">Back to Home</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>