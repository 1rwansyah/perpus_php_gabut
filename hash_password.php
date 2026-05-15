<?php
include 'config.php';

// Hash password '111' dengan bcrypt
$hashed = password_hash('111', PASSWORD_BCRYPT, ['cost' => 10]);

// Update semua user password ke hash
$sql = "UPDATE users SET password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $hashed);

if ($stmt->execute()) {
    echo "✅ Semua password sudah di-hash dengan bcrypt!<br><br>";
    echo "Password hash: <code>" . $hashed . "</code><br><br>";
    echo "<strong>Sekarang login dengan password: 111</strong><br>";
    echo "Username bisa: <strong>admin</strong>, <strong>user1</strong>, dll<br><br>";
    echo "<a href='login.php' class='btn btn-primary'>Ke Halaman Login</a>";
} else {
    echo "❌ Error: " . $stmt->error;
}

$stmt->close();
?>
