-- phpMyAdmin SQL Dump
-- version 3.4.9
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Apr 09, 2024 at 03:04 PM
-- Server version: 5.5.20
-- PHP Version: 5.3.10
-- DROP DATABASE IF EXISTS ShopLapTop;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
--
-- Database: `shoplaptop`
--
CREATE DATABASE IF NOT EXISTS ShopLapTop
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
-- --------------------------------------------------------
use ShopLapTop;
--
-- Table structure for table `tbl_nguoidung`
--
-- DROP TABLE IF EXISTS nguoidung;
CREATE TABLE tbl_nguoidung (
    MaNguoiDung INT AUTO_INCREMENT PRIMARY KEY,
    TenNguoiDung VARCHAR(100) NOT NULL,
    TenDangNhap VARCHAR(50) NOT NULL UNIQUE,
    MatKhau VARCHAR(255) NOT NULL,
    Email VARCHAR(100) UNIQUE,
    SoDienThoai VARCHAR(15),
    DiaChi VARCHAR(255),
    QuyenHan ENUM('Quản trị viên', 'Khách hàng')
        NOT NULL DEFAULT 'Khách hàng',
    TrangThai ENUM('Hoạt động', 'Đã khóa')
        NOT NULL DEFAULT 'Hoạt động',
    NgayTao DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nguoidung_quyen (QuyenHan),
    INDEX idx_nguoidung_trangthai (TrangThai)
) ENGINE=InnoDB;
--
-- Dumping data for table `tbl_nguoidung`
--
INSERT INTO tbl_nguoidung(TenDangNhap, MatKhau, TenNguoiDung, Email, SoDienThoai, DiaChi, QuyenHan, TrangThai)VALUES
	('admin','admin123','Quản trị viên','admin@laptopstore.com','0900000000','Long Xuyên, An Giang','Quản trị viên','Hoạt động'),
	('nguyenvana','123456','Nguyễn Văn A','nguyenvana@gmail.com','0912345678','Long Xuyên, An Giang','Khách hàng','Hoạt động'),
	('tranthib','123456','Trần Thị B','tranthib@gmail.com','0987654321','Châu Đốc, An Giang','Khách hàng','Hoạt động');
-- --------------------------------------------------------
-- Table structure for table `tbl_nhasanxuat`
-- DROP TABLE IF EXISTS `tbl_nhasanxuat`;
CREATE TABLE tbl_nhasanxuat (
    IdNhaSanXuat INT AUTO_INCREMENT PRIMARY KEY,
    TenNhaSanXuat VARCHAR(100) NOT NULL UNIQUE,
    MoTa TEXT,
    HinhAnh VARCHAR(255),
    TrangThai ENUM('Đang kinh doanh', 'Ngừng kinh doanh') NOT NULL DEFAULT 'Đang kinh doanh'
) ENGINE=InnoDB;
--
-- Dumping data for table `tbl_nhasanxuat`
--
INSERT INTO tbl_nhasanxuat(TenNhaSanXuat,MoTa,HinhAnh)VALUES
	 ('ASUS', 'Thương hiệu laptop ASUS', 'asus.png'),
    ('ACER', 'Thương hiệu laptop Acer', 'acer.png'),
    ('DELL', 'Thương hiệu laptop Dell', 'dell.png'),
    ('HP', 'Thương hiệu laptop HP', 'hp.png'),
    ('LENOVO', 'Thương hiệu laptop Lenovo', 'lenovo.png'),
    ('MSI', 'Thương hiệu laptop MSI', 'msi.png'),
    ('APPLE', 'Thương hiệu laptop Apple MacBook', 'apple.png');
