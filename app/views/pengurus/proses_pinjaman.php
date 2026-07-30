<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$success = "";
$error = "";
$stats = ['pending' => 0, 'disetujui' => 0, 'ditolak' => 0];
$result = null;

$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
$pinjamanTableExists = ($tableCheck && $tableCheck->num_rows > 0);

if ($pinjamanTableExists) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pinjaman_id'], $_POST['aksi'])) {
        $pinjaman_id = (int)$_POST['pinjaman_id'];
        $aksi = $_POST['aksi'];

        if ($aksi === 'disetujui' || $aksi === 'ditolak') {
            $stmt = $conn->prepare("UPDATE pinjaman SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $aksi, $pinjaman_id);
            if ($stmt->execute()) {
                $success = "✅ Pinjaman berhasil diperbarui menjadi: " . ucfirst($aksi);
            } else {
                $error = "❌ Terjadi kesalahan: " . $conn->error;
            }
        } else {
            $error = "❌ Aksi tidak valid!";
        }
    }

    $result = $conn->query("SELECT p.id, u.username, p.jumlah, p.tanggal, p.status 
                            FROM pinjaman p 
                            JOIN users u ON p.user_id = u.id 
                            ORDER BY p.tanggal DESC");

    if ($result && $result->num_rows > 0) {
        $result->data_seek(0);
        while ($row = $result->fetch_assoc()) {
            if (isset($stats[$row['status']])) $stats[$row['status']]++;
        }
        $result->data_seek(0);
    }
} else {
    $error = "⚠️ Tabel 'pinjaman' belum tersedia. Silakan hubungi administrator.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proses Pinjaman - Koperasi Sekolah</title>
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
        .stats-card {
            border: none; border-radius: 20px; background: white; padding: 1rem; text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: transform 0.2s;
        }
        .stats-card:hover { transform: translateY(-3px); }
        .stats-number { font-size: 2rem; font-weight: 800; }
        .table-custom { border-radius: 20px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.03); }
        .table-custom thead th { background-color: #f8fafc; color: #1e2a3e; font-weight: 600; border-bottom: 2px solid #e2e8f0; padding: 14px 12px; }
        .table-custom tbody tr:hover { background-color: #fef9e3; }
        .badge-status { font-size: 0.75rem; padding: 6px 12px; border-radius: 40px; font-weight: 500; }
        .btn-action { border-radius: 30px; padding: 5px 16px; font-weight: 500; margin: 0 4px; transition: 0.2s; border: none; }
        .btn-approve { background: #28a745; color: white; }
        .btn-approve:hover { background: #218838; transform: translateY(-2px); }
        .btn-reject { background: #dc3545; color: white; }
        .btn-reject:hover { background: #c82333; transform: translateY(-2px); }
        footer { text-align: center; margin-top: 3rem; color: #7f8c8d; font-size: 0.8rem; }
        @media (max-width: 768px) { .sidebar { height: auto; position: relative; } .content { padding: 1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php" class="active"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-hand-holding-usd me-2"></i> Proses Pinjaman Siswa</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('l, d F Y'); ?></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if ($pinjamanTableExists): ?>
                <div class="row g-3 mb-5">
                    <div class="col-md-4"><div class="stats-card"><div class="text-warning mb-2"><i class="fas fa-clock fa-2x"></i></div><div class="stats-number text-warning"><?php echo $stats['pending']; ?></div><div class="text-muted">Menunggu Persetujuan</div></div></div>
                    <div class="col-md-4"><div class="stats-card"><div class="text-success mb-2"><i class="fas fa-check-circle fa-2x"></i></div><div class="stats-number text-success"><?php echo $stats['disetujui']; ?></div><div class="text-muted">Disetujui</div></div></div>
                    <div class="col-md-4"><div class="stats-card"><div class="text-danger mb-2"><i class="fas fa-times-circle fa-2x"></i></div><div class="stats-number text-danger"><?php echo $stats['ditolak']; ?></div><div class="text-muted">Ditolak</div></div></div>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4"><h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i> Daftar Pengajuan Pinjaman</h5><p class="text-muted small mt-1">Klik setujui/tolak untuk mengubah status</p></div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle">
                                <thead><tr><th>Nama Siswa</th><th>Jumlah Pinjaman</th><th>Tanggal Pengajuan</th><th>Status</th><th>Aksi</th></tr></thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                                <td><?php
                                                    $status = $row['status'];
                                                    if ($status == 'pending'): ?>
                                                        <span class="badge-status bg-warning bg-opacity-25 text-warning"><i class="fas fa-hourglass-half me-1"></i> Pending</span>
                                                    <?php elseif ($status == 'disetujui'): ?>
                                                        <span class="badge-status bg-success bg-opacity-25 text-success"><i class="fas fa-check me-1"></i> Disetujui</span>
                                                    <?php else: ?>
                                                        <span class="badge-status bg-danger bg-opacity-25 text-danger"><i class="fas fa-ban me-1"></i> Ditolak</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($status == 'pending'): ?>
                                                        <form method="POST" style="display:inline-block;"><input type="hidden" name="pinjaman_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="aksi" value="disetujui"><button type="submit" class="btn-action btn-approve"><i class="fas fa-thumbs-up"></i> Setujui</button></form>
                                                        <form method="POST" style="display:inline-block;"><input type="hidden" name="pinjaman_id" value="<?php echo $row['id']; ?>"><input type="hidden" name="aksi" value="ditolak"><button type="submit" class="btn-action btn-reject"><i class="fas fa-thumbs-down"></i> Tolak</button></form>
                                                    <?php else: ?>
                                                        <span class="text-muted"><i class="fas fa-lock"></i> Tidak ada aksi</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i> Belum ada pengajuan pinjaman</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> Modul pinjaman belum tersedia. Silakan hubungi administrator untuk membuat tabel pinjaman.</div>
            <?php endif; ?>
            <footer><i class="fas fa-file-invoice-dollar"></i> Sistem Informasi Koperasi Sekolah | Kelola Pinjaman dengan Mudah</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>