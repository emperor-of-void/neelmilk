<?php
session_start();
include '../includes/config.php';

// Kiểm tra quyền Admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// (Tùy chọn) Lấy tên Admin nếu có trong session, không thì để mặc định
$admin_name = $_SESSION['admin_name'] ?? 'Administrator';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NeelMilk</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts: Playfair Display & Quicksand -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-bg: #fffbf0;      /* Nền kem mềm mại */
            --admin-card: #ffffff;
            --brand-green: #2e7d5e;
            --brand-gold: #c5a059;
            --text-dark: #333;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
            --shadow-hover: 0 20px 40px rgba(46, 125, 94, 0.15);
        }

        body {
            background-color: var(--admin-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Welcome Banner */
        .welcome-section {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff;
            padding: 40px;
            border-radius: 30px;
            margin-bottom: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(46, 125, 94, 0.3);
        }
        
        /* Họa tiết trang trí mờ */
        .welcome-section::after {
            content: '';
            position: absolute;
            top: -50%; right: -10%;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .welcome-title {
            font-family: "Playfair Display", serif;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        /* Dashboard Grid */
        .dashboard-card {
            background: var(--admin-card);
            border-radius: 25px;
            padding: 30px;
            height: 100%;
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.02);
            box-shadow: var(--shadow-soft);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            text-decoration: none; /* Để thẻ a không gạch chân */
            color: var(--text-dark);
        }

        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-hover);
            color: var(--brand-green);
        }

        /* Icon Container */
        .card-icon-box {
            width: 60px; height: 60px;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
            transition: 0.3s;
        }
        
        /* Màu sắc riêng cho từng loại thẻ */
        .card-product .card-icon-box { background: #e8f5e9; color: var(--brand-green); }
        .card-order .card-icon-box { background: #fff8e1; color: #ffa000; }
        .card-user .card-icon-box { background: #e3f2fd; color: #1976d2; }
        .card-stat .card-icon-box { background: #f3e5f5; color: #8e24aa; }

        .dashboard-card:hover .card-icon-box {
            transform: scale(1.1) rotate(5deg);
        }

        .card-title {
            font-family: "Playfair Display", serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .card-desc {
            font-size: 0.9rem;
            color: #888;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .card-arrow {
            margin-top: auto;
            width: 40px; height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            display: flex; align-items: center; justify-content: center;
            color: #aaa;
            transition: 0.3s;
        }
        .dashboard-card:hover .card-arrow {
            background: var(--brand-green);
            color: #fff;
            transform: translateX(10px);
        }

        /* Logout Button Style */
        .btn-logout-soft {
            background: #fff;
            color: #d32f2f;
            border: 1px solid #ffcdd2;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.1);
        }
        .btn-logout-soft:hover {
            background: #d32f2f;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(211, 47, 47, 0.2);
        }

        /* Quick Stats Row (Optional) */
        .stat-number {
            font-family: "Playfair Display", serif;
            font-size: 2rem;
            color: var(--brand-gold);
            font-weight: 700;
        }
    </style>
</head>
<body>
    
    <!-- Nếu header.php chứa navbar, nó sẽ hiển thị ở đây -->
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        
        <!-- Welcome Banner -->
        <div class="welcome-section d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h1 class="welcome-title">Xin chào, <?php echo htmlspecialchars($admin_name); ?>!</h1>
                <p class="mb-0 opacity-75">Chào mừng trở lại hệ thống quản trị NeelMilk.</p>
            </div>
            <div class="mt-3 mt-md-0">
                <span class="badge bg-white text-success rounded-pill px-3 py-2">
                    <i class="fas fa-circle me-2 small"></i>Hệ thống đang hoạt động
                </span>
            </div>
        </div>

        <!-- Quick Stats (Demo Layout - Bạn có thể thay số thật vào) -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-light text-center">
                    <p class="text-muted small text-uppercase mb-1 fw-bold">Đơn hàng mới</p>
                    <div class="stat-number">12</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-light text-center">
                    <p class="text-muted small text-uppercase mb-1 fw-bold">Doanh thu ngày</p>
                    <div class="stat-number">5.2M</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-light text-center">
                    <p class="text-muted small text-uppercase mb-1 fw-bold">Sản phẩm</p>
                    <div class="stat-number">48</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-light text-center">
                    <p class="text-muted small text-uppercase mb-1 fw-bold">Khách hàng</p>
                    <div class="stat-number">150</div>
                </div>
            </div>
        </div>

        <!-- Main Navigation Grid -->
        <h3 class="mb-4" style="font-family: 'Playfair Display', serif; color: var(--text-dark);">Quản Lý Hệ Thống</h3>
        
        <div class="row g-4">
    <!-- Hàng 1: 3 Thẻ -->
    <div class="col-md-6 col-lg-4">
        <a href="manage_products.php" class="dashboard-card card-product">
            <div class="card-icon-box"><i class="fas fa-box-open"></i></div>
            <h4 class="card-title">Sản Phẩm</h4>
            <p class="card-desc">Thêm mới, chỉnh sửa, cập nhật giá và kho hàng sữa tươi.</p>
            <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <div class="col-md-6 col-lg-4">
        <a href="manage_orders.php" class="dashboard-card card-order">
            <div class="card-icon-box"><i class="fas fa-shopping-bag"></i></div>
            <h4 class="card-title">Đơn Hàng</h4>
            <p class="card-desc">Xem danh sách đơn, cập nhật trạng thái giao hàng và thanh toán.</p>
            <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <div class="col-md-6 col-lg-4">
        <a href="manage_employees.php" class="dashboard-card card-user">
            <div class="card-icon-box"><i class="fas fa-users-cog"></i></div>
            <h4 class="card-title">Nhân Viên</h4>
            <p class="card-desc">Quản lý tài khoản truy cập, phân quyền và thông tin nhân sự.</p>
            <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <!-- Hàng 2: 2 Thẻ (Để cân đối) -->
    <div class="col-md-6 col-lg-6">
        <a href="statistics.php" class="dashboard-card card-stat">
            <div class="card-icon-box"><i class="fas fa-chart-pie"></i></div>
            <h4 class="card-title">Thống Kê</h4>
            <p class="card-desc">Báo cáo doanh thu, sản phẩm bán chạy và phân tích dữ liệu kinh doanh.</p>
            <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>

    <div class="col-md-6 col-lg-6">
        <a href="manage_reviews.php" class="dashboard-card card-feedback">
            <div class="card-icon-box" style="background: #fff3e0; color: #ff9800;"><i class="fas fa-comments"></i></div>
            <h4 class="card-title">Phản Hồi</h4>
            <p class="card-desc">Quản lý đánh giá sao và tin nhắn liên hệ từ khách hàng.</p>
            <div class="card-arrow"><i class="fas fa-arrow-right"></i></div>
        </a>
    </div>
</div>

        <!-- Logout Section -->
        <div class="mt-5 text-center border-top pt-4">
            <a href="logout.php" class="btn-logout-soft">
                <i class="fas fa-sign-out-alt me-2"></i> Đăng xuất hệ thống
            </a>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>