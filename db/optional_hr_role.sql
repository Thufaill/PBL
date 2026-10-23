-- OPSIONAL: aktifkan role 'hr' di database.
-- Skema final (db/schema.sql) hanya punya 'user' dan 'admin' (Opsi A). Konsol HR di public/hr/
-- baru bisa dipakai login jika ada akun ber-role 'hr'. Jalankan sekali di luar transaksi:
ALTER TYPE user_role ADD VALUE IF NOT EXISTS 'hr';
