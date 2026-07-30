<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data user saat ini
$query = $conn->prepare("SELECT username, email, phone, nama_lengkap, alamat FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();
$username = $user['username'];
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$nama_lengkap = $user['nama_lengkap'] ?? '';
$alamat = $user['alamat'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $alamat = trim($_POST['alamat']);

    if (empty($nama_lengkap)) {
        $error = "Nama lengkap harus diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid.";
    } elseif (!preg_match('/^[0-9]{10,13}$/', $phone)) {
        $error = "Nomor telepon harus 10-13 digit angka.";
    } else {
        $cek = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $cek->bind_param("si", $email, $user_id);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = "Email sudah digunakan oleh pengguna lain.";
        } else {
            $update = $conn->prepare("UPDATE users SET nama_lengkap = ?, email = ?, phone = ?, alamat = ? WHERE id = ?");
            $update->bind_param("ssssi", $nama_lengkap, $email, $phone, $alamat, $user_id);
            if ($update->execute()) {
                // Redirect ke halaman profil setelah berhasil
                header("Location: profile.php?success=1");
                exit;
            } else {
                $error = "❌ Gagal memperbarui profil: " . $conn->error;
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
    <title>Edit Profil - Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Poppins',sans-serif; }
        .sidebar { background:linear-gradient(135deg,#0f2b3d,#1a4a6f); color:white; height:100vh; position:sticky; top:0; }
        .sidebar a { display:flex; align-items:center; gap:12px; color:#e0e7ff; padding:12px 16px; margin:6px 0; border-radius:12px; text-decoration:none; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); }
        .content { padding:2rem; }
        .card-form { border: none; border-radius: 28px; background: white; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1); padding: 2rem; }
        @media (max-width:768px) { .sidebar { height:auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-user-graduate"></i> Siswa</h4>
            <a href="dashboard.php">Dashboard</a>
            <a href="lihat_saldo.php">Lihat Saldo</a>
            <a href="ajukan_pinjaman.php">Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php">Riwayat Transaksi</a>
            <a href="status_pinjaman.php">Status Pinjaman</a>
            <a href="profile.php">Profil</a>
            <a href="edit_profile.php" class="active">Edit Profil</a>
            <a href="../auth/logout.php">Logout</a>
        </nav>
        <main class="col-md-10 content">
            <h2><i class="fas fa-user-edit"></i> Edit Profil</h2>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-form">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($username) ?>" disabled>
                                <small class="text-muted">Username tidak dapat diubah.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" value="<?= htmlspecialchars($nama_lengkap) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Nomor Telepon</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($phone) ?>" placeholder="081234567890" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alamat</label>
                                <textarea name="alamat" class="form-control" rows="3"><?= htmlspecialchars($alamat) ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan Perubahan</button>
                        </form>
                        <div class="text-center mt-3">
                            <a href="profile.php" class="btn btn-link">Kembali ke Profil</a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>