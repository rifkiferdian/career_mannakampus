-- Perubahan database career_mannakampus untuk fitur sinkronisasi file CV.
-- Versi shared-hosting: tidak mengakses database information_schema.
-- Target: MySQL/MariaDB melalui phpMyAdmin.
-- Migration sumber: 2026-09-01-010000_AddLocalStorageTransferFields.php
--
-- PENTING:
-- 1. Backup database hosting sebelum menjalankan file ini.
-- 2. Pilih database career_mannakampus milik aplikasi di phpMyAdmin.
-- 3. Jalankan file ini SATU KALI saja.
-- 4. Jika phpMyAdmin sebelumnya menampilkan error #1044 information_schema,
--    gunakan versi file ini dari awal; query yang gagal tersebut belum
--    menambahkan kolom ke applicant_documents.

SET NAMES utf8mb4;

ALTER TABLE `applicant_documents`
    ADD COLUMN `sha256_checksum` CHAR(64) NULL AFTER `file_size`,
    ADD COLUMN `local_transfer_status` VARCHAR(20) NULL DEFAULT 'pending' AFTER `sha256_checksum`,
    ADD COLUMN `local_transferred_at` DATETIME NULL AFTER `local_transfer_status`,
    ADD COLUMN `local_confirmed_checksum` CHAR(64) NULL AFTER `local_transferred_at`,
    ADD COLUMN `local_confirmed_size` BIGINT(20) UNSIGNED NULL AFTER `local_confirmed_checksum`,
    ADD COLUMN `hosting_deleted_at` DATETIME NULL AFTER `local_confirmed_size`,
    ADD INDEX `applicant_documents_local_transfer_idx`
        (`local_transfer_status`, `hosting_deleted_at`, `created_at`);

-- Tandai migration sebagai sudah dijalankan agar `php spark migrate`
-- tidak mencoba menambahkan kolom yang sama pada deployment berikutnya.
SET @migration_batch := COALESCE((SELECT MAX(`batch`) FROM `migrations`), 0) + 1;

INSERT INTO `migrations` (`version`, `class`, `group`, `namespace`, `time`, `batch`)
SELECT
    '2026-09-01-010000',
    'App\\Database\\Migrations\\AddLocalStorageTransferFields',
    'default',
    'App',
    UNIX_TIMESTAMP(),
    @migration_batch
WHERE NOT EXISTS (
    SELECT 1
    FROM `migrations`
    WHERE `version` = '2026-09-01-010000'
      AND `class` = 'App\\Database\\Migrations\\AddLocalStorageTransferFields'
      AND `group` = 'default'
      AND `namespace` = 'App'
);

-- Pemeriksaan hasil tanpa mengakses information_schema.
SHOW COLUMNS FROM `applicant_documents`;
SHOW INDEX FROM `applicant_documents`;
