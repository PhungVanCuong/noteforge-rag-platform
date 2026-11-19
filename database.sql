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
-- DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
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
-- DROP TABLE IF EXISTS `email_otps`;
CREATE TABLE IF NOT EXISTS `email_otps` (
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
-- DROP TABLE IF EXISTS `notebooks`;
CREATE TABLE IF NOT EXISTS `notebooks` (
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
-- DROP TABLE IF EXISTS `documents`;
CREATE TABLE IF NOT EXISTS `documents` (
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
-- DROP TABLE IF EXISTS `conversion_jobs`;
CREATE TABLE IF NOT EXISTS `conversion_jobs` (
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
-- DROP TABLE IF EXISTS `study_sets`;
CREATE TABLE IF NOT EXISTS `study_sets` (
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
-- DROP TABLE IF EXISTS `question_progress`;
CREATE TABLE IF NOT EXISTS `question_progress` (
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
-- DROP TABLE IF EXISTS `exam_attempts`;
CREATE TABLE IF NOT EXISTS `exam_attempts` (
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
-- DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
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

-- -----------------------------------------------------------------------------
-- 10. Table: cache & cache_locks (Laravel Database Cache Driver)
-- -----------------------------------------------------------------------------
-- DROP TABLE IF EXISTS `cache`;
CREATE TABLE IF NOT EXISTS `cache` (
  `key` VARCHAR(255) NOT NULL,
  `value` MEDIUMTEXT NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE IF NOT EXISTS `cache_locks` (
  `key` VARCHAR(255) NOT NULL,
  `owner` VARCHAR(255) NOT NULL,
  `expiration` INT NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 11. Table: sessions (Laravel Database Session Driver)
-- -----------------------------------------------------------------------------
-- DROP TABLE IF EXISTS `sessions`;
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` VARCHAR(255) NOT NULL,
  `user_id` BIGINT UNSIGNED NULL DEFAULT NULL,
  `ip_address` VARCHAR(45) NULL DEFAULT NULL,
  `user_agent` TEXT NULL DEFAULT NULL,
  `payload` LONGTEXT NOT NULL,
  `last_activity` INT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- 12. Tables: jobs, job_batches, failed_jobs (Laravel Queue Database Driver)
-- -----------------------------------------------------------------------------
-- DROP TABLE IF EXISTS `jobs`;
CREATE TABLE IF NOT EXISTS `jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` VARCHAR(255) NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `attempts` TINYINT UNSIGNED NOT NULL,
  `reserved_at` INT UNSIGNED NULL DEFAULT NULL,
  `available_at` INT UNSIGNED NOT NULL,
  `created_at` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE IF NOT EXISTS `job_batches` (
  `id` VARCHAR(255) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `total_jobs` INT NOT NULL,
  `pending_jobs` INT NOT NULL,
  `failed_jobs` INT NOT NULL,
  `failed_job_ids` LONGTEXT NOT NULL,
  `options` MEDIUMTEXT NULL,
  `cancelled_at` INT NULL,
  `created_at` INT NOT NULL,
  `finished_at` INT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` VARCHAR(255) NOT NULL,
  `connection` TEXT NOT NULL,
  `queue` TEXT NOT NULL,
  `payload` LONGTEXT NOT NULL,
  `exception` LONGTEXT NOT NULL,
  `failed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- Seed Sample Data: Ready-to-Use Accounts, Notebooks & 50-Question Study Set
-- (Mật khẩu mặc định cho tất cả tài khoản mẫu: password123)
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `name`, `email`, `password`, `avatar`, `email_verified_at`)
VALUES
  (1, 'Phùng Văn Cường (Admin)', 'cuongphung1810@gmail.com', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', 'https://ui-avatars.com/api/?name=Cuong+Phung&background=0284c7&color=fff&bold=true', NOW()),
  (2, 'Quản Trị Viên Hệ Thống', 'admin@noteforge.ai', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', 'https://ui-avatars.com/api/?name=Admin+NoteForge&background=4f46e5&color=fff&bold=true', NOW()),
  (3, 'Nguyễn Văn An (Sinh viên CNTT)', 'sinhvien@noteforge.ai', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', 'https://ui-avatars.com/api/?name=Nguyen+Van+An&background=10b981&color=fff&bold=true', NOW()),
  (4, 'TS. Trần Minh Đức (Giảng viên)', 'giangvien@noteforge.ai', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', 'https://ui-avatars.com/api/?name=Tran+Minh+Duc&background=f59e0b&color=fff&bold=true', NOW()),
  (5, 'Tài Khoản Trải Nghiệm Demo', 'demo@noteforge.ai', '$2y$12$eImiTXuWVxfM37uY4JANj.bF3g3Vq3q0sJd1Wc9Nf1L02v2vF3LzK', 'https://ui-avatars.com/api/?name=Demo+User&background=ec4899&color=fff&bold=true', NOW())
ON DUPLICATE KEY UPDATE `email_verified_at` = NOW(), `name` = VALUES(`name`);

INSERT INTO `notebooks` (`id`, `user_id`, `title`, `subject_code`, `description`)
VALUES
  (1, 1, 'Lập trình .NET & Ứng dụng Desktop WPF', 'PRN211', 'Toàn bộ giáo trình C#, XAML Data Binding, MVVM Pattern, Entity Framework Core và lập trình bất đồng bộ.'),
  (2, 1, 'Kiến trúc Microservices & AI RAG Engine', 'AIE401', 'Tìm kiếm ngữ nghĩa (Semantic Search), Vector Database ChromaDB, Hybrid Microservices với Laravel và FastAPI.'),
  (3, 2, 'Hệ Quản trị Cơ sở Dữ liệu & Tối ưu SQL', 'DBI202', 'Thiết kế chuẩn hóa CSDL, chỉ mục Indexing B-Tree, tối ưu truy vấn phức tạp và quản lý Transactions.'),
  (4, 3, 'Cấu trúc Dữ liệu & Giải thuật nâng cao', 'CSD201', 'Cây nhị phân tìm kiếm BST, Đồ thị Graph, Quy hoạch động Dynamic Programming và tối ưu độ phức tạp thuật toán.'),
  (5, 4, 'Lập trình Web Fullstack với Laravel & Vue 3', 'SWE301', 'Kiến trúc SPA, RESTful API, Sanctum Token Authentication, Pinia Store và Reactive Component Pattern.'),
  (6, 5, 'Nhập môn Trí tuệ Nhân tạo & LLMs', 'AIT101', 'Khái niệm nền tảng về Generative AI, Retrieval Augmented Generation, Prompt Engineering và Vector Embeddings.')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

INSERT INTO `study_sets` (`id`, `notebook_id`, `user_id`, `share_slug`, `title`, `categories_json`, `questions_json`, `code_exercises_json`, `is_public`)
VALUES
  (1, 1, 1, 'dotnet-wpf-master-exam', 'Bộ Đề Chuẩn 50 Câu: Lập Trình .NET & WPF Kiến Trúc MVVM',
   JSON_ARRAY(
     'C# Core & OOP', 
     'WPF & XAML Data Binding', 
     'Entity Framework Core', 
     'MVVM Pattern & Commands', 
     'LINQ & Async/Await'
   ),
   JSON_ARRAY(
     -- C# Core & OOP (Q1 - Q10)
     JSON_OBJECT('id', 'Q1', 'cat', 0, 'en', 'Trong C#, từ khóa nào được dùng để ngăn chặn một lớp không cho phép lớp khác kế thừa?', 'opts', JSON_ARRAY('static', 'sealed', 'abstract', 'readonly'), 'ans', 1, 'ex', 'Từ khóa "sealed" khi áp dụng lên một class sẽ ngăn chặn các class khác kế thừa từ nó.'),
     JSON_OBJECT('id', 'Q2', 'cat', 0, 'en', 'Sự khác biệt chính giữa struct và class trong .NET là gì?', 'opts', JSON_ARRAY('struct là reference type, class là value type', 'struct là value type lưu trên Stack, class là reference type lưu trên Heap', 'struct hỗ trợ kế thừa đa cấp, class thì không', 'Không có sự khác biệt'), 'ans', 1, 'ex', 'Structs là value types (thường cấp phát trên Stack) trong khi classes là reference types (cấp phát trên Managed Heap).'),
     JSON_OBJECT('id', 'Q3', 'cat', 0, 'en', 'Giao diện IDisposable trong C# định nghĩa phương thức nào để giải phóng tài nguyên unmanaged?', 'opts', JSON_ARRAY('Close()', 'Finalize()', 'Dispose()', 'Release()'), 'ans', 2, 'ex', 'IDisposable yêu cầu thực thi phương thức Dispose() để giải phóng các unmanaged resources.'),
     JSON_OBJECT('id', 'Q4', 'cat', 0, 'en', 'Cú pháp "using" block trong C# bản chất tương đương với cấu trúc nào?', 'opts', JSON_ARRAY('try - catch', 'try - finally (gọi Dispose() trong finally)', 'while loop', 'switch - case'), 'ans', 1, 'ex', 'using (var x = ...) tự động dịch thành khối try-finally, trong đó finally sẽ kiểm tra và gọi x.Dispose().'),
     JSON_OBJECT('id', 'Q5', 'cat', 0, 'en', 'Delegate trong C# là gì?', 'opts', JSON_ARRAY('Một biến kiểu chuỗi', 'Type-safe function pointer (con trỏ hàm an toàn kiểu dữ liệu)', 'Một interface trừu tượng', 'Một lớp chuyên xử lý ngoại lệ'), 'ans', 1, 'ex', 'Delegate là một kiểu dữ liệu tham chiếu đại diện cho con trỏ hàm có kiểu an toàn (type-safe).'),
     JSON_OBJECT('id', 'Q6', 'cat', 0, 'en', 'Từ khóa "virtual" trong định nghĩa phương thức của class cha có ý nghĩa gì?', 'opts', JSON_ARRAY('Cho phép lớp con ghi đè (override) phương thức này', 'Bắt buộc lớp con phải thực thi', 'Ngăn không cho gọi phương thức này', 'Biến phương thức thành hàm static'), 'ans', 0, 'ex', '"virtual" cho phép các lớp con sử dụng từ khóa "override" để định nghĩa lại hành vi.'),
     JSON_OBJECT('id', 'Q7', 'cat', 0, 'en', 'Toán tử "?." (Null-conditional operator) trong C# dùng để làm gì?', 'opts', JSON_ARRAY('Ép kiểu cưỡng bức', 'Kiểm tra null trước khi truy cập thuộc tính/phương thức để tránh NullReferenceException', 'So sánh hai giá trị null', 'Gán giá trị mặc định'), 'ans', 1, 'ex', 'Toán tử ?. sẽ trả về null ngay nếu đối tượng đứng trước nó là null, tránh văng lỗi runtime.'),
     JSON_OBJECT('id', 'Q8', 'cat', 0, 'en', 'Record type trong C# 9.0+ được sinh ra chủ yếu với mục đích gì?', 'opts', JSON_ARRAY('Tăng tốc độ khởi động app', 'Tạo dữ liệu bất biến (immutable data model) và so sánh theo giá trị (value equality)', 'Thay thế hoàn toàn mảng', 'Xử lý đa luồng ngầm định'), 'ans', 1, 'ex', 'Records cung cấp cơ chế value-based equality và tính bất biến (immutability) với cú pháp ngắn gọn.'),
     JSON_OBJECT('id', 'Q9', 'cat', 0, 'en', 'Collection nào trong System.Collections.Generic hoạt động theo nguyên tắc LIFO (Last In First Out)?', 'opts', JSON_ARRAY('Queue<T>', 'Stack<T>', 'List<T>', 'Dictionary<TKey, TValue>'), 'ans', 1, 'ex', 'Stack<T> hoạt động theo cơ chế vào sau ra trước (LIFO) với 2 phương thức chính là Push và Pop.'),
     JSON_OBJECT('id', 'Q10', 'cat', 0, 'en', 'Boxing trong C# là quá trình nào?', 'opts', JSON_ARRAY('Chuyển đổi từ Reference Type sang Value Type', 'Chuyển đổi từ Value Type sang kiểu object (Reference Type)', 'Giải phóng đối tượng khỏi bộ nhớ', 'Ép kiểu chuỗi sang số nguyên'), 'ans', 1, 'ex', 'Boxing là đóng gói một value type vào một object reference type trên Managed Heap.'),

     -- WPF & XAML Data Binding (Q11 - Q20)
     JSON_OBJECT('id', 'Q11', 'cat', 1, 'en', 'Cơ chế Data Binding mặc định trong WPF của TextBox.Text là gì?', 'opts', JSON_ARRAY('OneWay', 'TwoWay (với UpdateSourceTrigger = LostFocus)', 'OneTime', 'OneWayToSource'), 'ans', 1, 'ex', 'TextBox.Text mặc định dùng chế độ TwoWay, nhưng UpdateSourceTrigger mặc định là LostFocus.'),
     JSON_OBJECT('id', 'Q12', 'cat', 1, 'en', 'Để TextBox cập nhật giá trị vào ViewModel ngay khi người dùng gõ từng ký tự, ta cần thiết lập thuộc tính nào?', 'opts', JSON_ARRAY('UpdateSourceTrigger=PropertyChanged', 'Mode=TwoWayFast', 'IsImmediate=True', 'Binding=Live'), 'ans', 0, 'ex', 'UpdateSourceTrigger=PropertyChanged cập nhật giá trị nguồn ngay lập tức mỗi khi text thay đổi.'),
     JSON_OBJECT('id', 'Q13', 'cat', 1, 'en', 'Để View tự động cập nhật UI khi một thuộc tính trong ViewModel thay đổi, ViewModel bắt buộc phải triển khai interface nào?', 'opts', JSON_ARRAY('ICollection', 'INotifyPropertyChanged', 'ICommand', 'IObservable'), 'ans', 1, 'ex', 'INotifyPropertyChanged với sự kiện PropertyChanged(this, new PropertyChangedEventArgs("PropertyName")) thông báo cho View cập nhật UI.'),
     JSON_OBJECT('id', 'Q14', 'cat', 1, 'en', 'Khi danh sách items trong ViewModel thay đổi (thêm, xóa), kiểu dữ liệu nào tự động thông báo UI cập nhật danh sách?', 'opts', JSON_ARRAY('List<T>', 'ObservableCollection<T>', 'HashSet<T>', 'Array'), 'ans', 1, 'ex', 'ObservableCollection<T> triển khai INotifyCollectionChanged, tự động kích hoạt event thông báo UI khi Add/Remove item.'),
     JSON_OBJECT('id', 'Q15', 'cat', 1, 'en', 'Markup Extension nào trong XAML được dùng để tái sử dụng tài nguyên màu sắc/style đã định nghĩa trong App.xaml?', 'opts', JSON_ARRAY('{Binding}', '{StaticResource}', '{x:Type}', '{TemplateBinding}'), 'ans', 1, 'ex', '{StaticResource KeyName} hoặc {DynamicResource KeyName} dùng để tra cứu ResourceDictionary.'),
     JSON_OBJECT('id', 'Q16', 'cat', 1, 'en', 'IValueConverter trong WPF được sử dụng vào mục đích gì?', 'opts', JSON_ARRAY('Chuyển đổi kiểu dữ liệu giữa Source và Target trong quá trình Binding (VD: boolean sang Visibility)', 'Chuyển đổi file ảnh sang PDF', 'Tự động biên dịch XAML', 'Xử lý kết nối cơ sở dữ liệu'), 'ans', 0, 'ex', 'IValueConverter định nghĩa Convert() và ConvertBack() để biến đổi dữ liệu phù hợp với View.'),
     JSON_OBJECT('id', 'Q17', 'cat', 1, 'en', 'Dependency Property trong WPF có ưu điểm gì so với CLR Property thông thường?', 'opts', JSON_ARRAY('Hỗ trợ Data Binding, Animation, Styling và kế thừa giá trị từ phần tử cha', 'Chỉ dùng được trong C# Console', 'Tốn ít bộ nhớ hơn biến int', 'Không thể gán giá trị mặc định'), 'ans', 0, 'ex', 'Dependency Properties là xương sống của hệ thống WPF hỗ trợ data binding, styling và change notification.'),
     JSON_OBJECT('id', 'Q18', 'cat', 1, 'en', 'Layout Control nào trong WPF sắp xếp các phần tử con theo dạng lưới hàng và cột?', 'opts', JSON_ARRAY('StackPanel', 'Grid', 'Canvas', 'WrapPanel'), 'ans', 1, 'ex', 'Grid cho phép định nghĩa Grid.RowDefinitions và Grid.ColumnDefinitions để chia layout linh hoạt.'),
     JSON_OBJECT('id', 'Q19', 'cat', 1, 'en', 'Để thiết lập kích thước cột trong Grid tự động co giãn theo tỷ lệ phần trăm còn lại của màn hình, ta dùng đơn vị nào?', 'opts', JSON_ARRAY('Auto', 'Star (*) (VD: 2*)', 'Pixels cố định', 'Percentage (%)'), 'ans', 1, 'ex', 'Đơn vị Star sizing (*) chia không gian còn lại theo tỷ trọng được chỉ định.'),
     JSON_OBJECT('id', 'Q20', 'cat', 1, 'en', 'ItemTemplate trong ListBox / ListView WPF được dùng để làm gì?', 'opts', JSON_ARRAY('Định nghĩa giao diện hiển thị cho từng phần tử dữ liệu trong danh sách', 'Tạo hiệu ứng bóng đổ cho cả danh sách', 'Sắp xếp danh sách tự động', 'Xóa các phần tử rỗng'), 'ans', 0, 'ex', 'DataTemplate gắn vào ItemTemplate xác định cấu trúc hiển thị của mỗi đối tượng data item.'),

     -- Entity Framework Core (Q21 - Q30)
     JSON_OBJECT('id', 'Q21', 'cat', 2, 'en', 'Lớp cơ sở quan trọng nhất đại diện cho một phiên làm việc với cơ sở dữ liệu trong EF Core là gì?', 'opts', JSON_ARRAY('DbSet<T>', 'DbContext', 'DbConnection', 'EntityManager'), 'ans', 1, 'ex', 'DbContext là class đại diện cho session kết nối, quản lý entity tracking và transaction trong EF Core.'),
     JSON_OBJECT('id', 'Q22', 'cat', 2, 'en', 'Phương thức nào được dùng để tải dữ liệu liên quan (Eager Loading) trong EF Core?', 'opts', JSON_ARRAY('.Include()', '.Join()', '.Fetch()', '.Attach()'), 'ans', 0, 'ex', '.Include(x => x.Relation) thực hiện Eager Loading để nạp các bảng quan hệ ngay trong câu truy vấn.'),
     JSON_OBJECT('id', 'Q23', 'cat', 2, 'en', 'Lệnh CLI nào trong EF Core dùng để tạo một migration mới?', 'opts', JSON_ARRAY('dotnet ef database update', 'dotnet ef migrations add <MigrationName>', 'dotnet ef create', 'dotnet ef migrations run'), 'ans', 1, 'ex', 'dotnet ef migrations add <Name> sinh code C# migration dựa trên sự thay đổi của DbContext và Entities.'),
     JSON_OBJECT('id', 'Q24', 'cat', 2, 'en', 'Phương pháp truy vấn .AsNoTracking() trong EF Core mang lại lợi ích gì?', 'opts', JSON_ARRAY('Bật tính năng tự động lưu dữ liệu', 'Tăng tốc độ truy vấn chỉ đọc vì DbContext không cần theo dõi trạng thái (Change Tracking) của các Entity', 'Ngăn chặn người dùng đọc dữ liệu', 'Tự động tạo bảng mới'), 'ans', 1, 'ex', 'AsNoTracking() tối ưu bộ nhớ và CPU cho các thao tác read-only vì bỏ qua Change Tracker.'),
     JSON_OBJECT('id', 'Q25', 'cat', 2, 'en', 'Phương thức nào của DbContext dùng để lưu mọi thay đổi (Insert, Update, Delete) xuống CSDL?', 'opts', JSON_ARRAY('Commit()', 'SaveChanges() hoặc SaveChangesAsync()', 'Execute()', 'PushChanges()'), 'ans', 1, 'ex', 'SaveChanges() / SaveChangesAsync() đẩy các thay đổi đã được track xuống database trong một transaction.'),
     JSON_OBJECT('id', 'Q26', 'cat', 2, 'en', 'Trong Fluent API, phương thức nào dùng để cấu hình khóa chính (Primary Key)?', 'opts', JSON_ARRAY('HasForeignKey()', 'HasKey()', 'IsRequired()', 'HasIndex()'), 'ans', 1, 'ex', 'modelBuilder.Entity<T>().HasKey(e => e.Id) định nghĩa Primary Key.'),
     JSON_OBJECT('id', 'Q27', 'cat', 2, 'en', 'Chiến lược khóa ngoại OnDelete nào sẽ tự động xóa tất cả bản ghi con khi bản ghi cha bị xóa?', 'opts', JSON_ARRAY('DeleteBehavior.Restrict', 'DeleteBehavior.Cascade', 'DeleteBehavior.SetNull', 'DeleteBehavior.NoAction'), 'ans', 1, 'ex', 'DeleteBehavior.Cascade thực hiện xóa xếp tầng trên CSDL.'),
     JSON_OBJECT('id', 'Q28', 'cat', 2, 'en', 'Vấn đề "N+1 Query Problem" thường xảy ra khi sử dụng cơ chế nào thiếu kiểm soát?', 'opts', JSON_ARRAY('Eager Loading', 'Lazy Loading trong vòng lặp', 'Explicit Loading', 'Bulk Insert'), 'ans', 1, 'ex', 'Lazy loading trong vòng lặp duyệt N bản ghi sẽ phát sinh thêm N câu truy vấn đơn lẻ gây chậm hệ thống.'),
     JSON_OBJECT('id', 'Q29', 'cat', 2, 'en', 'Thuộc tính [Key] trong Data Annotations dùng để làm gì?', 'opts', JSON_ARRAY('Đánh dấu property là khóa chính', 'Đánh dấu property cần mã hóa', 'Đánh dấu property là foreign key', 'Đặt tên cột trong database'), 'ans', 0, 'ex', '[Key] chỉ định property làm khóa chính của bảng.'),
     JSON_OBJECT('id', 'Q30', 'cat', 2, 'en', 'Hàm OnModelCreating(ModelBuilder modelBuilder) trong DbContext được dùng để làm gì?', 'opts', JSON_ARRAY('Cấu hình chuỗi kết nối', 'Cấu hình Schema, quan hệ, chỉ mục bằng Fluent API', 'Thực thi SQL thô', 'Gửi email cho admin'), 'ans', 1, 'ex', 'OnModelCreating là nơi định nghĩa Fluent API để cấu hình quan hệ giữa các bảng.'),

     -- MVVM Pattern & Commands (Q31 - Q40)
     JSON_OBJECT('id', 'Q31', 'cat', 3, 'en', 'Trong kiến trúc MVVM, ba thành phần M - V - VM viết tắt của những từ nào?', 'opts', JSON_ARRAY('Module - View - Variable Module', 'Model - View - ViewModel', 'Master - View - Visual Model', 'Management - Visual - View Module'), 'ans', 1, 'ex', 'MVVM là viết tắt của Model (Dữ liệu), View (Giao diện XAML), ViewModel (Logic điều phối).'),
     JSON_OBJECT('id', 'Q32', 'cat', 3, 'en', 'Nguyên tắc bất di bất dịch của kiến trúc MVVM thuần túy là gì?', 'opts', JSON_ARRAY('ViewModel không được tham chiếu trực tiếp đến các thành phần giao diện (UI controls) của View', 'View phải gọi trực tiếp SQL Server', 'Model phải chứa mã sự kiện click chuột', 'Không được dùng Data Binding'), 'ans', 0, 'ex', 'ViewModel phải độc lập với View để dễ dàng Unit Test và tái sử dụng logic.'),
     JSON_OBJECT('id', 'Q33', 'cat', 3, 'en', 'Interface nào trong WPF đại diện cho hành động tương tác (Command) từ View xuống ViewModel?', 'opts', JSON_ARRAY('IAction', 'ICommand', 'IExecutable', 'IEvent'), 'ans', 1, 'ex', 'ICommand với Execute(object) và CanExecute(object) là chuẩn mực xử lý hành động người dùng trong MVVM.'),
     JSON_OBJECT('id', 'Q34', 'cat', 3, 'en', 'Phương thức CanExecute(object parameter) trong ICommand có tác dụng gì đối với Button trên View?', 'opts', JSON_ARRAY('Đổi màu nút thành xanh', 'Tự động Enable hoặc Disable nút bấm tương ứng trên giao diện tùy theo giá trị true/false', 'Ẩn hoàn toàn nút khỏi giao diện', 'Kích hoạt sự kiện đóng cửa sổ'), 'ans', 1, 'ex', 'Khi CanExecute trả về false, WPF tự động làm mờ và vô hiệu hóa (disabled) nút bấm được bind.'),
     JSON_OBJECT('id', 'Q35', 'cat', 3, 'en', 'Lớp RelayCommand (hoặc AsyncRelayCommand) thường được xây dựng nhằm mục đích gì trong MVVM?', 'opts', JSON_ARRAY('Triển khai ICommand bằng cách truyền Action và Func delegate từ ViewModel mà không cần tạo class riêng cho từng nút', 'Thay thế DbContext', 'Xử lý âm thanh', 'Quản lý phiên đăng nhập'), 'ans', 0, 'ex', 'RelayCommand cho phép truyền trực tiếp lambda expression vào constructor để thực thi lệnh nhanh gọn.'),
     JSON_OBJECT('id', 'Q36', 'cat', 3, 'en', 'Để View nhận biết được ViewModel tương ứng, cách liên kết phổ biến nhất trong XAML là gì?', 'opts', JSON_ARRAY('Gán DataContext của Window/UserControl bằng instance của ViewModel', 'Gọi hàm MessageBox.Show()', 'Tạo biến toàn cục trong C#', 'Sử dụng thẻ <Style>'), 'ans', 0, 'ex', 'DataContext là nguồn dữ liệu mặc định mà mọi ràng buộc Binding trong View sẽ trỏ tới.'),
     JSON_OBJECT('id', 'Q37', 'cat', 3, 'en', 'Trong MVVM, làm thế nào để hai ViewModel độc lập có thể truyền tin cho nhau mà không phụ thuộc lẫn nhau?', 'opts', JSON_ARRAY('Gọi biến static công khai', 'Sử dụng cơ chế Event Aggregator / Messenger (Mediator Pattern)', 'Truy cập thẳng vào DOM của cửa sổ', 'Ghi dữ liệu vào file tạm trên ổ đĩa'), 'ans', 1, 'ex', 'Mô hình Messenger / Event Aggregator (như trong CommunityToolkit.Mvvm) giúp loosely-coupled communication.'),
     JSON_OBJECT('id', 'Q38', 'cat', 3, 'en', 'Lợi ích lớn nhất của việc tách biệt ViewModel ra khỏi View là gì?', 'opts', JSON_ARRAY('Tăng dung lượng tệp thực thi', 'Khả năng viết Unit Test tự động cho logic nghiệp vụ mà không cần khởi tạo giao diện đồ họa', 'Không cần cài đặt .NET Framework', 'Chỉ dùng được cho ứng dụng di động'), 'ans', 1, 'ex', 'ViewModel không dính dáng UI nên có thể test tự động với xUnit/NUnit độc lập.'),
     JSON_OBJECT('id', 'Q39', 'cat', 3, 'en', 'Thuộc tính CommandParameter trong Button XAML dùng để làm gì?', 'opts', JSON_ARRAY('Đổi màu nút', 'Truyền tham số dữ liệu từ View vào phương thức Execute của ICommand trong ViewModel', 'Đặt tên cho nút', 'Căn lề'), 'ans', 1, 'ex', 'CommandParameter truyền đối tượng tham số vào CanExecute(param) và Execute(param).'),
     JSON_OBJECT('id', 'Q40', 'cat', 3, 'en', 'Cơ chế nào được dùng để thông báo cho hệ thống WPF đánh giá lại CanExecute của tất cả các Command?', 'opts', JSON_ARRAY('CommandManager.InvalidateRequerySuggested()', 'GC.Collect()', 'Application.Restart()', 'Thread.Sleep()'), 'ans', 0, 'ex', 'CommandManager.InvalidateRequerySuggested() buộc WPF gọi lại CanExecute để cập nhật trạng thái nút.'),

     -- LINQ & Asynchronous Programming (Q41 - Q50)
     JSON_OBJECT('id', 'Q41', 'cat', 4, 'en', 'Toán tử LINQ nào được dùng để chiếu (transform/project) mỗi phần tử sang một cấu trúc mới?', 'opts', JSON_ARRAY('.Where()', '.Select()', '.OrderBy()', '.GroupBy()'), 'ans', 1, 'ex', '.Select(x => new { ... }) dùng để thực hiện phép chiếu (Projection) sang kiểu dữ liệu mới.'),
     JSON_OBJECT('id', 'Q42', 'cat', 4, 'en', 'Khái niệm "Deferred Execution" (Thực thi trễ) trong LINQ có nghĩa là gì?', 'opts', JSON_ARRAY('Truy vấn chạy ngay lập tức khi được khai báo', 'Truy vấn không được thực thi cho đến khi dữ liệu thực sự được duyệt (qua foreach, .ToList(), .Count())', 'Truy vấn bị hoãn lại 5 giây', 'Truy vấn chạy trên luồng phụ'), 'ans', 1, 'ex', 'LINQ queries sử dụng deferred execution; dữ liệu chỉ được tải khi thực sự duyệt collection.'),
     JSON_OBJECT('id', 'Q43', 'cat', 4, 'en', 'Phương thức LINQ nào sau đây sẽ kích hoạt thực thi truy vấn ngay lập tức (Immediate Execution)?', 'opts', JSON_ARRAY('.Where()', '.Select()', '.ToList()', '.Take()'), 'ans', 2, 'ex', 'Các toán tử như .ToList(), .ToArray(), .Count(), .First() kích hoạt thực thi ngay lập tức.'),
     JSON_OBJECT('id', 'Q44', 'cat', 4, 'en', 'Từ khóa "async" và "await" trong C# mang lại lợi ích lớn nhất nào cho ứng dụng WPF?', 'opts', JSON_ARRAY('Tăng tốc độ xung nhịp CPU', 'Giải phóng luồng giao diện chính (UI Thread) trong khi chờ I/O, ngăn ứng dụng bị "Not Responding" hoặc đơ giao diện', 'Tự động sao lưu dữ liệu', 'Mã hóa kết nối mạng'), 'ans', 1, 'ex', 'await trả lại quyền điều khiển cho UI thread để ứng dụng luôn phản hồi mượt mà trong khi tác vụ nền đang chạy.'),
     JSON_OBJECT('id', 'Q45', 'cat', 4, 'en', 'Kiểu trả về khuyến nghị cho một phương thức bất đồng bộ không trả về kết quả là gì?', 'opts', JSON_ARRAY('void', 'Task', 'Thread', 'Action'), 'ans', 1, 'ex', 'Luôn dùng Task thay vì async void (trừ các event handler) để có thể bắt ngoại lệ và await.'),
     JSON_OBJECT('id', 'Q46', 'cat', 4, 'en', 'Để đợi đồng thời nhiều Task hoàn thành trong C#, ta dùng phương thức nào?', 'opts', JSON_ARRAY('Task.WaitOne()', 'Task.WhenAll()', 'Task.Join()', 'Task.Combine()'), 'ans', 1, 'ex', 'await Task.WhenAll(task1, task2) sẽ đợi cho đến khi tất cả các task hoàn thành.'),
     JSON_OBJECT('id', 'Q47', 'cat', 4, 'en', 'Lớp CancellationTokenSource trong .NET được sử dụng nhằm mục đích gì?', 'opts', JSON_ARRAY('Báo hiệu yêu cầu hủy bỏ một tác vụ bất đồng bộ đang chạy khi người dùng bấm nút Hủy', 'Sinh mã token bảo mật OAuth', 'Tạm dừng ứng dụng trong 5 phút', 'Đếm số lượng thread'), 'ans', 0, 'ex', 'CancellationTokenSource phát tín hiệu Cancel để graceful termination các tác vụ async.'),
     JSON_OBJECT('id', 'Q48', 'cat', 4, 'en', 'Nếu cố tình cập nhật một UI control (như Label.Content) từ một background thread trong WPF, lỗi gì sẽ xảy ra?', 'opts', JSON_ARRAY('NullReferenceException', 'InvalidOperationException (The calling thread cannot access this object because a different thread owns it)', 'StackOverflowException', 'OutOfMemoryException'), 'ans', 1, 'ex', 'UI thread sở hữu các visual components. Chỉ UI thread mới có quyền thao tác trực tiếp lên controls.'),
     JSON_OBJECT('id', 'Q49', 'cat', 4, 'en', 'Để đưa một hành động cập nhật giao diện từ background thread về UI thread trong WPF, ta dùng đối tượng nào?', 'opts', JSON_ARRAY('Dispatcher.Invoke() hoặc Dispatcher.InvokeAsync()', 'Task.Run()', 'Thread.Sleep()', 'Process.Start()'), 'ans', 0, 'ex', 'Application.Current.Dispatcher.Invoke(...) điều phối mã thực thi trở lại luồng UI chính.'),
     JSON_OBJECT('id', 'Q50', 'cat', 4, 'en', 'Toán tử LINQ nào trả về phần tử đầu tiên thỏa mãn điều kiện hoặc giá trị mặc định (null) nếu không tìm thấy?', 'opts', JSON_ARRAY('.First()', '.FirstOrDefault()', '.Single()', '.ElementAt()'), 'ans', 1, 'ex', 'FirstOrDefault() an toàn hơn First() vì nó không ném ngoại lệ InvalidOperationException khi rỗng.')
   ),
   JSON_ARRAY(
     JSON_OBJECT(
       'title', 'Triển khai INotifyPropertyChanged trong ViewModel',
       'code', 'public class BaseViewModel : [[INotifyPropertyChanged]]\n{\n    public event PropertyChangedEventHandler [[PropertyChanged]];\n\n    protected void OnPropertyChanged([CallerMemberName] string propName = null)\n    {\n        PropertyChanged?.Invoke(this, new [[PropertyChangedEventArgs]](propName));\n    }\n}'
     ),
     JSON_OBJECT(
       'title', 'Xây dựng RelayCommand chuẩn trong MVVM',
       'code', 'public class RelayCommand : [[ICommand]]\n{\n    private readonly Action<object> _execute;\n    private readonly Predicate<object> _canExecute;\n\n    public RelayCommand(Action<object> execute, Predicate<object> canExecute = null)\n    {\n        _execute = execute ?? throw new ArgumentNullException(nameof(execute));\n        _canExecute = canExecute;\n    }\n\n    public bool CanExecute(object parameter) => _canExecute == null || [[_canExecute]](parameter);\n    public void Execute(object parameter) => [[_execute]](parameter);\n}'
     ),
     JSON_OBJECT(
       'title', 'Đăng ký Dependency Injection trong .NET Core / WPF App',
       'code', 'public void ConfigureServices(IServiceCollection services)\n{\n    services.AddDbContext<[[AppDbContext]]>(options => \n        options.UseSqlServer(connectionString));\n\n    services.AddSingleton<INavigationService, NavigationService>();\n    services.AddTransient<MainViewModel>();\n    services.[[AddScoped]]<IUserRepository, UserRepository>();\n}'
     ),
     JSON_OBJECT(
       'title', 'Gọi API Bất đồng bộ với HttpClient và async/await',
       'code', 'public async Task<List<Product>> FetchProductsAsync()\n{\n    using var client = new [[HttpClient]]();\n    var response = await client.[[GetAsync]]("https://api.noteforge.io/products");\n    response.EnsureSuccessStatusCode();\n    \n    var json = await response.Content.[[ReadAsStringAsync]]();\n    return JsonSerializer.Deserialize<List<Product>>(json);\n}'
     ),
     JSON_OBJECT(
       'title', 'Truy vấn EF Core với AsNoTracking và Include',
       'code', 'public async Task<List<Order>> GetActiveOrdersAsync(int customerId)\n{\n    return await _context.Orders\n        .[[AsNoTracking]]()\n        .[[Include]](o => o.OrderDetails)\n        .Where(o => o.CustomerId == customerId && o.Status == "Active")\n        .[[ToListAsync]]();\n}'
     )
   ),
   1
)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `questions_json` = VALUES(`questions_json`);
