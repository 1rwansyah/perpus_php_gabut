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

function timeAgo(string $datetime): string
{
    $ts = strtotime($datetime);
    if ($ts === false) {
        return '';
    }

    $diff = time() - $ts;
    if ($diff < 60) {
        return 'Baru';
    }

    $mins = (int)floor($diff / 60);
    if ($mins < 60) {
        return $mins . 'm';
    }

    $hours = (int)floor($mins / 60);
    if ($hours < 24) {
        return $hours . 'j';
    }

    $days = (int)floor($hours / 24);
    if ($days < 7) {
        return $days . 'h';
    }

    $weeks = (int)floor($days / 7);
    if ($weeks < 4) {
        return $weeks . 'mgg';
    }

    $months = (int)floor($days / 30);
    if ($months < 12) {
        return $months . 'bln';
    }

    $years = (int)floor($days / 365);
    return $years . 'th';
}

$q = trim((string)($_GET['q'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

$where = " WHERE urh.user_id = ? ";
$params = [$userId];
$types = 'i';

if ($q !== '') {
    $where .= " AND (b.judul LIKE ? OR b.author LIKE ?) ";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}

$totalRows = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM user_read_history urh JOIN buku b ON b.id = urh.buku_id" . $where);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = (int)($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$history = [];
$sql = "SELECT urh.buku_id, urh.chapter_id, urh.last_read_at,
               b.judul AS judul_buku, b.author AS author_buku, b.cover,
               c.nomor AS chapter_nomor, c.judul AS judul_chapter
        FROM user_read_history urh
        JOIN buku b ON b.id = urh.buku_id
        JOIN chapters c ON c.id = urh.chapter_id" .
        $where .
        " ORDER BY urh.last_read_at DESC
          LIMIT ? OFFSET ?";

$params2 = $params;
$params2[] = $perPage;
$params2[] = $offset;
$types2 = $types . 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types2, ...$params2);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $history[] = $r;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>History Membaca - WebNovel</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body { background-color: #f8f9fc; }

        .history-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            background: #1a1a1a;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
            position: relative;
        }
        .history-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 0.75rem 1.5rem rgba(0,0,0,0.18);
        }

        .history-cover {
            aspect-ratio: 2/3;
            object-fit: cover;
            width: 100%;
            display: block;
        }

        .history-overlay {
            position: absolute;
            inset: auto 0 0 0;
            padding: 34px 10px 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.96) 0%, rgba(0,0,0,0.35) 55%, transparent 100%);
            color: #fff;
        }

        .history-title {
            font-size: 0.9rem;
            font-weight: 800;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
        }

        .history-sub {
            font-size: 11px;
            opacity: 0.85;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
        }

        .badge-time {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.65);
            color: #fff;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 8px;
            z-index: 2;
            backdrop-filter: blur(6px);
        }

        .badge-ch {
            display: inline-block;
            background: #4e73df;
            color: #fff;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 800;
        }

        .topbar-card {
            border: none;
            border-radius: 12px;
        }

        .search-input {
            border-radius: 999px;
        }

        .search-btn {
            border-radius: 999px;
        }
    </style>
</head>

<body>
<div class="container py-4">
    <div class="d-flex align-items-start align-items-md-center justify-content-between mb-3 flex-column flex-md-row">
        <div class="mb-2 mb-md-0">
            <h1 class="h4 mb-0 text-gray-800">History Membaca</h1>
            <div class="small text-muted">Akun: <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></strong></div>
        </div>
        <div class="d-flex">
            <a href="user.php" class="btn btn-sm btn-secondary shadow-sm"><i class="fas fa-arrow-left fa-sm"></i> Kembali</a>
            <a href="logout.php" class="btn btn-sm btn-outline-danger shadow-sm ml-2">Logout</a>
        </div>
    </div>

    <div class="card shadow-sm mb-4 topbar-card">
        <div class="card-body">
            <form method="get" class="mb-0">
                <div class="row no-gutters align-items-center">
                    <div class="col">
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm search-input" placeholder="Cari judul / author...">
                    </div>
                    <div class="col-auto pl-2">
                        <button class="btn btn-sm btn-primary search-btn" type="submit"><i class="fas fa-search fa-sm"></i></button>
                    </div>
                </div>
            </form>
            <div class="small text-muted mt-2">
                Menampilkan <strong><?= count($history) ?></strong> dari <strong><?= $totalRows ?></strong>
            </div>
        </div>
    </div>

    <?php if (empty($history)): ?>
        <div class="text-center py-5 bg-white rounded shadow-sm">
            <div class="text-muted">Belum ada riwayat membaca.</div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($history as $h): ?>
                <div class="col-6 col-md-3 col-lg-2 mb-3">
                    <a class="text-decoration-none" href="user_chapter_baca.php?id=<?= $h['chapter_id'] ?>&from=detail&buku_id=<?= $h['buku_id'] ?>">
                        <div class="history-card">
                            <div class="badge-time"><i class="fas fa-clock fa-xs"></i> <?= htmlspecialchars(timeAgo((string)$h['last_read_at'])) ?></div>
                            <img
                                src="img/covers/<?= htmlspecialchars($h['cover'] ?? 'default.jpg') ?>"
                                class="history-cover"
                                onerror="this.src='https://via.placeholder.com/200x300?text=No+Cover'"
                                alt="cover"
                            >
                            <div class="history-overlay">
                                <div class="history-title"><?= htmlspecialchars($h['judul_buku']) ?></div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <span class="badge-ch">CH. <?= htmlspecialchars((string)$h['chapter_nomor']) ?></span>
                                    <span class="history-sub"><?= htmlspecialchars((string)($h['author_buku'] ?? '')) ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination" class="mt-3">
                <ul class="pagination pagination-sm justify-content-center">
                    <?php
                        $base = 'user_history.php?q=' . urlencode($q) . '&page=';
                        $prevDisabled = $page <= 1 ? ' disabled' : '';
                        $nextDisabled = $page >= $totalPages ? ' disabled' : '';
                    ?>

                    <li class="page-item<?= $prevDisabled ?>">
                        <a class="page-link" href="<?= $base . max(1, $page - 1) ?>">Prev</a>
                    </li>

                    <?php
                        $start = max(1, $page - 2);
                        $end = min($totalPages, $page + 2);
                    ?>

                    <?php for ($p = $start; $p <= $end; $p++): ?>
                        <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                            <a class="page-link" href="<?= $base . $p ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item<?= $nextDisabled ?>">
                        <a class="page-link" href="<?= $base . min($totalPages, $page + 1) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
