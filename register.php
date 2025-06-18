<?php
require_once 'db_connect.php'; // Untuk session_start dan fungsi helper
// Pesan error atau sukses dari proses_register.php
$error_message = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);
$success_message = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
$form_data = $_SESSION['form_data_register'] ?? [];
unset($_SESSION['form_data_register']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Akun - Bloomarie</title>
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
    <div class="page-container form-focus text-center" style="max-width: 550px;">
        <h1 class="internal-page-title">BLOOMARIE</h1>
        <h2 class="internal-page-header">Create New Account</h2>

        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><?php echo $error_message; /* sanitize_output jika error bisa HTML */ ?></div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert"><?php echo sanitize_output($success_message); ?></div>
        <?php endif; ?>

        <form action="proses_register.php" method="POST" class="text-start mt-4">
            <div class="mb-3">
                <label for="nama_lengkap" class="form-label">Fullname <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required value="<?php echo sanitize_output($form_data['nama_lengkap'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required value="<?php echo sanitize_output($form_data['username'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email" required value="<?php echo sanitize_output($form_data['email'] ?? ''); ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-submit w-100 mt-3">Register</button>
        </form>

        <p class="mt-3 nav-links-internal">Already have account? <a href="login.php">Login here</a></p>
        <p class="mt-2 nav-links-internal"><a href="index.php">Back to home</a></p>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>