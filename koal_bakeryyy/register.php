<?php
session_start();
include 'includes/config.php';

// --- LOGIC PHP GIỮ NGUYÊN ---
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $confirm_password = filter_input(INPUT_POST, 'confirm_password', FILTER_SANITIZE_STRING);

    if ($password !== $confirm_password) {
        $error = 'Mật khẩu và xác nhận mật khẩu không khớp!';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO customers (name, email, password) VALUES (?, ?, ?)");
        if ($stmt === false) {
            $error = 'Lỗi truy vấn cơ sở dữ liệu.';
        } else {
            $stmt->bind_param("sss", $name, $email, $hashed_password);
            if ($stmt->execute()) {
                $success = "Đăng ký thành công, " . htmlspecialchars($name) . "! Vui lòng đăng nhập.";
            } else {
                $error = 'Email đã tồn tại hoặc lỗi hệ thống.';
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Koal Bakery</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts Sang trọng: Playfair Display & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --luxury-primary: #d64161; /* Màu thương hiệu */
            --luxury-gold: #c5a059;     /* Màu vàng kim */
            --soft-bg: #fffbf0;         /* Nền kem ấm */
            --text-dark: #2d2d2d;
        }

        body {
            background-color: var(--soft-bg);
            font-family: "Poppins", sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .register-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            /* Họa tiết nền chấm bi mờ sang trọng */
            background-image: radial-gradient(#e3d0b9 1px, transparent 1px);
            background-size: 25px 25px;
        }

        /* Card Container - Mềm mại, nổi khối nhẹ */
        .auth-card {
            background: #fff;
            width: 100%;
            max-width: 950px; /* Rộng hơn login chút */
            border: none;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06);
            display: flex;
            overflow: hidden;
            min-height: 650px;
        }

        /* Cột hình ảnh bên trái */
        .auth-image {
            flex: 1;
            background-image: url('images/bread_illustration.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .auth-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.6), rgba(214, 65, 97, 0.2));
        }
        
        /* Decor text trên ảnh */
        .auth-image-text {
            position: absolute;
            bottom: 50px; left: 40px; right: 40px;
            z-index: 2; color: #fff; text-align: center;
        }
        .cursive-title {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        /* Cột Form bên phải */
        .auth-form-container {
            flex: 1.1; /* Rộng hơn ảnh một chút */
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        h2.form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 5px;
        }

        .form-subtitle {
            color: #888;
            margin-bottom: 30px;
            font-weight: 300;
            font-size: 0.95rem;
        }

        /* Input Fields - Pill Shape */
        .form-control {
            border: 1px solid #eee;
            border-radius: 50px;
            padding: 12px 20px 12px 45px; /* Padding left để chừa chỗ cho icon */
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #fdfdfd;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--luxury-gold);
            box-shadow: 0 4px 15px rgba(197, 160, 89, 0.15);
        }

        /* Icon nằm trong Input */
        .input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }
        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #bbb;
            transition: 0.3s;
            z-index: 5;
        }
        .input-wrapper:focus-within .input-icon {
            color: var(--luxury-primary);
        }

        /* Button Luxury */
        .btn-luxury {
            background: linear-gradient(135deg, var(--luxury-primary), #ff9a9e);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 14px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            letter-spacing: 1px;
            font-size: 1rem;
            width: 100%;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 10px 20px rgba(214, 65, 97, 0.2);
        }
        .btn-luxury:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(214, 65, 97, 0.3);
            color: #fff;
        }

        /* Link styles */
        .link-gold {
            color: var(--luxury-gold);
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }
        .link-gold:hover {
            color: var(--luxury-primary);
        }

        /* Alert Messages */
        .alert-soft {
            border-radius: 20px;
            font-size: 0.9rem;
            border: none;
        }
        .alert-soft-success { background: #e8f5e9; color: #2e7d32; }
        .alert-soft-danger { background: #ffebee; color: #c62828; }

        /* Mobile */
        @media (max-width: 768px) {
            .auth-card { flex-direction: column; }
            .auth-image { min-height: 200px; display: none; }
            .auth-form-container { padding: 30px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="register-wrapper">
        <div class="auth-card">
            <!-- Cột trái: Hình ảnh Decor -->
            <div class="auth-image">
                <div class="auth-image-text">
                    <div class="cursive-title">Join Our Family</div>
                    <p class="small text-white-50">Experience the art of baking.</p>
                </div>
            </div>

            <!-- Cột phải: Form Đăng ký -->
            <div class="auth-form-container">
                <h2 class="form-title">Đăng Ký</h2>
                <p class="form-subtitle">Tạo tài khoản để nhận ngay ưu đãi thành viên.</p>

                <!-- Thông báo -->
                <?php if ($success): ?>
                    <div class="alert alert-soft alert-soft-success d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <div><?php echo $success; ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-soft alert-soft-danger d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Họ tên -->
                    <div class="input-wrapper">
                        <i class="far fa-user input-icon"></i>
                        <input type="text" class="form-control" name="name" placeholder="Họ và tên của bạn" required>
                    </div>

                    <!-- Email -->
                    <div class="input-wrapper">
                        <i class="far fa-envelope input-icon"></i>
                        <input type="email" class="form-control" name="email" placeholder="Địa chỉ Email" required>
                    </div>

                    <!-- Mật khẩu -->
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" class="form-control" name="password" placeholder="Tạo mật khẩu" required>
                    </div>

                    <!-- Xác nhận MK -->
                    <div class="input-wrapper">
                        <i class="fas fa-shield-alt input-icon"></i>
                        <input type="password" class="form-control" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                    </div>

                    <button type="submit" class="btn btn-luxury">
                        Tạo Tài Khoản
                    </button>

                    <p class="text-center mt-4 mb-0 text-muted small">
                        Đã là thành viên? 
                        <a href="login_customer.php" class="link-gold ms-1">Đăng nhập</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>