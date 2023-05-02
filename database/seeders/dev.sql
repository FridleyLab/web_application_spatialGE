-- --------------------------------------------------------
-- Host:                         localhost
-- Server version:               5.7.24 - MySQL Community Server (GPL)
-- Server OS:                    Win32
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;

-- Dumping structure for table spatial-ge-dev.files

-- Dumping data for table spatial-ge-dev.files: ~6 rows (approximately)
/*!40000 ALTER TABLE `files` DISABLE KEYS */;
INSERT INTO `files` (`id`, `filename`, `type`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                             (1, 'GSM6433590_093D_filtered_feature_bc_matrix.h5', 'expressionFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (2, 'GSM6433590_093D_tissue_positions_list.csv', 'coordinatesFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (3, 'GSM6433596_095B_filtered_feature_bc_matrix.h5', 'expressionFile', '2023-03-16 11:11:52', '2023-03-16 11:11:52', NULL),
                                                                                             (4, 'GSM6433596_095B_tissue_positions_list.csv', 'coordinatesFile', '2023-03-16 11:11:52', '2023-03-16 11:11:52', NULL),
                                                                                             (5, 'GSM6433618_396C_filtered_feature_bc_matrix.h5', 'expressionFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (6, 'GSM6433618_396C_tissue_positions_list.csv', 'coordinatesFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
/*!40000 ALTER TABLE `files` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.file_sample: ~6 rows (approximately)
/*!40000 ALTER TABLE `file_sample` DISABLE KEYS */;
INSERT INTO `file_sample` (`id`, `file_id`, `sample_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                                       (1, 1, 1, NULL, NULL, NULL),
                                                                                                       (2, 2, 1, NULL, NULL, NULL),
                                                                                                       (3, 3, 2, NULL, NULL, NULL),
                                                                                                       (4, 4, 2, NULL, NULL, NULL),
                                                                                                       (5, 5, 3, NULL, NULL, NULL),
                                                                                                       (6, 6, 3, NULL, NULL, NULL);
/*!40000 ALTER TABLE `file_sample` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.projects: ~0 rows (approximately)
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` (`id`, `name`, `description`, `current_step`, `project_status_id`, `user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
    (1, 'Sandbox', 'Test Project', 1, 1, 9999, '2023-03-16 11:04:49', '2023-03-16 11:12:03', NULL);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.project_sample: ~2 rows (approximately)
/*!40000 ALTER TABLE `project_sample` DISABLE KEYS */;
INSERT INTO `project_sample` (`id`, `project_id`, `sample_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                                             (1, 1, 1, NULL, NULL, NULL),
                                                                                                             (2, 1, 2, NULL, NULL, NULL),
                                                                                                             (3, 1, 3, NULL, NULL, NULL);
/*!40000 ALTER TABLE `project_sample` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.samples: ~2 rows (approximately)
/*!40000 ALTER TABLE `samples` DISABLE KEYS */;
INSERT INTO `samples` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                   (1, 'sample_093d', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                   (2, 'sample_095b', '2023-03-16 11:11:51', '2023-03-16 11:11:51', NULL),
                                                                                   (3, 'sample_396c', '2023-03-16 11:11:58', '2023-03-16 11:11:58', NULL);
/*!40000 ALTER TABLE `samples` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.users: ~0 rows (approximately)
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verification_code`, `email_verified_at`, `password`, `industry`, `job`, `interest`, `remember_token`, `created_at`, `updated_at`) VALUES
    (9999, 'TestFirstName', 'TestLastName', 'Roberto.Manjarres-Betancur@moffitt.org', 'verified', '2023-03-16 10:53:36', '$2y$10$4jUWqrhPUAAPPDt8EfLLl.15IWBQIBs4pjl.j.pJO4EDnzQiD8Tou', 'test', 'test', 'test', NULL, NULL, NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;


/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
