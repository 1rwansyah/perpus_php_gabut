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

$saved = [];
$stmt = $conn->prepare("SELECT b.id, b.judul, b.author, b.tahun_terbit, b.cover, k.nama_kategori, usb.created_at
                        FROM user_saved_books usb
                        JOIN buku b ON b.id = usb.buku_id
                        LEFT JOIN kategori k ON k.id = b.kategori_id
                        WHERE usb.user_id = ?
                        ORDER BY usb.created_at DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $saved[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Buku Tersimpan</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Buku Tersimpan</h1>
            <div class="text-muted">User: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div>
            <a href="user_buku.php" class="btn btn-sm btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Judul</th>
                            <th>Author</th>
                            <th>Kategori</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($saved) === 0): ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada buku tersimpan</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($saved as $b): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center" style="gap:10px;">
                                            <img
                                                src="img/covers/<?= htmlspecialchars($b['cover'] ?? 'default.jpg') ?>"
                                                alt="cover"
                                                style="width:48px;height:72px;object-fit:cover;border-radius:8px;flex:0 0 auto;"
                                                onerror="this.src='https://via.placeholder.com/96x144?text=No+Cover'"
                                            >
                                            <div>
                                                <div class="font-weight-bold text-gray-800"><?= htmlspecialchars($b['judul'] ?? '') ?></div>
                                                <div class="small text-muted"><?= htmlspecialchars((string)($b['tahun_terbit'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($b['author'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($b['nama_kategori'] ?? '') ?></td>
                                    <td style="white-space: nowrap;">
                                        <a class="btn btn-sm btn-primary" href="user_buku_detail.php?id=<?= (int)$b['id'] ?>">Detail</a>
                                        <form method="POST" action="user_buku_simpan_toggle.php" style="display:inline;">
                                            <input type="hidden" name="buku_id" value="<?= (int)$b['id'] ?>">
                                            <input type="hidden" name="redirect" value="user_buku_simpan.php">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Batal Simpan</button>
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
