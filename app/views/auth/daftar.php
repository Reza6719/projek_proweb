<?php
session_start(); // diperlukan karena tidak ada front controller
$conn = require __DIR__ . '/../../../config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_siswa = trim($_POST['id_siswa']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $confirm_email = trim($_POST['confirm_email']);
    $password = $_POST['password'];
    $role = 'siswa';

    if (empty($id_siswa) || empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif ($email !== $confirm_email) {
        $error = "Konfirmasi email tidak cocok.";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $cek->bind_param("ss", $username, $email);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = "Username atau email sudah terdaftar.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (id_siswa, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $id_siswa, $username, $email, $hashed, $role);
            if ($stmt->execute()) {
                $success = "✅ Pendaftaran berhasil! Silakan <a href='login.php'>login</a>.";
            } else {
                $error = "❌ Gagal menyimpan data. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - Koperasi Sekolah</title>
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
        .register-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            animation: fadeInUp 0.6s;
        }
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(30px);}
            to { opacity:1; transform:translateY(0);}
        }
        .icon { text-align:center; font-size:3rem; color:#28a745; margin-bottom:1rem; }
        h3 { text-align:center; font-weight:700; color:#1e2a3e; margin-bottom:0.5rem; }
        .subtitle { text-align:center; color:#5e6f8d; margin-bottom:1.5rem; font-size:0.9rem; }
        .form-label { font-weight:600; color:#1e2a3e; }
        .btn-register {
            background: linear-gradient(90deg, #28a745, #34ce57);
            border: none; padding: 12px; border-radius: 40px; font-weight:600; width:100%;
            transition:0.2s;
        }
        .btn-register:hover { transform:translateY(-2px); background:#1e7e34; }
        .login-link { text-align:center; margin-top:1.5rem; }
        .login-link a { color:#28a745; text-decoration:none; font-weight:600; }
        .login-link a:hover { text-decoration:underline; }
        footer { text-align:center; margin-top:2rem; font-size:0.7rem; color:#6c757d; }
        .alert { border-radius: 40px; }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="icon"><i class="fas fa-user-plus"></i></div>
        <h3>Daftar Akun</h3>
        <div class="subtitle">Bergabunglah menjadi anggota koperasi</div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-id-card"></i> ID Siswa</label>
                <input type="text" name="id_siswa" class="form-control" placeholder="Contoh: SISWA001" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-user"></i> Username</label>
                <input type="text" name="username" class="form-control" placeholder="Pilih username" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope"></i> Alamat Email</label>
                <input type="email" name="email" class="form-control" placeholder="contoh: nama@email.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-check-circle"></i> Konfirmasi Email</label>
                <input type="email" name="confirm_email" class="form-control" placeholder="Ketik ulang email" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
            </div>
            <button type="submit" class="btn-register"><i class="fas fa-arrow-right"></i> Daftar</button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login di sini</a>
        </div>
        <footer>Sistem Informasi Koperasi Sekolah</footer>
    </div>
</body>
</html>