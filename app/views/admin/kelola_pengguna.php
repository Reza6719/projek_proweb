<?php
session_start();
$conn = require __DIR__ . '/../../../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak!");
}

$success = "";
$error = "";

// Proses hapus pengguna (dengan menghapus data terkait)
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    
    // Mulai transaksi
    $conn->begin_transaction();
    try {
        // Hapus data pinjaman
        $stmt1 = $conn->prepare("DELETE FROM pinjaman WHERE user_id = ?");
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        
        // Hapus data tabungan
        $stmt2 = $conn->prepare("DELETE FROM tabungan WHERE user_id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        
        // Hapus data transaksi
        $stmt3 = $conn->prepare("DELETE FROM transaksi WHERE user_id = ?");
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        
        // Hapus user
        $stmt4 = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt4->bind_param("i", $id);
        $stmt4->execute();
        
        $conn->commit();
        $success = "✅ Pengguna dan semua data terkait berhasil dihapus.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = "❌ Gagal menghapus pengguna: " . $e->getMessage();
    }
}

// Ambil semua data pengguna
$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin</title>
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
        .card-table {
            border: none; border-radius: 28px; background: white; box-shadow: 0 20px 35px -10px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .btn-add {
            background: linear-gradient(90deg, #28a745, #34ce57);
            border: none;
            border-radius: 40px;
            padding: 8px 20px;
            color: white;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-add:hover { transform: translateY(-2px); background: #1e7e34; color: white; }
        .btn-edit, .btn-delete {
            border-radius: 30px;
            padding: 5px 16px;
            font-size: 0.8rem;
            font-weight: 500;
            margin: 0 4px;
            transition: 0.2s;
            border: none;
        }
        .btn-edit { background: #0077b6; color: white; }
        .btn-edit:hover { background: #005f8c; transform: translateY(-2px); }
        .btn-delete { background: #dc3545; color: white; }
        .btn-delete:hover { background: #c82333; transform: translateY(-2px); }
        .badge-role {
            padding: 6px 14px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .table-custom { border-radius: 20px; overflow: hidden; }
        .table-custom thead th { background: #f8fafc; border-bottom: 2px solid #e2e8f0; color: #1e2a3e; }
        .table-custom tbody tr:hover { background: #fef9e3; }
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
            <h4><i class="fas fa-cogs me-2"></i> Admin</h4>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="kelola_pengguna.php" class="active"><i class="fas fa-users"></i> Kelola Pengguna</a>
            <a href="kelola_parameter.php"><i class="fas fa-sliders-h"></i> Parameter Sistem</a>
            <a href="laporan_global.php"><i class="fas fa-chart-bar"></i> Laporan Global</a>
            <a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <main class="col-md-10 content">
            <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
                <h2 class="page-title"><i class="fas fa-users me-2"></i> Kelola Pengguna</h2>
                <div class="text-muted"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('d F Y'); ?></div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle"></i> <?php echo $success; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php elseif (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <div class="mb-3 text-end">
                <a href="tambah_pengguna.php" class="btn btn-add"><i class="fas fa-plus me-1"></i> Tambah Pengguna</a>
            </div>

            <div class="card-table">
                <div class="table-responsive">
                    <table class="table table-custom table-hover align-middle">
                        <thead>
                            <tr><th>ID</th><th>Username</th><th>Role</th><th>Tanggal Daftar</th><th>Aksi</th></tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <?php
                                    $created_at = !empty($row['created_at']) ? date('d-m-Y', strtotime($row['created_at'])) : '-';
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td>
                                            <?php if ($row['role'] == 'admin'): ?>
                                                <span class="badge-role bg-danger bg-opacity-25 text-danger"><i class="fas fa-user-shield"></i> Admin</span>
                                            <?php elseif ($row['role'] == 'pengurus'): ?>
                                                <span class="badge-role bg-primary bg-opacity-25 text-primary"><i class="fas fa-user-tie"></i> Pengurus</span>
                                            <?php else: ?>
                                                <span class="badge-role bg-success bg-opacity-25 text-success"><i class="fas fa-user-graduate"></i> Siswa</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $created_at; ?></td>
                                        <td>
                                            <a href="edit_pengguna.php?id=<?php echo $row['id']; ?>" class="btn-edit"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="kelola_pengguna.php?hapus=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('Yakin hapus pengguna ini?')"><i class="fas fa-trash"></i> Hapus</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada data pengguna</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <footer><i class="fas fa-users"></i> Sistem Informasi Koperasi Sekolah | Kelola akses pengguna</footer>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>