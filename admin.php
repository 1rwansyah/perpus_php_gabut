<?php
session_start();
include "config.php";

if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit;
}

$flashSuccess = '';
$flashError = '';

if (isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $newRole = (string)($_POST['role'] ?? '');
    $allowedRoles = ['user', 'operator', 'admin'];

    if ($targetUserId <= 0) {
        $flashError = 'User tidak valid.';
    } elseif (!in_array($newRole, $allowedRoles, true)) {
        $flashError = 'Role tidak valid.';
    } elseif ($targetUserId === (int)($_SESSION['user_id'] ?? 0)) {
        $flashError = 'Tidak bisa mengubah role akun yang sedang login.';
    } else {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        if (!$stmt) {
            $flashError = 'Gagal memproses.';
        } else {
            $stmt->bind_param('i', $targetUserId);
            $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $targetRole = (string)($target['role'] ?? '');
            if ($targetRole === '') {
                $flashError = 'User tidak ditemukan.';
            } elseif ($targetRole === 'admin') {
                $flashError = 'Admin tidak bisa mengubah role admin lain (setara).';
            } else {
                $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
                if (!$stmt) {
                    $flashError = 'Gagal memproses.';
                } else {
                    $stmt->bind_param("si", $newRole, $targetUserId);
                    if ($stmt->execute()) {
                        $flashSuccess = 'Role berhasil diubah.';
                    } else {
                        $flashError = 'Role gagal diubah.';
                    }
                    $stmt->close();
                }
            }
        }
    }
}

$stats = [
    'users' => 0,
    'kategori' => 0,
    'buku' => 0,
    'peminjaman' => 0,
    'pengumuman' => 0,
];

if ($result = $conn->query("SELECT COUNT(*) AS total FROM users")) {
    $row = $result->fetch_assoc();
    $stats['users'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM kategori")) {
    $row = $result->fetch_assoc();
    $stats['kategori'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM buku")) {
    $row = $result->fetch_assoc();
    $stats['buku'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM peminjaman")) {
    $row = $result->fetch_assoc();
    $stats['peminjaman'] = (int)($row['total'] ?? 0);
}
if ($result = $conn->query("SELECT COUNT(*) AS total FROM pengumuman")) {
    $row = $result->fetch_assoc();
    $stats['pengumuman'] = (int)($row['total'] ?? 0);
}

$users = [];
if ($result = $conn->query("SELECT id, nama, email, role, created_at FROM users ORDER BY id DESC LIMIT 50")) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Halaman Admin</h1>
            <div class="text-muted">Login sebagai: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
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
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Users</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['users'] ?></div>
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
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Buku</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['buku'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Peminjaman</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['peminjaman'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-3">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar User</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                    <th>Dibuat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($users) === 0): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Belum ada data</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td><?= (int)$u['id'] ?></td>
                                            <td><?= htmlspecialchars($u['nama'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($u['role'] ?? '') ?></td>
                                            <td>
                                                <?php if (((int)$u['id']) === (int)($_SESSION['user_id'] ?? 0)): ?>
                                                    <span class="text-muted small">-</span>
                                                <?php else: ?>
                                                    <form method="POST" class="form-inline" style="gap: 8px;">
                                                        <input type="hidden" name="action" value="update_role">
                                                        <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                                                        <select name="role" class="form-control form-control-sm">
                                                            <option value="user" <?= (($u['role'] ?? '') === 'user') ? 'selected' : '' ?>>user</option>
                                                            <option value="operator" <?= (($u['role'] ?? '') === 'operator') ? 'selected' : '' ?>>operator</option>
                                                            <option value="admin" <?= (($u['role'] ?? '') === 'admin') ? 'selected' : '' ?>>admin</option>
                                                        </select>
                                                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($u['created_at'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Ringkasan</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>Pengumuman</div>
                        <div class="font-weight-bold"><?= $stats['pengumuman'] ?></div>
                    </div>
                    <hr>
                    <div class="small text-muted">Admin bisa mengelola semua data (users/kategori/buku/peminjaman/pengumuman).</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>
