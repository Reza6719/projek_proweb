<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
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

// Cek file foto untuk ditampilkan di header
$foto_url = '';
if (!empty($foto) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/TA/uploads/' . $foto)) {
    $foto_url = '../../uploads/' . $foto . '?v=' . time();
}

function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possibleColumns = ['saldo', 'jumlah', 'nominal', 'besar_tabungan'];
    foreach ($possibleColumns as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

// Total pengguna berdasarkan role
$total_admin = 0;
$total_pengurus = 0;
$total_siswa = 0;

$query = "SELECT role, COUNT(*) as jumlah FROM users GROUP BY role";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if ($row['role'] == 'admin') $total_admin = $row['jumlah'];
        elseif ($row['role'] == 'pengurus') $total_pengurus = $row['jumlah'];
        elseif ($row['role'] == 'siswa') $total_siswa = $row['jumlah'];
    }
}

// Total pinjaman aktif
$total_pinjaman_aktif = 0;
$pinjaman_table = $conn->query("SHOW TABLES LIKE 'pinjaman'");
if ($pinjaman_table && $pinjaman_table->num_rows > 0) {
    $q = $conn->query("SELECT SUM(jumlah) as total FROM pinjaman WHERE status = 'disetujui'");
    if ($q) $total_pinjaman_aktif = $q->fetch_assoc()['total'] ?? 0;
}

// Total tabungan seluruh siswa
$total_tabungan = 0;
$tabungan_table = $conn->query("SHOW TABLES LIKE 'tabungan'");
if ($tabungan_table && $tabungan_table->num_rows > 0) {
    $saldoColumn = getSaldoColumn($conn, 'tabungan');
    if ($saldoColumn) {
        $q = $conn->query("SELECT SUM($saldoColumn) as total FROM tabungan");
        if ($q) $total_tabungan = $q->fetch_assoc()['total'] ?? 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Koperasi Sekolah</title>
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
        .header-top .text-muted { color: rgba(255,255,255,0.7) !important; }
        .header-top strong { color: white; }
        .content { padding: 2rem 1.5rem; }
        .main-wrapper { display: flex; flex-direction: column; }
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
        .chart-card { border-radius: 24px; background: white; box-shadow: 0 8px 20px rgba(0,0,0,0.03); padding: 1.5rem; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        .profile-avatar-sm { width:45px; height:45px; border-radius:50%; object-fit:cover; border:2px solid #0077b6; cursor:pointer; }
        .profile-avatar-sm-icon { width:45px; height:45px; border-radius:50%; background:#0077b6; display:flex; align-items:center; justify-content:center; color:white; font-size:1.2rem; border:2px solid #0077b6; cursor:pointer; }
        .dropdown-menu-profile { min-width:200px; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.15); border:none; padding:0.5rem 0; margin-top:12px; }
        .dropdown-menu-profile .dropdown-item { padding:10px 20px; transition:0.2s; border-radius:8px; margin:2px 8px; }
        .dropdown-menu-profile .dropdown-item:hover { background:#f0f2f5; }
        .dropdown-menu-profile .dropdown-item i { width:20px; text-align:center; margin-right:10px; color:#0077b6; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
            .header-top { padding: 0.75rem 1rem; }
        }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-cogs me-2"></i> Admin</h4>
            <a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="kelola_pengguna.php"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="kelola_parameter.php"><i class="fas fa-sliders-h"></i> Parameter Sistem</a>
            <a href="laporan_global.php"><i class="fas fa-chart-bar"></i> Laporan Global</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 main-wrapper">
            <!-- Header Freeze -->
            <div class="header-top">
                <div class="d-flex justify-content-between align-items-center flex-wrap">
                    <div>
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> Dashboard Admin</h5>
                    </div>
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
            </div>

            <!-- Main Content -->
            <div class="content">

            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Total Admin</p><h3 class="stat-value text-primary"><?php echo $total_admin; ?></h3></div>
                                <div class="stat-icon text-primary"><i class="fas fa-user-shield"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Total Pengurus</p><h3 class="stat-value text-info"><?php echo $total_pengurus; ?></h3></div>
                                <div class="stat-icon text-info"><i class="fas fa-user-tie"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Total Siswa</p><h3 class="stat-value text-success"><?php echo $total_siswa; ?></h3></div>
                                <div class="stat-icon text-success"><i class="fas fa-user-graduate"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div><p class="stat-label">Pinjaman Aktif</p><h3 class="stat-value text-warning">Rp <?php echo number_format($total_pinjaman_aktif,0,',','.'); ?></h3></div>
                                <div class="stat-icon text-warning"><i class="fas fa-hand-holding-usd"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-chart-pie me-2"></i> Komposisi Pengguna</h5>
                        <canvas id="chartRole" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-card">
                        <h5 class="fw-bold mb-3"><i class="fas fa-money-bill-wave me-2"></i> Total Tabungan Siswa</h5>
                        <div class="text-center mt-4">
                            <p class="stat-label">Keseluruhan</p>
                            <h2 class="text-success">Rp <?php echo number_format($total_tabungan,0,',','.'); ?></h2>
                            <i class="fas fa-piggy-bank fa-3x text-success opacity-50 mt-3"></i>
                        </div>
                    </div>
                </div>
            </div>
            <footer><i class="fas fa-chart-line"></i> Sistem Informasi Koperasi Sekolah | Panel Admin</footer>
            </div>
        </main>
    </div>
</div>

<script>
    const ctx = document.getElementById('chartRole').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Admin', 'Pengurus', 'Siswa'],
            datasets: [{
                data: [<?php echo $total_admin; ?>, <?php echo $total_pengurus; ?>, <?php echo $total_siswa; ?>],
                backgroundColor: ['#0077b6', '#ffc107', '#28a745'],
                borderWidth: 0,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { callbacks: { label: (tooltipItem) => `${tooltipItem.label}: ${tooltipItem.raw} orang` } }
            }
        }
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>