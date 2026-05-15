<?php
session_start();
include "config.php";

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'user') {
    header("Location: index.php");
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$peminjamanId = (int)($_POST['peminjaman_id'] ?? 0);

if ($peminjamanId <= 0) {
    header('Location: user.php?msg=' . urlencode('Data tidak valid.'));
    exit;
}

$stmt = $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan', tanggal_kembali = CURDATE() WHERE id = ? AND user_id = ? AND status = 'dipinjam'");
if (!$stmt) {
    header('Location: user.php?msg=' . urlencode('Gagal memproses pengembalian.'));
    exit;
}

$stmt->bind_param('ii', $peminjamanId, $userId);
$stmt->execute();
$ok = ($stmt->affected_rows > 0);
$stmt->close();

if ($ok) {
    header('Location: user.php?msg=' . urlencode('Buku berhasil dikembalikan.'));
    exit;
}

header('Location: user.php?msg=' . urlencode('Buku gagal dikembalikan / sudah dikembalikan.'));
exit;
