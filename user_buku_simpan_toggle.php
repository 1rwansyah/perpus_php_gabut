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
$bukuId = (int)($_POST['buku_id'] ?? 0);
$redirect = trim((string)($_POST['redirect'] ?? ''));

if ($userId <= 0 || $bukuId <= 0) {
    header('Location: user_buku.php');
    exit;
}

$stmt = $conn->prepare("SELECT id FROM user_saved_books WHERE user_id = ? AND buku_id = ? LIMIT 1");
$stmt->bind_param('ii', $userId, $bukuId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    $stmt = $conn->prepare("DELETE FROM user_saved_books WHERE user_id = ? AND buku_id = ?");
    $stmt->bind_param('ii', $userId, $bukuId);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO user_saved_books (user_id, buku_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $userId, $bukuId);
    $stmt->execute();
    $stmt->close();
}

if ($redirect !== '') {
    header('Location: ' . $redirect);
    exit;
}

header('Location: user_buku_detail.php?id=' . (int)$bukuId);
exit;
