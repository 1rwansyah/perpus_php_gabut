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

$chapterId = (int)($_GET['id'] ?? 0);
if ($chapterId <= 0) {
    header('Location: user_buku.php');
    exit;
}

$stmt = $conn->prepare("SELECT c.id, c.buku_id, c.nomor, c.judul AS judul_chapter, c.isi, c.created_at, b.judul AS judul_buku, b.author AS author_buku FROM chapters c JOIN buku b ON b.id = c.buku_id WHERE c.id = ?");
$stmt->bind_param('i', $chapterId);
$stmt->execute();
$chapter = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$chapter) {
    header('Location: user_buku.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);
$bukuId = (int)($chapter['buku_id'] ?? 0);
$chapterId = (int)($chapter['id'] ?? 0);

if ($userId > 0 && $bukuId > 0 && $chapterId > 0) {
    $stmt = $conn->prepare("INSERT INTO user_read_history (user_id, buku_id, chapter_id, last_read_at)
                            VALUES (?, ?, ?, CURRENT_TIMESTAMP)
                            ON DUPLICATE KEY UPDATE
                                chapter_id = VALUES(chapter_id),
                                last_read_at = CURRENT_TIMESTAMP");
    $stmt->bind_param('iii', $userId, $bukuId, $chapterId);
    $stmt->execute();
    $stmt->close();
}

$from = trim((string)($_GET['from'] ?? ''));
$backBukuId = (int)($_GET['buku_id'] ?? 0);
$backUrl = '';
if ($from === 'chapters') {
    $targetBukuId = $backBukuId > 0 ? $backBukuId : $bukuId;
    $backUrl = 'user_chapters.php?buku_id=' . (int)$targetBukuId;
} else {
    $backUrl = 'user_chapters.php?buku_id=' . (int)$bukuId;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Baca Chapter</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-0 text-gray-800"><?= htmlspecialchars($chapter['judul_buku'] ?? '') ?></h1>
            <div class="text-muted">Chapter <?= (int)($chapter['nomor'] ?? 0) ?> - <?= htmlspecialchars($chapter['judul_chapter'] ?? '') ?></div>
        </div>
        <div>
            <a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-sm btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div style="white-space: pre-wrap; line-height: 1.75;">
                <?= htmlspecialchars($chapter['isi'] ?? '') ?>
            </div>
        </div>
    </div>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>
