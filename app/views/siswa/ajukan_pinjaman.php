<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'siswa') {
    die("Akses ditolak!");
}

$user_id = $_SESSION['user_id'];
$error = "";
$success = "";

// Cek tabel pinjaman
$tableCheck = $conn->query("SHOW TABLES LIKE 'pinjaman'");
$pinjamanExists = ($tableCheck && $tableCheck->num_rows > 0);

// Ambil parameter batas pinjaman
$batas_pinjaman = 5000000; // default
$paramResult = $conn->query("SELECT batas_pinjaman FROM parameter LIMIT 1");
if ($paramResult && $paramResult->num_rows > 0) {
    $row = $paramResult->fetch_assoc();
    $batas_pinjaman = $row['batas_pinjaman'];
}

// Hitung total pinjaman aktif (pending + disetujui) yang belum lunas
$total_pinjaman_aktif = 0;
if ($pinjamanExists) {
    $q = $conn->prepare("SELECT SUM(jumlah) as total FROM pinjaman WHERE user_id = ? AND status IN ('pending', 'disetujui')");
    $q->bind_param("i", $user_id);
    $q->execute();
    $res = $q->get_result();
    if ($row = $res->fetch_assoc()) {
        $total_pinjaman_aktif = $row['total'] ?? 0;
    }
}

$sisa_limit = max(0, $batas_pinjaman - $total_pinjaman_aktif);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $pinjamanExists) {
    $jumlah = (int) $_POST['jumlah'];

    if ($jumlah < 50000) {
        $error = "⚠️ Jumlah pinjaman minimal Rp 50.000";
    } elseif ($jumlah > $batas_pinjaman) {
        $error = "⚠️ Jumlah pinjaman melebihi batas maksimal Rp " . number_format($batas_pinjaman, 0, ',', '.');
    } elseif ($jumlah > $sisa_limit) {
        $error = "⚠️ Sisa limit pinjaman Anda hanya Rp " . number_format($sisa_limit, 0, ',', '.');
    } else {
        $tanggal = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("INSERT INTO pinjaman (user_id, jumlah, tanggal, status) VALUES (?, ?, ?, 'pending')");
        $stmt->bind_param("iis", $user_id, $jumlah, $tanggal);
        if ($stmt->execute()) {
            $success = "✅ Pengajuan pinjaman sebesar Rp " . number_format($jumlah, 0, ',', '.') . " berhasil dikirim! Menunggu persetujuan pengurus.";
            // Update sisa limit setelah pengajuan
            $total_pinjaman_aktif += $jumlah;
            $sisa_limit = max(0, $batas_pinjaman - $total_pinjaman_aktif);
        } else {
            $error = "❌ Terjadi kesalahan: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajukan Pinjaman - Koperasi Sekolah</title>
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
        .card-form {
            border: none; border-radius: 28px; background: white; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .card-form:hover { transform: translateY(-5px); }
        .info-box { background: #eef2ff; border-radius: 20px; padding: 1rem; margin-bottom: 1.5rem; }
        .btn-pinjaman { background: linear-gradient(90deg, #28a745, #34ce57); border: none; padding: 12px; border-radius: 40px; font-weight: 600; transition: 0.2s; }
        .btn-pinjaman:hover { background: linear-gradient(90deg, #1e7e34, #2b9e44); transform: translateY(-2px); }
        .limit-box { background: #f8f9fa; border-radius: 20px; padding: 0.75rem 1rem; margin-bottom: 1.5rem; border-left: 4px solid #0077b6; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
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
            <a href="ajukan_pinjaman.php" class="active"><i class="fas fa-file-invoice"></i> Ajukan Pinjaman</a>
            <a href="riwayat_transaksi.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
            <a href="status_pinjaman.php"><i class="fas fa-hourglass-half"></i> Status Pinjaman</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-hand-holding-usd me-2"></i> Ajukan Pinjaman</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!$pinjamanExists): ?>
                <div class="alert alert-warning"><i class="fas fa-database"></i> Modul pinjaman belum tersedia. Silakan hubungi pengurus.</div>
            <?php endif; ?>

            <?php if ($pinjamanExists): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-form p-4 p-lg-5">
                        <div class="limit-box">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            <strong>Batas Maksimal Pinjaman:</strong> Rp <?php echo number_format($batas_pinjaman, 0, ',', '.'); ?> &nbsp;|&nbsp;
                            <strong>Sisa Limit Anda:</strong> Rp <?php echo number_format($sisa_limit, 0, ',', '.'); ?>
                        </div>
                        <div class="info-box">
                            <i class="fas fa-info-circle text-primary me-2"></i> 
                            <strong>Ketentuan Pinjaman:</strong>
                            <ul class="mt-2 mb-0">
                                <li>Minimal pinjaman <strong>Rp 50.000</strong></li>
                                <li>Maksimal pinjaman <strong>Rp <?php echo number_format($batas_pinjaman, 0, ',', '.'); ?></strong></li>
                                <li>Denda keterlambatan <strong>Rp 5.000/hari</strong> (setelah jatuh tempo 30 hari)</li>
                                <li>Pengajuan akan diproses oleh pengurus dalam 1x24 jam</li>
                            </ul>
                        </div>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-bold"><i class="fas fa-money-bill-wave me-1"></i> Jumlah Pinjaman (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rp</span>
                                    <input type="number" name="jumlah" class="form-control form-control-lg" 
                                           min="50000" max="<?php echo $sisa_limit; ?>" step="10000" 
                                           placeholder="Contoh: 500000" required>
                                </div>
                                <div class="form-text">Kelipatan Rp 10.000, maksimal Rp <?php echo number_format($sisa_limit, 0, ',', '.'); ?></div>
                            </div>
                            <button type="submit" class="btn btn-pinjaman w-100 btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Kirim Pengajuan
                            </button>
                        </form>
                        <div class="mt-4 text-center">
                            <small class="text-muted">Pastikan data yang Anda isi sudah benar. Pengajuan tidak dapat diubah setelah dikirim.</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <footer><i class="fas fa-hand-holding-usd"></i> Sistem Informasi Koperasi Sekolah | Ajukan pinjaman dengan mudah dan cepat</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>