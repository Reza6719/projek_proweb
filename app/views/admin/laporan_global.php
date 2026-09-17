<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possible = ['saldo', 'jumlah', 'nominal', 'besar_tabungan'];
    foreach ($possible as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

// 1. Total simpanan dari tabungan
$total_simpanan = 0;
$tab_table = $conn->query("SHOW TABLES LIKE 'tabungan'");
if ($tab_table && $tab_table->num_rows > 0) {
    $saldoCol = getSaldoColumn($conn, 'tabungan');
    if ($saldoCol) {
        $q = $conn->query("SELECT SUM($saldoCol) as total FROM tabungan");
        if ($q) $total_simpanan = $q->fetch_assoc()['total'] ?? 0;
    }
}

// 2. Total pinjaman aktif (disetujui)
$total_pinjaman = 0;
$pinj_table = $conn->query("SHOW TABLES LIKE 'pinjaman'");
if ($pinj_table && $pinj_table->num_rows > 0) {
    $q = $conn->query("SELECT SUM(jumlah) as total FROM pinjaman WHERE status = 'disetujui'");
    if ($q) $total_pinjaman = $q->fetch_assoc()['total'] ?? 0;
}

// 3. Total denda dari pinjaman disetujui
$total_denda = 0;
if ($pinj_table && $pinj_table->num_rows > 0) {
    $q = $conn->query("SELECT SUM(denda) as total FROM pinjaman WHERE status = 'disetujui'");
    if ($q) $total_denda = $q->fetch_assoc()['total'] ?? 0;
}

$neraca = $total_simpanan - $total_pinjaman;
$laba_rugi = $total_denda;
$shu = $laba_rugi * 0.1;

// 4. Data transaksi per tahun (dari tabel transaksi)
$periode_data = [];
$query_tahun = "SELECT YEAR(tanggal) as tahun, SUM(jumlah) as total 
                FROM transaksi 
                WHERE jenis = 'tabungan' 
                GROUP BY YEAR(tanggal) 
                ORDER BY tahun DESC";
$res_tahun = $conn->query($query_tahun);
if ($res_tahun && $res_tahun->num_rows > 0) {
    while ($row = $res_tahun->fetch_assoc()) {
        $periode_data[$row['tahun']]['simpanan'] = $row['total'];
    }
}
$query_pinjaman_tahun = "SELECT YEAR(tanggal) as tahun, SUM(jumlah) as total 
                        FROM transaksi 
                        WHERE jenis = 'pinjaman' 
                        GROUP BY YEAR(tanggal)";
$res_pinjam_tahun = $conn->query($query_pinjaman_tahun);
if ($res_pinjam_tahun && $res_pinjam_tahun->num_rows > 0) {
    while ($row = $res_pinjam_tahun->fetch_assoc()) {
        $periode_data[$row['tahun']]['pinjaman'] = $row['total'];
    }
}

// 5. Ambil semua transaksi (untuk tabel riwayat)
$query_transaksi = "SELECT t.id, u.username, u.id_siswa, t.jenis, t.jumlah, t.tanggal, t.keterangan 
                    FROM transaksi t 
                    JOIN users u ON t.user_id = u.id 
                    ORDER BY t.tanggal DESC";
$result_transaksi = $conn->query($query_transaksi);
$transaksi_list = [];
$total_transaksi = 0;
$total_tabungan_transaksi = 0;
$total_pinjaman_transaksi = 0;
$total_denda_transaksi = 0;

if ($result_transaksi && $result_transaksi->num_rows > 0) {
    while ($row = $result_transaksi->fetch_assoc()) {
        $transaksi_list[] = $row;
        $total_transaksi += $row['jumlah'];
        if ($row['jenis'] == 'tabungan') $total_tabungan_transaksi += $row['jumlah'];
        elseif ($row['jenis'] == 'pinjaman') $total_pinjaman_transaksi += $row['jumlah'];
        elseif ($row['jenis'] == 'denda') $total_denda_transaksi += $row['jumlah'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Global - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .header-top {
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            padding: 1rem 1.5rem;
        }
        .header-top .d-flex { gap: 12px; }
        .content { padding: 2rem 1.5rem; }
        .main-wrapper { display: flex; flex-direction: column; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .laporan-card {
            background: white; border-radius: 28px; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .summary-box {
            background: linear-gradient(145deg, #0f2b3d, #1a4a6f);
            border-radius: 20px;
            padding: 1rem;
            text-align: center;
            color: white;
        }
        .summary-box h5 { font-size:0.9rem; opacity:0.9; margin-bottom:0.5rem; }
        .summary-box p { font-size:1.6rem; font-weight:700; margin:0; }
        .table-custom { border-radius: 20px; overflow:hidden; }
        .table-custom thead th { background:#f8fafc; border-bottom:2px solid #e2e8f0; }
        .btn-export {
            background: linear-gradient(90deg, #28a745, #34ce57);
            border: none; border-radius: 40px; padding: 10px 24px; font-weight:600;
            transition: 0.2s; text-decoration: none; color: white; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-export:hover { transform: translateY(-2px); background: #1e7e34; color: white; }
        .badge-jenis { padding:6px 14px; border-radius:40px; font-size:0.75rem; font-weight:600; }
        .badge-tabungan { background:#d4edda; color:#155724; }
        .badge-pinjaman { background:#fff3cd; color:#856404; }
        .badge-denda { background:#f8d7da; color:#721c24; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
            .summary-box p { font-size:1.2rem; }
            .header-top { padding: 0.75rem 1rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-cogs me-2"></i> Admin</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="kelola_pengguna.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="kelola_parameter.php"><i class="fas fa-sliders-h"></i> Parameter Sistem</a>
            <a href="laporan_global.php" class="active"><i class="fas fa-chart-bar"></i> Laporan Global</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 main-wrapper">
            <!-- Header Freeze -->
            <div class="header-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Laporan Global</h5>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">
                <h2 class="page-title"><i class="fas fa-chart-line me-2"></i> Laporan Keuangan Global</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <div class="laporan-card">
                <!-- Ringkasan -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6 col-lg-3">
                        <div class="summary-box">
                            <h5><i class="fas fa-piggy-bank"></i> Total Simpanan</h5>
                            <p>Rp <?php echo number_format($total_simpanan,0,',','.'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="summary-box">
                            <h5><i class="fas fa-hand-holding-usd"></i> Total Pinjaman Aktif</h5>
                            <p>Rp <?php echo number_format($total_pinjaman,0,',','.'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="summary-box">
                            <h5><i class="fas fa-exclamation-triangle"></i> Total Denda</h5>
                            <p>Rp <?php echo number_format($total_denda,0,',','.'); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="summary-box">
                            <h5><i class="fas fa-balance-scale"></i> Neraca (Simpanan - Pinjaman)</h5>
                            <p>Rp <?php echo number_format($neraca,0,',','.'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Laba Rugi & SHU -->
                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="card border-0 bg-light rounded-4 p-3 text-center">
                            <h5 class="text-warning"><i class="fas fa-chart-simple"></i> Laba/Rugi (Pendapatan Denda)</h5>
                            <h3 class="text-success">Rp <?php echo number_format($laba_rugi,0,',','.'); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-0 bg-light rounded-4 p-3 text-center">
                            <h5 class="text-info"><i class="fas fa-hand-holding-heart"></i> Estimasi SHU (10% dari Laba)</h5>
                            <h3 class="text-primary">Rp <?php echo number_format($shu,0,',','.'); ?></h3>
                        </div>
                    </div>
                </div>

                <!-- Tabel per tahun -->
                <?php if (!empty($periode_data)): ?>
                <div class="mt-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-calendar-week"></i> Ringkasan per Tahun</h5>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr><th>Tahun</th><th>Total Simpanan (Rp)</th><th>Total Pinjaman (Rp)</th><th>Neraca (Rp)</th></tr>
                            </thead>
                            <tbody>
                                <?php krsort($periode_data); foreach ($periode_data as $tahun => $data): ?>
                                <tr>
                                    <td><strong><?php echo $tahun; ?></strong></td>
                                    <td>Rp <?php echo number_format($data['simpanan'] ?? 0,0,',','.'); ?></td>
                                    <td>Rp <?php echo number_format($data['pinjaman'] ?? 0,0,',','.'); ?></td>
                                    <td>Rp <?php echo number_format(($data['simpanan'] ?? 0) - ($data['pinjaman'] ?? 0),0,',','.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tabel Riwayat Transaksi (sinkron dengan pengurus) -->
                <div class="mt-5">
                    <h5 class="fw-bold mb-3"><i class="fas fa-history"></i> Riwayat Transaksi</h5>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="summary-box" style="background:#0f2b3d;"><h5 style="font-size:0.8rem;">Total</h5><p style="font-size:1.2rem;">Rp <?= number_format($total_transaksi,0,',','.') ?></p></div></div>
                        <div class="col-md-3"><div class="summary-box" style="background:#28a745;"><h5 style="font-size:0.8rem;">Tabungan</h5><p style="font-size:1.2rem;">Rp <?= number_format($total_tabungan_transaksi,0,',','.') ?></p></div></div>
                        <div class="col-md-3"><div class="summary-box" style="background:#ffc107; color:#1e2a3e;"><h5 style="font-size:0.8rem;">Pinjaman</h5><p style="font-size:1.2rem;">Rp <?= number_format($total_pinjaman_transaksi,0,',','.') ?></p></div></div>
                        <div class="col-md-3"><div class="summary-box" style="background:#dc3545;"><h5 style="font-size:0.8rem;">Denda</h5><p style="font-size:1.2rem;">Rp <?= number_format($total_denda_transaksi,0,',','.') ?></p></div></div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead>
                                <tr><th>ID</th><th>Siswa</th><th>NIS</th><th>Jenis</th><th>Jumlah (Rp)</th><th>Tanggal</th><th>Keterangan</th></tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($transaksi_list)): ?>
                                    <?php foreach ($transaksi_list as $r): ?>
                                    <tr>
                                        <td><?= $r['id'] ?></td>
                                        <td><?= htmlspecialchars($r['username']) ?></td>
                                        <td><?= htmlspecialchars($r['id_siswa']) ?></td>
                                        <td>
                                            <?php if ($r['jenis'] == 'tabungan'): ?>
                                                <span class="badge-jenis badge-tabungan"><i class="fas fa-arrow-up"></i> Tabungan</span>
                                            <?php elseif ($r['jenis'] == 'pinjaman'): ?>
                                                <span class="badge-jenis badge-pinjaman"><i class="fas fa-arrow-down"></i> Pinjaman</span>
                                            <?php else: ?>
                                                <span class="badge-jenis badge-denda"><i class="fas fa-exclamation-triangle"></i> Denda</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>Rp <?= number_format($r['jumlah'],0,',','.') ?></td>
                                        <td><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
                                        <td><?= htmlspecialchars($r['keterangan'] ?? '-') ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center text-muted py-3">Belum ada transaksi</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tombol export -->
                <div class="text-center mt-4">
                    <a href="export_laporan.php" class="btn-export"><i class="fas fa-download"></i> Export ke Excel</a>
                </div>
            </div>
            <footer><i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah | Laporan keuangan realtime</footer>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>