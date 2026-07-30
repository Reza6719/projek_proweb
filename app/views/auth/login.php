<?php
session_start(); // HARUS ADA karena tidak menggunakan front controller
$conn = require __DIR__ . '/../../../config/database.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] == 'admin') {
            header("Location: ../admin/dashboard.php");
            exit;
        } elseif ($user['role'] == 'pengurus') {
            header("Location: ../pengurus/dashboard.php");
            exit;
        } elseif ($user['role'] == 'siswa') {
            header("Location: ../siswa/dashboard.php");
            exit;
        }
    } else {
        $error = "⚠️ Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .login-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 460px;
            animation: fadeInUp 0.6s ease-out;
            transition: transform 0.3s;
        }
        .login-card:hover { transform: translateY(-5px); }
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(30px);}
            to { opacity:1; transform:translateY(0);}
        }
        .icon-koperasi {
            width: 70px;
            height: 70px;
            background: linear-gradient(145deg, #0077b6, #00b4d8);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            box-shadow: 0 8px 20px rgba(0,119,182,0.3);
        }
        .icon-koperasi i { font-size: 2.5rem; color: white; }
        h3 { font-weight:700; color:#1e2a3e; text-align:center; margin-bottom:0.5rem; }
        .subtitle { text-align:center; color:#5e6f8d; font-size:0.85rem; margin-bottom:1.8rem; }
        .form-label { font-weight:600; color:#1e2a3e; margin-bottom:0.5rem; }
        .input-group-custom input {
            border: 1px solid #e2e8f0;
            padding: 12px 16px;
            font-size: 0.95rem;
            border-radius: 40px;
            background: #f8fafc;
            width: 100%;
            transition: 0.2s;
        }
        .input-group-custom input:focus {
            background: white;
            border-color: #0077b6;
            box-shadow: 0 0 0 3px rgba(0,119,182,0.2);
            outline: none;
        }
        .btn-login {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            border: none;
            padding: 12px;
            border-radius: 40px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            color: white;
            width: 100%;
            margin-top: 10px;
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #005f8c, #0096c7);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,119,182,0.4);
        }
        .register-link {
            color: #0077b6;
            text-decoration: none;
            font-weight: 600;
        }
        .register-link:hover { color:#005f8c; text-decoration:underline; }
        .alert-danger { border-radius: 40px; font-size:0.85rem; background:#fee2e2; border:none; color:#dc2626; }
        footer { text-align:center; margin-top:1.5rem; font-size:0.7rem; color:#6c757d; }
        @media (max-width:480px) {
            .login-card { padding:1.8rem; }
            .icon-koperasi { width:55px; height:55px; }
            .icon-koperasi i { font-size:2rem; }
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="icon-koperasi">
            <i class="fas fa-hand-holding-usd"></i>
        </div>
        <h3>Selamat Datang</h3>
        <div class="subtitle">Silakan login ke sistem koperasi sekolah</div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger text-center"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label"><i class="fas fa-user me-1"></i> Username</label>
                <div class="input-group-custom">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Masukkan username" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label"><i class="fas fa-lock me-1"></i> Password</label>
                <div class="input-group-custom">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label" for="remember">Ingat saya</label>
                </div>
                <a href="lupa_password.php" class="register-link" style="font-size:0.8rem;">Lupa password?</a>
            </div>
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt me-2"></i> Login</button>
        </form>

        <p class="text-center mt-4 mb-0">
            Belum punya akun? <a href="daftar.php" class="register-link">Daftar di sini</a>
        </p>
        <footer>
            <i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah
        </footer>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>