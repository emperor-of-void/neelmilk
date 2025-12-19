<!-- Load Fonts (Nếu header chưa load) -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;1,600&family=Quicksand:wght@300;400;500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    :root {
        --footer-bg: #1f4d3a;       /* Xanh rêu đậm sang trọng */
        --footer-text: #e8f5e9;     /* Trắng ngà */
        --footer-gold: #d4af37;     /* Vàng kim */
        --footer-input: rgba(255,255,255,0.1);
    }

    .lux-footer {
        background-color: var(--footer-bg);
        color: var(--footer-text);
        font-family: "Quicksand", sans-serif;
        padding-top: 60px;
        position: relative;
        overflow: hidden;
        /* Họa tiết chìm sang trọng */
        background-image: radial-gradient(rgba(255,255,255,0.03) 1px, transparent 1px);
        background-size: 30px 30px;
    }

    /* Trang trí viền trên */
    .lux-footer::before {
        content: '';
        position: absolute; top: 0; left: 0; right: 0; height: 4px;
        background: linear-gradient(90deg, var(--footer-bg), var(--footer-gold), var(--footer-bg));
    }

    .footer-title {
        font-family: "Playfair Display", serif;
        font-weight: 700;
        font-size: 1.8rem;
        color: #fff;
        margin-bottom: 20px;
        letter-spacing: 0.5px;
    }

    .footer-desc {
        color: rgba(255,255,255,0.7);
        line-height: 1.6;
        font-size: 0.95rem;
        margin-bottom: 20px;
    }

    /* Links List */
    .footer-links {
        list-style: none;
        padding: 0;
    }
    .footer-links li { margin-bottom: 12px; }
    .footer-links a {
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        padding-left: 0;
    }
    .footer-links a:hover {
        color: var(--footer-gold);
        padding-left: 10px; /* Hiệu ứng trượt nhẹ */
    }
    .footer-links a::before {
        content: '•'; position: absolute; left: 0; opacity: 0; transition: 0.3s; color: var(--footer-gold);
    }
    .footer-links a:hover::before { opacity: 1; left: -15px; }

    /* Newsletter */
    .lux-email-box {
        position: relative;
        margin-top: 20px;
    }
    .lux-email-box input {
        width: 100%;
        background: var(--footer-input);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 50px;
        padding: 15px 20px;
        color: #fff;
        outline: none;
        transition: 0.3s;
    }
    .lux-email-box input:focus {
        background: rgba(255,255,255,0.15);
        border-color: var(--footer-gold);
    }
    .lux-email-box button {
        position: absolute;
        right: 5px; top: 5px; bottom: 5px;
        border-radius: 50px;
        background: var(--footer-gold);
        color: var(--footer-bg);
        border: none;
        width: 45px;
        cursor: pointer;
        transition: 0.3s;
    }
    .lux-email-box button:hover {
        background: #fff;
        transform: scale(1.05);
    }

    /* Social Icons */
    .social-icons a {
        display: inline-flex;
        width: 40px; height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        color: #fff;
        align-items: center; justify-content: center;
        margin-right: 10px;
        transition: 0.3s;
        border: 1px solid transparent;
    }
    .social-icons a:hover {
        background: var(--footer-gold);
        color: var(--footer-bg);
        transform: translateY(-3px);
    }

    /* App Badges */
    .app-badges img {
        height: 40px;
        margin-right: 10px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.2);
        transition: 0.3s;
    }
    .app-badges img:hover {
        transform: translateY(-2px);
        border-color: var(--footer-gold);
    }

    /* Bottom Bar */
    .footer-bottom {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding: 20px 0;
        margin-top: 50px;
        text-align: center;
        font-size: 0.85rem;
        color: rgba(255,255,255,0.5);
    }
    
    .contact-info p {
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .contact-info i { color: var(--footer-gold); width: 20px; }
</style>

<footer class="lux-footer">
    <div class="container">
        <div class="row g-5">
            
            <!-- Cột 1: Thương hiệu & Giới thiệu -->
            <div class="col-lg-4 col-md-6">
                <h2 class="footer-title">NeelMilk Premium</h2>
                <p class="footer-desc">
                    Hương vị sữa tươi thanh trùng thuần khiết từ nông trại xanh. Chúng tôi cam kết mang đến nguồn dinh dưỡng tốt nhất cho gia đình bạn mỗi ngày.
                </p>
                <div class="social-icons mt-4">
                    <a href="https://www.facebook.com/profile.php?id=61579833731297&locale=vi_VN"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="https://www.youtube.com/@quan04-w2w"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>

            <!-- Cột 2: Liên kết nhanh -->
            <div class="col-lg-2 col-md-6">
                <h4 class="footer-title" style="font-size: 1.2rem;">Khám Phá</h4>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>index.php">Trang Chủ</a></li>
                    <li><a href="#">Về Chúng Tôi</a></li>
                    <li><a href="#">Sản Phẩm Mới</a></li>
                    <li><a href="#">Blog Dinh Dưỡng</a></li>
                    <li><a href="#">Liên Hệ</a></li>
                </ul>
            </div>

            <!-- Cột 3: Thông tin liên hệ -->
            <div class="col-lg-3 col-md-6">
                <h4 class="footer-title" style="font-size: 1.2rem;">Liên Hệ</h4>
                <div class="contact-info text-white-50">
                    <p><i class="fa-solid fa-location-dot"></i> EAUT, Nam Từ Liêm, Hà Nội</p>
                    <p><i class="fa-solid fa-phone"></i> 0383 356 361</p>
                    <p><i class="fa-solid fa-envelope"></i> ngocquan12062004@gmail.com</p>
                    <p><i class="fa-solid fa-clock"></i> 8:00 - 22:00 (Hàng ngày)</p>
                </div>
            </div>

            <!-- Cột 4: Tải ứng dụng & Đăng ký -->
            <div class="col-lg-3 col-md-6">
                <h4 class="footer-title" style="font-size: 1.2rem;">Tải Ứng Dụng</h4>
                <div class="app-badges mb-4">
                    <a href="#"><img src="<?= BASE_URL ?>images/appstore.png" alt="App Store"></a>
                    <a href="#"><img src="<?= BASE_URL ?>images/googleplay.png" alt="Google Play"></a>
                </div>

                <h4 class="footer-title" style="font-size: 1.2rem;">Nhận Ưu Đãi</h4>
                <form class="lux-email-box">
                    <input type="email" placeholder="Email của bạn...">
                    <button type="submit"><i class="fa-solid fa-paper-plane"></i></button>
                </form>
            </div>

        </div>
    </div>

    <!-- Phần cuối trang -->
    <div class="footer-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-md-start">
                    &copy; 2024 NeelMilk Store. All rights reserved.
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    Designed with <i class="fa-solid fa-heart text-danger"></i> by <span style="color: var(--footer-gold);">NGOCQUANVIPPRO</span>
                </div>
            </div>
        </div>
    </div>
</footer>