-- Bảng danh mục 
CREATE TABLE tbl_danhmuc (
    MaDanhMuc INT AUTO_INCREMENT PRIMARY KEY,
    TenDanhMuc VARCHAR(100) NOT NULL UNIQUE,
    MoTa TEXT,
    TrangThai ENUM('Đang hoạt động', 'Ngừng hoạt động') NOT NULL DEFAULT 'Đang hoạt động'
) ENGINE=InnoDB;
INSERT INTO tbl_danhmuc (TenDanhMuc, MoTa)VALUES
	('Laptop văn phòng', 'Laptop phục vụ học tập và công việc văn phòng'),
	('Laptop gaming', 'Laptop phục vụ chơi game và giải trí'),
	('Laptop đồ họa', 'Laptop phục vụ thiết kế và xử lý đồ họa'),
	('Laptop mỏng nhẹ', 'Laptop có thiết kế nhỏ gọn, dễ di chuyển'),
	('MacBook', 'Các dòng máy tính xách tay của Apple');
-- Bảng sản phẩm	
-- DROP TABLE IF EXISTS tbl_sanpham;
-- SELECT * FROM tbl_sanpham;
CREATE TABLE tbl_sanpham (
    IdSanPham INT AUTO_INCREMENT PRIMARY KEY,
    MaSanPham VARCHAR(50) NOT NULL UNIQUE,
    TenSanPham VARCHAR(200) NOT NULL,
    IdNhaSanXuat INT NOT NULL,
    MaDanhMuc INT NOT NULL,
    HinhAnh VARCHAR(255),
    DonGia DECIMAL(15,2) NOT NULL DEFAULT 0,
    TiLeGiamGia DECIMAL(5,2) NOT NULL DEFAULT 0,
	GiaBan DECIMAL(15,2)
    GENERATED ALWAYS AS (
        DonGia - (DonGia * TiLeGiamGia / 100)
    ) STORED,
    SoLuong INT NOT NULL DEFAULT 0,
    MoTa  varchar(250),
    CauHinh  varchar(250),
    LuotXem INT UNSIGNED NOT NULL DEFAULT 0,
    TrangThai ENUM('Đang kinh doanh','Ngừng kinh doanh') NOT NULL DEFAULT 'Đang kinh doanh',
    CONSTRAINT fk_sanpham_nhasanxuat
        FOREIGN KEY (IdNhaSanXuat)
        REFERENCES tbl_nhasanxuat(IdNhaSanXuat)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    CONSTRAINT fk_sanpham_danhmuc
        FOREIGN KEY (MaDanhMuc)
        REFERENCES tbl_danhmuc(MaDanhMuc)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
	CONSTRAINT chk_sanpham_dongia
        CHECK (DonGia >= 0),

    CONSTRAINT chk_sanpham_giamgia
        CHECK (TiLeGiamGia >= 0 AND TiLeGiamGia <= 100),

    CONSTRAINT chk_sanpham_soluong
        CHECK (SoLuong >= 0),

 
    INDEX idx_sanpham_ten (TenSanPham),
    INDEX idx_sanpham_gia (DonGia),
    INDEX idx_sanpham_hang (IdNhaSanXuat),
    INDEX idx_sanpham_danhmuc (MaDanhMuc),
    INDEX idx_sanpham_trangthai (TrangThai)
) ENGINE=InnoDB;


--
-- Dumping data for table `tbl_sanpham`
--

INSERT INTO tbl_sanpham(MaSanPham, TenSanPham, IdNhaSanXuat, MaDanhMuc, HinhAnh, DonGia, TiLeGiamGia, SoLuong, MoTa, CauHinh, LuotXem, TrangThai) VALUES
	
   -- Nhóm Laptop Văn Phòng (MaDanhMuc = 1)
