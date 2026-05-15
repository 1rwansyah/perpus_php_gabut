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

$stats = [
    'buku' => 0,
    'kategori' => 0,
    'dipinjam' => 0,
    'dikembalikan' => 0,
];

if ($result = $conn->query("SELECT COUNT(*) AS total FROM buku")) {
    $row = $result->fetch_assoc();
    $stats['buku'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM kategori")) {
    $row = $result->fetch_assoc();
    $stats['kategori'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dipinjam'")) {
    $row = $result->fetch_assoc();
    $stats['dipinjam'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dikembalikan'")) {
    $row = $result->fetch_assoc();
    $stats['dikembalikan'] = (int)($row['total'] ?? 0);
}

$peminjaman = [];
$sql = "SELECT p.id, u.nama AS nama_user, p.tanggal_pinjam, p.tanggal_kembali, p.status
        FROM peminjaman p
        JOIN users u ON u.id = p.user_id
        ORDER BY p.id DESC
        LIMIT 10";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $peminjaman[] = $row;
    }
}

$bukuTerbaru = [];
$sql = "SELECT b.id, b.judul, b.author, b.tahun_terbit
        FROM buku b
        ORDER BY b.id DESC
        LIMIT 10";
if ($result = $conn->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $bukuTerbaru[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Operator</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4" style="gap: 12px; flex-wrap: wrap;">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Halaman Operator</h1>
            <div class="text-muted">Halo, <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div style="display:flex; gap:8px; flex-wrap: wrap;">
            <a href="operator_buku_tambah.php" class="btn btn-sm btn-primary"><i class="fas fa-plus"></i> Tambah Buku</a>
            <a href="operator_peminjaman.php" class="btn btn-sm btn-info"><i class="fas fa-clipboard-list"></i> Peminjaman</a>
            <a href="operator_laporan.php" class="btn btn-sm btn-secondary"><i class="fas fa-file-alt"></i> Laporan</a>
            <a href="logout.php" class="btn btn-sm btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-3 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Buku</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['buku'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Kategori</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['kategori'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Dipinjam</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['dipinjam'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Dikembalikan</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['dikembalikan'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-3">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">10 Peminjaman Terakhir</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Tanggal Pinjam</th>
                        <th>Tanggal Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($peminjaman) === 0): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($peminjaman as $p): ?>
                            <tr>
                                <td><?= (int)$p['id'] ?></td>
                                <td><?= htmlspecialchars($p['nama_user'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['tanggal_pinjam'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['tanggal_kembali'] ?? '') ?></td>
                                <td><?= htmlspecialchars($p['status'] ?? '') ?></td>
                                <td>
                                    <?php if (($p['status'] ?? '') === 'dipinjam'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="kembalikan">
                                            <input type="hidden" name="peminjaman_id" value="<?= (int)$p['id'] ?>">
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

    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">10 Buku Terbaru</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>ID</th>
                        <th>Judul</th>
                        <th>Penulis</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (count($bukuTerbaru) === 0): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted">Belum ada buku</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bukuTerbaru as $b): ?>
                            <tr>
                                <td><?= (int)$b['id'] ?></td>
                                <td><?= htmlspecialchars($b['judul'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['author'] ?? '') ?></td>
                                <td><?= htmlspecialchars($b['tahun_terbit'] ?? '') ?></td>
                                <td>
                                    <a class="btn btn-sm btn-warning" href="operator_buku_edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
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
