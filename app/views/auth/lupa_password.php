<?php
// session_start() sudah di index.php
// Koneksi database
$conn = require __DIR__ . '/../../../config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_siswa = trim($_POST['id_siswa']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id, username FROM users WHERE id_siswa = ? AND email = ?");
    $stmt->bind_param("ss", $id_siswa, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $update->bind_param("ssi", $token, $expires, $user['id']);
        $update->execute();

        // Buat link reset menggunakan route
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/TA/public/reset-password?token=" . $token;
        $subject = "Reset Password - Koperasi Sekolah";
        $message = "Halo " . $user['username'] . ",\n\n";
        $message .= "Klik link berikut untuk mereset password Anda:\n";
        $message .= $reset_link . "\n\n";
        $message .= "Link ini berlaku selama 1 jam.\n\n";
        $message .= "Sistem Informasi Koperasi Sekolah";
        $headers = "From: noreply@koperasisekolah.com\r\n";

        if (mail($email, $subject, $message, $headers)) {
            $success = "✅ Link reset password telah dikirim ke email Anda. Cek inbox/spam.";
        } else {
            // Fallback untuk localhost
            $success = "⚠️ Gagal mengirim email. Gunakan link ini: <a href='$reset_link'>$reset_link</a>";
        }
    } else {
        $error = "❌ ID Siswa dan Email tidak cocok atau tidak terdaftar.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .forgot-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
            animation: fadeInUp 0.6s;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(30px);}
            to { opacity:1; transform:translateY(0);}
        }
        .icon { text-align:center; font-size:3rem; margin-bottom:1rem; color:#0077b6; }
        h3 { text-align:center; font-weight:700; color:#1e2a3e; margin-bottom:0.5rem; }
        .subtitle { text-align:center; color:#5e6f8d; margin-bottom:1.8rem; font-size:0.9rem; }
        .form-label { font-weight:600; color:#1e2a3e; }
        .btn-reset {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            border: none; padding: 12px; border-radius: 40px; font-weight:600; width:100%; margin-top:10px;
        }
        .btn-reset:hover { transform:translateY(-2px); background:#005f8c; }
        .login-link { text-align:center; margin-top:1.5rem; }
        .login-link a { color:#0077b6; text-decoration:none; font-weight:600; }
        .login-link a:hover { text-decoration:underline; }
        footer { text-align:center; margin-top:2rem; font-size:0.7rem; color:#6c757d; }
        .alert { border-radius: 40px; }
    </style>
</head>
<body>
    <div class="forgot-card">
        <div class="icon"><i class="fas fa-key"></i></div>
        <h3>Lupa Password?</h3>
        <div class="subtitle">Masukkan ID Siswa dan Email terdaftar</div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-id-card me-1"></i> ID Siswa</label>
                <input type="text" name="id_siswa" class="form-control form-control-lg" placeholder="Contoh: SISWA001" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="fas fa-envelope me-1"></i> Alamat Email</label>
                <input type="email" name="email" class="form-control form-control-lg" placeholder="contoh: siswa@email.com" required>
            </div>
            <button type="submit" class="btn-reset"><i class="fas fa-paper-plane me-2"></i> Kirim Link Reset</button>
        </form>
        <div class="login-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Kembali ke Login</a>
        </div>
        <footer>Sistem Informasi Koperasi Sekolah</footer>
    </div>
</body>
</html>