('ASUS-VIVOBOOK-001','ASUS Vivobook 15',1,1,'asus-vivobook-15.jpg',14990000,5,18,'Laptop ASUS Vivobook 15 sở hữu thiết kế hiện đại, hiệu năng ổn định, phù hợp cho học sinh, sinh viên và nhân viên văn phòng. Đáp ứng tốt nhu cầu học tập, làm việc và giải trí hằng ngày.','Intel Core i5, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 15.6 inch FHD.',430,'Đang kinh doanh'),
('DELL-INSP-15-001','Dell Inspiron 15',3,1,'dell-inspiron-15.jpg',15990000,0,20,'Laptop Dell Inspiron 15 có thiết kế thanh lịch, bàn phím tiện dụng và hiệu năng đáp ứng tốt các công việc văn phòng, học trực tuyến và xử lý tài liệu.','Intel Core i5-1335U, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 15.6 inch FHD.',750,'Đang kinh doanh'),
('ASUS-BOOK-14-001','ASUS Vivobook Go 14 E1404FA',1,1,'asus-vivobook-go-14.jpg',11990000,10,30,'Laptop phổ thông có thiết kế nhỏ gọn, trọng lượng nhẹ, phù hợp với học sinh, sinh viên và người dùng có nhu cầu làm việc văn phòng cơ bản.','AMD Ryzen 3 7320U, RAM 8GB, SSD 256GB, AMD Radeon Graphics, màn hình 14 inch FHD.',620,'Đang kinh doanh'),
('ACER-ASPIRE-3-002','Acer Aspire 3 A315-59-57WY',2,1,'acer-aspire-3.jpg',13490000,8,25,'Laptop Acer Aspire 3 có thiết kế đơn giản, bền bỉ, phù hợp với học sinh, sinh viên và nhân viên văn phòng. Hỗ trợ tốt các tác vụ soạn thảo văn bản, học trực tuyến và làm việc hằng ngày.','Intel Core i5-1235U, RAM 8GB, SSD 512GB, Intel Iris Xe, màn hình 15.6 inch FHD.',550,'Đang kinh doanh'),
('DELL-INSP-3520-002','Dell Inspiron 15 3520 i5',3,1,'dell-inspiron-3520.jpg',16990000,5,22,'Laptop Dell Inspiron 15 có màn hình lớn, bàn phím thoải mái và khả năng xử lý đa nhiệm tốt. Phù hợp cho công việc văn phòng, kế toán và học tập.','Intel Core i5-1235U, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 15.6 inch FHD 120Hz.',710,'Đang kinh doanh'),
('HP-LAPTOP-15-002','HP Laptop 15-fc0085AU',4,1,'hp-laptop-15.jpg',12990000,7,28,'Laptop HP 15 có thiết kế hiện đại, màn hình rộng và bàn phím số tiện lợi. Phù hợp với học sinh, sinh viên, nhân viên văn phòng và người làm công việc kế toán.','AMD Ryzen 3 7320U, RAM 8GB, SSD 512GB, AMD Radeon Graphics, màn hình 15.6 inch FHD.',590,'Đang kinh doanh'),
('LENOVO-IDEA-001','Lenovo IdeaPad Slim 5',5,1,'lenovo-ideapad-slim-5.jpg',17990000,0,8,'Laptop Lenovo IdeaPad Slim 5 sở hữu thiết kế thanh lịch, hiệu năng ổn định và khả năng đa nhiệm tốt. Phù hợp cho học tập, làm việc văn phòng và giải trí.','Intel Core i5-13420H, RAM 16GB, SSD 512GB, Intel UHD Graphics, màn hình 14 inch 2.2K.',540,'Đang kinh doanh'),

