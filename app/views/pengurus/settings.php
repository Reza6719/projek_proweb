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
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f2f5; font-family:'Poppins','Segoe UI',sans-serif; }
        .sidebar {
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            color: white;
            height: 100vh;
            position: sticky;
            top: 0;
            box-shadow: 2px 0 12px rgba(0,0,0,0.05);
        }
        .sidebar h4 { font-weight:600; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:12px; margin-bottom:20px; }
        .sidebar a {
            display: flex; align-items: center; gap: 12px; color: #e0e7ff; text-decoration: none;
            padding: 12px 16px; margin: 6px 0; border-radius: 12px; transition: all 0.2s ease; font-weight:500;
        }
        .sidebar a i { width: 24px; text-align:center; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); color:white; transform:translateX(5px); }
        .sidebar a.active { background:#0077b6; color:white; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
        .content { padding: 2rem 1.5rem; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .card-form {
            border: none; border-radius: 28px; background: white;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem; max-width:600px; margin:0 auto;
        }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <!-- SIDEBAR -->
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="rekap_transaksi.php"><i class="fas fa-chart-line"></i> Rekap Transaksi</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php" class="active"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <!-- KONTEN -->
        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-cog me-2"></i> Pengaturan Akun</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?></div>
            </div>

            <div class="card-form">
                <h4 class="mb-4"><i class="fas fa-key me-2"></i> Ubah Password</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Password Lama</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                </form>

                <div class="text-center mt-3">
                    <a href="profile.php" class="btn btn-link">Kembali ke Profil</a>
                </div>
            </div>

            <footer><i class="fas fa-cog"></i> Sistem Informasi Koperasi Sekolah | Pengaturan Akun</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>