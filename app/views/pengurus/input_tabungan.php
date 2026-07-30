<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pengurus') {
    die("Akses ditolak!");
}

$error = "";
$success = "";
$nama_siswa = "";
$user_internal_id = null;

// Fungsi untuk mendapatkan kolom saldo (dinamis)
function getSaldoColumn(mysqli $conn, string $table = 'tabungan') {
    $possible = ['saldo', 'jumlah', 'nominal', 'besar_tabungan'];
    foreach ($possible as $col) {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
        if ($check && $check->num_rows > 0) return $col;
    }
    return null;
}

// Proses cari siswa
if (isset($_POST['cari'])) {
    $id_siswa_input = trim($_POST['id_siswa']);
    $stmt = $conn->prepare("SELECT id, id_siswa, username FROM users WHERE id_siswa = ? AND role = 'siswa'");
    $stmt->bind_param("s", $id_siswa_input);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $user_internal_id = $row['id'];
        $nama_siswa = $row['username'];
        $_SESSION['temp_id_siswa'] = $id_siswa_input;
        $_SESSION['temp_user_id'] = $user_internal_id;
    } else {
        $error = "❌ ID Siswa tidak ditemukan!";
        unset($_SESSION['temp_id_siswa'], $_SESSION['temp_user_id']);
    }
}

// Proses simpan tabungan
if (isset($_POST['simpan'])) {
    $id_siswa_input = $_POST['id_siswa'] ?? $_SESSION['temp_id_siswa'] ?? '';
    $jumlah = (int) ($_POST['jumlah'] ?? 0);

    if ($id_siswa_input && $jumlah > 0) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE id_siswa = ? AND role = 'siswa'");
        $stmt->bind_param("s", $id_siswa_input);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_internal_id = $row['id'];
            $saldoColumn = getSaldoColumn($conn, 'tabungan');
            if (!$saldoColumn) {
                $error = "❌ Tabel tabungan tidak memiliki kolom saldo yang dikenali.";
            } else {
                // Mulai transaksi agar atomic
                $conn->begin_transaction();
                try {
                    // 1. Insert/Update tabungan
                    $sql = "INSERT INTO tabungan (user_id, $saldoColumn) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE $saldoColumn = $saldoColumn + VALUES($saldoColumn)";
                    $stmt2 = $conn->prepare($sql);
                    $stmt2->bind_param("ii", $user_internal_id, $jumlah);
                    $stmt2->execute();

                    // 2. Insert ke tabel transaksi (riwayat)
                    $jenis = 'tabungan'; // sesuai ENUM di tabel transaksi
                    $tanggal = date('Y-m-d'); // tipe kolom date (tanpa jam)
                    $keterangan = 'Setoran tabungan oleh pengurus';
                    $stmt3 = $conn->prepare("INSERT INTO transaksi (user_id, jenis, jumlah, tanggal, keterangan) VALUES (?, ?, ?, ?, ?)");
                    $stmt3->bind_param("isiss", $user_internal_id, $jenis, $jumlah, $tanggal, $keterangan);
                    $stmt3->execute();

                    $conn->commit();
                    $success = "✅ Tabungan berhasil ditambahkan! (Rp " . number_format($jumlah, 0, ',', '.') . ")";
                    unset($_SESSION['temp_id_siswa'], $_SESSION['temp_user_id']);
                    $nama_siswa = "";
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "❌ Terjadi kesalahan: " . $e->getMessage();
                }
            }
        } else {
            $error = "❌ ID Siswa tidak valid, silakan cari ulang!";
            unset($_SESSION['temp_id_siswa'], $_SESSION['temp_user_id']);
        }
    } else {
        $error = "❌ Masukkan jumlah simpanan yang valid (minimal Rp 1.000).";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Tabungan - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* (CSS sama seperti sebelumnya) */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { background:#f0f2f5; font-family:'Poppins','Segoe UI',sans-serif; }
        .sidebar { background:linear-gradient(135deg,#0f2b3d,#1a4a6f); color:white; height:100vh; position:sticky; top:0; box-shadow:2px 0 12px rgba(0,0,0,0.05); }
        .sidebar h4 { font-weight:600; letter-spacing:1px; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:12px; margin-bottom:20px; }
        .sidebar a { display:flex; align-items:center; gap:12px; color:#e0e7ff; text-decoration:none; padding:12px 16px; margin:6px 0; border-radius:12px; transition:all 0.2s ease; font-weight:500; }
        .sidebar a i { width:24px; text-align:center; }
        .sidebar a:hover { background:rgba(255,255,255,0.15); color:white; transform:translateX(5px); }
        .sidebar a.active { background:#0077b6; color:white; box-shadow:0 4px 8px rgba(0,0,0,0.2); }
        .content { padding:2rem 1.5rem; }
        .page-title { font-weight:700; color:#1e2a3e; border-left:5px solid #0077b6; padding-left:15px; margin-bottom:1.8rem; }
        .card-form { border:none; border-radius:28px; background:white; box-shadow:0 20px 35px -10px rgba(0,0,0,0.1); padding:2rem; transition:transform 0.2s; }
        .card-form:hover { transform:translateY(-5px); }
        .form-label { font-weight:600; color:#1e2a3e; }
        .btn-simpan { background:linear-gradient(90deg,#28a745,#34ce57); border:none; padding:12px; border-radius:40px; font-weight:600; transition:0.2s; }
        .btn-simpan:hover { transform:translateY(-2px); background:#1e7e34; }
        .info-badge { background:#eef2ff; border-radius:20px; padding:0.75rem; margin-top:0.5rem; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) { .sidebar { height:auto; position:relative; } .content { padding:1rem; } }
    </style>
</head>
<body>
<div class="container-fluid p-0">
    <div class="row g-0">
        <nav class="col-md-2 sidebar p-3">
            <h4><i class="fas fa-hand-holding-usd me-2"></i> Koperasi</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="input_tabungan.php" class="active"><i class="fas fa-coins"></i> Input Tabungan</a>
            <a href="proses_pinjaman.php"><i class="fas fa-file-invoice-dollar"></i> Proses Pinjaman</a>
            <a href="hitung_denda.php"><i class="fas fa-clock"></i> Hitung Denda</a>
            <a href="profile.php"><i class="fas fa-user-circle"></i> Profil</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Pengaturan</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-piggy-bank me-2"></i> Input Tabungan Siswa</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card-form">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><i class="fas fa-id-card me-1"></i> ID Siswa</label>
                                <div class="input-group">
                                    <input type="text" name="id_siswa" class="form-control" placeholder="Masukkan ID siswa" value="<?php echo htmlspecialchars($_SESSION['temp_id_siswa'] ?? ''); ?>" required>
                                    <button type="submit" name="cari" class="btn btn-info"><i class="fas fa-search"></i> Cari</button>
                                </div>
                                <?php if (!empty($nama_siswa)): ?>
                                    <div class="info-badge"><i class="fas fa-user-check text-success"></i> Nama: <strong><?php echo htmlspecialchars($nama_siswa); ?></strong></div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i> Jumlah Simpanan (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rp</span>
                                    <input type="number" name="jumlah" class="form-control" min="1000" step="1000" placeholder="Minimal Rp 1.000" required>
                                </div>
                                <div class="form-text">Kelipatan Rp 1.000</div>
                            </div>
                            <button type="submit" name="simpan" class="btn btn-simpan w-100"><i class="fas fa-save me-2"></i> Simpan Tabungan</button>
                        </form>
                    </div>
                </div>
            </div>
            <footer><i class="fas fa-coins"></i> Sistem Informasi Koperasi Sekolah | Transparan & Terpercaya</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>