-- Nhóm Laptop Gaming (MaDanhMuc = 2)
('ASUS-TUF-A15-002','ASUS TUF Gaming A15 FA506NC',1,2,'asus-tuf-a15.jpg',21990000,8,15,'Laptop gaming ASUS TUF A15 sở hữu thiết kế mạnh mẽ, độ bền cao và hệ thống tản nhiệt hiệu quả. Phù hợp cho game thủ và người dùng cần hiệu năng xử lý đồ họa mạnh mẽ.','AMD Ryzen 5 7535HS, RAM 16GB DDR5, SSD 512GB, RTX 3050 4GB, màn hình 15.6 inch FHD 144Hz.',1420,'Đang kinh doanh'),
('ACER-NITRO-16-001','Acer Nitro 16 Phoenix AN16',2,2,'acer-nitro-16.jpg',28990000,10,10,'Laptop gaming Acer Nitro 16 được trang bị cấu hình mạnh mẽ và màn hình tần số quét cao. Phù hợp cho chơi game, livestream và các tác vụ giải trí đòi hỏi hiệu năng.','AMD Ryzen 5 7640HS, RAM 16GB DDR5, SSD 512GB, RTX 4050 6GB, màn hình 16 inch WUXGA 165Hz.',960,'Đang kinh doanh'),
('MSI-CYBORG-15-001','MSI Cyborg 15 A12VE',6,2,'msi-cyborg-15.jpg',23990000,12,14,'Laptop MSI Cyborg 15 nổi bật với thiết kế hiện đại, phong cách gaming cá tính và cấu hình mạnh mẽ. Phù hợp cho game thủ trẻ và người dùng yêu thích hiệu năng đồ họa.','Intel Core i5-12450H, RAM 16GB DDR5, SSD 512GB, RTX 4050 6GB, màn hình 15.6 inch FHD 144Hz.',1150,'Đang kinh doanh'),
('LENOVO-LOQ-15-001','Lenovo LOQ 15IAX9',5,2,'lenovo-loq-15.jpg',20990000,5,18,'Laptop Lenovo LOQ sở hữu thiết kế gaming chắc chắn, hiệu năng ổn định và khả năng tản nhiệt tốt. Phù hợp cho chơi game, học tập công nghệ và giải trí đa phương tiện.','Intel Core i5-12450HX, RAM 16GB DDR5, SSD 512GB, RTX 3050 6GB, màn hình 15.6 inch FHD 144Hz.',890,'Đang kinh doanh'),
('DELL-G16-7630-001','Dell G16 Gaming 7630',3,2,'dell-g16.jpg',31990000,7,8,'Laptop Dell G16 Gaming được trang bị cấu hình hiệu năng cao, màn hình độ phân giải lớn và tần số quét cao. Phù hợp cho game thủ và người dùng thường xuyên chơi các tựa game nặng.','Intel Core i7-13650HX, RAM 16GB DDR5, SSD 1TB, RTX 4060 8GB, màn hình 16 inch QHD+ 165Hz.',740,'Đang kinh doanh'),
('HP-VICTUS-16-001','HP Victus 16-s0175AX',4,2,'hp-victus-16.jpg',25490000,10,12,'Laptop HP Victus 16 có thiết kế gaming hiện đại, màn hình lớn và hiệu năng mạnh mẽ. Phù hợp cho chơi game, giải trí và xử lý các ứng dụng đồ họa phổ thông.','AMD Ryzen 5 7640HS, RAM 16GB DDR5, SSD 512GB, RTX 4050 6GB, màn hình 16.1 inch FHD 144Hz.',680,'Đang kinh doanh'),
('ACER-NITRO-V15-001','Acer Nitro V 15',2,2,'Acer Nitro V 15.jpg',19990000,5,15,'Laptop Acer Nitro V 15 được thiết kế dành cho game thủ với hiệu năng mạnh mẽ, card đồ họa rời và màn hình tần số quét cao. Phù hợp cho chơi game và các tác vụ đồ họa.','Intel Core i5-13420H, RAM 16GB DDR5, SSD 512GB, RTX 4050, màn hình 15.6 inch FHD 144Hz.',980,'Đang kinh doanh'),

