<template>
  <div class="min-h-screen pb-16">
    <div class="max-w-3xl mx-auto px-4 py-8">
      <div class="blueprint-card p-6 md:p-8">
        <div class="flex items-center gap-2 mb-2">
          <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
            SETTINGS
          </span>
          <span class="text-xs text-gray-500 font-mono">Bảo mật & Cấu hình API</span>
        </div>

        <h1 class="text-2xl font-bold text-blueprint-dark mb-2">Cài Đặt API Key Cá Nhân</h1>
        <p class="text-xs sm:text-sm text-gray-700 leading-relaxed mb-6">
          Hệ thống mặc định cung cấp API Key chung của nền tảng. Bạn có thể thiết lập khóa <strong>Gemini API Key riêng</strong> của mình để hưởng hạn mức quota cá nhân cao hơn khi bóc băng bài giảng lớn hoặc tạo bộ đề hàng trăm câu hỏi. Khóa của bạn được <strong>mã hóa AES-256</strong> tuyệt đối an toàn trong cơ sở dữ liệu.
        </p>

        <!-- Current Key Status -->
        <div class="p-4 rounded-xl border mb-6 flex items-center justify-between" :class="authStore.hasCustomGeminiKey ? 'bg-emerald-50 border-emerald-300' : 'bg-amber-50 border-amber-300'">
          <div>
            <div class="text-xs font-bold" :class="authStore.hasCustomGeminiKey ? 'text-emerald-900' : 'text-amber-900'">
              Trạng thái: {{ authStore.hasCustomGeminiKey ? 'Đang kích hoạt API Key cá nhân' : 'Đang sử dụng API Key mặc định của hệ thống' }}
            </div>
            <div class="text-[11px] mt-0.5" :class="authStore.hasCustomGeminiKey ? 'text-emerald-700' : 'text-amber-700'">
              {{ authStore.hasCustomGeminiKey ? 'Mọi yêu cầu RAG và sinh bài tập sẽ sử dụng quota riêng của bạn.' : 'Hệ thống tự động điều phối qua khóa dùng chung.' }}
            </div>
          </div>
          <span class="text-2xl">{{ authStore.hasCustomGeminiKey ? '🛡️' : '⚡' }}</span>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSaveKey" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">
              Google Gemini API Key (Bắt đầu bằng <code class="font-mono bg-gray-200 px-1 rounded">AIzaSy...</code>)
            </label>
            <input
              v-model="geminiKey"
              type="password"
              placeholder="Nhập khóa API mới hoặc để trống để hủy..."
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs sm:text-sm font-mono bg-white outline-none focus:ring-2 focus:ring-blueprint"
            />
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-2.5 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-50"
            >
              {{ saving ? 'Đang lưu khóa mã hóa...' : 'Lưu cài đặt API Key' }}
            </button>

            <button
              v-if="authStore.hasCustomGeminiKey"
              type="button"
              @click="handleClearKey"
              class="px-4 py-2.5 rounded-lg border border-red-300 bg-red-50 text-red-700 hover:bg-red-100 font-semibold text-xs"
            >
              Xóa khóa riêng & dùng lại mặc định
            </button>
          </div>
        </form>

        <div v-if="alertMsg" class="mt-4 p-3 rounded-lg text-xs font-medium" :class="isSuccess ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
          {{ alertMsg }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const geminiKey = ref('')
const saving = ref(false)
const alertMsg = ref('')
const isSuccess = ref(false)

const handleSaveKey = async () => {
  if (!geminiKey.value.trim()) {
    alert('Vui lòng nhập API Key hoặc bấm nút xóa.')
    return
  }

  saving.value = true
  alertMsg.value = ''

  try {
    const res = await authStore.updateGeminiKey(geminiKey.value.trim())
    isSuccess.value = true
    alertMsg.value = res.message || 'Cập nhật API Key thành công!'
    geminiKey.value = ''
  } catch (err) {
    isSuccess.value = false
    alertMsg.value = 'Lưu API Key thất bại.'
  } finally {
    saving.value = false
  }
}

const handleClearKey = async () => {
  if (!confirm('Bạn có chắc muốn xóa API Key cá nhân và chuyển về khóa mặc định?')) return
  saving.value = true
  alertMsg.value = ''

  try {
    const res = await authStore.updateGeminiKey(null)
    isSuccess.value = true
    alertMsg.value = res.message || 'Đã chuyển về API Key mặc định.'
  } catch (err) {
    isSuccess.value = false
    alertMsg.value = 'Xóa key thất bại.'
  } finally {
    saving.value = false
  }
}
</script>
