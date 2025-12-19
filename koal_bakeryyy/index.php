<?php
session_start();
include 'includes/config.php';

// --- 1. XỬ LÝ GỬI PHẢN HỒI KHÁCH HÀNG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_contact'])) {
    $c_name = filter_input(INPUT_POST, 'fullname', FILTER_SANITIZE_STRING);
    $c_email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $c_subject = filter_input(INPUT_POST, 'subject', FILTER_SANITIZE_STRING);
    $c_message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if ($c_name && $c_email && $c_message) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (fullname, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $c_name, $c_email, $c_subject, $c_message);
        
        if ($stmt->execute()) {
            echo "<script>alert('Cảm ơn bạn! Chúng tôi đã nhận được tin nhắn.'); window.location.href='index.php#contact-section';</script>";
        } else {
            echo "<script>alert('Có lỗi xảy ra. Vui lòng thử lại!');</script>";
        }
        $stmt->close();
    }
}

$success = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// --- 2. XỬ LÝ LOGIC SẢN PHẨM ---
$products = [];
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$limit = 6;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;

if ($search_query) :
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? LIMIT ? OFFSET ?");
    $search_term = "%" . $search_query . "%";
    $stmt->bind_param("sii", $search_term, $limit, $offset);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($products as $product) :
        $stmt_update = $conn->prepare("UPDATE products SET search_count = search_count + 1 WHERE id = ?");
        $stmt_update->bind_param("i", $product['id']);
        $stmt_update->execute();
        $stmt_update->close();
    endforeach;
else :
    $stmt = $conn->prepare("SELECT * FROM products LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
endif;
$stmt->close();

// Đếm tổng
$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM products" . ($search_query ? " WHERE name LIKE ?" : ""));
if ($search_query) :
    $total_stmt->bind_param("s", $search_term);
endif;
$total_stmt->execute();
$total_products = $total_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_products / $limit);
$total_stmt->close();