-- Nhóm Laptop Đồ Họa (MaDanhMuc = 3)
('ASUS-PROART-16-001','ASUS ProArt Studiobook 16',1,3,'asus-proart-16.jpg',39990000,5,6,'Laptop ASUS ProArt Studiobook 16 được thiết kế dành cho nhà sáng tạo nội dung và chuyên gia thiết kế. Phù hợp với công việc chỉnh sửa hình ảnh, dựng phim và thiết kế mô hình 3D.','Intel Core i7-13700H, RAM 32GB DDR5, SSD 1TB, RTX 4060 8GB, màn hình 16 inch 3.2K OLED.',310,'Đang kinh doanh'),
('DELL-PRECISION-001','Dell Precision 3581',3,3,'dell-precision-3581.jpg',35990000,5,8,'Máy trạm Dell Precision 3581 được thiết kế cho môi trường làm việc chuyên nghiệp. Phù hợp với lập trình kỹ thuật, thiết kế CAD, dựng hình và xử lý các dự án đồ họa.','Intel Core i7-13800H, RAM 32GB, SSD 1TB, RTX A1000 6GB, màn hình 15.6 inch FHD.',450,'Đang kinh doanh'),
('MSI-CREATOR-M16-001','MSI Creator M16 B13V',6,3,'msi-creator-m16.jpg',33990000,10,7,'Laptop MSI Creator M16 hướng đến người làm thiết kế và sáng tạo nội dung. Sở hữu cấu hình mạnh, màn hình lớn và khả năng xử lý tốt các phần mềm chỉnh sửa ảnh, video.','Intel Core i7-13700H, RAM 16GB, SSD 512GB, RTX 4050 6GB, màn hình 16 inch QHD+ 144Hz.',380,'Đang kinh doanh'),
('LENOVO-THINKPAD-P1-001','Lenovo ThinkPad P1',5,3,'lenovo-thinkpad-p1.jpg',42990000,5,5,'Lenovo ThinkPad P1 là dòng máy trạm di động dành cho kỹ sư, kiến trúc sư và chuyên gia sáng tạo. Đáp ứng tốt nhu cầu thiết kế kỹ thuật, dựng hình và xử lý dữ liệu chuyên sâu.','Intel Core i7, RAM 32GB, SSD 1TB, NVIDIA RTX A1000, màn hình 16 inch WQXGA.',290,'Đang kinh doanh'),
('ASUS-VIVOBOOK-PRO-001','ASUS Vivobook Pro 15 OLED',1,3,'asus-vivobook-pro-15.jpg',27990000,5,9,'Laptop ASUS Vivobook Pro 15 OLED kết hợp hiệu năng xử lý mạnh mẽ với màn hình OLED chất lượng cao. Phù hợp cho thiết kế đồ họa, chỉnh sửa hình ảnh và sáng tạo nội dung đa phương tiện.','Intel Core i7, RAM 16GB, SSD 512GB, NVIDIA GeForce RTX 4050, màn hình 15.6 inch OLED.',410,'Đang kinh doanh'),

-- Nhóm Laptop Mỏng Nhẹ (MaDanhMuc = 4)
('ASUS-ZENBOOK-14-001','ASUS Zenbook 14 OLED UX3402',1,4,'asus-zenbook-14.jpg',24990000,10,25,'Laptop ASUS Zenbook 14 OLED sở hữu thiết kế mỏng nhẹ, vỏ ngoài cao cấp và màn hình OLED sắc nét. Phù hợp với người thường xuyên di chuyển, học tập và làm việc văn phòng.','Intel Core i5-1340P, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 14 inch 2.8K OLED 90Hz.',850,'Đang kinh doanh'),
('ACER-SWIFT-GO-001','Acer Swift Go 14 SFG14',2,4,'acer-swift-go-14.jpg',18990000,5,20,'Acer Swift Go 14 có thiết kế thanh lịch, trọng lượng gọn nhẹ và khả năng đáp ứng tốt công việc hằng ngày. Phù hợp với sinh viên và nhân viên văn phòng thường xuyên di chuyển.','Intel Core i5-1335U, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 14 inch 2.2K IPS.',520,'Đang kinh doanh'),
('LENOVO-YOGA-7-001','Lenovo Yoga 7 14IRL8',5,4,'lenovo-yoga-7.jpg',26990000,8,10,'Laptop Lenovo Yoga 7 sở hữu thiết kế xoay gập 360 độ linh hoạt, màn hình cảm ứng và kiểu dáng hiện đại. Phù hợp cho học tập, ghi chú, thuyết trình và làm việc sáng tạo.','Intel Core i5-1340P, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 14 inch 2.8K OLED cảm ứng.',610,'Đang kinh doanh'),
('HP-ENVY-14-001','HP Envy x360 14-es0013TU',4,4,'hp-envy-14.jpg',23990000,6,15,'HP Envy x360 sở hữu thiết kế sang trọng, khả năng xoay gập linh hoạt và màn hình cảm ứng tiện lợi. Phù hợp với người dùng văn phòng, sinh viên và những người thường xuyên làm việc di động.','Intel Core i5-1335U, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 14 inch FHD cảm ứng.',490,'Đang kinh doanh'),
('DELL-XPS-13-001','Dell XPS 13',3,4,'dell-xps-13.jpg',32990000,5,7,'Dell XPS 13 nổi bật với thiết kế nhỏ gọn, cao cấp và khả năng di động linh hoạt. Phù hợp với doanh nhân, sinh viên và người dùng thường xuyên làm việc khi di chuyển.','Intel Core i5, RAM 16GB, SSD 512GB, Intel Iris Xe, màn hình 13.4 inch FHD+.',360,'Đang kinh doanh'),

