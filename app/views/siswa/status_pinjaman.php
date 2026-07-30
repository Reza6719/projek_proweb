<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

$rows = [];
$total_pinjaman = 0;
$total_denda = 0;

$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
$pinjamanExists = ($tableCheck && $tableCheck->num_rows > 0);

if ($pinjamanExists) {
    $colCheck = $conn->query("SHOW COLUMNS FROM pinjaman LIKE 'denda'");
    $hasDenda = ($colCheck && $colCheck->num_rows > 0);
    
    $sql = "SELECT tanggal, jumlah, status";
    if ($hasDenda) $sql .= ", denda";
    $sql .= " FROM pinjaman WHERE user_id = ? ORDER BY tanggal DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        if (strtolower($row['status']) == 'disetujui') {
            $total_pinjaman += $row['jumlah'];
        }
        if ($hasDenda) {
            $total_denda += ($row['denda'] ?? 0);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pinjaman - Koperasi Sekolah</title>
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
        .pinjaman-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem;
            transition: transform 0.2s;
        }
        .pinjaman-card:hover { transform: translateY(-3px); }
        .summary-box {
            background: linear-gradient(145deg, #1e2a3e, #0f172a);
            border-radius: 24px;
            padding: 1rem;
            text-align: center;
            color: white;
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .summary-box h5 { font-size:0.9rem; text-transform:uppercase; letter-spacing:1px; opacity:0.8; }
        .summary-box p { font-size:1.8rem; font-weight:800; margin:0; }
        .table-custom { border-radius: 20px; overflow: hidden; }
        .table-custom thead th { background: #f8fafc; color: #1e2a3e; font-weight: 600; border-bottom: 2px solid #e2e8f0; }
        .table-custom tbody tr:hover { background: #fef9e3; }
        .status-badge { padding: 6px 14px; border-radius: 40px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .status-pending { background:#ffc107; color:#1e2a3e; }
        .status-disetujui { background:#28a745; color:white; }
        .status-ditolak { background:#dc3545; color:white; }
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
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php" class="active"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-chart-line me-2"></i> Status Pinjaman</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <div class="pinjaman-card">
                <?php if (!$pinjamanExists): ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> Modul pinjaman belum tersedia. Silakan hubungi pengurus.</div>
                <?php else: ?>
                    <div class="row g-3 mb-5">
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h5><i class="fas fa-hand-holding-usd me-1"></i> Total Pinjaman Disetujui</h5>
                                <p>Rp <?php echo number_format($total_pinjaman, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-box">
                                <h5><i class="fas fa-exclamation-triangle me-1"></i> Total Denda</h5>
                                <p>Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-calendar"></i> Tanggal</th>
                                    <th><i class="fas fa-money-bill"></i> Jumlah</th>
                                    <th><i class="fas fa-tag"></i> Status</th>
                                    <th><i class="fas fa-coins"></i> Denda</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($rows) > 0): ?>
                                    <?php foreach ($rows as $row): ?>
                                        <tr>
                                            <td><?php echo date('d-m-Y', strtotime($row['tanggal'])); ?></td>
                                            <td>Rp <?php echo number_format($row['jumlah'], 0, ',', '.'); ?></td>
                                            <td>
                                                <?php 
                                                $status = strtolower($row['status']);
                                                if ($status == 'pending') {
                                                    echo "<span class='status-badge status-pending'><i class='fas fa-clock me-1'></i> Pending</span>";
                                                } elseif ($status == 'disetujui') {
                                                    echo "<span class='status-badge status-disetujui'><i class='fas fa-check me-1'></i> Disetujui</span>";
                                                } elseif ($status == 'ditolak') {
                                                    echo "<span class='status-badge status-ditolak'><i class='fas fa-ban me-1'></i> Ditolak</span>";
                                                } else {
                                                    echo ucfirst($status);
                                                }
                                                ?>
                                            </td>
                                            <td>Rp <?php echo number_format($row['denda'] ?? 0, 0, ',', '.'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4"><i class="fas fa-inbox fa-2x d-block mb-2 text-muted"></i>Belum ada pengajuan pinjaman</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <footer><i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah | Pantau status pinjamanmu</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>