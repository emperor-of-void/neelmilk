<?php
session_start();
include 'includes/config.php';

// --- GIỮ NGUYÊN LOGIC PHP ---
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT id, name, password FROM customers WHERE email = ?");
    if ($stmt === false) {
        $error = 'Lỗi truy vấn cơ sở dữ liệu.';
    } else {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['customer_id'] = $user['id'];
                $_SESSION['customer_name'] = $user['name']; 
                header('Location: index.php');
                exit;
            } else {
                $error = 'Email hoặc mật khẩu không đúng!';
            }
        } else {
            $error = 'Email hoặc mật khẩu không đúng!';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Koal Bakery</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts Sang trọng: Playfair Display (Tiêu đề) & Poppins (Nội dung) -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Poppins:wght@300;400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --luxury-primary: #d64161; /* Màu thương hiệu */
            --luxury-gold: #c5a059;     /* Màu vàng kim sang trọng */
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

        .login-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            /* Hình nền hoa văn mờ nhẹ nếu muốn */
            background-image: radial-gradient(#e8dwr4 1px, transparent 1px);
            background-size: 20px 20px;
        }

        /* Card thiết kế kiểu Luxury Soft */
        .auth-card {
            background: #fff;
            width: 100%;
            max-width: 900px;
            border: none;
            border-radius: 30px; /* Bo tròn lớn */
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.05); /* Bóng đổ rất mờ và mịn */
            display: flex;
            overflow: hidden;
            min-height: 600px;
        }

        /* Phần hình ảnh bên trái */
        .auth-image {
            flex: 1;
            background-image: url('images/bread_illustration.png');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        /* Lớp phủ gradient sang trọng lên ảnh */
        .auth-image::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(214, 65, 97, 0.2), rgba(0, 0, 0, 0.5));
        }
        
        /* Text trang trí trên ảnh */
        .auth-image-content {
            position: absolute;
            bottom: 40px; left: 40px; right: 40px;
            z-index: 2; color: #fff;
        }
        .quote-text {
            font-family: 'Playfair Display', serif;
            font-style: italic;
            font-size: 1.5rem;
            text-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        /* Phần Form bên phải */
        .auth-form-container {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: #fff;
        }

        h2.form-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            color: var(--text-dark);
        }

        .form-subtitle {
            color: #888;
            margin-bottom: 40px;
            font-weight: 300;
        }

        /* Input Fields mềm mại */
        .form-control {
            border: 1px solid #eee;
            border-radius: 50px; /* Hình viên thuốc */
            padding: 14px 25px;
            font-size: 0.95rem;
            transition: all 0.3s;
            background: #fcfcfc;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--luxury-gold);
            box-shadow: 0 5px 15px rgba(197, 160, 89, 0.15);
        }

        /* Input Icon styling */
        .input-group {
            position: relative;
        }
        .input-icon-wrapper {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #ccc;
            z-index: 5;
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
            margin-top: 20px;
            box-shadow: 0 10px 20px rgba(214, 65, 97, 0.2);
        }
        .btn-luxury:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(214, 65, 97, 0.3);
            color: #fff;
        }

        /* Link & Checkbox */
        .form-check-input {
            cursor: pointer;
            border-color: #ddd;
        }
        .form-check-input:checked {
            background-color: var(--luxury-primary);
            border-color: var(--luxury-primary);
        }
        
        .link-gold {
            color: var(--luxury-gold);
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }
        .link-gold:hover {
            color: var(--luxury-primary);
            text-decoration: underline;
        }

        /* Alert Box */
        .alert-soft {
            border-radius: 20px;
            background-color: #fff5f5;
            color: #e53935;
            border: 1px solid #ffebee;
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-card { flex-direction: column; min-height: auto; }
            .auth-image { min-height: 200px; display: none; /* Ẩn ảnh trên mobile để gọn */ }
            .auth-form-container { padding: 30px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="login-wrapper">
        <div class="auth-card">
            <!-- Cột trái: Hình ảnh với Quote sang trọng -->
            <div class="auth-image">
                <div class="auth-image-content">
                    <p class="quote-text">"Taste the sweetness of life in every bite."</p>
                </div>
            </div>

            <!-- Cột phải: Form -->
            <div class="auth-form-container">
                <h2 class="form-title">Chào mừng</h2>
                <p class="form-subtitle">Hãy đăng nhập để thưởng thức hương vị tuyệt vời.</p>

                <?php if ($error): ?>
                    <div class="alert alert-soft d-flex align-items-center mb-4" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <!-- Email Input -->
                    <div class="mb-4 position-relative">
                        <label class="form-label text-muted small ms-3">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="name@example.com" required>
                        <div class="input-icon-wrapper">
                            <i class="far fa-envelope"></i>
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="mb-3 position-relative">
                        <label class="form-label text-muted small ms-3">Mật khẩu</label>
                        <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                        <div class="input-icon-wrapper">
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
                        <div class="form-check ms-1">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label small text-muted" for="remember">Ghi nhớ</label>
                        </div>
                        <a href="#" class="small link-gold">Quên mật khẩu?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-luxury">
                        Đăng Nhập
                    </button>

                    <!-- Register Link -->
                    <p class="text-center mt-5 mb-0 text-muted small">
                        Chưa là thành viên? 
                        <a href="register.php" class="link-gold fw-bold ms-1">Đăng ký ngay</a>
                    </p>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>