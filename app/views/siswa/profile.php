<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

$query = $conn->prepare("SELECT username, id_siswa, nama_lengkap, email, phone, alamat FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Poppins',sans-serif; }
        .sidebar { background:linear-gradient(135deg,#0f2b3d,#1a4a6f); color:white; height:100vh; position:sticky; top:0; }
        .sidebar a { display:flex; align-items:center; gap:12px; color:#e0e7ff; padding:12px 16px; margin:6px 0; border-radius:12px; text-decoration:none; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); }
        .content { padding:2rem; }
        .profile-card { background:white; border-radius:28px; padding:2rem; box-shadow:0 20px 35px rgba(0,0,0,0.1); }
        .profile-avatar { width:100px; height:100px; border-radius:50%; background:#0077b6; display:flex; align-items:center; justify-content:center; font-size:3rem; color:white; margin:0 auto 1rem; }
        @media (max-width:768px) { .sidebar { height:auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-user-graduate"></i> Siswa</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="lihat_saldo.php"><i class="fas fa-wallet"></i> Lihat Saldo</a>
            <a href="ajukan_pinjaman.php"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-user-circle"></i> Profil Saya</h2>
                <div class="text-muted"><?= date('d F Y') ?></div>
            </div>
            <div class="profile-card">
                <div class="profile-avatar"><i class="fas fa-user"></i></div>
                <div class="row">
                    <div class="col-md-6"><strong>Nama Lengkap</strong><br><?= htmlspecialchars($user['nama_lengkap'] ?? '-') ?></div>
                    <div class="col-md-6"><strong>Username</strong><br><?= htmlspecialchars($user['username']) ?></div>
                    <div class="col-md-6"><strong>ID Siswa / NIS</strong><br><?= htmlspecialchars($user['id_siswa'] ?? '-') ?></div>
                    <div class="col-md-6"><strong>Email</strong><br><?= htmlspecialchars($user['email'] ?? '-') ?></div>
                    <div class="col-md-6"><strong>Nomor Telepon</strong><br><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
                    <div class="col-md-12"><strong>Alamat</strong><br><?= htmlspecialchars($user['alamat'] ?? '-') ?></div>
                </div>
                <div class="mt-4">
                    <a href="edit_profile.php" class="btn btn-primary"><i class="fas fa-edit"></i> Edit Profil</a>
                </div>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>