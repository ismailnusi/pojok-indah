-- Database Web Penjualan & Percetakan
CREATE DATABASE IF NOT EXISTS web_penjualan;
USE web_penjualan;

-- Tabel Kategori
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(20) DEFAULT '📦',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Produk
CREATE TABLE IF NOT EXISTS produk (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_kategori INT NOT NULL,
    nama_produk VARCHAR(200) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(12,0) NOT NULL DEFAULT 0,
    satuan VARCHAR(50) DEFAULT 'pcs',
    stok INT DEFAULT 0,
    minimal_order INT DEFAULT 1,
    waktu_pengerjaan VARCHAR(100) DEFAULT '1-2 hari',
    gambar VARCHAR(500) DEFAULT 'default.svg',
    is_unggulan TINYINT(1) DEFAULT 0,
    is_aktif TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Pesanan
CREATE TABLE IF NOT EXISTS pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pesanan VARCHAR(20) NOT NULL UNIQUE,
    nama_pemesan VARCHAR(200) NOT NULL,
    no_hp VARCHAR(20) NOT NULL,
    email VARCHAR(200),
    alamat TEXT,
    catatan TEXT,
    total_harga DECIMAL(12,0) NOT NULL DEFAULT 0,
    status ENUM('pending','diproses','selesai','dibatalkan') DEFAULT 'pending',
    metode_bayar VARCHAR(50) DEFAULT 'COD',
    bukti_bayar VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Item Pesanan
CREATE TABLE IF NOT EXISTS detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pesanan INT NOT NULL,
    id_produk INT NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,0) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,0) NOT NULL DEFAULT 0,
    catatan_item TEXT,
    FOREIGN KEY (id_pesanan) REFERENCES pesanan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tabel Admin
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabel Pengaturan (tema situs: gelap / terang)
CREATE TABLE IF NOT EXISTS pengaturan (
    kunci VARCHAR(50) NOT NULL PRIMARY KEY,
    nilai TEXT
) ENGINE=InnoDB;

