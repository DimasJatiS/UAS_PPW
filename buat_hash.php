<?php
// File: buat_hash.php
$passwordSuperAdmin = 'Superadmin123'; // GANTI DENGAN PASSWORD ANDA
$hashedPassword = password_hash($passwordSuperAdmin, PASSWORD_DEFAULT);
echo "Password Anda: " . $passwordSuperAdmin . "<br>";
echo "Hash Password untuk dimasukkan ke database: " . $hashedPassword;
?>