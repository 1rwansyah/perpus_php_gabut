<?php
session_start();
include "config.php";

$error = "";

// kalau sudah login
if (isset($_SESSION['login'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {

    $nama = $_POST['nama'];
    $password = $_POST['password'];

    // AMAN pakai prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE nama = ?");
    $stmt->bind_param("s", $nama);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {

        // Cek password dengan password_verify (hash bcrypt)
        if (password_verify($password, $user['password'])) {

            $_SESSION['login'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['nama'] = $user['nama'];

            header("Location: index.php");
            exit;

        } else {
            $error = "Password salah!";
        }

    } else {
        $error = "Username tidak ditemukan!";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Login</title>

    <link href="/perpustakaan/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
    <link href="/perpustakaan/css/sb-admin-2.min.css" rel="stylesheet">

    <style>
        .auth-bg {
            min-height: 100vh;
            background: url('/perpustakaan/img/login.webp') center/cover no-repeat fixed;
        }
        .auth-overlay {
            min-height: 100vh;
            background: rgba(17, 24, 39, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 12px;
        }
        .auth-card {
            border-radius: 14px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.22);
            border: 1px solid rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(10px);
        }
        .auth-form-side {
            background: transparent;
        }
        .auth-title { color: rgba(17, 24, 39, 0.95); }
        .auth-subtitle { color: rgba(17, 24, 39, 0.75); }
        .auth-input {
            height: 44px;
            border-radius: 999px !important;
            background: rgba(255, 255, 255, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.65);
        }
        .auth-btn {
            height: 44px;
            border-radius: 999px;
        }
        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(17, 24, 39, 0.55);
            pointer-events: none;
        }
        .bg-login-image {
            display: none;
        }
    </style>
</head>

<body class="auth-bg">

<div class="auth-overlay">
<div class="container" style="max-width: 520px;">
    <div class="card border-0 shadow-lg auth-card">
        <div class="card-body p-0">
            <div class="p-5 auth-form-side">

                <div class="text-center">
                    <h1 class="h4 mb-1 auth-title">Login</h1>
                    <div class="small mb-3 auth-subtitle">Masuk untuk melanjutkan</div>
                </div>

                <?php if (trim((string)$error) !== '') : ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" class="user">
                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="text" name="nama"
                                class="form-control auth-input"
                                placeholder="Username" required>
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="password" name="password"
                                class="form-control auth-input"
                                placeholder="Password" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>

                    <button type="submit" name="login"
                        class="btn btn-primary btn-block auth-btn">
                        Login
                    </button>
                </form>

                <hr>
                <div class="text-center">
                    <a class="small" href="register.php">Buat akun</a>
                </div>

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


