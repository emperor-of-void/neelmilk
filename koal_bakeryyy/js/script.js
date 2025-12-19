/**
 * NEELMILK LUXURY SCRIPTS
 * Bao gồm: Giỏ hàng, Thông báo, Hiệu ứng giao diện
 */

document.addEventListener('DOMContentLoaded', function() {
    // Khởi chạy các hiệu ứng khi trang tải xong
    initFlashSaleCountdown();
    initBackToTop();
    initNavbarEffect();
});

/* =========================================
   1. CART FUNCTIONS (Xử lý Giỏ hàng)
   ========================================= */

function addToCart(productId) {
    // Hiệu ứng nút bấm (Optional: làm mờ nút khi đang xử lý)
    const btn = event ? event.currentTarget : null;
    const originalText = btn ? btn.innerHTML : '';
    if(btn) {
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btn.style.pointerEvents = 'none';
    }

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId)
    })
    .then(response => response.json())
    .then(data => {
        // Reset nút bấm
        if(btn) {
            btn.innerHTML = originalText;
            btn.style.pointerEvents = 'auto';
        }

        if (data.success || data.status === 'success') { // Kiểm tra tùy theo cách PHP trả về
            showLuxuryToast(data.message || 'Đã thêm sản phẩm vào giỏ hàng!', 'success');
            
            // Cập nhật số lượng trên icon giỏ hàng (nếu có class .cart-badge)
            updateCartBadgeCount(); 
        } else {
            showLuxuryToast(data.message || 'Có lỗi xảy ra!', 'error');
        }
    })
    .catch(error => {
        if(btn) {
            btn.innerHTML = originalText;
            btn.style.pointerEvents = 'auto';
        }
        console.error('Error:', error);
        showLuxuryToast('Lỗi kết nối! Vui lòng thử lại.', 'error');
    });
}

function updateCart(productId, quantity) {
    if (quantity < 1) {
        removeFromCart(productId);
        return;
    }
    
    fetch('update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(quantity)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Không hiện toast để đỡ rối, chỉ reload
            location.reload(); 
        } else {
            showLuxuryToast(data.message, 'error');
        }
    })
    .catch(error => {
        showLuxuryToast('Lỗi cập nhật giỏ hàng!', 'error');
    });
}

function removeFromCart(productId) {
    // Dùng confirm mặc định hoặc Custom Modal nếu muốn xịn hơn
    if (!confirm('Bạn muốn bỏ sản phẩm này khỏi giỏ hàng?')) return;

    fetch('update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'product_id=' + encodeURIComponent(productId) + '&remove=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showLuxuryToast('Đã xóa sản phẩm!', 'success');
            // Delay 1 chút để người dùng đọc thông báo rồi mới reload
            setTimeout(() => {
                location.reload();
            }, 800);
        } else {
            showLuxuryToast(data.message, 'error');
        }
    })
    .catch(error => {
        showLuxuryToast('Lỗi khi xóa sản phẩm!', 'error');
    });
}

// Hàm cập nhật số lượng trên Navbar (Giả lập)
function updateCartBadgeCount() {
    const badge = document.querySelector('.cart-badge');
    if(badge) {
        let count = parseInt(badge.innerText);
        badge.innerText = count + 1;
        // Hiệu ứng nảy lên
        badge.style.transform = 'scale(1.5)';
        setTimeout(() => badge.style.transform = 'scale(1)', 300);
    }
}

/* =========================================
   2. LUXURY TOAST (Thông báo Sang trọng)
   ========================================= */

function showLuxuryToast(message, type = 'success') {
    // Tạo container nếu chưa có
    let toastContainer = document.getElementById('luxury-toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'luxury-toast-container';
        toastContainer.style.cssText = `
            position: fixed; top: 100px; right: 20px; z-index: 9999;
            display: flex; flex-direction: column; gap: 10px;
        `;
        document.body.appendChild(toastContainer);
    }

    // Cấu hình màu sắc
    const config = {
        success: { icon: 'fa-check-circle', color: '#2e7d5e', bg: '#e8f5e9', border: '#2e7d5e' },
        error:   { icon: 'fa-exclamation-circle', color: '#c62828', bg: '#ffebee', border: '#c62828' },
        warning: { icon: 'fa-bell', color: '#f57c00', bg: '#fff3e0', border: '#f57c00' }
    };
    const style = config[type] || config.success;

    // Tạo Toast Element
    const toast = document.createElement('div');
    toast.className = 'luxury-toast';
    toast.style.cssText = `
        background: #fff;
        border-left: 5px solid ${style.border};
        padding: 15px 25px;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        display: flex; align-items: center; gap: 15px;
        min-width: 300px;
        transform: translateX(120%);
        transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        font-family: 'Quicksand', sans-serif;
        font-weight: 600; color: #333;
    `;

    toast.innerHTML = `
        <i class="fas ${style.icon}" style="font-size: 1.5rem; color: ${style.color}"></i>
        <span>${message}</span>
    `;

    toastContainer.appendChild(toast);

    // Kích hoạt hiệu ứng trượt vào
    requestAnimationFrame(() => {
        toast.style.transform = 'translateX(0)';
    });

    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 400);
    }, 3000);
}

/* =========================================
   3. UI EFFECTS (Đếm ngược & Back to Top)
   ========================================= */

function initFlashSaleCountdown() {
    const countdownEl = document.getElementById('countdown');
    if (!countdownEl) return;

    function update() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(now.getDate() + 1);
        tomorrow.setHours(0, 0, 0, 0);
        
        const diff = tomorrow - now;
        
        const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const s = Math.floor((diff % (1000 * 60)) / 1000);
        
        const hoursEl = document.getElementById("hours");
        const minsEl = document.getElementById("minutes");
        const secsEl = document.getElementById("seconds");

        if(hoursEl) hoursEl.innerText = h < 10 ? "0" + h : h;
        if(minsEl) minsEl.innerText = m < 10 ? "0" + m : m;
        if(secsEl) secsEl.innerText = s < 10 ? "0" + s : s;
    }
    setInterval(update, 1000);
    update();
}

function initBackToTop() {
    const backBtn = document.getElementById("btn-back-to-top");
    if (!backBtn) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backBtn.style.display = "block";
        } else {
            backBtn.style.display = "none";
        }
    });

    backBtn.addEventListener("click", () => {
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
}

function initNavbarEffect() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.95)';
            navbar.style.boxShadow = '0 5px 20px rgba(0,0,0,0.05)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.1)';
            navbar.style.boxShadow = 'none';
        }
    });
}