-- Nhóm MacBook (MaDanhMuc = 5)
('APPLE-MAC-AIR-M2-001','Apple MacBook Air 13 inch M2',7,5,'macbook-air-m2.jpg',26990000,7,20,'MacBook Air M2 sở hữu thiết kế mỏng nhẹ, hiệu năng ổn định và thời lượng pin tốt. Phù hợp với sinh viên, nhân viên văn phòng và người dùng thường xuyên di chuyển.','Apple M2 chip, RAM 8GB, SSD 256GB, màn hình 13.6 inch Liquid Retina.',1320,'Đang kinh doanh'),
('APPLE-MAC-PRO-14-001','Apple MacBook Pro 14 inch M3',7,5,'macbook-pro-14.jpg',39990000,5,10,'MacBook Pro 14 inch M3 mang đến hiệu năng xử lý mạnh mẽ, màn hình chất lượng cao và khả năng tối ưu cho công việc chuyên nghiệp. Phù hợp với lập trình viên và nhà sáng tạo nội dung.','Apple M3 chip, RAM 8GB, SSD 512GB, màn hình 14.2 inch Liquid Retina XDR.',940,'Đang kinh doanh'),
('APPLE-MAC-AIR-M3-001','Apple MacBook Air 15 inch M3',7,5,'macbook-air-15.jpg',32990000,6,12,'MacBook Air 15 inch M3 sở hữu màn hình lớn, thiết kế mỏng nhẹ và khả năng vận hành êm ái. Phù hợp với người dùng văn phòng, sinh viên và nhu cầu giải trí đa phương tiện.','Apple M3 chip, RAM 8GB, SSD 256GB, màn hình 15.3 inch Liquid Retina.',780,'Đang kinh doanh'),
('APPLE-MAC-AIR-M1-001','Apple MacBook Air 13 inch M1',7,5,'macbook-air-m1.jpg',18990000,5,15,'MacBook Air M1 có thiết kế nhỏ gọn, hoạt động ổn định và khả năng tiết kiệm năng lượng tốt. Phù hợp với sinh viên và người dùng có nhu cầu học tập, làm việc văn phòng.','Apple M1 chip, RAM 8GB, SSD 256GB, màn hình 13.3 inch Retina.',670,'Đang kinh doanh'),
('APPLE-MAC-PRO-16-M3-001','Apple MacBook Pro 16 inch M3 Pro',7,5,'macbook-pro-16-m3.jpg',52990000,5,5,'MacBook Pro 16 inch M3 Pro sở hữu màn hình lớn, hiệu năng xử lý mạnh mẽ và khả năng đáp ứng các công việc chuyên sâu. Phù hợp với lập trình viên, nhà thiết kế và người làm nội dung chuyên nghiệp.','Apple M3 Pro chip, RAM 18GB, SSD 512GB, màn hình 16.2 inch Liquid Retina XDR.',530,'Đang kinh doanh');
	
