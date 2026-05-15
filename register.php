<?php
include "config.php";

$error = "";
$success = "";

if (isset($_POST['register'])) {

    $nama     = $_POST['nama'];
    $email    = $_POST['email'];
    $password = $_POST['password'];
    $repeat   = $_POST['repeat'];

    // validasi
    if ($password !== $repeat) {
        $error = "Password tidak sama!";
    } else {

        // hash password
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // cek email sudah ada
        $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // insert
            $insert = mysqli_query($conn, "INSERT INTO users (nama, email, password) 
                                          VALUES ('$nama','$email','$hash')");
            if ($insert) {
                $success = "Register berhasil! Silakan login.";
            } else {
                $error = "Gagal register!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Register</title>

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
        .bg-register-image {
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
                    <h1 class="h4 mb-2 auth-title">Buat Akun</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" class="user">
                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="text" name="nama" class="form-control auth-input" placeholder="Nama" required>
                            <span class="input-icon"><i class="fas fa-user"></i></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="email" name="email" class="form-control auth-input" placeholder="Email" required>
                            <span class="input-icon"><i class="fas fa-envelope"></i></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="password" name="password" class="form-control auth-input" placeholder="Password" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>

                    <div class="form-group">
                        <div style="position: relative;">
                            <input type="password" name="repeat" class="form-control auth-input" placeholder="Ulangi Password" required>
                            <span class="input-icon"><i class="fas fa-lock"></i></span>
                        </div>
                    </div>

                    <button type="submit" name="register" class="btn btn-primary btn-block auth-btn">
                        Register
                    </button>
                </form>

                <hr>
                <div class="text-center">
                    <a class="small" href="login.php">Sudah punya akun? Login</a>
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