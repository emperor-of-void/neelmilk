<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// --- 1. LOGIC PHP CŨ (GIỮ NGUYÊN & TỐI ƯU) ---

// Hàm ghi log
function logDebug($message) {
    $logFile = __DIR__ . '/debug.log';
    $currentTime = date('Y-m-d H:i:s');
    // Tắt log để tránh đầy file trên production, bật lại khi cần debug
    // file_put_contents($logFile, "[$currentTime] $message\n", FILE_APPEND); 
}

// Phân trang
$limit = 5; // Giảm limit bảng xuống 5 để chừa chỗ cho biểu đồ đẹp hơn
$page = isset($_GET['page']) && filter_var($_GET['page'], FILTER_VALIDATE_INT) ? $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// A. LẤY DỮ LIỆU CHO BIỂU ĐỒ (NEW)

// 1. Biểu đồ doanh thu 7 ngày gần nhất
$chart_dates = [];
$chart_revenues = [];
$stmt = $conn->prepare("
    SELECT DATE(created_at) as order_date, SUM(total_amount) as daily_total 
    FROM orders 
    WHERE status != 'cancelled'
    GROUP BY DATE(created_at) 
    ORDER BY order_date DESC 
    LIMIT 7
");
$stmt->execute();
$chart_res = $stmt->get_result();
while($row = $chart_res->fetch_assoc()) {
    // Đảo ngược mảng sau để hiển thị từ trái qua phải (cũ -> mới)
    array_unshift($chart_dates, date('d/m', strtotime($row['order_date'])));
    array_unshift($chart_revenues, $row['daily_total']);
}
$stmt->close();

// 2. Biểu đồ Top 5 sản phẩm bán chạy
$chart_prod_names = [];
$chart_prod_qtys = [];
$stmt = $conn->prepare("
    SELECT p.name, SUM(od.quantity) as total_sold 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    GROUP BY p.id 
    ORDER BY total_sold DESC 
    LIMIT 5
");
$stmt->execute();
$chart_res = $stmt->get_result();
while($row = $chart_res->fetch_assoc()) {
    $chart_prod_names[] = $row['name'];
    $chart_prod_qtys[] = $row['total_sold'];
}
$stmt->close();


// B. LẤY DỮ LIỆU BẢNG (OLD LOGIC)

// Tổng doanh thu toàn thời gian
$sql = "SELECT SUM(total_amount) as total_revenue FROM orders WHERE status != 'cancelled'";
$result = $conn->query($sql);
$total_revenue = $result->fetch_assoc()['total_revenue'] ?? 0;

// Tổng số đơn hàng
$sql = "SELECT COUNT(*) as total_orders FROM orders";
$result = $conn->query($sql);
$total_orders_count = $result->fetch_assoc()['total_orders'] ?? 0;

// Danh sách sản phẩm (Bảng)
$sql = "SELECT p.id, p.name, SUM(od.quantity) as total_quantity, SUM(od.quantity * od.price) as total_product_revenue
        FROM order_details od
        JOIN products p ON od.product_id = p.id
        JOIN orders o ON od.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY p.id, p.name
        ORDER BY total_product_revenue DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$product_stats = $stmt->get_result();

// Phân trang sản phẩm
$sql = "SELECT COUNT(DISTINCT p.id) as total_products FROM order_details od JOIN products p ON od.product_id = p.id";
$result = $conn->query($sql);
$total_products_count = $result->fetch_assoc()['total_products'] ?? 0;
$total_pages = ceil($total_products_count / $limit);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Doanh Thu - NeelMilk</title>
    
    <!-- Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Fonts Luxury -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --admin-bg: #fffbf0;
            --brand-green: #2e7d5e;
            --brand-gold: #c5a059;
            --text-dark: #333;
            --shadow-soft: 0 10px 30px rgba(0,0,0,0.05);
        }

        body {
            background-color: var(--admin-bg);
            font-family: "Quicksand", sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(197, 160, 89, 0.2);
            padding-bottom: 15px;
        }
        .page-title {
            font-family: "Playfair Display", serif;
            font-size: 2rem;
            color: var(--brand-green);
        }

        /* Stats Cards (Summary) */
        .stat-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px;
            box-shadow: var(--shadow-soft);
            border: none;
            height: 100%;
            transition: transform 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon {
            width: 50px; height: 50px;
            border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 15px;
        }
        .icon-green { background: #e8f5e9; color: var(--brand-green); }
        .icon-gold { background: #fff8e1; color: #ffa000; }
        
        .stat-value {
            font-family: "Playfair Display", serif;
            font-size: 1.8rem; font-weight: 700; color: var(--text-dark);
        }
        .stat-label { font-size: 0.9rem; color: #888; text-transform: uppercase; letter-spacing: 1px; }

        /* Charts Section */
        .chart-container {
            background: #fff;
            border-radius: 25px;
            padding: 30px;
            box-shadow: var(--shadow-soft);
            margin-bottom: 30px;
            border: 1px solid #fcfcfc;
        }
        .chart-title {
            font-family: "Playfair Display", serif;
            font-size: 1.3rem; color: var(--brand-green);
            margin-bottom: 20px;
        }

        /* Table Styling (Floating Rows) */
        .table-custom { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        .table-custom thead th {
            border: none; text-transform: uppercase;
            font-size: 0.8rem; font-weight: 700; color: #888; padding: 15px;
        }
        .table-custom tbody tr {
            background: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            transition: transform 0.2s;
        }
        .table-custom tbody tr:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.08); }
        .table-custom td {
            border: none; padding: 15px; vertical-align: middle;
            border-top: 1px solid #f9f9f9; border-bottom: 1px solid #f9f9f9;
        }
        .table-custom td:first-child { border-top-left-radius: 15px; border-bottom-left-radius: 15px; border-left: 1px solid #f9f9f9; }
        .table-custom td:last-child { border-top-right-radius: 15px; border-bottom-right-radius: 15px; border-right: 1px solid #f9f9f9; }

        /* Pagination */
        .pagination .page-link {
            border: none; margin: 0 5px; border-radius: 50% !important;
            width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;
            color: #555; font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background: var(--brand-green); color: #fff;
            box-shadow: 0 4px 10px rgba(46, 125, 94, 0.3);
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container py-5">
        <!-- Header -->
        <div class="page-header d-flex justify-content-between align-items-center">
            <div>
                <h2 class="page-title">Báo Cáo & Thống Kê</h2>
                <p class="text-muted mb-0">Tổng quan tình hình kinh doanh của NeelMilk.</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i>Dashboard
            </a>
        </div>

        <!-- 1. Thẻ Tổng Quan (Summary Cards) -->
        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Tổng Doanh Thu</div>
                        <div class="stat-value text-success"><?php echo number_format($total_revenue); ?> <small class="fs-6">VNĐ</small></div>
                    </div>
                    <div class="stat-icon icon-green"><i class="fas fa-coins"></i></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stat-card d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="stat-label">Tổng Đơn Hàng</div>
                        <div class="stat-value text-warning"><?php echo number_format($total_orders_count); ?> <small class="fs-6">Đơn</small></div>
                    </div>
                    <div class="stat-icon icon-gold"><i class="fas fa-shopping-basket"></i></div>
                </div>
            </div>
        </div>

        <!-- 2. Biểu Đồ (Charts) -->
        <div class="row g-4 mb-5">
            <!-- Biểu đồ Doanh thu -->
            <div class="col-lg-8">
                <div class="chart-container h-100">
                    <h4 class="chart-title"><i class="fas fa-chart-line me-2"></i>Xu Hướng Doanh Thu (7 Ngày)</h4>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            <!-- Biểu đồ Top Sản phẩm -->
            <div class="col-lg-4">
                <div class="chart-container h-100">
                    <h4 class="chart-title"><i class="fas fa-chart-pie me-2"></i>Top Sản Phẩm Bán Chạy</h4>
                    <div style="height: 250px; display: flex; justify-content: center;">
                        <canvas id="productChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Bảng Chi Tiết (Detailed Table) -->
        <div class="chart-container">
            <h4 class="chart-title mb-4">Chi Tiết Doanh Thu Theo Sản Phẩm</h4>
            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Số lượng bán</th>
                            <th>Tổng doanh thu</th>
                            <th>Tỷ trọng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($product_stats && $product_stats->num_rows > 0): ?>
                            <?php while ($row = $product_stats->fetch_assoc()): ?>
                                <?php 
                                    // Tính tỷ trọng % doanh thu của sp này so với tổng
                                    $percentage = ($total_revenue > 0) ? round(($row['total_product_revenue'] / $total_revenue) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border rounded-pill px-3">
                                            <?php echo $row['total_quantity']; ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold" style="color: var(--brand-green);">
                                        <?php echo number_format($row['total_product_revenue']); ?> VNĐ
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="progress flex-grow-1 me-2" style="height: 6px; border-radius: 10px;">
                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo $percentage; ?>%</small>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có dữ liệu bán hàng.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <nav>
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>
    
    <!-- Cấu hình Biểu đồ Chart.js -->
    <script>
        // Dữ liệu từ PHP
        const dates = <?php echo json_encode($chart_dates); ?>;
        const revenues = <?php echo json_encode($chart_revenues); ?>;
        const productNames = <?php echo json_encode($chart_prod_names); ?>;
        const productQtys = <?php echo json_encode($chart_prod_qtys); ?>;

        // 1. Biểu đồ Doanh thu (Line Chart - Mềm mại)
        const ctxRev = document.getElementById('revenueChart').getContext('2d');
        
        // Tạo gradient nền cho biểu đồ
        const gradientRev = ctxRev.createLinearGradient(0, 0, 0, 400);
        gradientRev.addColorStop(0, 'rgba(46, 125, 94, 0.2)'); // Màu xanh thương hiệu mờ
        gradientRev.addColorStop(1, 'rgba(46, 125, 94, 0)');

        new Chart(ctxRev, {
            type: 'line',
            data: {
                labels: dates,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    borderColor: '#2e7d5e', // Màu xanh NeelMilk
                    backgroundColor: gradientRev,
                    borderWidth: 3,
                    tension: 0.4, // Làm cong đường kẻ (Soft curve)
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#c5a059', // Điểm màu vàng gold
                    pointRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#fff',
                        titleColor: '#333',
                        bodyColor: '#2e7d5e',
                        borderColor: '#eee',
                        borderWidth: 1,
                        padding: 10,
                        displayColors: false,
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Biểu đồ Sản phẩm (Doughnut - Sang trọng)
        const ctxProd = document.getElementById('productChart').getContext('2d');
        new Chart(ctxProd, {
            type: 'doughnut',
            data: {
                labels: productNames,
                datasets: [{
                    data: productQtys,
                    backgroundColor: [
                        '#2e7d5e', // Xanh đậm
                        '#43a07a', // Xanh vừa
                        '#c5a059', // Vàng Gold
                        '#e0c080', // Vàng nhạt
                        '#a8e6cf'  // Xanh mint
                    ],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                cutout: '70%', // Làm rỗng giữa tạo vẻ hiện đại
                plugins: {
                    legend: { position: 'bottom', labels: { font: { family: 'Quicksand' }, boxWidth: 12 } }
                }
            }
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>