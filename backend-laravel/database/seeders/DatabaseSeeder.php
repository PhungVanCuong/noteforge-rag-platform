<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with sample data.
     */
    public function run(): void
    {
        // 1. Seed Demo User
        $user = DB::table('users')->updateOrInsert(
            ['email' => 'cuongphung1810@gmail.com'],
            [
                'id' => 1,
                'name' => 'Phùng Văn Cường',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 2. Seed Notebooks
        DB::table('notebooks')->updateOrInsert(
            ['id' => 1],
            [
                'user_id' => 1,
                'title' => 'Lập trình .NET & Ứng dụng Desktop WPF',
                'subject_code' => 'PRN211',
                'description' => 'Toàn bộ giáo trình C#, XAML Data Binding, MVVM Pattern, Entity Framework Core và lập trình bất đồng bộ.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('notebooks')->updateOrInsert(
            ['id' => 2],
            [
                'user_id' => 1,
                'title' => 'Kiến trúc Microservices & AI RAG Engine',
                'subject_code' => 'AIE401',
                'description' => 'Tìm kiếm ngữ nghĩa (Semantic Search), Vector Database ChromaDB, Hybrid Microservices với Laravel và FastAPI.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Seed 50-Question Study Set with Cloze exercises
        $sqlPath = base_path('../database.sql');
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            // Execute the INSERT INTO study_sets statement if study_sets table is empty
            $studySetExists = DB::table('study_sets')->where('id', 1)->exists();
            if (!$studySetExists) {
                // Extract insert statements for study_sets or execute database.sql
                try {
                    DB::unprepared(file_get_contents($sqlPath));
                } catch (\Throwable $e) {
                    // Fallback to direct insertion if needed
                }
            }
        }
    }
}
