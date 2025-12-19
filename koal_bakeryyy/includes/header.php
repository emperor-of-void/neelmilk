<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php'; // đúng 100% nếu file trong thư mục includes/
?>


<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= BASE_URL ?>index.php">
            <i class="fas fa-cow me-2" style="font-size: 1.8rem;"></i>
            NeelMilk
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>index.php">
                        <i class="fas fa-home me-1"></i>Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>cart.php">
                        <i class="fas fa-shopping-cart me-1"></i>Giỏ hàng
                        <span class="badge bg-danger cart-badge"><?= array_sum($_SESSION['cart'] ?? []) ?></span>
                    </a>
                </li>
            </ul>

            <form class="d-flex me-3 search-form" action="<?= BASE_URL ?>index.php" method="GET">
                <i class="fas fa-search search-icon"></i>
                <input class="form-control" type="search" name="search" placeholder="Tìm sản phẩm..." 
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </form>

            <ul class="navbar-nav">
                <?php if (isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle user-name d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1" style="font-size: 1.3rem;"></i>
                            <?= htmlspecialchars($_SESSION['customer_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>order_history.php">
                                    <i class="fas fa-history me-2"></i>Lịch sử đơn hàng
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout_customer.php"
                                   onclick="return confirm('Bạn có chắc muốn đăng xuất?')">
                                    <i class="fas fa-sign-out-alt me-2"></i><strong>Đăng xuất</strong>
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>login_customer.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= BASE_URL ?>register.php"><i class="fas fa-user-plus me-1"></i>Đăng ký</a>
                    </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>admin/login.php"><i class="fas fa-user-shield me-1"></i>Admin</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
