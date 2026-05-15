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

$dari = trim((string)($_GET['dari'] ?? ''));
$sampai = trim((string)($_GET['sampai'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$where = [];
$params = [];
$types = '';

if ($dari !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
    $where[] = 'p.tanggal_pinjam >= ?';
    $params[] = $dari;
    $types .= 's';
}
if ($sampai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
    $where[] = 'p.tanggal_pinjam <= ?';
    $params[] = $sampai;
    $types .= 's';
}
if ($status !== '' && in_array($status, ['dipinjam', 'dikembalikan'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
    $types .= 's';
}

$sql = "SELECT p.id AS peminjaman_id, u.nama AS nama_user, p.tanggal_pinjam, p.tanggal_kembali, p.status,
               b.judul, dp.jumlah
        FROM peminjaman p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN detail_peminjaman dp ON dp.peminjaman_id = p.id
        LEFT JOIN buku b ON b.id = dp.buku_id";

if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY p.id DESC';

$rows = [];
if (count($params) > 0) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
} else {
    if ($result = $conn->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Laporan Peminjaman</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .card { border: 0 !important; box-shadow: none !important; }
        }
    </style>
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4 no-print">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Laporan Peminjaman</h1>
            <div class="text-muted">Operator: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div>
            <a href="operator.php" class="btn btn-sm btn-secondary">Kembali</a>
            <button onclick="window.print()" class="btn btn-sm btn-primary">Cetak</button>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <div class="card shadow mb-3 no-print">
        <div class="card-body">
            <form method="GET" class="form-row">
                <div class="col-md-3 mb-2">
                    <label>Dari</label>
                    <input type="date" name="dari" class="form-control" value="<?= htmlspecialchars($dari) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="<?= htmlspecialchars($sampai) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="" <?= ($status === '') ? 'selected' : '' ?>>Semua</option>
                        <option value="dipinjam" <?= ($status === 'dipinjam') ? 'selected' : '' ?>>dipinjam</option>
                        <option value="dikembalikan" <?= ($status === 'dikembalikan') ? 'selected' : '' ?>>dikembalikan</option>
                    </select>
                </div>
                <div class="col-md-3 mb-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Buku</th>
                            <th>Jumlah</th>
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
                                    <td><?= htmlspecialchars($r['judul'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r['jumlah'] ?? '-') ?></td>
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
