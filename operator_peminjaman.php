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

$flashSuccess = '';
$flashError = '';

if (isset($_POST['action']) && $_POST['action'] === 'kembalikan') {
    $peminjamanId = (int)($_POST['peminjaman_id'] ?? 0);
    if ($peminjamanId <= 0) {
        $flashError = 'Data tidak valid.';
    } else {
        $stmt = $conn->prepare("UPDATE peminjaman SET status = 'dikembalikan', tanggal_kembali = CURDATE() WHERE id = ? AND status = 'dipinjam'");
        if (!$stmt) {
            $flashError = 'Gagal memproses.';
        } else {
            $stmt->bind_param('i', $peminjamanId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $flashSuccess = 'Peminjaman berhasil dikembalikan.';
            } else {
                $flashError = 'Peminjaman gagal dikembalikan.';
            }
            $stmt->close();
        }
    }
}

$status = trim((string)($_GET['status'] ?? ''));

$where = '';
if ($status !== '' && in_array($status, ['dipinjam', 'dikembalikan'], true)) {
    $where = "WHERE p.status = '" . $conn->real_escape_string($status) . "'";
}

$rows = [];
$sql = "SELECT p.id AS peminjaman_id, u.nama AS nama_user, p.tanggal_pinjam, p.tanggal_kembali, p.status,
               GROUP_CONCAT(CONCAT(b.judul, ' (', dp.jumlah, ')') SEPARATOR ', ') AS items
        FROM peminjaman p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN detail_peminjaman dp ON dp.peminjaman_id = p.id
        LEFT JOIN buku b ON b.id = dp.buku_id
        $where
        GROUP BY p.id
        ORDER BY p.id DESC
        LIMIT 200";

if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Kelola Peminjaman</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Kelola Peminjaman</h1>
            <div class="text-muted">Operator: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div>
            <a href="operator.php" class="btn btn-sm btn-secondary">Kembali</a>
            <a href="operator_laporan.php" class="btn btn-sm btn-info">Laporan</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="card shadow mb-3">
        <div class="card-body">
            <form method="GET" class="form-inline" style="gap: 8px;">
                <label class="small mb-0">Status</label>
                <select name="status" class="form-control">
                    <option value="" <?= ($status === '') ? 'selected' : '' ?>>Semua</option>
                    <option value="dipinjam" <?= ($status === 'dipinjam') ? 'selected' : '' ?>>dipinjam</option>
                    <option value="dikembalikan" <?= ($status === 'dikembalikan') ? 'selected' : '' ?>>dikembalikan</option>
                </select>
                <button class="btn btn-primary" type="submit">Filter</button>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Item</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows) === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($rows as $r): ?>
                                <tr>
                                    <td><?= (int)($r['peminjaman_id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars($r['nama_user'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['tanggal_pinjam'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['tanggal_kembali'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['status'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($r['items'] ?? '-') ?></td>
                                    <td>
                                        <?php if (($r['status'] ?? '') === 'dipinjam'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="kembalikan">
                                                <input type="hidden" name="peminjaman_id" value="<?= (int)($r['peminjaman_id'] ?? 0) ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Kembalikan</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
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
