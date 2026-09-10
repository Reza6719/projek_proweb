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
        .profile-card { background:white; border-radius:28px; padding:2rem; box-shadow:0 20px 35px rgba(0,0,0,0.1); max-width:800px; margin:0 auto; }
        .profile-avatar { width:150px; height:150px; border-radius:50%; background:#e9ecef; display:flex; align-items:center; justify-content:center; overflow:hidden; margin:0 auto 1rem; border:4px solid #0077b6; }
        .profile-avatar img { width:100%; height:100%; object-fit:cover; }
        .profile-avatar i { font-size:5rem; color:#6c757d; }
        .btn-action { border-radius:40px; padding:10px 24px; font-weight:600; }
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
            <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <!-- KONTEN -->
        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-user-circle me-2"></i> Profil Pengurus</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?= date('d F Y') ?></div>
            </div>

            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> Profil berhasil diperbarui!</div>
            <?php endif; ?>

            <div class="profile-card">
                <div class="profile-avatar">
                    <?php if ($foto_url): ?>
                        <img src="<?= $foto_url ?>" alt="Foto Profil">
                    <?php else: ?>
                        <i class="fas fa-user"></i>
                    <?php endif; ?>
                </div>
                <div class="row mt-3 g-3">
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

            <footer><i class="fas fa-user-circle"></i> Sistem Informasi Koperasi Sekolah | Profil Pengurus</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>