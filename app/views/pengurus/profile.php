<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

$query = $conn->prepare("SELECT username, id_siswa, nama_lengkap, email, phone, alamat, foto FROM users WHERE id = ?");
$query->bind_param("i", $user_id);
$query->execute();
$user = $query->get_result()->fetch_assoc();

$foto_url = '';
if (!empty($user['foto'])) {
    $file_path = $_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/' . $user['foto'];
    if (file_exists($file_path)) {
        $foto_url = '../../uploads/' . $user['foto'] . '?v=' . time();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Pengurus</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { background:#f0f2f5; font-family:'Poppins',sans-serif; }
        .profile-card { background:white; border-radius:28px; padding:2rem; box-shadow:0 20px 35px rgba(0,0,0,0.1); max-width:800px; margin:2rem auto; }
        .profile-avatar { width:150px; height:150px; border-radius:50%; background:#e9ecef; display:flex; align-items:center; justify-content:center; overflow:hidden; margin:0 auto 1rem; border:4px solid #0077b6; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; }
        .profile-avatar i { font-size:5rem; color:#6c757d; }
        .btn-action { border-radius:40px; padding:10px 24px; font-weight:600; }
        .btn-back { border-radius:40px; padding:8px 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mt-4">
        <a href="dashboard.php" class="btn btn-outline-secondary btn-back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
        <div class="text-muted"><?= date('d F Y') ?></div>
    </div>
    <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <div class="alert alert-success mt-3">✅ Profil berhasil diperbarui!</div>
    <?php endif; ?>
    <div class="profile-card">
        <div class="profile-avatar">
            <?php if ($foto_url): ?>
                <img src="<?= $foto_url ?>" alt="Foto Profil">
            <?php else: ?>
                <i class="fas fa-user"></i>
            <?php endif; ?>
        </div>
        <div class="row mt-3">
            <div class="col-md-6"><strong>Nama Lengkap</strong><br><?= htmlspecialchars($user['nama_lengkap'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Username</strong><br><?= htmlspecialchars($user['username']) ?></div>
            <div class="col-md-6"><strong>ID Siswa / NIS</strong><br><?= htmlspecialchars($user['id_siswa'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Email</strong><br><?= htmlspecialchars($user['email'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Nomor Telepon</strong><br><?= htmlspecialchars($user['phone'] ?? '-') ?></div>
            <div class="col-md-12"><strong>Alamat</strong><br><?= htmlspecialchars($user['alamat'] ?? '-') ?></div>
        </div>
        <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <a href="edit_profile.php" class="btn btn-primary btn-action"><i class="fas fa-edit"></i> Edit Profil</a>
            <a href="settings.php" class="btn btn-secondary btn-action"><i class="fas fa-cog"></i> Pengaturan Akun</a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>