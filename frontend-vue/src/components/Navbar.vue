<template>
  <div>
    <!-- Top Header Navigation -->
    <header class="bg-blueprint-dark/95 backdrop-blur-md border-b-2 border-blueprint-accent sticky top-0 z-40 text-white shadow-xl">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo & Brand -->
        <router-link to="/dashboard" class="flex items-center gap-3 group">
          <div class="w-10 h-10 rounded-lg bg-blueprint-accent flex items-center justify-center font-bold text-blueprint-dark text-xl shadow-md group-hover:scale-105 transition-transform">
            ⚡
          </div>
          <div>
            <div class="font-bold text-lg tracking-wide text-white flex items-center gap-2">
              <span>NoteForge</span>
              <span class="text-blueprint-accent">AI</span>
              <span class="text-[10px] uppercase font-mono px-1.5 py-0.5 bg-blue-900/60 text-blue-300 border border-blue-700/50 rounded">
                v1.0
              </span>
            </div>
            <p class="text-[11px] text-gray-400 font-mono hidden sm:block">RAG Study & Converter Platform</p>
          </div>
        </router-link>

        <!-- Desktop Nav Links (Hidden on Mobile) -->
        <nav class="hidden md:flex items-center gap-1 bg-white/5 p-1 rounded-lg border border-white/10">
          <router-link
            to="/dashboard"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
            active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm font-bold"
          >
            🏠 Tổng quan
          </router-link>
          <router-link
            to="/dashboard"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
          >
            📚 Không gian Môn học
          </router-link>
          <router-link
            to="/converter"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
            active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm font-bold"
          >
            🔄 Trạm Chuyển Đổi
          </router-link>
          <router-link
            to="/study-sets"
            class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
            active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm font-bold"
          >
            ✍️ Kho Đề Ôn Tập
          </router-link>
        </nav>

        <!-- Right Side: API Key Badge & User Profile -->
        <div class="flex items-center gap-3">
          <!-- Gemini Key Status Pill -->
          <router-link
            to="/settings"
            class="px-2.5 py-1 rounded-full text-[11px] font-mono flex items-center gap-1.5 border transition-all hover:scale-105"
            :class="authStore.hasCustomGeminiKey 
              ? 'bg-emerald-950/80 text-emerald-300 border-emerald-600/60' 
              : 'bg-amber-950/80 text-amber-300 border-amber-600/60'"
            :title="authStore.hasCustomGeminiKey ? 'Đang dùng Gemini API Key riêng' : 'Đang dùng Gemini API Key hệ thống'"
          >
            <span class="w-2 h-2 rounded-full" :class="authStore.hasCustomGeminiKey ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400'"></span>
            <span class="font-bold hidden sm:inline">{{ authStore.hasCustomGeminiKey ? 'Personal Key' : 'System Key' }}</span>
            <span class="font-bold sm:hidden">{{ authStore.hasCustomGeminiKey ? 'Key riêng' : 'Key chung' }}</span>
          </router-link>

          <div v-if="authStore.user" class="flex items-center gap-2">
            <span class="text-xs font-bold text-white hidden lg:inline">{{ authStore.user.name }}</span>
            <button
              @click="handleLogout"
              class="px-2.5 py-1 text-xs font-semibold bg-red-950/50 hover:bg-red-800 text-red-200 border border-red-800/60 rounded-md transition-colors"
              title="Đăng xuất"
            >
              🚪
            </button>
          </div>
          <div v-else class="flex items-center gap-2">
            <router-link to="/login" class="px-3 py-1.5 text-xs font-bold bg-blueprint-accent text-blueprint-dark rounded-md">
              Đăng nhập
            </router-link>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Fixed Bottom Navigation Bar (md:hidden) -->
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-blueprint-dark/95 backdrop-blur-lg border-t-2 border-blueprint-accent text-white shadow-2xl pb-safe">
      <div class="grid grid-cols-4 h-14">
        <router-link
          to="/dashboard"
          class="flex flex-col items-center justify-center text-[10px] font-medium text-gray-400 hover:text-white transition-colors"
          active-class="!text-blueprint-accent font-bold bg-white/5"
        >
          <span class="text-base mb-0.5">🏠</span>
          <span>Trang chủ</span>
        </router-link>

        <router-link
          to="/dashboard"
          class="flex flex-col items-center justify-center text-[10px] font-medium text-gray-400 hover:text-white transition-colors"
        >
          <span class="text-base mb-0.5">📚</span>
          <span>Môn học</span>
        </router-link>

        <router-link
          to="/converter"
          class="flex flex-col items-center justify-center text-[10px] font-medium text-gray-400 hover:text-white transition-colors"
          active-class="!text-blueprint-accent font-bold bg-white/5"
        >
          <span class="text-base mb-0.5">🔄</span>
          <span>Chuyển đổi</span>
        </router-link>

        <router-link
          to="/study-sets"
          class="flex flex-col items-center justify-center text-[10px] font-medium text-gray-400 hover:text-white transition-colors"
          active-class="!text-blueprint-accent font-bold bg-white/5"
        >
          <span class="text-base mb-0.5">✍️</span>
          <span>Ôn thi</span>
        </router-link>
      </div>
    </nav>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>

<style scoped>
.pb-safe {
  padding-bottom: env(safe-area-inset-bottom, 0px);
}
</style>
