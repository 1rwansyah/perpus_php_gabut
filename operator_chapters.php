<?php
session_start();
include "config.php";

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'operator') {
    header("Location: index.php");
    exit;
}

$bukuId = (int)($_GET['buku_id'] ?? 0);
if ($bukuId <= 0) {
    header('Location: operator.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, judul, author FROM buku WHERE id = ?");
$stmt->bind_param('i', $bukuId);
$stmt->execute();
$buku = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$buku) {
    header('Location: operator.php');
    exit;
}

$flashSuccess = '';
$flashError = '';

if (isset($_POST['action']) && $_POST['action'] === 'add') {
    $nomor = (int)($_POST['nomor'] ?? 0);
    $judul = trim((string)($_POST['judul'] ?? ''));
    $isi = (string)($_POST['isi'] ?? '');

    if ($nomor <= 0 || $judul === '') {
        $flashError = 'Nomor chapter dan judul wajib diisi.';
    } else {
        $stmt = $conn->prepare("INSERT INTO chapters (buku_id, nomor, judul, isi) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            $flashError = 'Gagal memproses.';
        } else {
            $stmt->bind_param('iiss', $bukuId, $nomor, $judul, $isi);
            try {
                if ($stmt->execute()) {
                    $flashSuccess = 'Chapter berhasil ditambahkan.';
                } else {
                    $flashError = 'Chapter gagal ditambahkan.';
                }
            } catch (Throwable $e) {
                $flashError = 'Chapter gagal ditambahkan.';
            }
            $stmt->close();
        }
    }
}

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $chapterId = (int)($_POST['chapter_id'] ?? 0);
    if ($chapterId <= 0) {
        $flashError = 'Data tidak valid.';
    } else {
        $stmt = $conn->prepare("DELETE FROM chapters WHERE id = ? AND buku_id = ?");
        if (!$stmt) {
            $flashError = 'Gagal memproses.';
        } else {
            $stmt->bind_param('ii', $chapterId, $bukuId);
            if ($stmt->execute()) {
                $flashSuccess = 'Chapter berhasil dihapus.';
            } else {
                $flashError = 'Chapter gagal dihapus.';
            }
            $stmt->close();
        }
    }
}

$chapters = [];
$stmt = $conn->prepare("SELECT id, nomor, judul, created_at FROM chapters WHERE buku_id = ? ORDER BY nomor DESC");
$stmt->bind_param('i', $bukuId);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $chapters[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kelola Chapters</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fc !important; }
        .hero {
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,.08);
            position: relative;
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            background: #eef2ff;
        }
        .hero-bg::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,.70) 0%, rgba(255,255,255,.95) 55%, rgba(255,255,255,1) 100%);
        }
        .hero-content {
            position: relative;
            z-index: 2;
            padding: 18px;
            color: #111827;
        }
        .hero-title { font-weight: 900; margin: 0; }
        .hero-sub { color: #6b7280; }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 10px;
            background: #f1f3f9;
            border: 1px solid #e0e3eb;
            color: #111827;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
        }
        .chip:hover { text-decoration: none; background: #e9ecf5; }
        .panel {
            border-radius: 14px;
            overflow: hidden;
            background: #ffffff;
            border: 1px solid rgba(0,0,0,.08);
            color: #111827;
        }
        .panel .panel-header { padding: 14px 16px; border-bottom: 1px solid rgba(0,0,0,.08); font-weight: 900; }
        .panel .panel-body { padding: 16px; }

        .table { color: #111827; }
        .table-bordered { border-color: rgba(0,0,0,.10); }
        .table-bordered td, .table-bordered th { border-color: rgba(0,0,0,.10) !important; }
        .thead-light th { background: #f9fafb !important; color: #111827 !important; border-color: rgba(0,0,0,.10) !important; }
    </style>
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="hero mb-4">
        <div class="hero-bg"></div>
        <div class="hero-content">
            <div class="d-flex align-items-center justify-content-between" style="gap: 12px; flex-wrap: wrap;">
                <div>
                    <h1 class="hero-title h3">Kelola Chapter</h1>
                    <div class="hero-sub">Buku: <strong><?= htmlspecialchars($buku['judul'] ?? '') ?></strong> (<?= htmlspecialchars($buku['author'] ?? '') ?>)</div>
                </div>
                <div style="display:flex; gap:10px; flex-wrap: wrap;">
                    <a class="chip" href="operator_buku_edit.php?id=<?= (int)$bukuId ?>"><i class="fas fa-edit"></i> Edit Buku</a>
                    <a class="chip" href="operator.php"><i class="fas fa-arrow-left"></i> Kembali</a>
                    <a class="chip" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="panel mb-3">
        <div class="panel-header">Tambah Chapter</div>
        <div class="panel-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label>Nomor</label>
                        <input type="number" name="nomor" class="form-control" min="1" required>
                    </div>
                    <div class="form-group col-md-9">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Isi</label>
                    <textarea name="isi" class="form-control" rows="8"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">Daftar Chapter</div>
        <div class="panel-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Chapter</th>
                            <th>Judul</th>
                            <th>Update</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($chapters) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada chapter</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($chapters as $c): ?>
                                <tr>
                                    <td><?= (int)($c['nomor'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($c['judul'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                                    <td>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus chapter ini?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="chapter_id" value="<?= (int)($c['id'] ?? 0) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>