-- Tabel Galeri Produk (maks 3 gambar per produk)
CREATE TABLE IF NOT EXISTS produk_gambar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_produk INT NOT NULL,
    nama_file VARCHAR(255) NOT NULL,
    urutan INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_produk) REFERENCES produk(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert Kategori Default (aman diimport ulang)
INSERT IGNORE INTO kategori (nama_kategori, icon) VALUES
('Cetak Banner', '🖨️'),
('Cetak Kartu Nama', '💼'),
('Cetak Brosur', '📄'),
('Cetak Stiker', '🏷️'),
('Cetak Kaos', '👕'),
('Cetak Mug', '☕'),
('Cetak Kalender', '📅'),
('Cetak Spanduk', '🚩'),
('Sablon & Promosi', '🎯'),
('Akrilik & Neon Box', '💡');

-- Insert Produk Default (hanya untuk import pertama; import ulang akan menambah duplikat)
INSERT INTO produk (id_kategori, nama_produk, deskripsi, harga, satuan, stok, minimal_order, waktu_pengerjaan, gambar, is_unggulan) VALUES
(1, 'Banner Flexi 280g', 'Banner flexi standar untuk indoor/outdoor, tahan cuaca. Resolusi cetak tinggi.', 15000, 'meter', 100, 1, '1 hari', 'banner_flexi.svg', 1),
(1, 'Banner Backdrop Event', 'Backdrop custom untuk event, wedding, seminar. Bahan premium.', 35000, 'meter', 50, 1, '2 hari', 'banner_backdrop.svg', 1),
(1, 'X-Banner 60x160cm', 'X-Banner portable dengan stand lipat, mudah dibawa.', 85000, 'pcs', 30, 1, '1 hari', 'x_banner.svg', 0),
(1, 'Roll Banner 85x200cm', 'Roll-up banner premium, cocok untuk pameran dan promosi.', 120000, 'pcs', 25, 1, '1-2 hari', 'roll_banner.svg', 1),

(2, 'Kartu Nama Biasa', 'Kertas art carton 260g, ukuran 9x5cm. Desain free template.', 25000, 'box 100', 200, 1, '1 hari', 'kartu_nama_biasa.svg', 0),
(2, 'Kartu Nama Premium', 'Kertas art paper 300g, laminasi glossy/doff. Full color.', 45000, 'box 100', 150, 1, '1-2 hari', 'kartu_nama_premium.svg', 1),
(2, 'Kartu Nama PVC', 'Bahan PVC tahan air, cocok untuk kartu member/identitas.', 85000, 'box 100', 100, 1, '2-3 hari', 'kartu_nama_pvc.svg', 0),

(3, 'Brosur A4', 'Brosur ukuran A4, kertas art paper 150g. Double side.', 500, 'lembar', 500, 100, '1-2 hari', 'brosur_a4.svg', 0),
(3, 'Brosur A5', 'Brosur ukuran A5, cocok untuk promosi usaha. Full color.', 350, 'lembar', 500, 100, '1-2 hari', 'brosur_a5.svg', 0),
(3, 'Flyer / Leaflet', 'Flyer promosi ukuran A5/A6, kertas art paper.', 250, 'lembar', 1000, 100, '1 hari', 'flyer.svg', 0),

(4, 'Stiker Vinyl', 'Stiker vinyl tahan air, cocok untuk label produk. Custom shape.', 3000, 'lembar', 300, 10, '1 hari', 'stiker_vinyl.svg', 0),
(4, 'Stiker Kertas', 'Stiker kertas standar, harga terjangkau.', 1500, 'lembar', 500, 10, '1 hari', 'stiker_kertas.svg', 0),

(5, 'Kaos Sablon Manual', 'Kaos cotton combed 30s, sablon manual tahan lama.', 45000, 'pcs', 100, 5, '3-5 hari', 'kaos_sablon.svg', 1),
(5, 'Kaos DTG', 'Kaos full color dengan teknologi DTG, detail tinggi.', 75000, 'pcs', 50, 1, '2-3 hari', 'kaos_dtg.svg', 1),
(5, 'Kaos Polyflex', 'Kaos dengan sablon polyflex, warna mencolok.', 55000, 'pcs', 80, 5, '3-4 hari', 'kaos_polyflex.svg', 0),

(6, 'Mug Standar', 'Mug keramik putih, cetak full wrap. Cocok untuk hadiah.', 25000, 'pcs', 100, 2, '2-3 hari', 'mug_standar.svg', 0),
(6, 'Mug Magic / HITAM', 'Mug ajaib yang berubah warna saat diisi air panas.', 45000, 'pcs', 50, 2, '3-4 hari', 'mug_magic.svg', 1),

(7, 'Kalender Dinding', 'Kalender dinding A3, full color. Bisa custom foto/kolase.', 20000, 'pcs', 100, 10, '3-5 hari', 'kalender_dinding.svg', 0),
(7, 'Kalender Meja', 'Kalender meja kecil, ring binder. Cocok untuk kantor.', 15000, 'pcs', 100, 10, '3-5 hari', 'kalender_meja.svg', 0),

(8, 'Spanduk Biasa', 'Spanduk bahan flexi, untuk event atau promosi toko.', 12000, 'meter', 100, 1, '1 hari', 'spanduk_biasa.svg', 0),
(8, 'Spanduk Borda', 'Spanduk dengan tepi bordir, lebih rapi dan elegan.', 18000, 'meter', 50, 1, '2-3 hari', 'spanduk_borda.svg', 0),

(9, 'Totebag Kanvas', 'Totebag kanvas premium, sablon 1-2 warna.', 25000, 'pcs', 100, 10, '3-5 hari', 'totebag.svg', 0),
(9, 'Pin / Lencana', 'Pin diameter 4.4cm, cetak full color. Cocok untuk komunitas.', 5000, 'pcs', 500, 20, '2 hari', 'pin.svg', 0),

(10, 'Neon Box', 'Neon box custom, akrilik + rangka aluminium. Termasuk lampu.', 350000, 'pcs', 10, 1, '5-7 hari', 'neon_box.svg', 1),
(10, 'Huruf Timbul Akrilik', 'Huruf timbul dari akrilik, bisa backlit LED.', 150000, 'pcs', 20, 1, '5-7 hari', 'huruf_timbul.svg', 1);

-- Insert Admin Default (password: admin123)
INSERT IGNORE INTO admin (username, password, nama_lengkap) VALUES
('admin', '$2y$10$4xJehZSVZyftXDXFe1AvvO8LvK2cZ0usH1diMK3FMz5KpIQuNGbl.', 'Administrator');

-- Tema default situs
INSERT IGNORE INTO pengaturan (kunci, nilai) VALUES ('tema', 'gelap');
