<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possible = ['saldo', 'jumlah', 'nominal', 'besar_tabungan'];
    foreach ($possible as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

$total_simpanan = 0;
$saldoColumn = getSaldoColumn($conn, 'tabungan');
if ($saldoColumn) {
    $stmt = $conn->prepare("SELECT $saldoColumn as saldo FROM tabungan WHERE user_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $total_simpanan = $row['saldo'] ?? 0;
        }
    }
}

$total_pinjaman = 0;
$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt2 = $conn->prepare("SELECT SUM(jumlah) as total_pinjaman FROM pinjaman WHERE user_id = ? AND status = 'disetujui'");
    if ($stmt2) {
        $stmt2->bind_param("i", $user_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        $data2 = $res2->fetch_assoc();
        $total_pinjaman = $data2['total_pinjaman'] ?? 0;
    }
}

$saldo_bersih = $total_simpanan - $total_pinjaman;

$riwayat = [];
$q_riwayat = $conn->prepare("SELECT tanggal, jumlah FROM transaksi WHERE user_id = ? ORDER BY tanggal DESC LIMIT 5");
if ($q_riwayat) {
    $q_riwayat->bind_param("i", $user_id);
    $q_riwayat->execute();
    $res_riwayat = $q_riwayat->get_result();
    $riwayat = $res_riwayat->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Saldo - Koperasi Sekolah</title>
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
        .saldo-card {
            background: white;
            border-radius: 28px;
            box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem;
            text-align: center;
            transition: transform 0.2s;
        }
        .saldo-card:hover { transform: translateY(-5px); }
        .saldo-bersih {
            background: linear-gradient(145deg, #0077b6, #00b4d8);
            border-radius: 60px;
            padding: 1.2rem;
            color: white;
            margin-bottom: 2rem;
        }
        .saldo-bersih h4 { font-size: 1rem; opacity:0.9; margin-bottom:0.5rem; }
        .saldo-bersih .value { font-size: 3rem; font-weight:800; letter-spacing:1px; }
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .detail-item {
            background: #f8fafc;
            border-radius: 24px;
            padding: 1rem;
        }
        .detail-label { font-size:0.8rem; text-transform:uppercase; color:#5e6f8d; letter-spacing:1px; }
        .detail-value { font-size:1.8rem; font-weight:700; margin:0; }
        .table-custom { border-radius: 20px; overflow:hidden; }
        .table-custom thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
            .saldo-bersih .value { font-size:2rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-user-graduate me-2"></i> Siswa</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="lihat_saldo.php" class="active"><i class="fas fa-wallet"></i> Lihat Saldo</a>
            <a href="ajukan_pinjaman.php"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="page-title"><i class="fas fa-coins me-2"></i> Informasi Keuangan</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <div class="saldo-card">
                <div class="saldo-bersih">
                    <h4><i class="fas fa-chart-line"></i> SALDO BERSIH</h4>
                    <div class="value">Rp <?php echo number_format($saldo_bersih, 0, ',', '.'); ?></div>
                    <small>Tabungan - Pinjaman Aktif</small>
                </div>

                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-piggy-bank"></i> Total Tabungan</div>
                        <div class="detail-value text-success">Rp <?php echo number_format($total_simpanan, 0, ',', '.'); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label"><i class="fas fa-hand-holding-usd"></i> Pinjaman Aktif</div>
                        <div class="detail-value text-warning">Rp <?php echo number_format($total_pinjaman, 0, ',', '.'); ?></div>
                    </div>
                </div>

                <?php if (count($riwayat) > 0): ?>
                <hr>
                <div class="text-start mt-3">
                    <h5 class="mb-3"><i class="fas fa-history"></i> 5 Transaksi Terakhir</h5>
                    <div class="table-responsive">
                        <table class="table table-custom table-sm">
                            <thead><tr><th>Tanggal</th><th>Jumlah (Rp)</th></tr></thead>
                            <tbody>
                                <?php foreach ($riwayat as $tr): ?>
                                <tr><td><?php echo date('d-m-Y', strtotime($tr['tanggal'])); ?></td><td>Rp <?php echo number_format($tr['jumlah'], 0, ',', '.'); ?></td></tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-2">
                        <a href="riwayat_transaksi.php" class="text-decoration-none">Lihat semua →</a>
                    </div>
                </div>
                <?php else: ?>
                <p class="text-muted mt-3"><i class="fas fa-info-circle"></i> Belum ada transaksi.</p>
                <?php endif; ?>
            </div>
            <footer><i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah | Cek keuanganmu secara realtime</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>