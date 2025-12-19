<?php
session_start();
include '../includes/config.php';

if (isset($_SESSION['admin_id'])) {
    // Giao diện thông báo đã đăng nhập (Soft Style)
    echo '<!DOCTYPE html>
    <html lang="vi">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Đã đăng nhập</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f8f9fa; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: "Segoe UI", sans-serif; }
            .notice-box { 
                border: none; padding: 40px; background: #fff; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.05); 
                text-align: center; border-radius: 20px;
            }
            .btn-logout { 
                background: linear-gradient(135deg, #d64161, #ff7b9c); 
                color: #fff; text-decoration: none; padding: 10px 25px; 
                display: inline-block; margin-top: 20px; font-weight: 600; 
                border-radius: 50px; box-shadow: 0 4px 15px rgba(214, 65, 97, 0.3);
                transition: 0.3s;
            }
            .btn-logout:hover { transform: translateY(-2px); color: #fff; box-shadow: 0 6px 20px rgba(214, 65, 97, 0.4); }
        </style>
    </head>
    <body>
        <div class="notice-box">
            <h4 class="text-success mb-3"><i class="fas fa-check-circle"></i> Bạn đang đăng nhập!</h4>
            <p class="text-muted">Hệ thống đang hoạt động bình thường.</p>
            <a href="dashboard.php" class="btn-logout me-2">Vào Dashboard</a>
            <a href="logout.php" class="btn btn-light rounded-pill px-4 mt-3 border">Đăng xuất</a>
        </div>
    </body>
    </html>';
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    error_log("Login attempt - Username: $username, Password: $password");

    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $error = 'Lỗi server, vui lòng thử lại sau!';
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        error_log("Admin query result: " . json_encode($admin));

        if ($admin && $admin['password'] === md5($password)) {
            $_SESSION['admin_id'] = $admin['id'];
            error_log("Login successful for admin_id: " . $admin['id']);
            header('Location: dashboard.php');
            exit();
        } else {
            error_log("Login failed for username: $username");
            $error = 'Sai tên đăng nhập hoặc mật khẩu!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Koal Bakery</title>
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Font chữ mềm mại hơn -->
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f4f7f6;
            /* Hình nền mờ nhẹ phía sau */
            background-image: url('../images/bread_illustration.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Lớp phủ trắng mờ để làm dịu background */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(255, 255, 255, 0.7); /* Độ mờ */
            backdrop-filter: blur(5px); /* Hiệu ứng kính mờ */
            z-index: -1;
        }

        .login-card {
            background: #fff;
            border-radius: 25px; /* Bo tròn góc lớn */
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08); /* Bóng đổ rất mềm */
            padding: 40px;
            width: 100%;
            max-width: 420px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.5);
        }

        /* Trang trí nhẹ trên đầu card */
        .card-header-deco {
            position: absolute;
            top: 0; left: 0; width: 100%;
            height: 6px;
            background: linear-gradient(90deg, #d64161, #ff7b9c);
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .brand-logo i {
            color: #d64161;
            font-size: 3rem;
            margin-bottom: 10px;
        }
        .login-title {
            font-weight: 700;
            color: #444;
        }

        /* Input Style: Hình viên thuốc (Pill) */
        .form-control {
            border-radius: 50px; /* Bo tròn input */
            padding: 12px 20px;
            border: 1px solid #eee;
            background: #f9f9f9;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: #fff;
            border-color: #d64161;
            box-shadow: 0 0 0 4px rgba(214, 65, 97, 0.1); /* Bóng hồng nhạt khi focus */
        }

        /* Input Icon */
        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
        }

        /* Button Style: Gradient & Shadow */
        .btn-soft {
            background: linear-gradient(135deg, #d64161, #ff7b9c);
            border: none;
            border-radius: 50px;
            padding: 12px;
            color: white;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            box-shadow: 0 4px 15px rgba(214, 65, 97, 0.3);
            transition: all 0.3s ease;
        }
        .btn-soft:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(214, 65, 97, 0.4);
            color: white;
        }

        /* Alert mềm mại */
        .alert-soft {
            border-radius: 15px;
            background: #fff0f3;
            color: #d64161;
            border: 1px solid #ffd1dc;
            font-size: 0.9rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
        }
        .back-link:hover {
            color: #d64161;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-header-deco"></div>
        
        <div class="brand-logo">
            <!-- Icon quản trị viên mềm mại -->
            <i class="fas fa-user-circle"></i>
            <h3 class="login-title">Quản Trị Viên</h3>
            <p class="text-muted small">Vui lòng đăng nhập để tiếp tục</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-soft d-flex align-items-center fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div><?php echo htmlspecialchars($error); ?></div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="input-wrapper">
                <input type="text" class="form-control" name="username" placeholder="Tên đăng nhập" required>
                <i class="fas fa-user input-icon"></i>
            </div>
            
            <div class="input-wrapper">
                <input type="password" class="form-control" name="password" placeholder="Mật khẩu" required>
                <i class="fas fa-lock input-icon"></i>
            </div>

            <button type="submit" class="btn btn-soft">
                Đăng Nhập
            </button>

            <a href="../index.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Quay lại trang chủ
            </a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>