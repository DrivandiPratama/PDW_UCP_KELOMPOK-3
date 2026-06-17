-- ===============================================================
-- Seed data demo
-- Password default: owner123 / pengguna123 (sudah di-bcrypt PHP)
-- ===============================================================

-- Hash dihasilkan oleh password_hash('owner123', PASSWORD_BCRYPT)
-- Hash dihasilkan oleh password_hash('pengguna123', PASSWORD_BCRYPT)
-- (Hash di bawah valid untuk dua password di atas)



INSERT INTO kamar (kode, tipe, ukuran_m2, fasilitas, harga_bulanan, status, foto_utama, deskripsi) VALUES
('K-101', 'Standar', 9.00,  'Kasur, lemari, meja belajar, kipas angin',                       750000,  'tersedia', 'https://images.unsplash.com/photo-1505691938895-1758d7feb511?w=800', 'Kamar standar nyaman untuk mahasiswa.'),
('K-102', 'Standar', 9.00,  'Kasur, lemari, meja belajar, kipas angin',                       750000,  'terisi',   'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'Kamar standar nyaman untuk mahasiswa.'),
('K-201', 'Deluxe',  12.00, 'AC, WiFi, lemari, meja belajar, kamar mandi dalam',              1250000, 'tersedia', 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800', 'Kamar deluxe dengan kamar mandi dalam.'),
('K-202', 'Deluxe',  12.00, 'AC, WiFi, lemari, meja belajar, kamar mandi dalam',              1250000, 'terisi',   'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800', 'Kamar deluxe dengan kamar mandi dalam.'),
('K-301', 'VIP',     16.00, 'AC, WiFi, TV, kulkas, kamar mandi dalam, water heater, balkon',  1850000, 'tersedia', 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800', 'Kamar VIP fasilitas lengkap.'),
('K-302', 'VIP',     16.00, 'AC, WiFi, TV, kulkas, kamar mandi dalam, water heater',          1750000, 'tersedia', 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800', 'Kamar VIP nyaman dan luas.');

INSERT INTO booking (kode_booking, user_id, kamar_id, nama_lengkap, telepon, email, tanggal_mulai, durasi_bulan, status, approved_at) VALUES
('BK-2026-001', 2, 2, 'Andi Saputra', '0812-2000-0002', 'penghuni@kos.id', '2026-04-01', 6, 'ongoing',  '2026-03-25 10:00:00'),
('BK-2026-002', 3, 4, 'Budi Hartono', '0812-2000-0003', 'budi@kos.id',     '2026-04-15', 12,'ongoing',  '2026-04-10 14:30:00'),
('BK-2026-003', 4, 1, 'Citra Dewi',   '0812-2000-0004', 'citra@kos.id',    '2026-06-01', 3, 'pending',  NULL);

INSERT INTO tagihan (booking_id, user_id, kamar_id, periode, nominal, due_date, status) VALUES
(1, 2, 2, '2026-04', 750000,  '2026-04-05', 'lunas'),
(1, 2, 2, '2026-05', 750000,  '2026-05-05', 'lunas'),
(1, 2, 2, '2026-06', 750000,  '2026-06-05', 'belum'),
(2, 3, 4, '2026-04', 1250000, '2026-04-20', 'lunas'),
(2, 3, 4, '2026-05', 1250000, '2026-05-20', 'belum');

INSERT INTO pembayaran (user_id, tagihan_id, booking_id, nominal, metode, bukti_path, status, verified_at, verified_by) VALUES
(2, 1, 1, 750000,  'transfer', NULL, 'verified', '2026-04-06 09:00:00', 1),
(2, 2, 1, 750000,  'transfer', NULL, 'verified', '2026-05-06 09:00:00', 1),
(3, 4, 2, 1250000, 'transfer', NULL, 'verified', '2026-04-21 11:00:00', 1);

INSERT INTO pengumuman (judul, isi, kategori, created_by) VALUES
('Jadwal Kebersihan Bersama', 'Kerja bakti hari Minggu pagi pukul 07.00. Mohon partisipasi seluruh penghuni.', 'kebersihan', 1),
('Pemadaman Listrik Sementara', 'PLN akan memadamkan listrik Sabtu pukul 10.00 - 14.00. Mohon siapkan keperluan masing-masing.', 'listrik', 1),
('Kenaikan Tagihan Air', 'Mulai bulan depan tagihan air akan dirapel ke biaya bulanan, naik Rp 25.000.', 'umum', 1);
