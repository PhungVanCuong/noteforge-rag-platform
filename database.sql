-- =============================================================================
-- RAG Study & Document Conversion Platform (NoteForge AI)
-- Database: MySQL 8.0+
-- Character set: utf8mb4 / Collation: utf8mb4_unicode_ci
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `noteforge_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `noteforge_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Table: users
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NULL DEFAULT NULL,
  `google_id` VARCHAR(255) NULL DEFAULT NULL,
  `avatar` VARCHAR(500) NULL DEFAULT NULL,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `custom_gemini_key` TEXT NULL DEFAULT NULL COMMENT 'AES-256 encrypted personal Gemini API Key',
  `remember_token` VARCHAR(100) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_google_id_index` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 2. Table: email_otps
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `email_otps`;
CREATE TABLE `email_otps` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `otp_code` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `email_otps_email_index` (`email`),
  KEY `email_otps_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 3. Table: notebooks
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `notebooks`;
CREATE TABLE `notebooks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `subject_code` VARCHAR(50) NULL DEFAULT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notebooks_user_id_foreign` (`user_id`),
  CONSTRAINT `notebooks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 4. Table: documents
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `documents`;
CREATE TABLE `documents` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notebook_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(50) NOT NULL,
  `total_pages` INT UNSIGNED NOT NULL DEFAULT 0,
  `rag_status` ENUM('pending', 'indexed', 'failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `documents_notebook_id_foreign` (`notebook_id`),
  KEY `documents_user_id_foreign` (`user_id`),
  KEY `documents_rag_status_index` (`rag_status`),
  CONSTRAINT `documents_notebook_id_foreign` FOREIGN KEY (`notebook_id`) REFERENCES `notebooks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `documents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 5. Table: conversion_jobs
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `conversion_jobs`;
CREATE TABLE `conversion_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `tool_type` ENUM('pdf2word', 'word2pdf', 'merge_pdf', 'split_pdf', 'img_convert', 'img2pdf', 'vid2mp3', 'transcribe') NOT NULL,
  `input_files` JSON NOT NULL,
  `output_path` VARCHAR(500) NULL DEFAULT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `conversion_jobs_user_id_foreign` (`user_id`),
  KEY `conversion_jobs_status_index` (`status`),
  CONSTRAINT `conversion_jobs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 6. Table: study_sets
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `study_sets`;
CREATE TABLE `study_sets` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `notebook_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `share_slug` VARCHAR(64) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `categories_json` JSON NOT NULL,
  `questions_json` JSON NOT NULL,
  `code_exercises_json` JSON NOT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `study_sets_share_slug_unique` (`share_slug`),
  KEY `study_sets_notebook_id_foreign` (`notebook_id`),
  KEY `study_sets_user_id_foreign` (`user_id`),
  CONSTRAINT `study_sets_notebook_id_foreign` FOREIGN KEY (`notebook_id`) REFERENCES `notebooks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `study_sets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 7. Table: question_progress
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `question_progress`;
CREATE TABLE `question_progress` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `study_set_id` BIGINT UNSIGNED NOT NULL,
  `question_uid` VARCHAR(64) NOT NULL,
  `status` ENUM('right', 'wrong') NOT NULL,
  `wrong_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `last_practiced_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_study_question_unique` (`user_id`, `study_set_id`, `question_uid`),
  KEY `question_progress_status_index` (`status`),
  CONSTRAINT `question_progress_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `question_progress_study_set_id_foreign` FOREIGN KEY (`study_set_id`) REFERENCES `study_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 8. Table: exam_attempts
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `exam_attempts`;
CREATE TABLE `exam_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `study_set_id` BIGINT UNSIGNED NOT NULL,
  `score_10` DECIMAL(4,2) NOT NULL,
  `correct_count` INT UNSIGNED NOT NULL,
  `total_questions` INT UNSIGNED NOT NULL,
  `duration_seconds` INT UNSIGNED NOT NULL,
  `answers_detail_json` JSON NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_attempts_user_id_foreign` (`user_id`),
  KEY `exam_attempts_study_set_id_foreign` (`study_set_id`),
  CONSTRAINT `exam_attempts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_attempts_study_set_id_foreign` FOREIGN KEY (`study_set_id`) REFERENCES `study_sets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 9. Table: personal_access_tokens (Laravel Sanctum)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` VARCHAR(255) NOT NULL,
  `tokenable_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `abilities` TEXT NULL DEFAULT NULL,
  `last_used_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`, `tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Seed Sample Data for Testing / Demonstration
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `email_verified_at`)
VALUES
  (1, 'Sinh Viên Mẫu', 'student@noteforge.edu.vn', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', NOW());

INSERT INTO `notebooks` (`id`, `user_id`, `title`, `subject_code`, `description`)
VALUES
  (1, 1, 'Lập trình Web & Microservices', 'IT4020', 'Môn học về kiến trúc web hiện đại, Laravel, Vue 3 và AI Microservices.'),
  (2, 1, 'Trí tuệ nhân tạo & RAG', 'AI3010', 'Tìm kiếm thông tin, Retrieval-Augmented Generation, Vector Database.');

INSERT INTO `study_sets` (`id`, `notebook_id`, `user_id`, `share_slug`, `title`, `categories_json`, `questions_json`, `code_exercises_json`, `is_public`)
VALUES
  (1, 1, 1, 'demo-lap-trinh-web-2026', 'Bộ Đề Ôn Tập Trắc Nghiệm & Code Microservices',
   JSON_ARRAY('Kiến trúc Microservices', 'RAG & Vector Database', 'Laravel Core'),
   JSON_ARRAY(
     JSON_OBJECT(
       'id', 'Q1',
       'cat', 0,
       'en', 'Trong kiến trúc Hybrid Microservices, vai trò chính của Laravel Backend là gì?',
       'opts', JSON_ARRAY(
         'Chỉ dùng để tính toán vector embedding',
         'API Gateway, quản lý người dùng, phân quyền Sanctum và điều phối nghiệp vụ',
         'Thay thế hoàn toàn Python trong việc convert file',
         'Lưu trữ trực tiếp các vector dense 768 chiều'
       ),
       'ans', 1,
       'ex', 'Laravel 11 đóng vai trò Core API Gateway, xử lý Auth, OTP, quản lý DB MySQL quan hệ và chuyển tiếp các tác vụ AI nặng sang Python FastAPI.'
     ),
     JSON_OBJECT(
       'id', 'Q2',
       'cat', 1,
       'en', 'Nguyên tắc Strict Grounding trong RAG Assistant được thực hiện như thế nào để chống bịa đặt (hallucination)?',
       'opts', JSON_ARRAY(
         'Cho phép LLM tự do tìm kiếm trên Internet nếu tài liệu thiếu',
         'Chỉ sử dụng các đoạn trích đạt ngưỡng cosine threshold >= 0.65 và từ chối nếu không tìm thấy dữ liệu trong giáo trình',
         'Bỏ qua bước chunking và gửi toàn bộ file PDF vào prompt',
         'Chỉ trích xuất câu hỏi từ tiêu đề file'
       ),
       'ans', 1,
       'ex', 'Hệ thống ràng buộc chặt chẽ top 5 chunks vượt ngưỡng điểm tương đồng >= 0.65. Nếu không đủ dữ liệu, AI sẽ lập tức từ chối theo mẫu quy định.'
     )
   ),
   JSON_ARRAY(
     JSON_OBJECT(
       'title', 'Khởi tạo FastAPI Application và CORS Middleware',
       'code', 'from fastapi import FastAPI\nfrom fastapi.middleware.cors import [[CORSMiddleware]]\n\napp = [[FastAPI]](title="NoteForge AI Engine")\n\napp.add_middleware(\n    [[CORSMiddleware]],\n    allow_origins=["*"],\n    allow_credentials=True,\n    allow_methods=["*"],\n    allow_headers=["*"],\n)'
     )
   ),
   1
);