-- Bảng giỏ hàng
CREATE TABLE tbl_giohang (
    MaGioHang INT AUTO_INCREMENT PRIMARY KEY,
    MaNguoiDung INT NOT NULL,
    MaSanPham INT NOT NULL UNIQUE,
    SoLuong INT NOT NULL DEFAULT 1,
    CONSTRAINT fk_giohang_nguoidung
        FOREIGN KEY (MaNguoiDung)
        REFERENCES tbl_nguoidung(MaNguoiDung)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_giohang_sanpham
        FOREIGN KEY (MaSanPham)
        REFERENCES tbl_sanpham(IdSanPham)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_giohang_SoLuong
        CHECK (SoLuong > 0),
	INDEX idx_giohang_nguoidung (MaNguoiDung)
) ENGINE=InnoDB;
-- Bảng đơn hàng
CREATE TABLE tbl_donhang (
    MaDonHang INT AUTO_INCREMENT PRIMARY KEY,
    MaNguoiDung INT NOT NULL,
    PhiVanChuyen DECIMAL(15,2) NOT NULL DEFAULT 0,
    TongTien DECIMAL(15,2) NOT NULL DEFAULT 0,
    TrangThaiDonHang ENUM(
    'Chờ xác nhận',
    'Đã xác nhận',
    'Đang giao hàng',
    'Đã giao hàng',
    'Đã hủy'
) NOT NULL DEFAULT 'Chờ xác nhận',
    GhiChu TEXT,	

    CONSTRAINT fk_donhang_nguoidung
        FOREIGN KEY (MaNguoiDung)
        REFERENCES tbl_nguoidung(MaNguoiDung)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,

    INDEX idx_donhang_nguoidung (MaNguoiDung),
    INDEX idx_donhang_trangthai (TrangThaiDonHang),
    CONSTRAINT chk_donhang_vanchuyen
        CHECK (PhiVanChuyen >= 0),
    CONSTRAINT chk_donhang_tongtien
        CHECK (TongTien >= 0)
) ENGINE=InnoDB;

CREATE TABLE tbl_chitietdonhang (
    MaChiTiet INT AUTO_INCREMENT PRIMARY KEY,
    MaDonHang INT NOT NULL,
    MaSanPham INT NULL,
    TenSanPham VARCHAR(200) NOT NULL,
    GiaBan DECIMAL(15,2) NOT NULL,
    SoLuong INT NOT NULL,
    ThanhTien DECIMAL(15,2) NOT NULL,
    CONSTRAINT fk_chitiet_donhang
        FOREIGN KEY (MaDonHang)
        REFERENCES tbl_donhang(MaDonHang)
        ON UPDATE CASCADE
        ON DELETE CASCADE,

    CONSTRAINT fk_chitiet_sanpham
        FOREIGN KEY (MaSanPham)
        REFERENCES tbl_sanpham(IdSanPham)
        ON UPDATE CASCADE
        ON DELETE SET NULL,

    INDEX idx_chitiet_donhang (MaDonHang),
    INDEX idx_chitiet_sanpham (MaSanPham),
    CONSTRAINT chk_chitietdonhang_giaban
        CHECK (GiaBan >= 0),

    CONSTRAINT chk_chitietdonhang_soluong
        CHECK (SoLuong > 0)
) ENGINE=InnoDB;
CREATE TABLE tbl_anh_san_pham (
    ma_anh INT AUTO_INCREMENT PRIMARY KEY,
    id_san_pham INT NOT NULL,
    duong_dan_anh VARCHAR(255) NOT NULL,
    thu_tu_hien_thi INT DEFAULT 0,
    CONSTRAINT fk_anh_sanpham 
        FOREIGN KEY (id_san_pham) 
        REFERENCES tbl_sanpham(IdSanPham) 
        ON DELETE CASCADE
) ENGINE=InnoDB;
INSERT INTO tbl_anh_san_pham(id_san_pham, duong_dan_anh,thu_tu_hien_thi) VALUES
    (14, 'Acer Nitro V 15 (1).jpg',1),
    (14, 'Acer Nitro V 15 (2).jpg',2);
SELECT * FROM tbl_sanpham;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
DROP DATABASE ShopLapTop;
