<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];

// Ambil data user untuk foto dan nama
$query_user = $conn->prepare("SELECT username, nama_lengkap, foto FROM users WHERE id = ?");
$query_user->bind_param("i", $user_id);
$query_user->execute();
$user_data = $query_user->get_result()->fetch_assoc();
$username = $user_data['username'];
$nama_lengkap = $user_data['nama_lengkap'] ?? $username;
$foto = $user_data['foto'] ?? '';

// Cek file foto
$foto_url = '';
if (!empty($foto) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/' . $foto)) {
    $foto_url = '../../uploads/' . $foto . '?v=' . time();
}


function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possibleColumns = ['jumlah', 'saldo', 'nominal', 'besar_tabungan', 'total'];
    foreach ($possibleColumns as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

$total_siswa = 0;
$total_transaksi_siswa = 0;
$total_tabungan_siswa = 0;
$total_saldo_tabungan = 0;
$total_pinjaman_aktif = 0;

$res1 = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role='siswa'");
if ($res1) $total_siswa = $res1->fetch_assoc()['total'];

$res2 = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM transaksi");
if ($res2) $total_transaksi_siswa = $res2->fetch_assoc()['total'];

$res3 = $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM tabungan");
if ($res3) $total_tabungan_siswa = $res3->fetch_assoc()['total'];

$saldoColumn = getSaldoColumn($conn, 'tabungan');
if ($saldoColumn) {
    $res4 = $conn->query("SELECT SUM(`$saldoColumn`) AS total FROM tabungan");
    if ($res4) {
        $row4 = $res4->fetch_assoc();
        $total_saldo_tabungan = $row4['total'] ?? 0;
    }
}

$checkPinjamanTable = $conn->query("SHOW TABLES LIKE 'pinjaman'");
if ($checkPinjamanTable && $checkPinjamanTable->num_rows > 0) {
    $pinjamanColumns = ['sisa_pinjaman', 'saldo_pinjaman', 'sisa', 'sisa_hutang'];
    $pinjamanCol = null;
    foreach ($pinjamanColumns as $col) {
        $checkCol = $conn->query("SHOW COLUMNS FROM pinjaman LIKE '$col'");
        if ($checkCol && $checkCol->num_rows > 0) {
            $pinjamanCol = $col;
            break;
        }
    }
    if ($pinjamanCol) {
        $res5 = $conn->query("SELECT SUM(`$pinjamanCol`) AS total FROM pinjaman WHERE status != 'lunas'");
        if ($res5) {
            $row5 = $res5->fetch_assoc();
            $total_pinjaman_aktif = $row5['total'] ?? 0;
        }
    } else {
        $total_pinjaman_aktif = 0;
    }
}

$list_siswa = $conn->query("SELECT id_siswa, username, created_at FROM users WHERE role='siswa' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengurus - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card { border:none; border-radius:24px; background:white; transition:all 0.3s; box-shadow:0 8px 20px rgba(0,0,0,0.03), 0 2px 4px rgba(0,0,0,0.05); overflow:hidden; }
        .stat-card:hover { transform:translateY(-5px); box-shadow:0 20px 30px -12px rgba(0,0,0,0.1); }
        .stat-card .card-body { padding:1.5rem; }
        .stat-icon { font-size:2.8rem; opacity:0.8; }
        .stat-value { font-size:2.2rem; font-weight:800; margin-bottom:0; line-height:1.2; }
        .stat-label { font-size:0.85rem; text-transform:uppercase; letter-spacing:1px; color:#5e6f8d; font-weight:600; }
        .table-custom { border-radius:20px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.03); }
        .table-custom thead th { background-color:#f8fafc; color:#1e2a3e; font-weight:600; border-bottom:2px solid #e2e8f0; padding:14px 12px; }
        .table-custom tbody tr:hover { background-color:#fef9e3; }
        .badge-date { background:#e9ecef; color:#2c3e50; padding:5px 10px; border-radius:40px; font-size:0.75rem; font-weight:500; }
        footer { text-align:center; margin-top:3rem; color:#7f8c8d; font-size:0.8rem; }
        .profile-avatar-sm { width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #0077b6; cursor:pointer; }
        .profile-avatar-sm-icon { width:45px; height:45px; border-radius:50%; background:#0077b6; display:flex; align-items:center; justify-content:center; color:white; font-size:1.2rem; border:2px solid #0077b6; cursor:pointer; }
        .dropdown-menu-profile { min-width:200px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.15); border:none; padding:0.5rem 0; margin-top:12px; }
        .dropdown-menu-profile .dropdown-item { padding:10px 20px; transition:0.2s; border-radius:8px; margin:2px 8px; }
        .dropdown-menu-profile .dropdown-item:hover { background:#f0f2f5; }
        .dropdown-menu-profile .dropdown-item i { width:20px; text-align:center; margin-right:10px; color:#0077b6; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="rekap_transaksi.php"><i class="fas fa-chart-line"></i> Rekap Transaksi</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-chart-line me-2"></i>Dashboard Pengurus</h2>
                <div class="d-flex align-items-center">
                    <div class="me-3 text-end">
                        <div class="text-muted small">Selamat datang,</div>
                        <strong><?= htmlspecialchars($nama_lengkap) ?></strong>
                    </div>
                    <!-- Dropdown Foto Profil -->
                    <div class="dropdown">
                        <?php if ($foto_url): ?>
                            <img src="<?= $foto_url ?>" alt="Foto" class="profile-avatar-sm" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php else: ?>
                            <div class="profile-avatar-sm-icon" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> Lihat Profil</a></li>
                            <li><a class="dropdown-item" href="edit_profile.php"><i class="fas fa-edit"></i> Edit Profil</a></li>
                            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistik dan lainnya tetap seperti sebelumnya -->
            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><p class="stat-label">Siswa Terdaftar</p><h3 class="stat-value"><?php echo number_format($total_siswa); ?></h3><small class="text-success"><i class="fas fa-user-plus"></i> Total anggota</small></div>
                                <div class="stat-icon text-primary"><i class="fas fa-users"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><p class="stat-label">Aktif Transaksi</p><h3 class="stat-value"><?php echo number_format($total_transaksi_siswa); ?></h3><small class="text-info"><i class="fas fa-exchange-alt"></i> Simpan/Pinjam</small></div>
                                <div class="stat-icon text-info"><i class="fas fa-chart-simple"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><p class="stat-label">Siswa Menabung</p><h3 class="stat-value"><?php echo number_format($total_tabungan_siswa); ?></h3><small class="text-warning"><i class="fas fa-piggy-bank"></i> Memiliki saldo</small></div>
                                <div class="stat-icon text-warning"><i class="fas fa-sack-dollar"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div><p class="stat-label">Total Saldo Tabungan</p><h3 class="stat-value">Rp <?php echo number_format($total_saldo_tabungan, 0, ',', '.'); ?></h3><small class="text-success"><i class="fas fa-wallet"></i> Seluruh siswa</small></div>
                                <div class="stat-icon text-success"><i class="fas fa-money-bill-trend-up"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><p class="stat-label">Pinjaman Aktif Belum Lunas</p><h3 class="stat-value">Rp <?php echo number_format($total_pinjaman_aktif, 0, ',', '.'); ?></h3><small><i class="fas fa-exclamation-triangle text-danger"></i> Perlu pemantauan</small></div>
                                <div class="stat-icon text-danger"><i class="fas fa-hand-holding-usd"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <canvas id="aktivitasChart" style="max-height:150px;"></canvas>
                            <p class="text-center mt-2 mb-0 text-muted small">Aktivitas Siswa (Terdaftar vs Menabung)</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-table-list me-2"></i>📋 Daftar Siswa Terdaftar</h5>
                    <p class="text-muted small mt-1">Data terbaru berdasarkan tanggal pendaftaran</p>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table class="table table-custom table-hover align-middle">
                            <thead><tr><th>ID Siswa</th><th>Username</th><th>Tanggal Daftar</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php if ($list_siswa && $list_siswa->num_rows > 0): ?>
                                    <?php while ($row = $list_siswa->fetch_assoc()): ?>
                                        <tr><td class="fw-semibold"><?php echo htmlspecialchars($row['id_siswa']); ?></td><td><?php echo htmlspecialchars($row['username']); ?></td><td><span class="badge-date"><i class="far fa-calendar-alt me-1"></i> <?php echo date('d-m-Y H:i', strtotime($row['created_at'])); ?></span></td><td><span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Aktif</span></td></tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada siswa terdaftar</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <footer><i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah | Real-time & Modern</footer>
        </main>
    </div>
</div>

<script>
    const ctx = document.getElementById('aktivitasChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Siswa Menabung', 'Siswa Belum Menabung'],
            datasets: [{
                data: [<?php echo $total_tabungan_siswa; ?>, <?php echo $total_siswa - $total_tabungan_siswa; ?>],
                backgroundColor: ['#2a9d8f', '#e9c46a'],
                borderWidth: 0, hoverOffset: 8
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: true,
            plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } }, tooltip: { callbacks: { label: (tooltipItem) => `${tooltipItem.label}: ${tooltipItem.raw} siswa` } } }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>