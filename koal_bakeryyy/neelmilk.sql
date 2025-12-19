-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 17, 2025 lúc 07:55 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `neelmilk`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `customers`
--

INSERT INTO `customers` (`id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 'Lý Ngọc Quân', 'ngocquan12062004@gmail.com', '$2y$10$CSu1teUDMFHa/QdQT0C96ubSIArOKgr57MBlgnnybo4LYGt0ECP4e', '2025-05-19 10:05:06'),
(2, 'Nguyễn Văn A', 'customer1@example.com', '$2y$10$examplehash1234567890abcdef1234567890abcdef1234567890', '2025-05-22 08:04:47'),
(3, 'Trần Thị B', 'customer2@example.com', '$2y$10$examplehash1234567890abcdef1234567890abcdef1234567891', '2025-05-22 08:04:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('cashier','baker','manager') DEFAULT 'cashier',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `employees`
--

INSERT INTO `employees` (`id`, `name`, `email`, `role`, `status`, `created_at`) VALUES
(1, 'phạm ngọc ánh', 'ngocquan12062004@gmail.com', 'cashier', 'active', '2025-05-19 09:45:32'),
(2, 'Lê Văn C', 'employee1@example.com', 'cashier', 'active', '2025-05-22 08:04:47'),
(3, 'Phạm Thị D', 'employee2@example.com', 'baker', 'active', '2025-05-22 08:04:47'),
(4, 'Hoàng Văn E', 'employee3@example.com', 'manager', 'inactive', '2025-05-22 08:04:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `customer_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','bank_transfer','digital_wallet') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `quantity`, `status`, `created_at`, `customer_id`, `total_amount`, `payment_method`) VALUES
(1, NULL, 'pending', '2025-05-22 08:04:47', 1, 270000.00, 'cod'),
(2, NULL, 'completed', '2025-05-22 08:04:47', 2, 150000.00, 'bank_transfer'),
(3, NULL, 'pending', '2025-05-22 08:08:43', 1, 30000.00, 'cod'),
(4, NULL, 'pending', '2025-05-22 08:13:33', 1, 450000.00, 'digital_wallet'),
(5, NULL, 'pending', '2025-05-22 08:25:41', 1, 810000.00, 'bank_transfer'),
(8, NULL, 'pending', '2025-11-14 09:37:29', 1, 450000.00, 'cod'),
(9, NULL, 'pending', '2025-11-14 09:37:58', 1, 480000.00, 'cod'),
(10, NULL, 'pending', '2025-11-14 09:58:31', 1, 270000.00, 'cod'),
(11, NULL, 'pending', '2025-11-14 17:29:49', 1, 360000.00, 'cod'),
(12, NULL, 'pending', '2025-11-17 06:41:53', 1, 540000.00, 'cod');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 150000.00),
(2, 1, 2, 1, 120000.00),
(3, 2, 1, 1, 150000.00),
(4, 3, 3, 1, 30000.00),
(5, 4, 1, 2, 150000.00),
(6, 4, 2, 1, 120000.00),
(7, 4, 3, 1, 30000.00),
(8, 5, 1, 4, 150000.00),
(9, 5, 2, 1, 120000.00),
(10, 5, 5, 1, 90000.00),
(11, 8, 2, 3, 120000.00),
(12, 8, 5, 1, 90000.00),
(13, 9, 2, 2, 120000.00),
(14, 9, 3, 2, 30000.00),
(15, 9, 4, 1, 180000.00),
(16, 10, 2, 1, 120000.00),
(17, 10, 3, 2, 30000.00),
(18, 10, 5, 1, 90000.00),
(19, 11, 1, 1, 150000.00),
(20, 11, 2, 1, 120000.00),
(21, 11, 5, 1, 90000.00),
(22, 12, 2, 1, 120000.00),
(23, 12, 3, 2, 30000.00),
(24, 12, 5, 4, 90000.00);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 50,
  `search_count` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `description`, `image`, `stock`, `search_count`) VALUES
(1, 'Sữa Chocolate', 150000.00, 'Sữa Chocolate thơm ngon với lớp kem mịn.', 'buffalo-ghee.png', 43, 8),
(2, 'Sữa Dâu', 120000.00, 'Sữa Dâu tươi ngọt ngào, phủ kem dâu.', 'cow-gheeeeee.png', 20, 1),
(3, 'Sữa Vanilla', 30000.00, 'Sữa vani nhỏ xinh', 'cow-milkkkkkkk.png', 12, 0),
(4, 'Sữa Red Velvet', 180000.00, 'Sữa red velvet với kem phô mai', 'curd-premium.png', 39, 0),
(5, 'Sữa Chanh', 90000.00, 'Sữa chanh chua ngọt hấp dẫn', 'curd-total.png', 7, 0),
(6, 'Sữa Full Cream', 55000.00, 'Sữa nguyên kem béo thơm tự nhiên.', 'fullcream-milk.png', 50, 0),
(7, 'Kem Que IC Bars', 30000.00, 'Kem que mát lạnh vị trái cây.', 'icbars.png', 50, 0),
(8, 'Kem Kulfi', 35000.00, 'Kulfi truyền thống Ấn Độ béo thơm.', 'kulfi.png', 50, 0),
(9, 'Sữa Lắc Chocolate', 40000.00, 'Sữa lắc vị chocolate đậm đà.', 'milk-shake-chocolate.png', 50, 0),
(10, 'Sữa Lắc Dâu', 40000.00, 'Sữa lắc vị dâu thơm ngon.', 'milk-shake-strawberry.png', 50, 0),
(11, 'Sữa Lắc Vanilla', 40000.00, 'Sữa lắc vị vanilla dịu nhẹ.', 'milk-shake-vanilla.png', 50, 0),
(12, 'Phô Mai Mozzarella', 85000.00, 'Phô mai mozzarella mềm dẻo.', 'mozzarellacheese.png', 50, 0),
(13, 'Logo Neel', 10000.00, 'Hình ảnh logo thương hiệu Neel.', 'Neel.png', 50, 0),
(14, 'Paneer Tươi', 95000.00, 'Pho mát paneer tươi mềm béo.', 'paneer-fresh.png', 50, 0),
(15, 'Paneer Lite', 90000.00, 'Phiên bản ít béo của paneer.', 'paneer-lite.png', 50, 0),
(16, 'Phô Mai Chế Biến', 75000.00, 'Phô mai chế biến tiện dụng.', 'processedcheese.png', 50, 0),
(17, 'Phô Mai Lát', 78000.00, 'Phô mai cắt lát tiện lợi.', 'processedcheeseslice.png', 50, 0),
(18, 'Sữa Sabja', 45000.00, 'Thức uống sữa hạt sabja bổ dưỡng.', 'sabja.png', 50, 0),
(19, 'Sữa Chuẩn Hoá', 30000.00, 'Sữa tiêu chuẩn hoá giàu dinh dưỡng.', 'standardized-milk.png', 50, 0),
(20, 'Sweet Lassi', 35000.00, 'Lassi ngọt truyền thống mát lành.', 'sweet-lassi.png', 50, 0),
(21, 'Sữa Tone Milk', 32000.00, 'Sữa tách béo nhẹ nhàng.', 'tonemilk.png', 50, 0);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Chỉ mục cho bảng `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Chỉ mục cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Các ràng buộc cho bảng `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