// Top Search
$top_searched = $conn->query("SELECT id, name, image, search_count, stock FROM products ORDER BY search_count DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>NeelMilk - Premium Fresh Milk</title>

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Fonts Luxury -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* --- GLOBAL SETTINGS --- */
    :root {
        --brand-green: #2e7d5e;
        --brand-gold: #c5a059;
        --soft-bg: #fffbf0;
        --text-dark: #333;
        --shadow-soft: 0 15px 40px rgba(0,0,0,0.04);
        --shadow-hover: 0 20px 50px rgba(46, 125, 94, 0.1);
    }

    body {
        background-color: var(--soft-bg);
        font-family: "Quicksand", sans-serif;
        color: var(--text-dark);
        background-image: radial-gradient(#e3d0b9 0.5px, transparent 0.5px);
        background-size: 20px 20px;
    }

    /* Header Adjustment */
    header, .navbar {
        position: absolute !important;
        top: 0; left: 0; width: 100%; z-index: 1000;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }

    /* Banner */
    #bannerCarousel { height: 100vh; position: relative; }
    .carousel-item { height: 100vh; }
    .carousel-item img { height: 100%; width: 100%; object-fit: cover; }
    .overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: linear-gradient(to bottom, rgba(46,125,94,0.1), rgba(0,0,0,0.4)); z-index: 2;
    }
    .carousel-caption {
        z-index: 10; bottom: 30%;
        background: rgba(255, 255, 255, 0.15); backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.3); padding: 40px 60px; border-radius: 40px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.1); max-width: 800px; margin: 0 auto; left: 0; right: 0;
        animation: floatUp 1s ease-out;
    }
    .carousel-caption h1 { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 3.5rem; text-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    @keyframes floatUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    .btn-banner {
        background: #fff; color: var(--brand-green); border: none; padding: 12px 35px; border-radius: 50px; font-weight: 700; letter-spacing: 1px; transition: 0.3s;
    }
    .btn-banner:hover { background: var(--brand-gold); color: #fff; }

    /* SECTION COMMON */
    .section-header { text-align: center; margin-bottom: 3rem; margin-top: 2rem; position: relative; }
    .section-title {
        font-family: 'Playfair Display', serif; font-weight: 700; color: var(--brand-green);
        font-size: 3rem; display: inline-block; position: relative; padding-bottom: 15px;
    }
    .section-title::after {
        content: '☘'; display: block; font-size: 1.5rem; color: var(--brand-gold); margin-top: 5px;
    }

    /* --- NEW: SERVICE FEATURES --- */
    .features-section {
        background: #fff; margin-top: -50px; position: relative; z-index: 20;
        border-radius: 30px; box-shadow: var(--shadow-soft); padding: 40px 20px;
        max-width: 1200px; margin-left: auto; margin-right: auto;
    }
    .feature-box { text-align: center; padding: 20px; transition: 0.3s; }
    .feature-box:hover { transform: translateY(-5px); }
    .feature-icon {
        font-size: 2.5rem; color: var(--brand-gold); margin-bottom: 15px;
        background: #fffbf0; width: 80px; height: 80px; line-height: 80px;
        border-radius: 50%; display: inline-block; transition: 0.3s;
    }
    .feature-box:hover .feature-icon { background: var(--brand-green); color: #fff; }
    .feature-title { font-family: 'Playfair Display'; font-weight: 700; font-size: 1.2rem; margin-bottom: 5px; }

    /* --- NEW: FLASH SALE SECTION --- */
    .flash-sale-section {
        background: linear-gradient(135deg, var(--brand-green), #43a07a);
        color: #fff;
        border-radius: 30px;
        padding: 60px;
        margin-top: 80px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 50px rgba(46, 125, 94, 0.3);
    }
    .flash-sale-section::before {
        content: ''; position: absolute; top: -50px; right: -50px; width: 300px; height: 300px;
        background: rgba(255,255,255,0.1); border-radius: 50%;
    }
    .countdown-item {
        background: rgba(255,255,255,0.2);
        width: 80px; height: 80px;
        border-radius: 15px;
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        margin-right: 15px;
        border: 1px solid rgba(255,255,255,0.3);
    }
    .countdown-number { font-family: 'Playfair Display'; font-size: 2rem; font-weight: 700; line-height: 1; }
    .countdown-label { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; }

    /* --- NEW: ABOUT US --- */
    .about-section { padding: 80px 0; }
    .about-img { border-radius: 30px; width: 100%; height: 450px; object-fit: cover; box-shadow: var(--shadow-hover); }
    .about-content { padding-left: 40px; display: flex; flex-direction: column; justify-content: center; height: 100%; }
    .about-subtitle { color: var(--brand-gold); text-transform: uppercase; letter-spacing: 2px; font-weight: 700; font-size: 0.9rem; }

    /* --- PRODUCT CARDS --- */
    .card-luxury {
        background: #fff; border: none; border-radius: 30px; box-shadow: var(--shadow-soft);
        transition: all 0.4s ease; height: 100%; overflow: hidden; display: flex; flex-direction: column;
    }
    .card-luxury:hover { transform: translateY(-10px); box-shadow: var(--shadow-hover); }
    .img-container { padding: 20px; background: #fff; }
    .product-img { width: 100%; height: 250px; object-fit: cover; border-radius: 20px; transition: 0.6s; }
    .card-luxury:hover .product-img { transform: scale(1.05); }
    .card-body-lux { padding: 0 25px 25px; display: flex; flex-direction: column; flex-grow: 1; text-align: center; }
    .prod-name { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.4rem; color: #222; margin-bottom: 10px; }
    .price-area { font-family: 'Quicksand'; font-size: 1.3rem; font-weight: 700; color: var(--brand-green); margin-bottom: 15px; }
    
    .btn-lux-icon {
        width: 45px; height: 45px; border-radius: 50%; border: 1px solid #eee; color: #555;
        display: flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.3s;
    }
    .btn-lux-icon:hover { background: var(--brand-green); color: #fff; border-color: var(--brand-green); }
    .btn-lux-buy {
        background: var(--brand-green); color: #fff; border-radius: 50px; padding: 10px 25px;
        font-weight: 600; text-decoration: none; display: flex; align-items: center; transition: 0.3s;
    }
    .btn-lux-buy:hover { background: var(--brand-gold); color: #fff; }
    .action-buttons { display: flex; gap: 10px; justify-content: center; margin-top: auto; }

    /* --- LUXURY PAGINATION --- */
    .pagination-luxury {
        display: flex; justify-content: center; align-items: center;
        gap: 12px; margin-top: 3rem; padding: 0; list-style: none;
    }
    .pagination-luxury .page-item { margin: 0; }
    .pagination-luxury .page-link {
        display: flex; align-items: center; justify-content: center;
        width: 50px; height: 50px; border-radius: 50% !important;
        border: none; background-color: #fff; color: #555;
        font-family: 'Quicksand', sans-serif; font-weight: 700; font-size: 1.1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05); transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        text-decoration: none;
    }
    .pagination-luxury .page-link:hover {
        transform: translateY(-5px); box-shadow: 0 15px 30px rgba(46, 125, 94, 0.15);
        color: var(--brand-gold); background-color: #fff;
    }
    .pagination-luxury .page-item.active .page-link {
        background: linear-gradient(135deg, var(--brand-green), #43a07a); color: #fff;
        box-shadow: 0 8px 20px rgba(46, 125, 94, 0.3); transform: scale(1.1);
    }
    .pagination-luxury .page-item.disabled .page-link {
        background-color: rgba(255,255,255,0.5); color: #ccc; box-shadow: none; pointer-events: none;
    }
    
    /* --- PROCESS SECTION --- */
    .process-section {
        padding: 80px 0;
        background-color: #fff; border-radius: 30px;
        margin-bottom: 50px; box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        overflow: hidden; position: relative;
    }
    .process-line {
        position: absolute; top: 35%; left: 10%; right: 10%;
        height: 2px; background: #eee; z-index: 0; display: none;
    }
    @media (min-width: 992px) { .process-line { display: block; } }
    .process-step { text-align: center; position: relative; z-index: 1; padding: 20px; transition: 0.3s; }
    .process-step:hover { transform: translateY(-10px); }
    .step-icon-box {
        width: 100px; height: 100px; background: #fff; border: 2px solid var(--brand-gold); border-radius: 50%;
        display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;
        box-shadow: 0 5px 15px rgba(197, 160, 89, 0.2); transition: 0.3s;
    }
    .process-step:hover .step-icon-box { background: var(--brand-green); border-color: var(--brand-green); }
    .process-step:hover .step-icon-box i { color: #fff !important; }
    .step-number {
        display: block; font-family: 'Playfair Display', serif; color: #e0e0e0;
        font-size: 3rem; font-weight: 900; position: absolute; top: 0; right: 20px; z-index: -1; opacity: 0.5;
    }
    .step-title { font-family: 'Playfair Display', serif; font-weight: 700; font-size: 1.2rem; color: var(--text-dark); margin-bottom: 10px; }
    .step-desc { font-size: 0.9rem; color: #666; line-height: 1.6; }

    /* --- CONTACT & STORE --- */
    .contact-section { background: #fff; border-radius: 30px; box-shadow: var(--shadow-soft); overflow: hidden; margin-top: 80px; }
    .map-container { height: 100%; min-height: 500px; background: #eee; position: relative; }
    .map-overlay-info {
        position: absolute; bottom: 20px; left: 20px; background: rgba(255,255,255,0.95);
        padding: 25px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 300px;
    }
    .form-lux input, .form-lux textarea {
        border: 1px solid #eee; border-radius: 15px; padding: 15px; background: #fcfcfc; margin-bottom: 15px; width: 100%;
    }
    .form-lux input:focus, .form-lux textarea:focus { outline: none; border-color: var(--brand-gold); background: #fff; }

    /* Top Search */
    .leaderboard-card {
        background: #fff; border-radius: 25px; padding: 20px; box-shadow: var(--shadow-soft);
        display: flex; align-items: center; transition: 0.3s; border: 1px solid #fcfcfc;
    }
    .leaderboard-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
    .rank-badge { font-family: 'Playfair Display'; font-size: 2rem; font-weight: 900; font-style: italic; margin-right: 20px; color: #eee; width: 40px; text-align: center; }
    .leaderboard-card:nth-child(1) .rank-badge { color: #ffd700; }
    .leaderboard-card:nth-child(2) .rank-badge { color: #c0c0c0; }
    .leaderboard-card:nth-child(3) .rank-badge { color: #cd7f32; }
    .top-thumb-lux { width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-right: 15px; }

    /* Back to Top Button */
    #btn-back-to-top {
        position: fixed; bottom: 20px; right: 20px; display: none;
        background: var(--brand-gold); color: #fff; width: 50px; height: 50px;
        border-radius: 50%; text-align: center; line-height: 50px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 999; cursor: pointer; transition: 0.3s;
    }
    #btn-back-to-top:hover { transform: translateY(-5px); background: var(--brand-green); }
    
    /* --- LUXURY MODAL STYLE --- */
    .luxury-modal {
        border-radius: 30px; /* Bo tròn khung modal */
        border: none;
        box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        background-color: #fff;
        background-image: radial-gradient(#e3d0b9 0.5px, transparent 0.5px); /* Họa tiết chấm bi mờ đồng bộ nền web */
        background-size: 20px 20px;
    }

    .luxury-modal .btn-close {
        background-color: #f0f0f0;
        border-radius: 50%;
        padding: 10px;
        margin: 10px;
        transition: 0.3s;
        opacity: 1;
    }
    .luxury-modal .btn-close:hover {
        background-color: var(--brand-gold);
        transform: rotate(90deg);
    }

    /* Value Box trong Modal */
    .value-box {
        background: #fff;
        padding: 20px;
        border-radius: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        height: 100%;
        transition: 0.3s;
        border: 1px solid #fcfcfc;
    }
    .value-box:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(46, 125, 94, 0.15);
    }

    .value-icon {
        width: 50px; height: 50px;
        background: #e8f5e9; /* Xanh nhạt */
        color: var(--brand-green);
        border-radius: 50%;
        display: inline-flex;
        align-items: center; justify-content: center;
        font-size: 1.2rem;
    }
</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<!-- 1. BANNER CAROUSEL -->
<div id="bannerCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="4000">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="0" class="active"></button>
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="1"></button>
        <button type="button" data-bs-target="#bannerCarousel" data-bs-slide-to="2"></button>
    </div>
    <div class="carousel-inner">
        <div class="carousel-item active">
            <div class="overlay"></div>
            <img src="images/banner1.jpg" class="d-block w-100" alt="Fresh Milk" onerror="this.src='https://images.unsplash.com/photo-1550583724-b2692b85b150?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'">
            <div class="carousel-caption text-center">
                <h1 class="display-4 mb-3">Tinh Hoa Sữa Việt</h1>
                <p class="lead mb-4">Vị ngọt lành tự nhiên từ thảo nguyên xanh.</p>
                <a href="#products-section" class="btn btn-banner">Mua ngay</a>
            </div>
        </div>
        <div class="carousel-item">
            <div class="overlay"></div>
            <img src="images/banner2.jpg" class="d-block w-100" onerror="this.src='https://images.unsplash.com/photo-1606923829579-0cb981a83e2e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'">
            <div class="carousel-caption text-center">
                <h1 class="display-4 mb-3">Khởi Đầu Hoàn Hảo</h1>
                <p class="lead mb-4">Năng lượng cho ngày mới.</p>
                <a href="#products-section" class="btn btn-banner">Khám phá</a>
            </div>
        </div>
        <div class="carousel-item">
            <div class="overlay"></div>
            <img src="images/banner3.jpg" class="d-block w-100" onerror="this.src='https://images.unsplash.com/photo-1563636619-e9143da7973b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'">
            <div class="carousel-caption text-center">
                <h1 class="display-4 mb-3">Giao Trọn Yêu Thương</h1>
                <p class="lead mb-4">Dịch vụ giao hàng cao cấp.</p>
                <a href="#products-section" class="btn btn-banner">Đặt hàng</a>
            </div>
        </div>
    </div>
</div>

<!-- 2. SERVICE FEATURES -->
<div class="container px-4">
    <div class="features-section">
        <div class="row">
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-truck"></i></div>
                    <h5 class="feature-title">Giao Hàng Nhanh</h5>
                    <p class="text-muted small">Miễn phí vận chuyển cho đơn từ 500k</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h5 class="feature-title">Chất Lượng 100%</h5>
                    <p class="text-muted small">Cam kết sữa tươi nguyên chất</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-headset"></i></div>
                    <h5 class="feature-title">Hỗ Trợ 24/7</h5>
                    <p class="text-muted small">Giải đáp mọi thắc mắc nhanh chóng</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-gift"></i></div>
                    <h5 class="feature-title">Ưu Đãi Hấp Dẫn</h5>
                    <p class="text-muted small">Nhiều quà tặng cho thành viên</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 3. FLASH SALE -->
<div class="container">
    <div class="flash-sale-section">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark mb-2 rounded-pill px-3">HOT DEAL</span>
                <h2 style="font-family: 'Playfair Display'; font-weight: 700; font-size: 2.5rem;">Ưu Đãi Giờ Vàng - Giảm 30%</h2>
                <p class="mb-4 opacity-75">Cơ hội duy nhất trong ngày! Áp dụng cho các dòng sữa hạt cao cấp. Nhanh tay đặt hàng trước khi thời gian kết thúc.</p>
                <div class="d-flex mb-4" id="countdown">
                    <div class="countdown-item">
                        <span class="countdown-number" id="hours">08</span>
                        <span class="countdown-label">Giờ</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="minutes">45</span>
                        <span class="countdown-label">Phút</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="seconds">30</span>
                        <span class="countdown-label">Giây</span>
                    </div>
                </div>
                <a href="#products-section" class="btn btn-light rounded-pill px-4 fw-bold" style="color: var(--brand-green)">Săn Deal Ngay</a>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <img src="https://images.unsplash.com/photo-1628088062854-d1870b4553da?q=80&w=1000&auto=format&fit=crop" style="width:100%; border-radius: 20px; transform: rotate(5deg); box-shadow: 0 15px 40px rgba(0,0,0,0.2);" alt="Flash Sale">
            </div>
        </div>
    </div>
</div>

<!-- 4. ABOUT US -->
<div class="container about-section" id="about-us">
    <div class="row align-items-center">
        <div class="col-md-6">
            <img src="images/sol.jpg" class="about-img" alt="About NeelMilk">
        </div>
        <div class="col-md-6">
            <div class="about-content">
                <span class="about-subtitle">Câu Chuyện Của Chúng Tôi</span>
                <h2 class="section-title" style="text-align: left; margin-bottom: 20px; font-size: 2.5rem;">NeelMilk - Khơi Nguồn Dinh Dưỡng</h2>
                <p class="text-muted" style="line-height: 1.8;">
                    Bắt nguồn từ những cánh đồng cỏ xanh mướt tại cao nguyên Đà Lạt, NeelMilk ra đời với sứ mệnh mang dòng sữa tươi thuần khiết nhất đến mọi gia đình Việt.
                </p>
                <p class="text-muted" style="line-height: 1.8;">
                    Quy trình khép kín từ nông trại đến bàn ăn ("Farm to Table") giúp chúng tôi giữ trọn vẹn hương vị tự nhiên và giá trị dinh dưỡng.
                </p>
                <div class="mt-4">
                    <!-- SỬA NÚT BẤM TẠI ĐÂY ĐỂ KÍCH HOẠT MODAL -->
                    <button type="button" class="btn btn-lux-buy d-inline-flex px-4" data-bs-toggle="modal" data-bs-target="#aboutModal">
                        Xem thêm <i class="fas fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 5. MAIN PRODUCTS -->
<div class="container py-5" id="products-section">
    <div class="section-header">
        <h2 class="section-title">Bộ Sưu Tập Sữa</h2>
        <p class="text-muted mt-2 fst-italic">"Taste the sweetness of life"</p>
    </div>

    <div class="row g-5">
        <?php foreach ($products as $product): ?>
        <div class="col-md-4 mb-5">
            <div class="card-luxury h-100">
                <div class="img-container">
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" class="product-img" alt="<?= htmlspecialchars($product['name']) ?>">
                </div>
                <div class="card-body-lux">
                    <div class="prod-category">Premium Milk</div>
                    <h5 class="prod-name"><?= htmlspecialchars($product['name']) ?></h5>
                    <div class="mb-2">
                        <?php if ($product['stock'] <= 0): ?>
                            <span class="badge-lux bg-lux-danger">Hết hàng</span>
                        <?php elseif ($product['stock'] <= 5): ?>
                            <span class="badge-lux bg-lux-warning">Chỉ còn <?= $product['stock'] ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="prod-desc"><?= htmlspecialchars($product['description']) ?></p>
                    <div class="price-area"><?= number_format($product['price']) ?> VNĐ</div>
                    <div class="action-buttons">
                        <a href="product_detail.php?id=<?= $product['id'] ?>" class="btn-lux-icon" title="Xem chi tiết"><i class="fa-regular fa-eye"></i></a>
                        <?php if ($product['stock'] > 0): ?>
                            <a href="add_to_cart.php?product_id=<?= $product['id'] ?>" class="btn-lux-buy"><i class="fa-solid fa-basket-shopping me-2"></i> Thêm</a>
                        <?php else: ?>
                             <button class="btn-lux-buy" style="background: #ccc; cursor: not-allowed;" disabled>Hết hàng</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination Luxury -->
    <?php if ($total_pages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination-luxury">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Previous">
                    <i class="fas fa-chevron-left" style="font-size: 0.9rem;"></i>
                </a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Next">
                    <i class="fas fa-chevron-right" style="font-size: 0.9rem;"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<!-- 6. PROCESS SECTION (HÀNH TRÌNH SỮA SẠCH) -->
<div class="container">
    <div class="process-section">
        <div class="section-header">
            <h2 class="section-title">Hành Trình Giọt Sữa Sạch</h2>
            <p class="text-muted">Quy trình khép kín giữ trọn dinh dưỡng thiên nhiên</p>
        </div>

        <div class="process-line"></div>

        <div class="row">
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <span class="step-number">01</span>
                    <div class="step-icon-box"><i class="fas fa-tractor fa-2x" style="color: var(--brand-green);"></i></div>
                    <h5 class="step-title">Nông Trại Xanh</h5>
                    <p class="step-desc">Bò sữa được chăn thả tự nhiên trên thảo nguyên Đà Lạt, ăn cỏ tươi và nghe nhạc mỗi ngày.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <span class="step-number">02</span>
                    <div class="step-icon-box"><i class="fas fa-flask fa-2x" style="color: var(--brand-green);"></i></div>
                    <h5 class="step-title">Công Nghệ Lạnh</h5>
                    <p class="step-desc">Sữa được làm lạnh xuống 4°C ngay sau khi vắt để ngăn chặn vi khuẩn xâm nhập.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <span class="step-number">03</span>
                    <div class="step-icon-box"><i class="fas fa-check-double fa-2x" style="color: var(--brand-green);"></i></div>
                    <h5 class="step-title">Kiểm Định Kép</h5>
                    <p class="step-desc">Trải qua 12 bước kiểm tra nghiêm ngặt về an toàn vệ sinh thực phẩm chuẩn Châu Âu.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="process-step">
                    <span class="step-number">04</span>
                    <div class="step-icon-box"><i class="fas fa-shipping-fast fa-2x" style="color: var(--brand-green);"></i></div>
                    <h5 class="step-title">Giao Tận Tay</h5>
                    <p class="step-desc">Đóng gói bao bì sinh học và giao đến cửa nhà bạn trong vòng 24h.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 7. TOP SEARCH -->
<div class="container mt-5 pt-5">
    <h4 class="text-center mb-5" style="font-family: 'Playfair Display'; font-weight: 700; font-size: 2rem; color: #444;">
        <span style="border-bottom: 3px solid var(--brand-gold);">Xu Hướng Tìm Kiếm</span>
    </h4>
    <div class="row justify-content-center g-4">
        <?php foreach ($top_searched as $index => $product): ?>
        <div class="col-lg-4 col-md-6">
            <a href="product_detail.php?id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                <div class="leaderboard-card">
                    <div class="rank-badge"><?= $index + 1 ?></div>
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" class="top-thumb-lux">
                    <div>
                        <h6 class="fw-bold mb-1" style="font-family: 'Quicksand'; font-size: 1.1rem;"><?= htmlspecialchars($product['name']) ?></h6>
                        <small class="text-muted fst-italic"><i class="fas fa-fire text-danger me-1"></i> <?= $product['search_count'] ?> lượt quan tâm</small>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 8. CONTACT & STORE SYSTEM -->
<div class="container pb-5 mb-5" id="contact-section">
    <div class="section-header">
        <h2 class="section-title">Liên Hệ & Cửa Hàng</h2>
        <p class="text-muted">Ghé thăm chúng tôi hoặc để lại lời nhắn</p>
    </div>

    <div class="contact-section">
        <div class="row g-0">
            <!-- Cột Trái: Bản đồ -->
            <div class="col-lg-6">
                <div class="map-container" style="position: relative; height: 100%; min-height: 500px;">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3723.863855881404!2d105.74459307498152!3d21.038132780613566!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x313454b991d80fd5%3A0x53cefc99d6b0bf86!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBDw7RuZyBuZ2jhu4cgxJDDtG5nIMOB!5e0!3m2!1svi!2s!4v1709223849000!5m2!1svi!2s" 
                        width="100%" height="100%" style="border:0; filter: saturate(0.8);" 
                        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                    <div class="map-overlay-info">
                        <h5 style="font-family: 'Playfair Display'; font-weight:700; color: var(--brand-green); margin-bottom: 15px;">Trụ Sở Chính</h5>
                        <div class="d-flex align-items-start mb-3">
                            <i class="fas fa-map-marker-alt text-danger mt-1 me-3"></i>
                            <div>
                                <strong style="font-size: 0.9rem;">EAUT - Polytech</strong><br>
                                <span class="text-muted small">Trịnh Văn Bô, Nam Từ Liêm, Hà Nội</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-phone-alt text-success me-3"></i>
                            <span class="text-dark fw-bold">0383 356 361</span>
                        </div>
                        <div class="d-flex align-items-center mb-4">
                            <i class="fas fa-clock text-warning me-3"></i>
                            <span class="text-muted small">8:00 - 22:00 (Hàng ngày)</span>
                        </div>
                        <a href="https://www.google.com/maps/dir//Trường+Đại+học+Công+nghệ+Đông+Á,+P.+Trịnh+Văn+Bô,+Xuân+Phương,+Nam+Từ+Liêm,+Hà+Nội" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill w-100 fw-bold py-2" style="border-color: var(--brand-green); color: var(--brand-green);">
                            <i class="fas fa-location-arrow me-2"></i> Dẫn đường ngay
                        </a>
                    </div>
                </div>
            </div>

            <!-- Cột Phải: Form Liên hệ -->
            <div class="col-lg-6 p-5">
                <h3 style="font-family: 'Playfair Display'; color: var(--brand-green); margin-bottom: 10px;">Gửi Lời Nhắn</h3>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">Bạn có thắc mắc về sản phẩm hoặc muốn hợp tác? Hãy điền thông tin bên dưới, chúng tôi sẽ phản hồi sớm nhất.</p>
                
                <form class="form-lux" method="POST" action="">
                    <input type="hidden" name="submit_contact" value="1">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" name="fullname" placeholder="Họ tên của bạn" required>
                        </div>
                        <div class="col-md-6">
                            <input type="email" name="email" placeholder="Email liên hệ" required>
                        </div>
                    </div>
                    <input type="text" name="subject" placeholder="Tiêu đề / Vấn đề cần hỗ trợ" required>
                    <textarea name="message" rows="5" placeholder="Nội dung lời nhắn..." required style="resize: none;"></textarea>
                    <div class="text-end">
                        <button type="submit" class="btn btn-lux-buy px-5 py-3 shadow-sm">
                            Gửi Đi <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ABOUT US MODAL (ĐÃ SẮP XẾP LẠI VỊ TRÍ CHUẨN) -->
<div class="modal fade" id="aboutModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content luxury-modal">
            
            <!-- Header: Nút tắt -->
            <div class="modal-header border-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Body: Nội dung câu chuyện -->
            <div class="modal-body px-5 pb-5">
                <div class="text-center mb-4">
                    <h5 class="text-uppercase text-muted small" style="letter-spacing: 2px;">Câu Chuyện Thương Hiệu</h5>
                    <h2 style="font-family: 'Playfair Display'; font-weight: 700; color: var(--brand-green); font-size: 2.5rem;">
                        Hành Trình Từ Trái Tim
                    </h2>
                    <div style="width: 60px; height: 3px; background: var(--brand-gold); margin: 15px auto;"></div>
                </div>

                <p class="text-muted text-center mb-5" style="line-height: 1.8;">
                    NeelMilk không chỉ là một công ty sản xuất sữa, chúng tôi là những người nông dân mang trong mình tình yêu với thiên nhiên. Khởi đầu từ năm 2015 với 50 chú bò sữa thuần chủng tại Đà Lạt, đến nay NeelMilk đã trở thành biểu tượng của dòng sữa sạch, giữ trọn vẹn hương vị nguyên bản.
                </p>

                <div class="row g-4 text-center mb-5">
                    <div class="col-md-4">
                        <div class="value-box">
                            <div class="value-icon"><i class="fas fa-leaf"></i></div>
                            <h6 class="fw-bold mt-3" style="color: var(--brand-green)">100% Tự Nhiên</h6>
                            <p class="small text-muted">Không hóc môn tăng trưởng, không chất bảo quản.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="value-box">
                            <div class="value-icon"><i class="fas fa-heart"></i></div>
                            <h6 class="fw-bold mt-3" style="color: var(--brand-green)">Quy Trình Nhân Văn</h6>
                            <p class="small text-muted">Đàn bò được nghe nhạc và mát-xa mỗi ngày.</p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="value-box">
                            <div class="value-icon"><i class="fas fa-award"></i></div>
                            <h6 class="fw-bold mt-3" style="color: var(--brand-green)">Chuẩn Quốc Tế</h6>
                            <p class="small text-muted">Đạt chứng nhận ISO 22000 & VietGAP.</p>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <p class="fst-italic text-muted mb-2">"Chúng tôi tin rằng, một ly sữa ngon bắt đầu từ sự hạnh phúc của đàn bò."</p>
                    <h5 style="font-family: 'Playfair Display'; color: var(--text-dark);">Lý Ngọc Quân</h5>
                    <span class="text-muted small text-uppercase">Founder & CEO NeelMilk</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Back to Top Button -->
<div id="btn-back-to-top"><i class="fas fa-arrow-up"></i></div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Flash Sale Countdown
    function updateCountdown() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(now.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        const diff = tomorrow - now;
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        document.getElementById("hours").innerText = hours < 10 ? "0" + hours : hours;
        document.getElementById("minutes").innerText = minutes < 10 ? "0" + minutes : minutes;
        document.getElementById("seconds").innerText = seconds < 10 ? "0" + seconds : seconds;
    }
    setInterval(updateCountdown, 1000);
    updateCountdown();

    // Back to Top
    const backBtn = document.getElementById("btn-back-to-top");
    window.onscroll = function() {
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            backBtn.style.display = "block";
        } else {
            backBtn.style.display = "none";
        }
    };
    backBtn.addEventListener("click", function() {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
</script>
</body>
</html>