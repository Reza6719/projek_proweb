<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_siswa = trim($_POST['id_siswa'] ?? '');
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    if (empty($username) || empty($password) || empty($role)) {
        $error = "Username, password, dan role wajib diisi.";
    } elseif ($role == 'siswa' && empty($id_siswa)) {
        $error = "ID Siswa wajib diisi untuk role Siswa.";
    } else {
        // Cek username sudah ada
        $cek = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $cek->bind_param("s", $username);
        $cek->execute();
        $cek->store_result();
        if ($cek->num_rows > 0) {
            $error = "Username sudah digunakan. Silakan pilih username lain.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            // Untuk role selain siswa, id_siswa diisi string kosong
            if (empty($id_siswa)) {
                $id_siswa = '';
            }
            // Selalu masukkan id_siswa (meskipun kosong) agar tidak error "no default value"
            $stmt = $conn->prepare("INSERT INTO users (id_siswa, username, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $id_siswa, $username, $hashed, $role);
            
            if ($stmt->execute()) {
                $success = "✅ Pengguna baru berhasil ditambahkan!";
            } else {
                $error = "❌ Gagal menyimpan: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Pengguna - Admin</title>
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
        .user-card {
            background: white; border-radius: 28px; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
            transition: transform 0.2s;
        }
        .user-card:hover { transform: translateY(-5px); }
        .form-label { font-weight:600; color:#1e2a3e; }
        .btn-save {
            background: linear-gradient(90deg, #28a745, #34ce57);
            border: none; padding: 12px; border-radius: 40px; font-weight:600; transition:0.2s;
        }
        .btn-save:hover { transform: translateY(-2px); background: #1e7e34; }
        .btn-back {
            background: #6c757d; border: none; padding: 10px 20px; border-radius: 40px; color: white;
            text-decoration: none; display: inline-block; transition:0.2s;
        }
        .btn-back:hover { background: #5a6268; transform: translateY(-2px); color: white; }
        footer { text-align:center; margin-top:2rem; color:#7f8c8d; font-size:0.8rem; }
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
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
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
                        <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i> Tambah Pengguna Baru</h5>
                    </div>
                    <div>
                        <span class="text-white"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="content">

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="user-card">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-id-card me-1"></i> ID Siswa (hanya untuk role Siswa)</label>
                        <input type="text" name="id_siswa" class="form-control" placeholder="Contoh: SISWA001 (kosongkan jika bukan siswa)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-user me-1"></i> Username <span class="text-danger">*</span></label>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="fas fa-lock me-1"></i> Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required minlength="6">
                    </div>
                    <div class="mb-4">
                        <label class="form-label"><i class="fas fa-tag me-1"></i> Role <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="">-- Pilih Role --</option>
                            <option value="siswa">Siswa</option>
                            <option value="pengurus">Pengurus</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-save w-100"><i class="fas fa-save me-2"></i> Simpan Pengguna</button>
                </form>
                <div class="text-center mt-4">
                    <a href="kelola_pengguna.php" class="btn-back"><i class="fas fa-arrow-left me-1"></i> Kembali ke Kelola Pengguna</a>
                </div>
            </div>
            <footer><i class="fas fa-user-plus"></i> Sistem Informasi Koperasi Sekolah | Tambah akses pengguna</footer>
            </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>