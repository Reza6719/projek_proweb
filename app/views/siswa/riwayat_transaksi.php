<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

$tableCheck = $conn->query("SHOW TABLES LIKE 'transaksi'");
$transaksiExists = ($tableCheck && $tableCheck->num_rows > 0);

$rows = [];
$total_transaksi = 0;
$total_simpanan = 0;
$total_pinjaman = 0;

if ($transaksiExists) {
    $stmt = $conn->prepare("SELECT tanggal, jenis, jumlah FROM transaksi WHERE user_id = ? ORDER BY tanggal DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $total_transaksi += $row['jumlah'];
        if ($row['jenis'] == 'simpanan') $total_simpanan += $row['jumlah'];
        if ($row['jenis'] == 'pinjaman') $total_pinjaman += $row['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - Koperasi Sekolah</title>
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
        .transaksi-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem;
            transition: transform 0.2s;
        }
        .transaksi-card:hover { transform: translateY(-5px); }
        .summary-box {
            background: linear-gradient(145deg, #0f2b3d, #1a4a6f);
            border-radius: 20px;
            padding: 1rem;
            text-align: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .summary-box h5 { font-size:0.9rem; opacity:0.9; margin-bottom:0.5rem; }
        .summary-box p { font-size:1.5rem; font-weight:700; margin:0; }
        .badge-jenis {
            padding:6px 14px;
            border-radius:40px;
            font-size:0.75rem;
            font-weight:600;
            display:inline-block;
        }
        .badge-simpanan { background:#d4edda; color:#155724; }
        .badge-pinjaman { background:#fff3cd; color:#856404; }
        .table-custom { border-radius:20px; overflow:hidden; }
        .table-custom thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#1e2a3e; }
        .table-custom tbody tr:hover { background:#fef9e3; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
            .summary-box p { font-size:1.2rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-user-graduate me-2"></i> Siswa</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="lihat_saldo.php"><i class="fas fa-wallet"></i> Lihat Saldo</a>
            <a href="ajukan_pinjaman.php"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php" class="active"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-chart-line me-2"></i> Riwayat Transaksi</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <?php if (!$transaksiExists): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> Modul transaksi belum tersedia.</div>
            <?php else: ?>
                <div class="transaksi-card">
                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h5><i class="fas fa-chart-line"></i> Total Transaksi</h5>
                                <p>Rp <?php echo number_format($total_transaksi, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h5><i class="fas fa-piggy-bank"></i> Total Simpanan</h5>
                                <p>Rp <?php echo number_format($total_simpanan, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="summary-box">
                                <h5><i class="fas fa-hand-holding-usd"></i> Total Pinjaman</h5>
                                <p>Rp <?php echo number_format($total_pinjaman, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr><th>Tanggal</th><th>Jenis</th><th>Jumlah (Rp)</th></tr>
                            </thead>
                            <tbody>
                                <?php if (count($rows) > 0): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td>
                                                <?php if ($row['jenis'] == 'simpanan'): ?>
                                                    <span class="badge-jenis badge-simpanan"><i class="fas fa-arrow-up me-1"></i> Simpanan</span>
                                                <?php elseif ($row['jenis'] == 'pinjaman'): ?>
                                                    <span class="badge-jenis badge-pinjaman"><i class="fas fa-arrow-down me-1"></i> Pinjaman</span>
                                                <?php else: ?>
                                                    <span class="badge-jenis badge-secondary"><?php echo ucfirst($row['jenis']); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada transaksi</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
            <footer><i class="fas fa-history"></i> Sistem Informasi Koperasi Sekolah | Semua riwayat transaksi Anda</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>