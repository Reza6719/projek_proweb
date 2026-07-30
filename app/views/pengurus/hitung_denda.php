<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$success = "";
$error = "";
$result = null;
$total_denda_keseluruhan = 0;

function hitungDenda(string $tanggal_pinjam, int $denda_per_hari = 5000, int $jatuh_tempo_hari = 30): int {
    $tgl_pinjam = new DateTime($tanggal_pinjam);
    $tgl_jatuh_tempo = clone $tgl_pinjam;
    $tgl_jatuh_tempo->modify("+$jatuh_tempo_hari days");
    $sekarang = new DateTime();
    
    if ($sekarang <= $tgl_jatuh_tempo) {
        return 0;
    }
    
    $selisih_hari = $tgl_jatuh_tempo->diff($sekarang)->days;
    return $selisih_hari * $denda_per_hari;
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
$pinjamanTableExists = ($tableCheck && $tableCheck->num_rows > 0);

if ($pinjamanTableExists) {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_denda'])) {
        $query = "SELECT id, tanggal FROM pinjaman WHERE status = 'disetujui'";
        $pinjaman_list = $conn->query($query);
        
        if ($pinjaman_list && $pinjaman_list->num_rows > 0) {
            $updated = 0;
            while ($row = $pinjaman_list->fetch_assoc()) {
                $id = $row['id'];
                $tanggal = $row['tanggal'];
                $denda_baru = hitungDenda($tanggal);
                
                $stmt = $conn->prepare("UPDATE pinjaman SET denda = ? WHERE id = ?");
                $stmt->bind_param("ii", $denda_baru, $id);
                if ($stmt->execute()) {
                    $updated++;
                }
            }
            $success = "✅ Denda berhasil dihitung dan diperbarui untuk $updated pinjaman.";
        } else {
            $error = "❌ Tidak ada pinjaman aktif yang perlu dihitung dendanya.";
        }
    }
    
    $result = $conn->query("SELECT p.id, u.username, p.jumlah, p.tanggal, p.status, p.denda 
                            FROM pinjaman p 
                            JOIN users u ON p.user_id = u.id 
                            WHERE p.status = 'disetujui' 
                            ORDER BY p.tanggal DESC");
    
    if ($result && $result->num_rows > 0) {
        $tmp = $conn->query("SELECT SUM(denda) AS total FROM pinjaman WHERE status = 'disetujui'");
        if ($tmp && $tmp->num_rows) {
            $total_denda_keseluruhan = $tmp->fetch_assoc()['total'] ?? 0;
        }
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
    <title>Hitung Denda - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f2f5; font-family:'Poppins','Segoe UI',sans-serif; }
        .sidebar { background:linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%); color:white; height:100vh; position:sticky; top:0; box-shadow:2px 0 12px rgba(0,0,0,0.05); }
        .sidebar h4 { font-weight:600; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:12px; margin-bottom:20px; }
        .sidebar a { display:flex; align-items:center; gap:12px; color:#e0e7ff; text-decoration:none; padding:12px 16px; margin:6px 0; border-radius:12px; transition:all 0.2s ease; font-weight:500; }
        .sidebar a i { width:24px; text-align:center; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); color:white; transform:translateX(5px); }
        .sidebar a.active { background:#0077b6; color:white; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
        .content { padding:2rem 1.5rem; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .stats-card { border:none; border-radius:20px; background:white; padding:1.2rem; box-shadow:0 4px 12px rgba(0,0,0,0.03); transition:transform 0.2s; }
        .stats-card:hover { transform:translateY(-3px); }
        .table-custom { border-radius:20px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.03); }
        .table-custom thead th { background-color:#f8fafc; color:#1e2a3e; font-weight:600; border-bottom:2px solid #e2e8f0; padding:14px 12px; }
        .table-custom tbody tr:hover { background-color:#fef9e3; }
        .badge-status { font-size:0.75rem; padding:6px 12px; border-radius:40px; font-weight:500; }
        .btn-update { background:#ffc107; color:#1e2a3e; border:none; border-radius:40px; padding:10px 24px; font-weight:600; transition:0.2s; }
        .btn-update:hover { background:#e0a800; transform:translateY(-2px); }
        footer { text-align:center; margin-top:3rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php" class="active"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-calculator me-2"></i> Hitung Denda Keterlambatan</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('l, d F Y'); ?></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if (!$pinjamanTableExists): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> Tabel 'pinjaman' belum tersedia. Silakan hubungi administrator.</div>
            <?php else: ?>
                <div class="row g-3 mb-5">
                    <div class="col-md-6 col-lg-4">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><div class="text-muted small">Total Denda Keseluruhan</div><div class="fs-2 fw-bold text-danger">Rp <?php echo number_format($total_denda_keseluruhan, 0, ',', '.'); ?></div></div>
                                <i class="fas fa-money-bill-wave fa-2x text-danger opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="stats-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><div class="text-muted small">Aturan Denda</div><div class="fs-5 fw-semibold">Rp 5.000 / hari</div><small>Setelah 30 hari dari pinjaman</small></div>
                                <i class="fas fa-clock fa-2x text-warning opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <form method="POST" class="d-flex justify-content-end align-items-center h-100">
                            <button type="submit" name="update_denda" class="btn-update"><i class="fas fa-sync-alt me-2"></i> Hitung & Update Denda</button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i> Daftar Pinjaman Aktif & Denda</h5>
                        <p class="text-muted small mt-1">Klik tombol di atas untuk menghitung denda terbaru</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle">
                                <thead>
                                    <tr><th>Nama Siswa</th><th>Jumlah Pinjaman</th><th>Tanggal Pinjaman</th><th>Jatuh Tempo</th><th>Hari Terlambat</th><th>Denda (Rp)</th></tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && $result->num_rows > 0): ?>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <?php
                                                $tgl_pinjam = new DateTime($row['tanggal']);
                                                $jatuh_tempo = clone $tgl_pinjam;
                                                $jatuh_tempo->modify('+30 days');
                                                $sekarang = new DateTime();
                                                $hari_terlambat = ($sekarang > $jatuh_tempo) ? $jatuh_tempo->diff($sekarang)->days : 0;
                                                $denda_final = $row['denda'] ?? ($hari_terlambat * 5000);
                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?php echo htmlspecialchars($row['username']); ?></td>
                                                <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                                <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                                <td><?php echo $jatuh_tempo->format('d-m-Y'); ?></td>
                                                <td><?php if ($hari_terlambat > 0): ?><span class="badge-status bg-danger bg-opacity-25 text-danger"><?php echo $hari_terlambat; ?> hari</span><?php else: ?><span class="badge-status bg-success bg-opacity-25 text-success">Tepat waktu</span><?php endif; ?></td>
                                                <td><?php if ($denda_final > 0): ?><span class="fw-bold text-danger">Rp <?php echo number_format($denda_final, 0, ',', '.'); ?></span><?php else: ?><span class="text-muted">Rp 0</span><?php endif; ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>Tidak ada pinjaman aktif yang perlu dihitung dendanya.<?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            <footer><i class="fas fa-clock"></i> Sistem Informasi Koperasi Sekolah | Denda keterlambatan dihitung otomatis</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>