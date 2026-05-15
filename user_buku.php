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

$q = trim((string)($_GET['q'] ?? ''));

$buku = [];
if ($q !== '') {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT b.id, b.judul, b.author, b.tahun_terbit, b.cover, k.nama_kategori
                            FROM buku b
                            LEFT JOIN kategori k ON k.id = b.kategori_id
                            WHERE b.judul LIKE ? OR b.author LIKE ?
                            ORDER BY b.id DESC");
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $buku[] = $row;
    }
    $stmt->close();
} else {
    $sql = "SELECT b.id, b.judul, b.author, b.tahun_terbit, b.cover, k.nama_kategori
            FROM buku b
            LEFT JOIN kategori k ON k.id = b.kategori_id
            ORDER BY b.id DESC";
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $buku[] = $row;
        }
    }
}

$chaptersByBook = [];
$stmtCh = $conn->prepare("SELECT id, nomor FROM chapters WHERE buku_id = ? ORDER BY nomor DESC LIMIT 4");
if ($stmtCh) {
    foreach ($buku as $b) {
        $bookId = (int)($b['id'] ?? 0);
        if ($bookId <= 0) {
            continue;
        }
        $stmtCh->bind_param('i', $bookId);
        $stmtCh->execute();
        $resultCh = $stmtCh->get_result();
        $rows = [];
        while ($row = $resultCh->fetch_assoc()) {
            $rows[] = $row;
        }
        $chaptersByBook[$bookId] = $rows;
    }
    $stmtCh->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Daftar Buku</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .book-card {
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            color: #111827;
            border: 1px solid rgba(0,0,0,.08);
        }
        .book-cover {
            position: relative;
            width: 100%;
            padding-top: 140%;
            background: #f3f4f6;
        }
        .book-cover img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .book-cover::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0,0,0,0) 55%, rgba(0,0,0,.25) 100%);
        }
        .book-title {
            font-weight: 800;
            line-height: 1.2;
            font-size: 14px;
            margin: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .book-meta {
            color: #6b7280;
            font-size: 12px;
        }
        .chapter-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 8px 12px;
            background: #f9fafb;
            border-top: 1px solid rgba(0,0,0,.06);
            font-size: 12px;
            text-decoration: none;
        }
        .chapter-row a { color: #111827; text-decoration: none; }
        .chapter-row a:hover { text-decoration: underline; }
        .chapter-range { color: #6b7280; white-space: nowrap; }
        .topbar-dark {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,.08);
        }
    </style>
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Daftar Buku</h1>
            <div class="text-muted">User: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div>
            <a href="user.php" class="btn btn-sm btn-secondary">Pinjaman</a>
            <a href="user_riwayat.php" class="btn btn-sm btn-secondary">Riwayat</a>
            <a href="user_buku_simpan.php" class="btn btn-sm btn-primary">Tersimpan</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <div class="card shadow mb-3 topbar-dark">
        <div class="card-body">
            <form method="GET" class="form-inline" style="gap: 8px;">
                <input type="text" name="q" class="form-control" placeholder="Cari judul/author" value="<?= htmlspecialchars($q) ?>">
                <button type="submit" class="btn btn-primary">Cari</button>
            </form>
        </div>
    </div>

    <?php if (count($buku) === 0): ?>
        <div class="card shadow">
            <div class="card-body text-center text-muted">Belum ada buku</div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($buku as $b): ?>
                <?php
                $bookId = (int)($b['id'] ?? 0);
                $cover = trim((string)($b['cover'] ?? ''));
                $chapterRows = $chaptersByBook[$bookId] ?? [];
                $nums = [];
                $latestChapterId = 0;
                $latestChapterNo = 0;
                if (count($chapterRows) > 0) {
                    $latestChapterId = (int)($chapterRows[0]['id'] ?? 0);
                    $latestChapterNo = (int)($chapterRows[0]['nomor'] ?? 0);
                }
                foreach ($chapterRows as $cr) {
                    $n = (int)($cr['nomor'] ?? 0);
                    if ($n > 0) {
                        $nums[] = $n;
                    }
                }
                $chapterText = '';
                if (count($nums) === 1) {
                    $chapterText = (string)$nums[0];
                } elseif (count($nums) > 1) {
                    $chapterText = (string)min($nums) . '-' . (string)max($nums);
                }
                ?>
                <div class="col-6 col-md-4 col-lg-3 mb-3">
                    <div class="book-card shadow">
                        <div class="book-cover">
                            <?php if ($cover !== ''): ?>
                                <img src="<?= htmlspecialchars($cover) ?>" alt="cover">
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <div class="d-flex align-items-start justify-content-between" style="gap: 8px;">
                                <div style="min-width: 0;">
                                    <p class="book-title" title="<?= htmlspecialchars($b['judul'] ?? '') ?>"><?= htmlspecialchars($b['judul'] ?? '') ?></p>
                                    <div class="book-meta"><?= htmlspecialchars($b['author'] ?? '') ?></div>
                                </div>
                                <div class="text-right" style="white-space: nowrap;">
                                    <a class="btn btn-sm btn-outline-light" href="user_buku_detail.php?id=<?= (int)$bookId ?>">Detail</a>
                                </div>
                            </div>

                            <div class="mt-2" style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a class="btn btn-sm btn-primary" href="user_buku_detail.php?id=<?= (int)$bookId ?>">Baca / Pinjam</a>
                                <a class="btn btn-sm btn-info" href="user_chapters.php?buku_id=<?= (int)$bookId ?>">Chapters</a>
                            </div>
                        </div>

                        <?php if (count($chapterRows) === 0): ?>
                            <div class="chapter-row">
                                <span class="text-muted">Belum ada chapter</span>
                                <span class="chapter-range">-</span>
                            </div>
                        <?php else: ?>
                            <a class="chapter-row" href="user_chapter_baca.php?id=<?= (int)$latestChapterId ?>&from=chapters">
                                <span>Chapter <?= (int)$latestChapterNo ?></span>
                                <span class="chapter-range"><?= htmlspecialchars($chapterText) ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>
