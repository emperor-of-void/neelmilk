<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Xử lý xóa
if (isset($_GET['delete_review'])) {
    $id = intval($_GET['delete_review']);
    $conn->query("DELETE FROM ratings WHERE id=$id");
    header("Location: manage_reviews.php?tab=reviews");
}
if (isset($_GET['delete_msg'])) {
    $id = intval($_GET['delete_msg']);
    $conn->query("DELETE FROM contact_messages WHERE id=$id");
    header("Location: manage_reviews.php?tab=messages");
}

// Lấy dữ liệu Đánh giá
$reviews = $conn->query("SELECT r.*, p.name as p_name, c.name as c_name FROM ratings r 
                         JOIN products p ON r.product_id = p.id 
                         JOIN customers c ON r.customer_id = c.id 
                         ORDER BY r.created_at DESC");

// Lấy dữ liệu Tin nhắn liên hệ
$messages = $conn->query("SELECT * FROM contact_messages ORDER BY created_at DESC");

$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'reviews';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phản Hồi & Đánh Giá - NeelMilk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-bg: #fffbf0;
            --brand-green: #2e7d5e;
            --brand-gold: #c5a059;
            --text-dark: #333;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }
        body { background-color: var(--admin-bg); font-family: "Quicksand", sans-serif; color: var(--text-dark); }
        
        .page-header { border-bottom: 2px solid rgba(197, 160, 89, 0.2); padding-bottom: 15px; margin-bottom: 30px; }
        .page-title { font-family: "Playfair Display", serif; font-size: 2rem; color: var(--brand-green); }
        
        .admin-card { background: #fff; border-radius: 25px; box-shadow: var(--shadow-soft); padding: 30px; border: none; min-height: 500px; }
        
        /* Tabs Styling */
        .nav-pills .nav-link {
            border-radius: 50px; padding: 10px 25px; color: #666; font-weight: 600; margin-right: 10px;
            background: #f5f5f5; transition: 0.3s;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--brand-green), #43a07a);
            color: #fff; box-shadow: 0 5px 15px rgba(46, 125, 94, 0.3);
        }
        
        /* List Item Styling */
        .item-row {
            background: #fff; border-bottom: 1px solid #f0f0f0; padding: 20px 0;
            transition: 0.2s; display: flex; align-items: flex-start;
        }
        .item-row:hover { background: #fdfdfd; transform: translateX(5px); }
        .item-row:last-child { border-bottom: none; }

        .avatar-circle {
            width: 50px; height: 50px; background: #e8f5e9; color: var(--brand-green);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 1.2rem; margin-right: 20px; flex-shrink: 0;
        }
        .star-rating { color: #ffc107; font-size: 0.9rem; }
        
        .btn-action {
            width: 35px; height: 35px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center;
            color: #555; background: #eee; text-decoration: none; transition: 0.2s; margin-left: 5px;
        }
        .btn-reply:hover { background: var(--brand-green); color: #fff; }
        .btn-delete:hover { background: #ff6b6b; color: #fff; }

        .msg-subject { font-weight: 700; color: var(--text-dark); display: block; margin-bottom: 5px; }
        .msg-content { color: #666; font-size: 0.95rem; line-height: 1.5; background: #f9f9f9; padding: 10px; border-radius: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Trung Tâm Phản Hồi</h2>
                <p class="text-muted mb-0">Quản lý đánh giá sản phẩm và tin nhắn khách hàng.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Dashboard</a>
        </div>

        <div class="admin-card">
            <!-- Tabs Navigation -->
            <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab=='reviews'?'active':'' ?>" id="tab-reviews" data-bs-toggle="pill" data-bs-target="#content-reviews" type="button">
                        <i class="fas fa-star me-2"></i>Đánh Giá Sản Phẩm
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link <?= $active_tab=='messages'?'active':'' ?>" id="tab-messages" data-bs-toggle="pill" data-bs-target="#content-messages" type="button">
                        <i class="fas fa-envelope me-2"></i>Tin Nhắn Liên Hệ
                    </button>
                </li>
            </ul>

            <div class="tab-content">
                
                <!-- TAB 1: ĐÁNH GIÁ SẢN PHẨM -->
                <div class="tab-pane fade <?= $active_tab=='reviews'?'show active':'' ?>" id="content-reviews">
                    <?php if ($reviews->num_rows > 0): ?>
                        <?php while ($r = $reviews->fetch_assoc()): ?>
                            <div class="item-row">
                                <div class="avatar-circle">
                                    <?= strtoupper(substr($r['c_name'], 0, 1)) ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($r['c_name']) ?></h6>
                                            <small class="text-muted">Đánh giá: <strong class="text-success"><?= htmlspecialchars($r['p_name']) ?></strong></small>
                                        </div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                                    </div>
                                    <div class="star-rating my-1">
                                        <?php for($i=1; $i<=5; $i++) echo ($i <= $r['rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </div>
                                    <p class="mb-0 text-secondary small">"<?= htmlspecialchars($r['comment']) ?>"</p>
                                </div>
                                <div class="ms-3 d-flex align-items-center">
                                    <a href="?delete_review=<?= $r['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Xóa đánh giá này?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">Chưa có đánh giá nào.</p>
                    <?php endif; ?>
                </div>

                <!-- TAB 2: TIN NHẮN LIÊN HỆ -->
                <div class="tab-pane fade <?= $active_tab=='messages'?'show active':'' ?>" id="content-messages">
                    <?php if ($messages->num_rows > 0): ?>
                        <?php while ($msg = $messages->fetch_assoc()): ?>
                            <div class="item-row">
                                <div class="avatar-circle" style="background: #fff3e0; color: #ef6c00;">
                                    <i class="far fa-user"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between mb-1">
                                        <div>
                                            <span class="fw-bold"><?= htmlspecialchars($msg['fullname']) ?></span>
                                            <span class="text-muted small ms-2">&lt;<?= htmlspecialchars($msg['email']) ?>&gt;</span>
                                        </div>
                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></small>
                                    </div>
                                    
                                    <span class="msg-subject">Tiêu đề: <?= htmlspecialchars($msg['subject']) ?></span>
                                    <div class="msg-content">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                </div>
                                <div class="ms-3 d-flex flex-column gap-2">
                                    <!-- Nút Reply: Mở Gmail soạn sẵn thư trả lời -->
                                    <a href="mailto:<?= $msg['email'] ?>?subject=Phản hồi: <?= urlencode($msg['subject']) ?>&body=Chào <?= urlencode($msg['fullname']) ?>,%0A%0ACảm ơn bạn đã liên hệ với NeelMilk..." 
                                       class="btn-action btn-reply" title="Trả lời qua Email">
                                        <i class="fas fa-reply"></i>
                                    </a>
                                    <a href="?delete_msg=<?= $msg['id'] ?>&tab=messages" class="btn-action btn-delete" onclick="return confirm('Xóa tin nhắn này?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-center text-muted py-5">Hộp thư đến trống.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>