<<<<<<< HEAD
<?php
// File: buat_hash.php
$passwordSuperAdmin = 'Superadmin123'; // GANTI DENGAN PASSWORD ANDA
$hashedPassword = password_hash($passwordSuperAdmin, PASSWORD_DEFAULT);
echo "Password Anda: " . $passwordSuperAdmin . "<br>";
echo "Hash Password untuk dimasukkan ke database: " . $hashedPassword;
=======
<?php
// File: buat_hash.php
$passwordSuperAdmin = 'Superadmin123'; // GANTI DENGAN PASSWORD ANDA
$hashedPassword = password_hash($passwordSuperAdmin, PASSWORD_DEFAULT);
echo "Password Anda: " . $passwordSuperAdmin . "<br>";
echo "Hash Password untuk dimasukkan ke database: " . $hashedPassword;
>>>>>>> a4b679ae837631359e77abc3f6882f3374f3d36a
?>