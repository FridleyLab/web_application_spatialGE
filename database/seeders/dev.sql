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
                                                                                             (1, 'GSM6433611_120D_filtered_feature_bc_matrix.h5', 'expressionFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (2, 'GSM6433611_120D_tissue_positions_list.csv', 'coordinatesFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (3, 'GSM6433611_120D_tissue_hires_image.png', 'imageFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (4, 'GSM6433611_120D_scalefactors_json.json', 'scaleFile', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                             (5, 'GSM6433617_396A_filtered_feature_bc_matrix.h5', 'expressionFile', '2023-03-16 11:11:52', '2023-03-16 11:11:52', NULL),
                                                                                             (6, 'GSM6433617_396A_tissue_positions_list.csv', 'coordinatesFile', '2023-03-16 11:11:52', '2023-03-16 11:11:52', NULL);
/*!40000 ALTER TABLE `files` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.file_sample: ~6 rows (approximately)
/*!40000 ALTER TABLE `file_sample` DISABLE KEYS */;
INSERT INTO `file_sample` (`id`, `file_id`, `sample_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                                       (1, 1, 1, NULL, NULL, NULL),
                                                                                                       (2, 2, 1, NULL, NULL, NULL),
                                                                                                       (3, 3, 1, NULL, NULL, NULL),
                                                                                                       (4, 4, 1, NULL, NULL, NULL),
                                                                                                       (5, 5, 2, NULL, NULL, NULL),
                                                                                                       (6, 6, 2, NULL, NULL, NULL);
/*!40000 ALTER TABLE `file_sample` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.projects: ~0 rows (approximately)
/*!40000 ALTER TABLE `projects` DISABLE KEYS */;
INSERT INTO `projects` (`id`, `name`, `description`, `current_step`, `project_status_id`, `user_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
    (1, 'Project with Visium', 'Test Project', 1, 1, 9999, '2023-03-16 11:04:49', '2023-03-16 11:12:03', NULL);
/*!40000 ALTER TABLE `projects` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.project_sample: ~2 rows (approximately)
/*!40000 ALTER TABLE `project_sample` DISABLE KEYS */;
INSERT INTO `project_sample` (`id`, `project_id`, `sample_id`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                                             (1, 1, 1, NULL, NULL, NULL),
                                                                                                             (2, 1, 2, NULL, NULL, NULL);
/*!40000 ALTER TABLE `project_sample` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.samples: ~2 rows (approximately)
/*!40000 ALTER TABLE `samples` DISABLE KEYS */;
INSERT INTO `samples` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
                                                                                   (1, 'Sample01', '2023-03-16 11:11:16', '2023-03-16 11:11:16', NULL),
                                                                                   (2, 'Sample02', '2023-03-16 11:11:51', '2023-03-16 11:11:51', NULL);
/*!40000 ALTER TABLE `samples` ENABLE KEYS */;


-- Dumping data for table spatial-ge-dev.users: ~0 rows (approximately)
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `email_verification_code`, `email_verified_at`, `password`, `industry`, `job`, `interest`, `remember_token`, `created_at`, `updated_at`) VALUES
    (9999, 'TestFirstName', 'TestLastName', 'test@moffitt.org', 'verified', '2023-03-16 10:53:36', '$2y$10$4jUWqrhPUAAPPDt8EfLLl.15IWBQIBs4pjl.j.pJO4EDnzQiD8Tou', 'test', 'test', 'test', NULL, NULL, NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;


insert into color_palettes(label, value) values('Vik','vik');
insert into color_palettes(label, value) values('Berlin','berlin');
insert into color_palettes(label, value) values('Roma','roma');
insert into color_palettes(label, value) values('Bam','bam');
insert into color_palettes(label, value) values('Vanimo','vanimo');
insert into color_palettes(label, value) values('La Jolla','lajolla');
insert into color_palettes(label, value) values('Bamako','bamako');
insert into color_palettes(label, value) values('Nuuk','nuuk');
insert into color_palettes(label, value) values('GrayC','grayC');
insert into color_palettes(label, value) values('Hawaii','hawaii');
insert into color_palettes(label, value) values('Tokyo','tokyo');
insert into color_palettes(label, value) values('Buda','buda');
insert into color_palettes(label, value) values('Acton','acton');
insert into color_palettes(label, value) values('Imola','imola');
insert into color_palettes(label, value) values('Batlow','batlow');
insert into color_palettes(label, value) values('Cork','corkO');
insert into color_palettes(label, value) values('Sunset','sunset');
insert into color_palettes(label, value) values('Discrete Rainbow','discreterainbow');
insert into color_palettes(label, value) values('Smooth Rainbow','smoothrainbow');
insert into color_palettes(label, value) values('Okabe Ito','okabeito');
insert into color_palettes(label, value) values('Soil','soil');
insert into color_palettes(label, value) values('BrownGreen','BrBG');
insert into color_palettes(label, value) values('Pink-Green','PiYG');
insert into color_palettes(label, value) values('Purple-Green','PRGn');
insert into color_palettes(label, value) values('Purple-Orange','PuOr');
insert into color_palettes(label, value) values('Red-Blue','RdBu');
insert into color_palettes(label, value) values('Red-Gray','RdGy');
insert into color_palettes(label, value) values('Red-Yellow-Blue','RdYlBu');
insert into color_palettes(label, value) values('Red-Yellow-Green','RdYlGn');
insert into color_palettes(label, value) values('Spectral','Spectral');
insert into color_palettes(label, value) values('Accent','Accent');
insert into color_palettes(label, value) values('Dark2','Dark2');
insert into color_palettes(label, value) values('Paired','Paired');
insert into color_palettes(label, value) values('Pastel1','Pastel1');
insert into color_palettes(label, value) values('Pastel2','Pastel2');
insert into color_palettes(label, value) values('Set1','Set1');
insert into color_palettes(label, value) values('Set2','Set2');
insert into color_palettes(label, value) values('Set3','Set3');
insert into color_palettes(label, value) values('Blues','Blues');
insert into color_palettes(label, value) values('Blue-Green','BuGn');
insert into color_palettes(label, value) values('Blue-Purple','BuPu');
insert into color_palettes(label, value) values('Green-Blue','GnBu');
insert into color_palettes(label, value) values('Greens','Greens');
insert into color_palettes(label, value) values('Greys','Greys');
insert into color_palettes(label, value) values('Oranges','Oranges');
insert into color_palettes(label, value) values('Orange-Red','OrRd');
insert into color_palettes(label, value) values('Purple-Blue','PuBu');
insert into color_palettes(label, value) values('Purple-Blue-Green','PuBuGn');
insert into color_palettes(label, value) values('Purple-Red','PuRd');
insert into color_palettes(label, value) values('Purples','Purples');
insert into color_palettes(label, value) values('Red-Purple','RdPu');
insert into color_palettes(label, value) values('Reds','Reds');
insert into color_palettes(label, value) values('Yellow-Green','YlGn');
insert into color_palettes(label, value) values('Yellow-Green-Blue','YlGnBu');
insert into color_palettes(label, value) values('Yellow-Orange-Brown','YlOrBr');
insert into color_palettes(label, value) values('Yellow-Orange-Red','YlOrRd');



/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
