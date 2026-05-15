<?php
session_start();
include "config.php";

// Proteksi Halaman
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'user') {
    header("Location: index.php");
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

$flashMsg = trim((string)($_GET['msg'] ?? ''));

// Ambil Daftar Buku yang Sedang Dipinjam
$pinjamanAktif = [];
$stmt = $conn->prepare("SELECT p.id AS peminjaman_id, p.tanggal_pinjam, p.tanggal_kembali, p.status,
                               b.id AS buku_id, b.judul, b.author, b.cover,
                               dp.jumlah
                        FROM peminjaman p
                        JOIN detail_peminjaman dp ON dp.peminjaman_id = p.id
                        JOIN buku b ON b.id = dp.buku_id
                        WHERE p.user_id = ? AND p.status = 'dipinjam'
                        ORDER BY p.id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $pinjamanAktif[] = $r;
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard User - WebNovel</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,300,400,600,700,800,900" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
    
    <style>
        body { background-color: #f8f9fc; }

        .pinjam-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            background: #111;
            position: relative;
        }

        .pinjam-cover {
            aspect-ratio: 2/3;
            object-fit: cover;
            width: 100%;
            display: block;
        }

        .pinjam-overlay {
            position: absolute;
            inset: auto 0 0 0;
            padding: 36px 10px 10px;
            background: linear-gradient(to top, rgba(0,0,0,0.96) 0%, rgba(0,0,0,0.35) 55%, transparent 100%);
            color: #fff;
        }

        .pinjam-title {
            font-size: 0.9rem;
            font-weight: 800;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.2;
        }

        .badge-count {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(0,0,0,0.65);
            color: #fff;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 8px;
            z-index: 2;
        }

        .badge-date {
            display: inline-block;
            background: #4e73df;
            color: #fff;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 8px;
            font-weight: 800;
        }
    </style>
</head>

<body class="bg-light">
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h4 mb-0 text-gray-800">Beranda Saya</h1>
            <div class="small text-muted">Selamat datang kembali, <strong><?= htmlspecialchars($_SESSION['nama'] ?? 'User') ?></strong></div>
        </div>
        <div>
            <a href="user.php" class="btn btn-sm btn-secondary">Pinjaman</a>
            <a href="user_riwayat.php" class="btn btn-sm btn-secondary">Riwayat</a>
            <a href="user_buku_simpan.php" class="btn btn-sm btn-primary">Tersimpan</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($flashMsg !== ''): ?>
        <div class="alert alert-info shadow-sm"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h5 class="h6 font-weight-bold text-gray-800 mb-0">Peminjaman Saya</h5>
        </div>

        <?php if (empty($pinjamanAktif)): ?>
            <div class="w-100 text-center py-5 bg-white rounded shadow-sm">
                <div class="text-muted">Belum ada buku yang sedang dipinjam.</div>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pinjamanAktif as $p): ?>
                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                        <a class="text-decoration-none" href="user_buku_detail.php?id=<?= (int)($p['buku_id'] ?? 0) ?>">
                            <div class="pinjam-card shadow-sm">
                                <div class="badge-count">x<?= (int)($p['jumlah'] ?? 1) ?></div>
                                <img
                                    src="img/covers/<?= htmlspecialchars($p['cover'] ?? 'default.jpg') ?>"
                                    class="pinjam-cover"
                                    onerror="this.src='https://via.placeholder.com/200x300?text=No+Cover'"
                                    alt="cover"
                                >
                                <div class="pinjam-overlay">
                                    <div class="pinjam-title"><?= htmlspecialchars($p['judul'] ?? '') ?></div>
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge-date"><?= htmlspecialchars((string)($p['tanggal_pinjam'] ?? '')) ?></span>
                                        <span class="small" style="opacity:0.85"><?= htmlspecialchars((string)($p['author'] ?? '')) ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>

                        <form method="POST" action="user_kembalikan.php" class="mt-2" onsubmit="return confirm('Yakin ingin mengembalikan buku ini?')">
                            <input type="hidden" name="peminjaman_id" value="<?= (int)($p['peminjaman_id'] ?? 0) ?>">
                            <button type="submit" class="btn btn-sm btn-success btn-block">Kembalikan</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>