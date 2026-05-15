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
$jumlah = (int)($_POST['jumlah'] ?? 1);
$tanggalPinjam = trim((string)($_POST['tanggal_pinjam'] ?? ''));
$tanggalKembali = trim((string)($_POST['tanggal_kembali'] ?? ''));

if ($bukuId <= 0 || $jumlah <= 0) {
    header('Location: user_buku.php');
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalPinjam) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalKembali)) {
    header('Location: user_buku_detail.php?id=' . $bukuId . '&msg=' . urlencode('Tanggal tidak valid.'));
    exit;
}

if (strtotime($tanggalKembali) < strtotime($tanggalPinjam)) {
    header('Location: user_buku_detail.php?id=' . $bukuId . '&msg=' . urlencode('Tanggal kembali harus setelah/sama dengan tanggal pinjam.'));
    exit;
}

$conn->begin_transaction();
try {
    $stmt = $conn->prepare("INSERT INTO peminjaman (user_id, tanggal_pinjam, tanggal_kembali, status) VALUES (?, ?, ?, 'dipinjam')");
    if (!$stmt) {
        throw new Exception('Gagal membuat peminjaman.');
    }
    $stmt->bind_param('iss', $userId, $tanggalPinjam, $tanggalKembali);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Gagal membuat peminjaman.');
    }
    $peminjamanId = (int)$conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO detail_peminjaman (peminjaman_id, buku_id, jumlah) VALUES (?, ?, ?)");
    if (!$stmt) {
        throw new Exception('Gagal membuat detail peminjaman.');
    }
    $stmt->bind_param('iii', $peminjamanId, $bukuId, $jumlah);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception('Gagal membuat detail peminjaman.');
    }
    $stmt->close();

    $conn->commit();
    header('Location: user.php?msg=' . urlencode('Berhasil meminjam buku.'));
    exit;
} catch (Throwable $e) {
    // Guard against "MySQL server has gone away" on rollback
    // when the connection dropped mid-transaction
    try {
        if ($conn->ping()) {
            $conn->rollback();
        }
    } catch (Throwable $rollbackEx) {
        // Connection truly gone – nothing to roll back
    }
    header('Location: user.php?msg=' . urlencode('Gagal meminjam buku.'));
    exit;
}
