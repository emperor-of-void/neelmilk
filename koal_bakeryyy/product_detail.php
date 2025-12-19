<?php
session_start();
include 'includes/config.php';

// --- LOGIC PHP GIỮ NGUYÊN ---
$DEBUG = true;
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Lỗi kết nối CSDL");
}
$product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$product_id) {
    header('Location:index.php'); exit;
}
if ($DEBUG) mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
if (!$stmt) {
    if ($DEBUG) die('Prepare failed: '.$conn->error);
    header('Location:index.php'); exit;
}
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { header('Location:index.php'); exit; }

// Gửi đánh giá
if (isset($_SESSION['customer_id']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rating'])) {
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    if ($rating !== false && $rating >= 1 && $rating <= 5) {
        $ins = $conn->prepare("INSERT INTO ratings (product_id, customer_id, rating, comment, created_at) VALUES (?,?,?,?,NOW())");
        if ($ins) {
            $cust = (int)$_SESSION['customer_id'];
            $ins->bind_param("iiis", $product_id, $cust, $rating, $comment);
            $ins->execute();
            $ins->close();
            $_SESSION['success'] = 'Cảm ơn bạn đã gửi đánh giá!';
        }
    }
    header('Location: product_detail.php?id='.$product_id);
    exit;
}

// Lấy rating trung bình
$avg_rating = 0;
$total_ratings = 0;
$avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM ratings WHERE product_id = ?");
if ($avg_stmt) {
    $avg_stmt->bind_param("i", $product_id);
    $avg_stmt->execute();
    $res = $avg_stmt->get_result()->fetch_assoc();
    $avg_stmt->close();
    $avg_rating = round($res['avg_rating'] ?? 0, 1);
    $total_ratings = $res['cnt'] ?? 0;
}

// Lấy 5 đánh giá mới nhất
$reviews = [];
$rev = $conn->prepare("SELECT r.rating, r.comment, r.created_at, c.name FROM ratings r JOIN customers c ON r.customer_id=c.id WHERE r.product_id=? ORDER BY r.created_at DESC LIMIT 5");
if ($rev) {
    $rev->bind_param("i", $product_id);
    $rev->execute();
    $reviews = $rev->get_result()->fetch_all(MYSQLI_ASSOC);
    $rev->close();
}
?>

<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($product['name']) ?> - NeelMilk Premium</title>

<!-- Bootstrap & Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Fonts Luxury & Soft -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --brand-green: #2e7d5e;
        --brand-gold: #c5a059;
        --soft-bg: #fffbf0;
        --text-dark: #333;
        --shadow-soft: 0 15px 40px rgba(0,0,0,0.05);
    }

    body { 
        background-color: var(--soft-bg); 
        font-family: 'Quicksand', sans-serif; 
        color: var(--text-dark);
    }

    /* Container chính */
    .container-max { 
        max-width: 1100px; 
        margin: 40px auto; 
        padding: 0 20px; 
    }

    /* Card sản phẩm */
    .product-card {
        background: #fff;
        border-radius: 30px;
        box-shadow: var(--shadow-soft);
        padding: 40px;
        margin-bottom: 40px;
    }

    /* Hình ảnh sản phẩm */
    .product-image-wrapper {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    }
    img.product-image { 
        width: 100%; 
        transition: transform 0.5s ease; 
        object-fit: cover;
    }
    .product-image-wrapper:hover img.product-image {
        transform: scale(1.05);
    }

    /* Thông tin sản phẩm */
    .product-title { 
        font-family: 'Playfair Display', serif; 
        font-size: 2.5rem; 
        color: var(--brand-green);
        margin-bottom: 15px;
        line-height: 1.2;
    }

    .price-tag { 
        font-size: 2rem; 
        color: var(--brand-green); 
        font-weight: 700; 
        margin: 20px 0;
        font-family: 'Playfair Display', serif;
    }

    .rating-badge {
        background: #fff8e1;
        color: #ffa000;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-block;
    }

    /* Nút bấm */
    .btn-add-cart {
        background: linear-gradient(135deg, var(--brand-green), #43a07a);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 1.1rem;
        box-shadow: 0 8px 20px rgba(46, 125, 94, 0.25);
        transition: 0.3s;
    }
    .btn-add-cart:hover {
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(46, 125, 94, 0.35);
        color: white;
    }

    .btn-back {
        border: 2px solid #eee;
        border-radius: 50px;
        padding: 10px 25px;
        color: #777;
        font-weight: 600;
        transition: 0.2s;
        text-decoration: none;
        display: inline-block;
    }
    .btn-back:hover { border-color: var(--brand-green); color: var(--brand-green); }

    /* Nội dung mô tả */
    .content-box {
        margin-top: 30px;
        padding-top: 30px;
        border-top: 1px dashed #ddd;
    }
    .section-header {
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
        margin-bottom: 15px;
        color: #444;
        position: relative;
        padding-left: 15px;
    }
    .section-header::before {
        content: ''; position: absolute; left: 0; top: 5px; bottom: 5px;
        width: 4px; background: var(--brand-gold); border-radius: 2px;
    }

    /* Review Section */
    .review-section {
        background: #fff;
        border-radius: 30px;
        padding: 40px;
        box-shadow: var(--shadow-soft);
    }

    .review-item { 
        background: #fcfcfc; 
        padding: 20px; 
        border-radius: 20px; 
        border: 1px solid #f0f0f0;
        margin-bottom: 15px; 
        transition: 0.2s;
    }
    .review-item:hover { background: #fff; box-shadow: 0 5px 15px rgba(0,0,0,0.03); }

    .avatar-circle {
        width: 45px; height: 45px; 
        background: linear-gradient(135deg, #eee, #ddd);
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: bold; color: #555; font-size: 1.2rem;
        margin-right: 15px;
    }

    /* Star Rating Input */
    .star-rating.selectable i {
        cursor: pointer; color: #ddd; transition: 0.2s;
    }
    .star-rating.selectable i.fas { color: #ffc107; drop-shadow: 0 2px 4px rgba(255, 193, 7, 0.3); }
    .star-rating.selectable i:hover { transform: scale(1.2); }

    textarea.form-control {
        border-radius: 20px;
        background: #f9f9f9;
        border: 1px solid #eee;
        padding: 15px;
    }
    textarea.form-control:focus {
        background: #fff;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-color: var(--brand-green);
    }

</style>
</head>
<body>

<?php include 'includes/header.php'; ?>

<div class="container-max">
    
    <!-- Product Main Info -->
    <div class="product-card">
        <div class="row g-5">
            <div class="col-md-5">
                <div class="product-image-wrapper">
                    <img src="images/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image">
                </div>
            </div>
            
            <div class="col-md-7">
                <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

                <div class="d-flex align-items-center mb-3">
                    <div class="rating-badge me-3">
                        <i class="fas fa-star me-1"></i> <?= $avg_rating ?> / 5
                    </div>
                    <span class="text-muted small">(<?= $total_ratings ?> đánh giá từ khách hàng)</span>
                </div>

                <div class="price-tag"><?= number_format($product['price']) ?> <small>VNĐ</small></div>

                <p class="text-secondary mb-4" style="line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($product['description'] ?? 'Mô tả đang cập nhật...')) ?>
                </p>

                <div class="d-flex gap-3 align-items-center">
                    <a href="#" onclick="addToCart(<?= $product['id'] ?>);return false;" class="btn-add-cart">
                        <i class="fa-solid fa-cart-plus me-2"></i>Thêm vào giỏ
                    </a>
                    <a href="index.php" class="btn-back">
                        <i class="fas fa-arrow-left me-1"></i> Quay lại
                    </a>
                </div>
                
                <!-- Nội dung chi tiết -->
                <div class="content-box">
                    <h4 class="section-header">Chi tiết sản phẩm</h4>
                    <p class="text-secondary" style="line-height: 1.8;">
                        <?= nl2br(htmlspecialchars($product['content'] ?? 'Đang cập nhật nội dung chi tiết cho sản phẩm này.')) ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Review Section -->
    <div class="review-section">
        <h3 class="section-header mb-4">Đánh giá từ khách hàng</h3>

        <!-- Form gửi đánh giá -->
        <?php if (isset($_SESSION['customer_id'])): ?>
            <div class="card border-0 bg-light rounded-4 p-4 mb-5">
                <form method="POST">
                    <label class="fw-bold mb-2 text-muted small text-uppercase">Đánh giá của bạn</label>
                    <div class="star-rating selectable fs-3 mb-3">
                        <i class="far fa-star" data-rating="1"></i>
                        <i class="far fa-star" data-rating="2"></i>
                        <i class="far fa-star" data-rating="3"></i>
                        <i class="far fa-star" data-rating="4"></i>
                        <i class="far fa-star" data-rating="5"></i>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="0">
                    
                    <div class="mb-3">
                        <textarea name="comment" class="form-control" rows="3" placeholder="Chia sẻ cảm nhận của bạn về sản phẩm này..."></textarea>
                    </div>
                    <button class="btn-add-cart py-2 px-4 fs-6" id="submit-rating" disabled>
                        Gửi đánh giá
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-warning rounded-pill px-4 d-inline-block mb-4">
                <i class="fas fa-lock me-2"></i>Vui lòng <a href="login_customer.php" class="fw-bold text-dark">đăng nhập</a> để viết đánh giá.
            </div>
        <?php endif; ?>

        <!-- Danh sách đánh giá -->
        <div class="review-list">
            <?php if (!empty($reviews)): foreach($reviews as $r): ?>
                <div class="review-item d-flex">
                    <div class="avatar-circle">
                        <?= strtoupper(substr($r['name'], 0, 1)) ?>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($r['name']) ?></h6>
                            <small class="text-muted" style="font-size: 0.8rem"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                        </div>
                        <div class="text-warning my-1" style="font-size: 0.85rem">
                            <?php for($i=1;$i<=5;$i++): ?>
                                <i class="<?= $i <= $r['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($r['comment'])): ?>
                            <p class="text-secondary mb-0 mt-2"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="far fa-comment-dots fa-3x mb-3 opacity-50"></i>
                    <p>Chưa có đánh giá nào. Hãy là người đầu tiên chia sẻ cảm nhận!</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function addToCart(id){ window.location.href = 'add_to_cart.php?product_id='+id+'&qty=1'; }

// Xử lý chọn sao đánh giá
document.addEventListener('DOMContentLoaded', function(){
    const starContainer = document.querySelector('.star-rating.selectable');
    if (!starContainer) return;
    
    const stars = starContainer.querySelectorAll('i');
    const ratingInput = document.getElementById('rating');
    const submitBtn = document.getElementById('submit-rating');
    
    function highlight(r){ 
        stars.forEach((s,idx)=>{ 
            if(idx<r){ s.classList.remove('far'); s.classList.add('fas'); } 
            else { s.classList.remove('fas'); s.classList.add('far'); } 
        }); 
    }
    
    stars.forEach((s, idx)=>{
        s.addEventListener('mouseover', ()=> highlight(idx+1));
        s.addEventListener('mouseout', ()=> highlight(parseInt(ratingInput.value)||0));
        s.addEventListener('click', ()=>{ 
            ratingInput.value = idx+1; 
            highlight(idx+1); 
            submitBtn.disabled = false; 
            submitBtn.style.opacity = '1';
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>