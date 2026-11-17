<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample accounts and data.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password123');
        $now = Carbon::now();

        // 1. Seed Ready-to-use Accounts
        $users = [
            [
                'id' => 1,
                'name' => 'Phùng Văn Cường (Admin)',
                'email' => 'cuongphung1810@gmail.com',
                'password' => $defaultPassword,
                'avatar' => 'https://ui-avatars.com/api/?name=Cuong+Phung&background=0284c7&color=fff&bold=true',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Quản Trị Viên Hệ Thống',
                'email' => 'admin@noteforge.ai',
                'password' => $defaultPassword,
                'avatar' => 'https://ui-avatars.com/api/?name=Admin+NoteForge&background=4f46e5&color=fff&bold=true',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Nguyễn Văn An (Sinh viên CNTT)',
                'email' => 'sinhvien@noteforge.ai',
                'password' => $defaultPassword,
                'avatar' => 'https://ui-avatars.com/api/?name=Nguyen+Van+An&background=10b981&color=fff&bold=true',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'name' => 'TS. Trần Minh Đức (Giảng viên)',
                'email' => 'giangvien@noteforge.ai',
                'password' => $defaultPassword,
                'avatar' => 'https://ui-avatars.com/api/?name=Tran+Minh+Duc&background=f59e0b&color=fff&bold=true',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'name' => 'Tài Khoản Trải Nghiệm Demo',
                'email' => 'demo@noteforge.ai',
                'password' => $defaultPassword,
                'avatar' => 'https://ui-avatars.com/api/?name=Demo+User&background=ec4899&color=fff&bold=true',
                'email_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }

        // 2. Seed Notebooks for each user
        $notebooks = [
            [
                'id' => 1,
                'user_id' => 1,
                'title' => 'Lập trình .NET & Ứng dụng Desktop WPF',
                'subject_code' => 'PRN211',
                'description' => 'Toàn bộ giáo trình C#, XAML Data Binding, MVVM Pattern, Entity Framework Core và lập trình bất đồng bộ.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'title' => 'Kiến trúc Microservices & AI RAG Engine',
                'subject_code' => 'AIE401',
                'description' => 'Tìm kiếm ngữ nghĩa (Semantic Search), Vector Database ChromaDB, Hybrid Microservices với Laravel và FastAPI.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'title' => 'Hệ Quản trị Cơ sở Dữ liệu & Tối ưu SQL',
                'subject_code' => 'DBI202',
                'description' => 'Thiết kế chuẩn hóa CSDL, chỉ mục Indexing B-Tree, tối ưu truy vấn phức tạp và quản lý Transactions.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 4,
                'user_id' => 3,
                'title' => 'Cấu trúc Dữ liệu & Giải thuật nâng cao',
                'subject_code' => 'CSD201',
                'description' => 'Cây nhị phân tìm kiếm BST, Đồ thị Graph, Quy hoạch động Dynamic Programming và tối ưu độ phức tạp thuật toán.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 5,
                'user_id' => 4,
                'title' => 'Lập trình Web Fullstack với Laravel & Vue 3',
                'subject_code' => 'SWE301',
                'description' => 'Kiến trúc SPA, RESTful API, Sanctum Token Authentication, Pinia Store và Reactive Component Pattern.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 6,
                'user_id' => 5,
                'title' => 'Nhập môn Trí tuệ Nhân tạo & LLMs',
                'subject_code' => 'AIT101',
                'description' => 'Khái niệm nền tảng về Generative AI, Retrieval Augmented Generation, Prompt Engineering và Vector Embeddings.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($notebooks as $notebook) {
            DB::table('notebooks')->updateOrInsert(
                ['id' => $notebook['id']],
                $notebook
            );
        }

        // 3. Seed 50-Question Study Set with Cloze Code Exercises
        // Check if database.sql exists to sync rich questions
        $sqlPath = base_path('../database.sql');
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // If study_sets has no records or needs sync
            $existingSet = DB::table('study_sets')->where('id', 1)->first();
            if (!$existingSet) {
                try {
                    DB::unprepared($sql);
                } catch (\Throwable $e) {
                    // Ignore duplicate key errors if already present
                }
            }
        }
    }
}
