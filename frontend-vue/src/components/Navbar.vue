<template>
  <header class="bg-blueprint-dark/95 backdrop-blur-md border-b-2 border-blueprint-accent sticky top-0 z-50 text-white shadow-xl">
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

      <!-- Nav Links -->
      <nav class="hidden md:flex items-center gap-1 bg-white/5 p-1 rounded-lg border border-white/10">
        <router-link
          to="/dashboard"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
          active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm"
        >
          📚 Môn học
        </router-link>
        <router-link
          to="/converter"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
          active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm"
        >
          🔄 Trạm chuyển đổi
        </router-link>
        <router-link
          to="/study-sets"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
          active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm"
        >
          🎯 Bộ đề ôn tập
        </router-link>
        <router-link
          to="/settings"
          class="px-3.5 py-1.5 text-xs font-semibold rounded-md transition-all text-gray-300 hover:text-white"
          active-class="bg-blueprint-accent !text-blueprint-dark shadow-sm"
        >
          ⚙️ Cài đặt API
        </router-link>
      </nav>

      <!-- User Profile & Status -->
      <div class="flex items-center gap-3">
        <div v-if="authStore.user" class="flex items-center gap-3">
          <div class="text-right hidden sm:block">
            <div class="text-xs font-bold text-white">{{ authStore.user.name }}</div>
            <div class="text-[11px] font-mono text-gray-400 flex items-center justify-end gap-1">
              <span v-if="authStore.hasCustomGeminiKey" class="text-green-400 font-bold" title="Đang dùng Custom Key">● Key riêng</span>
              <span v-else class="text-amber-400 font-semibold" title="Đang dùng Key hệ thống">● Key hệ thống</span>
            </div>
          </div>
          <button
            @click="handleLogout"
            class="px-3 py-1.5 text-xs font-semibold bg-red-950/40 hover:bg-red-800 text-red-300 border border-red-800/60 rounded-md transition-colors"
          >
            Đăng xuất
          </button>
        </div>
        <div v-else class="flex items-center gap-2">
          <router-link to="/login" class="px-3 py-1.5 text-xs font-semibold text-white hover:text-blueprint-accent">
            Đăng nhập
          </router-link>
          <router-link to="/register" class="px-3.5 py-1.5 text-xs font-bold bg-blueprint-accent text-blueprint-dark rounded-md hover:bg-amber-400 shadow-sm">
            Đăng ký
          </router-link>
        </div>
      </div>
    </div>
  </header>
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
