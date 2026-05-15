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

$uploadBaseDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
$uploadCoverDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'covers';
$uploadBookDir = $uploadBaseDir . DIRECTORY_SEPARATOR . 'books';

$flashSuccess = '';
$flashError = '';

if (isset($_POST['submit'])) {
    $judul = trim((string)($_POST['judul'] ?? ''));
    $author = trim((string)($_POST['author'] ?? ''));
    $tahun = trim((string)($_POST['tahun_terbit'] ?? ''));
    $kategoriId = (int)($_POST['kategori_id'] ?? 0);
    $kategoriBaru = trim((string)($_POST['kategori_baru'] ?? ''));
    $coverPosition = trim((string)($_POST['cover_position'] ?? 'top'));
    $coverWidth = (int)($_POST['cover_width'] ?? 200);
    $coverHeight = (int)($_POST['cover_height'] ?? 0);
    $isi = (string)($_POST['isi'] ?? '');

    $allowedCoverExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowedBookExt = ['pdf', 'doc', 'docx'];
    $coverPath = '';
    $bookPath = null;
    $bookOriginalName = null;

    if ($judul === '' || $author === '' || $tahun === '' || $isi === '') {
        $flashError = 'Judul, author, tahun terbit, dan isi wajib diisi.';
    } elseif (!preg_match('/^\d{4}$/', $tahun)) {
        $flashError = 'Tahun terbit tidak valid.';
    } elseif (!in_array($coverPosition, ['top', 'left', 'right'], true)) {
        $flashError = 'Posisi cover tidak valid.';
    } elseif ($coverWidth < 50 || $coverWidth > 1200) {
        $flashError = 'Lebar cover tidak valid.';
    } elseif ($coverHeight < 0 || $coverHeight > 2000) {
        $flashError = 'Tinggi cover tidak valid.';
    } else {
        $conn->begin_transaction();
        try {
            if (!is_dir($uploadCoverDir) && !mkdir($uploadCoverDir, 0777, true) && !is_dir($uploadCoverDir)) {
                throw new Exception('Gagal menyiapkan folder cover.');
            }
            if (!is_dir($uploadBookDir) && !mkdir($uploadBookDir, 0777, true) && !is_dir($uploadBookDir)) {
                throw new Exception('Gagal menyiapkan folder file buku.');
            }

            if (isset($_FILES['cover_file']) && is_array($_FILES['cover_file']) && ($_FILES['cover_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($_FILES['cover_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new Exception('Upload cover gagal.');
                }

                $original = (string)($_FILES['cover_file']['name'] ?? '');
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedCoverExt, true)) {
                    throw new Exception('Cover harus gambar (jpg/png/webp/gif).');
                }

                $filename = 'cover_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadCoverDir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file((string)$_FILES['cover_file']['tmp_name'], $dest)) {
                    throw new Exception('Gagal menyimpan cover.');
                }

                $coverPath = 'uploads/covers/' . $filename;
            }

            if (isset($_FILES['book_file']) && is_array($_FILES['book_file']) && ($_FILES['book_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                if (($_FILES['book_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                    throw new Exception('Upload file buku gagal.');
                }

                $original = (string)($_FILES['book_file']['name'] ?? '');
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowedBookExt, true)) {
                    throw new Exception('File buku harus pdf/doc/docx.');
                }

                $filename = 'book_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadBookDir . DIRECTORY_SEPARATOR . $filename;

                if (!move_uploaded_file((string)$_FILES['book_file']['tmp_name'], $dest)) {
                    throw new Exception('Gagal menyimpan file buku.');
                }

                $bookPath = 'uploads/books/' . $filename;
                $bookOriginalName = $original;
            }

            if ($kategoriId <= 0) {
                if ($kategoriBaru === '') {
                    throw new Exception('Pilih kategori atau isi kategori baru.');
                }
                $stmt = $conn->prepare("INSERT INTO kategori (nama_kategori) VALUES (?)");
                if (!$stmt) {
                    throw new Exception('Gagal membuat kategori.');
                }
                $stmt->bind_param('s', $kategoriBaru);
                if (!$stmt->execute()) {
                    $stmt->close();
                    throw new Exception('Gagal membuat kategori.');
                }
                $kategoriId = (int)$conn->insert_id;
                $stmt->close();
            }

            $stmt = $conn->prepare("INSERT INTO buku (judul, author, tahun_terbit, kategori_id, cover, cover_position, cover_width, cover_height, file_buku, file_buku_nama, isi) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception('Gagal menambah buku.');
            }
            $stmt->bind_param('sssissiiiss', $judul, $author, $tahun, $kategoriId, $coverPath, $coverPosition, $coverWidth, $coverHeight, $bookPath, $bookOriginalName, $isi);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Gagal menambah buku.');
            }
            $stmt->close();

            $conn->commit();
            $flashSuccess = 'Buku berhasil ditambahkan.';
        } catch (Throwable $e) {
            $conn->rollback();
            $flashError = $e->getMessage();
        }
    }
}

$kategori = [];
if ($result = $conn->query("SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC")) {
    while ($row = $result->fetch_assoc()) {
        $kategori[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Tambah Buku</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container py-4">
    <div class="d-flex align-items-center justify-content-between mb-4" style="gap: 12px; flex-wrap: wrap;">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Tambah Buku</h1>
            <div class="text-muted">Operator: <strong><?= htmlspecialchars($_SESSION['nama'] ?? '') ?></strong></div>
        </div>
        <div>
            <a href="operator.php" class="btn btn-sm btn-secondary">Kembali</a>
            <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
        </div>
    </div>

    <?php if ($flashSuccess !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
    <?php endif; ?>
    <?php if ($flashError !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="judul" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Author</label>
                    <input type="text" name="author" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Tahun Terbit</label>
                    <input type="number" name="tahun_terbit" class="form-control" min="1000" max="9999" required>
                </div>

                <div class="form-group">
                    <label>Kategori</label>
                    <select name="kategori_id" class="form-control">
                        <option value="0">-- pilih kategori --</option>
                        <?php foreach ($kategori as $k): ?>
                            <option value="<?= (int)$k['id'] ?>"><?= htmlspecialchars($k['nama_kategori'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Kalau belum ada, isi kategori baru di bawah.</small>
                </div>

                <div class="form-group">
                    <label>Kategori Baru (opsional)</label>
                    <input type="text" name="kategori_baru" class="form-control">
                </div>

                <div class="form-group">
                    <label>Cover (opsional, upload gambar)</label>
                    <input type="file" name="cover_file" class="form-control" accept="image/*">
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Posisi Cover</label>
                        <select name="cover_position" class="form-control">
                            <option value="top">top</option>
                            <option value="left">left</option>
                            <option value="right">right</option>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Lebar Cover (px)</label>
                        <input type="number" name="cover_width" class="form-control" value="200" min="50" max="1200">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Tinggi Cover (px, 0=auto)</label>
                        <input type="number" name="cover_height" class="form-control" value="0" min="0" max="2000">
                    </div>
                </div>

                <div class="form-group">
                    <label>File Buku (opsional, pdf/doc/docx)</label>
                    <input type="file" name="book_file" class="form-control" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                </div>

                <div class="form-group">
                    <label>Isi Buku</label>
                    <textarea name="isi" class="form-control" rows="10" required></textarea>
                </div>

                <button type="submit" name="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script src="/perpustakaan/vendor/jquery/jquery.min.js"></script>
<script src="/perpustakaan/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/perpustakaan/js/sb-admin-2.min.js"></script>
</body>
</html>
