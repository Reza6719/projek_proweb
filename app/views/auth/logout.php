<?php
session_start(); // WAJIB: memulai session sebelum menghapus

// Hapus semua data session
$_SESSION = [];

// Hancurkan session
session_destroy();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Koperasi Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f2b3d 0%, #1a4a6f 100%);
            font-family: 'Poppins', 'Segoe UI', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .logout-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 480px;
            text-align: center;
            animation: fadeInUp 0.6s;
        }
        @keyframes fadeInUp {
            from { opacity:0; transform:translateY(30px);}
            to { opacity:1; transform:translateY(0);}
        }
        .icon-circle {
            width: 80px;
            height: 80px;
            background: linear-gradient(145deg, #28a745, #1e7e34);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 8px 20px rgba(40,167,69,0.3);
        }
        .icon-circle i { font-size: 3rem; color: white; }
        h3 { font-weight:700; color:#1e2a3e; margin-bottom:1rem; }
        .message { color:#4a5568; font-size:1rem; margin-bottom:1.8rem; line-height:1.5; }
        .btn-login {
            background: linear-gradient(90deg, #0077b6, #00b4d8);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 40px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,119,182,0.3);
        }
        .btn-login:hover {
            background: linear-gradient(90deg, #005f8c, #0096c7);
            transform: translateY(-2px);
            color: white;
        }
        .footer-note { margin-top:2rem; font-size:0.75rem; color:#6c757d; }
        @media (max-width:480px) {
            .logout-card { padding:1.8rem; }
            .icon-circle { width:60px; height:60px; }
            .icon-circle i { font-size:2.2rem; }
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <div class="icon-circle"><i class="fas fa-check-circle"></i></div>
        <h3>Anda Berhasil Logout</h3>
        <div class="message">
            <i class="fas fa-hand-peace me-1"></i> Terima kasih telah menggunakan<br>
            <strong>Sistem Informasi Koperasi Sekolah</strong>
        </div>
        <a href="login.php" class="btn-login">
            <i class="fas fa-sign-in-alt"></i> Kembali ke Login
        </a>
        <div class="footer-note"><i class="fas fa-lock"></i> Sesi Anda telah berakhir dengan aman</div>
    </div>
</body>
</html>