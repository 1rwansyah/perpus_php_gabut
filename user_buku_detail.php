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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: user_buku.php');
    exit;
}

$stmt = $conn->prepare("SELECT b.id, b.judul, b.author, b.tahun_terbit, b.cover, b.file_buku, b.file_buku_nama, b.isi, k.nama_kategori
                        FROM buku b
                        LEFT JOIN kategori k ON k.id = b.kategori_id
                        WHERE b.id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$buku) {
    header('Location: user_buku.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

$flash = trim((string)($_GET['msg'] ?? ''));
$hasCover = trim((string)($buku['cover'] ?? '')) !== '';
$hasFile = trim((string)($buku['file_buku'] ?? '')) !== '';

$isSaved = false;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT id FROM user_saved_books WHERE user_id = ? AND buku_id = ? LIMIT 1");
    $stmt->bind_param('ii', $userId, $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $isSaved = $row ? true : false;
    $stmt->close();
}

$lastRead = null;
if ($userId > 0) {
    $stmt = $conn->prepare("SELECT urh.chapter_id, urh.last_read_at, c.nomor AS chapter_nomor
                            FROM user_read_history urh
                            JOIN chapters c ON c.id = urh.chapter_id
                            WHERE urh.user_id = ? AND urh.buku_id = ?");
    $stmt->bind_param('ii', $userId, $id);
    $stmt->execute();
    $lastRead = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$chapterLatest = [];
$stmt = $conn->prepare("SELECT id, nomor, judul, created_at FROM chapters WHERE buku_id = ? ORDER BY nomor DESC LIMIT 12");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $chapterLatest[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Detail Buku</title>

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
}
.hero-content {
    padding: 18px;
}
.cover {
    width: 90px;
    height: 130px;
    border-radius: 10px;
    overflow: hidden;
}
.cover img { width:100%; height:100%; object-fit:cover; }

.hero-title { font-weight: 900; }
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
    <div class="hero mb-4">
        <div class="hero-content d-flex" style="gap:15px; align-items:center;">
            
            <div class="cover">
                <?php if ($hasCover): ?>
                    <img src="<?= htmlspecialchars($buku['cover']) ?>">
                <?php endif; ?>
            </div>

            <div style="flex:1;">
                <div class="hero-title h5"><?= htmlspecialchars($buku['judul']) ?></div>
                <div class="hero-sub">
                    <?= htmlspecialchars($buku['author']) ?> | 
                    <?= htmlspecialchars($buku['tahun_terbit']) ?> | 
                    <?= htmlspecialchars($buku['nama_kategori']) ?>
                </div>

                <div class="mt-2 d-flex" style="gap:8px; flex-wrap:wrap;">
                    
                    <a class="chip" href="user_chapters.php?buku_id=<?= $buku['id'] ?>&from=detail">
                        <i class="fas fa-list"></i> Semua Chapter
                    </a>

                    <?php if ($lastRead && (int)($lastRead['chapter_id'] ?? 0) > 0): ?>
                        <a class="chip" href="user_chapter_baca.php?id=<?= (int)$lastRead['chapter_id'] ?>&from=detail&buku_id=<?= (int)$buku['id'] ?>">
                            <i class="fas fa-book-open"></i> Lanjutkan (Ch. <?= (int)($lastRead['chapter_nomor'] ?? 0) ?>)
                        </a>
                    <?php endif; ?>

                    <form method="POST" action="user_buku_simpan_toggle.php" style="display:inline;">
                        <input type="hidden" name="buku_id" value="<?= (int)$buku['id'] ?>">
                        <input type="hidden" name="redirect" value="user_buku_detail.php?id=<?= (int)$buku['id'] ?>">
                        <button type="submit" class="chip">
                            <i class="fas <?= $isSaved ? 'fa-bookmark' : 'fa-bookmark' ?>"></i>
                            <?= $isSaved ? 'Tersimpan' : 'Simpan' ?>
                        </button>
                    </form>

                    <a class="chip" href="user_buku.php">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>

                </div>
            </div>

        </div>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="row">

        <!-- PINJAM -->
        <div class="col-lg-4 mb-3">
            <div class="panel">
                <div class="panel-header">Pinjam</div>
                <div class="panel-body">

                    <?php if ($hasFile): ?>
                        <a class="chip mb-3" href="<?= htmlspecialchars($buku['file_buku']) ?>" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    <?php endif; ?>

                    <form method="POST" action="user_pinjam.php">
                        <input type="hidden" name="buku_id" value="<?= $buku['id'] ?>">

                        <input type="number" name="jumlah" value="1" class="form-control mb-2" min="1" required>
                        <input type="date" name="tanggal_pinjam" value="<?= date('Y-m-d') ?>" class="form-control mb-2" required>
                        <input type="date" name="tanggal_kembali" class="form-control mb-2" required>

                        <button class="btn btn-primary btn-block">Pinjam</button>
                    </form>

                </div>
            </div>
        </div>

        <!-- DESKRIPSI + CHAPTER -->
        <div class="col-lg-8">

            <div class="panel mb-3">
                <div class="panel-header">Deskripsi</div>
                <div class="panel-body">
                    <div style="white-space: pre-wrap;">
                        <?= htmlspecialchars($buku['isi']) ?>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header d-flex justify-content-between">
                    <span>Chapter Terbaru</span>

                    <a class="chip" href="user_chapters.php?buku_id=<?= $buku['id'] ?>&from=detail">
                        <i class="fas fa-list"></i> Lihat Semua
                    </a>
                </div>

                <div class="panel-body">
                    <?php if (!$chapterLatest): ?>
                        <div class="text-muted">Belum ada chapter</div>
                    <?php else: ?>

                        <?php foreach ($chapterLatest as $c): ?>
                            <a class="chapter-item mb-2" href="user_chapter_baca.php?id=<?= $c['id'] ?>">
                                <div>
                                    <div class="chapter-title">Chapter <?= $c['nomor'] ?></div>
                                    <div class="chapter-time"><?= htmlspecialchars($c['judul']) ?></div>
                                </div>
                                <div class="chapter-time"><?= $c['created_at'] ?></div>
                            </a>
                        <?php endforeach; ?>

                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>
</html>