<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!password_verify($old_password, $user['password'])) {
        $error = "Password lama salah.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password baru minimal 6 karakter.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Konfirmasi password tidak cocok.";
    } else {
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hash, $user_id);
        if ($update->execute()) {
            $success = "✅ Password berhasil diubah!";
        } else {
            $error = "❌ Gagal mengubah password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengaturan Akun - Pengurus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Poppins',sans-serif; }
        .card-form { border: none; border-radius: 28px; background: white; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1); padding: 2rem; max-width:600px; margin:1rem auto; }
        .btn-back { border-radius:40px; padding:8px 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="text-muted"><?= date('d F Y') ?></div>
    </div>
    <div class="card-form">
        <h2><i class="fas fa-cog"></i> Pengaturan Akun</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label>Password Lama</label><input type="password" name="old_password" class="form-control" required></div>
            <div class="mb-3"><label>Password Baru</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
            <div class="mb-3"><label>Konfirmasi Password Baru</label><input type="password" name="confirm_password" class="form-control" required></div>
            <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
        </form>
        <div class="text-center mt-3"><a href="profile.php" class="btn btn-link">Kembali ke Profil</a></div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>