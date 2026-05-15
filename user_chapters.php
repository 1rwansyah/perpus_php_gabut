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

$bukuId = (int)($_GET['buku_id'] ?? 0);
if ($bukuId <= 0) {
    header('Location: user_buku.php');
    exit;
}

// ambil asal halaman
$from = $_GET['from'] ?? '';
$backUrl = ($from === 'detail') 
    ? "user_buku_detail.php?id=" . $bukuId 
    : "user_buku.php";

// ambil data buku
$stmt = $conn->prepare("SELECT id, judul, author, cover FROM buku WHERE id = ?");
$stmt->bind_param('i', $bukuId);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$buku) {
    header('Location: user_buku.php');
    exit;
}

// ambil chapter
$chapters = [];
$stmt = $conn->prepare("SELECT id, nomor, judul, created_at FROM chapters WHERE buku_id = ? ORDER BY nomor DESC");
$stmt->bind_param('i', $bukuId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $chapters[] = $row;
}
$stmt->close();

$hasCover = trim((string)($buku['cover'] ?? '')) !== '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Chapters</title>

<link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
<link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

<style>
body { background: #f8f9fc !important; }

/* HERO */
.hero {
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1px solid rgba(0,0,0,.08);
    margin-bottom: 16px;
}
.hero-content {
    padding: 18px;
}
.cover {
    width: 80px;
    height: 110px;
    border-radius: 10px;
    overflow: hidden;
    background: #f3f4f6;
}
.cover img { width: 100%; height: 100%; object-fit: cover; }

.hero-title { font-weight: 900; margin: 0; }
.hero-sub { color: #6b7280; }

/* BUTTON */
.chip {
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 12px;
    border-radius:10px;
    background:#f1f3f9;
    border:1px solid #e0e3eb;
    color:#111;
    text-decoration:none;
    font-size:13px;
    transition:.2s;
}
.chip:hover {
    transform: translateY(-1px);
    background:#e9ecf5;
}

/* PANEL */
.panel {
    border-radius:14px;
    background:#fff;
    border:1px solid rgba(0,0,0,.08);
    overflow: hidden;
}
.panel-header {
    padding:14px;
    font-weight:900;
    border-bottom:1px solid rgba(0,0,0,.08);
}
.panel-body { padding:16px; }

/* CHAPTER */
.chapter-item {
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:10px;
    border-radius:10px;
    background:#f6f7fb;
    border:1px solid #e5e7eb;
    text-decoration:none;
    color:#111;
    transition:.2s;
}
.chapter-item:hover {
    transform: translateX(3px);
    background:#eef1f7;
}
.chapter-title { font-weight:700; }
.chapter-time { font-size:12px; color:#6b7280; }
</style>
</head>

<body>

<div class="container py-4">

    <!-- HERO -->
    <div class="hero">
        <div class="hero-content d-flex align-items-center" style="gap:15px;">
            
            <div class="cover">
                <?php if ($hasCover): ?>
                    <img src="<?= htmlspecialchars($buku['cover']) ?>">
                <?php endif; ?>
            </div>

            <div style="flex:1;">
                <h1 class="hero-title h5"><?= htmlspecialchars($buku['judul']) ?></h1>
                <div class="hero-sub"><?= htmlspecialchars($buku['author']) ?></div>

                <div class="mt-2">
                    <a class="chip" href="<?= $backUrl ?>">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>

        </div>
    </div>

    <!-- LIST CHAPTER -->
    <div class="panel">
        <div class="panel-header">Daftar Chapter</div>
        <div class="panel-body">
            <?php if (count($chapters) === 0): ?>
                <div class="text-muted text-center">Belum ada chapter</div>
            <?php else: ?>
                <?php foreach ($chapters as $c): ?>
                    <a class="chapter-item mb-2" href="user_chapter_baca.php?id=<?= (int)$c['id'] ?>&from=chapters&buku_id=<?= (int)$bukuId ?>">
                        <div>
                            <div class="chapter-title">Chapter <?= (int)$c['nomor'] ?></div>
                            <div class="chapter-time"><?= htmlspecialchars($c['judul']) ?></div>
                        </div>
                        <div class="chapter-time"><?= htmlspecialchars($c['created_at']) ?></div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>