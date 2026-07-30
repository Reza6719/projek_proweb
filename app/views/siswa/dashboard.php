<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak! Halaman ini hanya untuk Siswa.");
}

$user_id = $_SESSION['user_id'];

// Ambil data user (username, id_siswa, dan nama_lengkap)
$query_user = $conn->prepare("SELECT username, id_siswa, nama_lengkap FROM users WHERE id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$user_data = $query_user->get_result()->fetch_assoc();
$username = $user_data['username'];
$id_siswa = $user_data['id_siswa'];
$nama_lengkap = $user_data['nama_lengkap'] ?? $username;

$saldo = 0;
$total_pinjaman_aktif = 0;
$total_denda = 0;

function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possibleColumns = ['saldo', 'jumlah', 'nominal', 'besar_tabungan'];
    foreach ($possibleColumns as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

$saldoColumn = getSaldoColumn($conn, 'tabungan');
if ($saldoColumn) {
    $q_saldo = $conn->prepare("SELECT $saldoColumn as saldo FROM tabungan WHERE user_id = ?");
    if ($q_saldo) {
        $q_saldo->bind_param("i", $user_id);
        $q_saldo->execute();
        $res_saldo = $q_saldo->get_result();
        if ($row = $res_saldo->fetch_assoc()) {
            $saldo = $row['saldo'] ?? 0;
        }
    }
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
$pinjamanExists = ($tableCheck && $tableCheck->num_rows > 0);

if ($pinjamanExists) {
    $q_pinjaman = $conn->prepare("SELECT jumlah, denda FROM pinjaman WHERE user_id = ? AND status = 'disetujui'");
    if ($q_pinjaman) {
        $q_pinjaman->bind_param("i", $user_id);
        $q_pinjaman->execute();
        $res_pinjaman = $q_pinjaman->get_result();
        while ($row = $res_pinjaman->fetch_assoc()) {
            $total_pinjaman_aktif += $row['jumlah'];
            $total_denda += ($row['denda'] ?? 0);
        }
    }
}

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
    <title>Dashboard Siswa - Koperasi Sekolah</title>
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
        .stat-card {
            border: none; border-radius: 24px; background: white; transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(0,0,0,0.03); overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 20px 30px -12px rgba(0,0,0,0.1); }
        .stat-card .card-body { padding:1.5rem; }
        .stat-icon { font-size: 2.8rem; opacity:0.8; }
        .stat-value { font-size: 2rem; font-weight:800; margin-bottom:0; line-height:1.2; }
        .stat-label { font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#5e6f8d; font-weight:600; }
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1.2rem;
            margin-top: 1rem;
        }
        .menu-item {
            background: #f8fafc;
            border-radius: 20px;
            padding: 1.2rem;
            text-align: center;
            font-weight: 600;
            color: #1e2a3e;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        .menu-item i { font-size: 2rem; display: block; margin-bottom: 10px; color: #0077b6; }
        .menu-item:hover { background: #0077b6; color: white; transform: translateY(-5px); }
        .menu-item:hover i { color: white; }
        .table-custom { border-radius: 20px; overflow: hidden; }
        .table-custom thead th { background-color: #f8fafc; color: #1e2a3e; font-weight:600; border-bottom:2px solid #e2e8f0; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-user-graduate me-2"></i> Siswa</h4>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="lihat_saldo.php"><i class="fas fa-wallet"></i> Lihat Saldo</a>
            <a href="ajukan_pinjaman.php"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-chart-line me-2"></i> Dashboard Siswa</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('l, d F Y'); ?></div>
            </div>

            <!-- Menampilkan nama lengkap dan ID siswa -->
            <div class="alert alert-primary mb-4">
                <i class="fas fa-user-graduate me-2"></i> 
                Selamat datang, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong> 
                (ID Siswa / NIS: <strong><?php echo htmlspecialchars($id_siswa); ?></strong>)
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Saldo Tabungan</p><h3 class="stat-value text-success">Rp <?php echo number_format($saldo, 0, ',', '.'); ?></h3></div>
                                <div class="stat-icon text-success"><i class="fas fa-piggy-bank"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Pinjaman Aktif</p><h3 class="stat-value text-warning">Rp <?php echo number_format($total_pinjaman_aktif, 0, ',', '.'); ?></h3></div>
                                <div class="stat-icon text-warning"><i class="fas fa-hand-holding-usd"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Denda</p><h3 class="stat-value text-danger">Rp <?php echo number_format($total_denda, 0, ',', '.'); ?></h3></div>
                                <div class="stat-icon text-danger"><i class="fas fa-exclamation-triangle"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 mb-5">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-compass me-2"></i> Menu Cepat</h5>
                    <div class="menu-grid">
                        <a href="lihat_saldo.php" class="menu-item"><i class="fas fa-wallet"></i> Lihat Saldo</a>
                        <a href="ajukan_pinjaman.php" class="menu-item"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
                        <a href="riwayat_transaksi.php" class="menu-item"><i class="fas fa-history"></i> Riwayat Transaksi</a>
                        <a href="status_pinjaman.php" class="menu-item"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-list me-2"></i> Riwayat Transaksi Terbaru</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (count($riwayat) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-custom table-hover align-middle">
                                <thead>
                                    <tr><th>Tanggal</th><th>Jumlah (Rp)</th><th>Keterangan</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $tr): ?>
                                    <tr>
                                        <td><?php echo date('d-m-Y', strtotime($tr['tanggal'])); ?></td>
                                        <td>Rp <?php echo number_format($tr['jumlah'] ?? 0, 0, ',', '.'); ?></td>
                                        <td><?php echo htmlspecialchars($tr['keterangan'] ?? '-'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center">Belum ada transaksi.</p>
                    <?php endif; ?>
                </div>
            </div>
            <footer><i class="fas fa-hand-holding-usd"></i> Sistem Informasi Koperasi Sekolah</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>