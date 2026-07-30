<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

$tableCheck = $conn->query("SHOW TABLES LIKE 'parameter'");
if (!$tableCheck || $tableCheck->num_rows == 0) {
    $createTable = "CREATE TABLE parameter (
        id INT AUTO_INCREMENT PRIMARY KEY,
        denda_per_hari INT DEFAULT 5000,
        batas_pinjaman INT DEFAULT 5000000,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->query($createTable);
}

$result = $conn->query("SELECT * FROM parameter LIMIT 1");
$param = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil nilai dari input (bisa berupa number atau string dengan titik)
    $denda_per_hari = preg_replace('/[^0-9]/', '', $_POST['denda_per_hari']);
    $batas_pinjaman = preg_replace('/[^0-9]/', '', $_POST['batas_pinjaman']);
    $denda_per_hari = (int)$denda_per_hari;
    $batas_pinjaman = (int)$batas_pinjaman;

    if ($denda_per_hari <= 0 || $batas_pinjaman <= 0) {
        $error = "⚠️ Nilai harus lebih dari 0.";
    } else {
        if ($param) {
            $stmt = $conn->prepare("UPDATE parameter SET denda_per_hari = ?, batas_pinjaman = ? WHERE id = ?");
            $stmt->bind_param("iii", $denda_per_hari, $batas_pinjaman, $param['id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO parameter (denda_per_hari, batas_pinjaman) VALUES (?, ?)");
            $stmt->bind_param("ii", $denda_per_hari, $batas_pinjaman);
        }

        if ($stmt->execute()) {
            $success = "✅ Parameter berhasil diperbarui!";
            $result = $conn->query("SELECT * FROM parameter LIMIT 1");
            $param = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
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
    <title>Kelola Parameter - Admin</title>
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
            padding: 2rem;
            transition: transform 0.2s;
        }
        .card-form:hover { transform: translateY(-5px); }
        .form-label { font-weight:600; color:#1e2a3e; }
        .btn-save {
            background: linear-gradient(90deg, #28a745, #34ce57);
            border: none; padding: 12px; border-radius: 40px; font-weight:600; transition:0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); background: #1e7e34; }
        .info-note {
            background: #eef2ff; border-radius: 20px; padding: 1rem; margin-top: 1.5rem;
        }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
        @media (max-width:768px) {
            .sidebar { height:auto; position:relative; }
            .content { padding:1rem; }
        }
        .rupiah-input {
            position: relative;
        }
        .rupiah-input input {
            padding-left: 40px;
        }
        .rupiah-preview {
            font-size: 0.9rem;
            margin-top: 5px;
            color: #28a745;
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
            <a href="kelola_parameter.php" class="active"><i class="fas fa-sliders-h"></i> Parameter Sistem</a>
            <a href="laporan_global.php"><i class="fas fa-chart-bar"></i> Laporan Global</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-sliders-h me-2"></i> Parameter Sistem</h2>
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
                        <form method="POST" id="parameterForm">
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-money-bill-wave me-1"></i> Denda per Hari (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rp</span>
                                    <input type="text" name="denda_per_hari" id="denda_per_hari" class="form-control form-control-lg" 
                                           value="<?php echo $param ? number_format($param['denda_per_hari'], 0, ',', '.') : '5.000'; ?>" 
                                           placeholder="Contoh: 5.000" required>
                                </div>
                                <div class="form-text">Denda keterlambatan per hari setelah jatuh tempo 30 hari.</div>
                                <div class="rupiah-preview" id="dendaPreview"></div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label"><i class="fas fa-hand-holding-usd me-1"></i> Batas Maksimal Pinjaman (Rp)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white">Rp</span>
                                    <input type="text" name="batas_pinjaman" id="batas_pinjaman" class="form-control form-control-lg" 
                                           value="<?php echo $param ? number_format($param['batas_pinjaman'], 0, ',', '.') : '5.000.000'; ?>" 
                                           placeholder="Contoh: 5.000.000" required>
                                </div>
                                <div class="form-text">Maksimal jumlah pinjaman yang dapat diajukan siswa.</div>
                                <div class="rupiah-preview" id="pinjamanPreview"></div>
                            </div>
                            <button type="submit" class="btn btn-save w-100 btn-lg"><i class="fas fa-save me-2"></i> Simpan Parameter</button>
                        </form>
                        <div class="info-note">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            <strong>Catatan:</strong> Parameter ini akan memengaruhi perhitungan denda otomatis dan batasan pinjaman di seluruh sistem.
                        </div>
                    </div>
                </div>
            </div>
            <footer><i class="fas fa-sliders-h"></i> Sistem Informasi Koperasi Sekolah | Atur parameter global</footer>
        </main>
    </div>
</div>
<script>
    // Fungsi untuk memformat angka menjadi rupiah (titik sebagai pemisah ribuan)
    function formatRupiah(angka) {
        var number_string = angka.replace(/[^,\d]/g, '').toString(),
            split = number_string.split(','),
            sisa = split[0].length % 3,
            rupiah = split[0].substr(0, sisa),
            ribuan = split[0].substr(sisa).match(/\d{3}/gi);
        if (ribuan) {
            separator = sisa ? '.' : '';
            rupiah += separator + ribuan.join('.');
        }
        rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
        return rupiah;
    }

    // Event listener untuk input denda
    var dendaInput = document.getElementById('denda_per_hari');
    var pinjamanInput = document.getElementById('batas_pinjaman');
    var dendaPreview = document.getElementById('dendaPreview');
    var pinjamanPreview = document.getElementById('pinjamanPreview');

    function updatePreview(input, preview) {
        var nilai = input.value.replace(/[^0-9]/g, '');
        if (nilai) {
            var formatted = formatRupiah(nilai);
            preview.innerHTML = '<i class="fas fa-check-circle text-success"></i> Nilai saat ini: Rp ' + formatted;
        } else {
            preview.innerHTML = '';
        }
    }

    dendaInput.addEventListener('input', function() {
        var nilai = this.value.replace(/[^0-9]/g, '');
        if (nilai) {
            this.value = formatRupiah(nilai);
        } else {
            this.value = '';
        }
        updatePreview(this, dendaPreview);
    });

    pinjamanInput.addEventListener('input', function() {
        var nilai = this.value.replace(/[^0-9]/g, '');
        if (nilai) {
            this.value = formatRupiah(nilai);
        } else {
            this.value = '';
        }
        updatePreview(this, pinjamanPreview);
    });

    // Trigger preview saat halaman dimuat
    updatePreview(dendaInput, dendaPreview);
    updatePreview(pinjamanInput, pinjamanPreview);
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>