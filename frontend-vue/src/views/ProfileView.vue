<template>
  <div class="min-h-screen pb-24">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
      <!-- Breadcrumb & Header -->
      <div class="mb-6 flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 text-xs font-mono text-gray-400 mb-1">
            <router-link to="/dashboard" class="hover:text-blueprint-accent">Trang chủ</router-link>
            <span>/</span>
            <span class="text-blueprint-accent font-bold">Hồ Sơ & Bảo Mật</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-white">
            Cài Đặt Tài Khoản & API Key
          </h1>
        </div>

        <button
          @click="handleLogout"
          class="px-4 py-2 bg-red-950/70 hover:bg-red-800 text-red-200 border border-red-700/60 rounded-xl text-xs font-bold flex items-center gap-2 shadow-sm transition-colors"
        >
          <span>🚪</span> Đăng xuất
        </button>
      </div>

      <!-- Alert Message -->
      <div
        v-if="statusMessage.text"
        :class="statusMessage.type === 'success' ? 'bg-emerald-950/90 text-emerald-200 border-emerald-600' : 'bg-red-950/90 text-red-200 border-red-600'"
        class="p-4 rounded-xl border text-xs font-semibold mb-6 flex items-center justify-between shadow-lg"
      >
        <div class="flex items-center gap-2">
          <span>{{ statusMessage.type === 'success' ? '✅' : '❌' }}</span>
          <span>{{ statusMessage.text }}</span>
        </div>
        <button @click="statusMessage.text = ''" class="text-gray-400 hover:text-white">✕</button>
      </div>

      <!-- Tab Navigation -->
      <div class="flex border-b border-white/10 mb-6 gap-2">
        <button
          @click="activeTab = 'profile'"
          :class="activeTab === 'profile' ? 'border-blueprint-accent text-blueprint-accent font-bold' : 'border-transparent text-gray-400 hover:text-gray-200'"
          class="py-2.5 px-4 text-xs sm:text-sm border-b-2 transition-colors flex items-center gap-2"
        >
          <span>👤</span> Hồ sơ cá nhân
        </button>
        <button
          @click="activeTab = 'password'"
          :class="activeTab === 'password' ? 'border-blueprint-accent text-blueprint-accent font-bold' : 'border-transparent text-gray-400 hover:text-gray-200'"
          class="py-2.5 px-4 text-xs sm:text-sm border-b-2 transition-colors flex items-center gap-2"
        >
          <span>🔒</span> Đổi mật khẩu
        </button>
        <button
          @click="activeTab = 'gemini'"
          :class="activeTab === 'gemini' ? 'border-blueprint-accent text-blueprint-accent font-bold' : 'border-transparent text-gray-400 hover:text-gray-200'"
          class="py-2.5 px-4 text-xs sm:text-sm border-b-2 transition-colors flex items-center gap-2"
        >
          <span>⚡</span> Cấu hình Gemini Key
        </button>
      </div>

      <!-- Tab 1: Profile -->
      <div v-if="activeTab === 'profile'" class="blueprint-card p-6 sm:p-8 space-y-6">
        <h3 class="text-base font-bold text-blueprint-dark border-b border-blueprint-paperBorder/60 pb-3">
          Thông Tin Người Dùng
        </h3>

        <form @submit.prevent="saveProfile" class="space-y-5">
          <!-- Avatar Section -->
          <div class="flex flex-col sm:flex-row items-center gap-5">
            <div class="relative w-24 h-24 rounded-full overflow-hidden border-2 border-blueprint shadow-md bg-gray-200 flex items-center justify-center">
              <img
                v-if="avatarPreview || authStore.user?.avatar"
                :src="avatarPreview || authStore.user?.avatar"
                alt="Avatar"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-3xl">👤</span>
            </div>
            <div>
              <label class="block text-xs font-bold text-blueprint-dark mb-1">Ảnh đại diện</label>
              <input
                type="file"
                @change="onAvatarFileSelected"
                accept="image/*"
                class="text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blueprint file:text-white hover:file:bg-blueprint-light cursor-pointer"
              />
              <p class="text-[11px] text-gray-500 mt-1">Định dạng PNG, JPG hoặc WebP (Tối đa 5MB)</p>
            </div>
          </div>

          <!-- Name -->
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Họ và tên</label>
            <input
              v-model="profileName"
              type="text"
              required
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Địa chỉ Email</label>
            <input
              :value="authStore.user?.email"
              disabled
              type="email"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-gray-100 text-gray-500 cursor-not-allowed font-mono"
            />
          </div>

          <!-- Linked Accounts Status -->
          <div class="p-3.5 rounded-lg bg-gray-50 border border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <svg class="w-4 h-4" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
              </svg>
              <span class="text-xs font-semibold text-gray-700">Tài khoản Google OAuth</span>
            </div>
            <span
              :class="authStore.user?.has_google ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-600'"
              class="px-2.5 py-0.5 rounded text-[11px] font-bold font-mono"
            >
              {{ authStore.user?.has_google ? 'Đã liên kết' : 'Chưa liên kết' }}
            </span>
          </div>

          <button
            type="submit"
            :disabled="saving"
            class="px-5 py-2.5 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-50"
          >
            {{ saving ? 'Đang lưu...' : '💾 Lưu thông tin' }}
          </button>
        </form>
      </div>

      <!-- Tab 2: Password -->
      <div v-if="activeTab === 'password'" class="blueprint-card p-6 sm:p-8 space-y-6">
        <h3 class="text-base font-bold text-blueprint-dark border-b border-blueprint-paperBorder/60 pb-3">
          Đổi Mật Khẩu
        </h3>

        <form @submit.prevent="savePassword" class="space-y-4 max-w-md">
          <div v-if="authStore.user?.has_password">
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mật khẩu hiện tại</label>
            <div class="relative">
              <input
                v-model="pwdCurrent"
                :type="showPwd ? 'text' : 'password'"
                required
                class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint pr-10"
              />
              <button
                type="button"
                @click="showPwd = !showPwd"
                class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-700 text-xs"
              >
                {{ showPwd ? '🙈' : '👁️' }}
              </button>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mật khẩu mới (Tối thiểu 6 ký tự)</label>
            <input
              v-model="pwdNew"
              :type="showPwd ? 'text' : 'password'"
              required
              minlength="6"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Xác nhận mật khẩu mới</label>
            <input
              v-model="pwdConfirm"
              :type="showPwd ? 'text' : 'password'"
              required
              minlength="6"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <button
            type="submit"
            :disabled="saving"
            class="px-5 py-2.5 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-50"
          >
            {{ saving ? 'Đang cập nhật...' : '🔒 Cập nhật mật khẩu' }}
          </button>
        </form>
      </div>

      <!-- Tab 3: Gemini Key -->
      <div v-if="activeTab === 'gemini'" class="blueprint-card p-6 sm:p-8 space-y-6">
        <h3 class="text-base font-bold text-blueprint-dark border-b border-blueprint-paperBorder/60 pb-3 flex items-center justify-between">
          <span>Khóa Cá Nhân Gemini API (Google AI Studio)</span>
          <span
            :class="authStore.hasCustomGeminiKey ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
            class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold"
          >
            {{ authStore.hasCustomGeminiKey ? 'Đang dùng Key cá nhân' : 'Đang dùng Key hệ thống' }}
          </span>
        </h3>

        <div class="text-xs text-gray-700 space-y-2">
          <p>
            Mặc định, NoteForge AI cung cấp sẵn API Key hệ thống cho bạn. Nếu bạn muốn sử dụng hạn mức riêng không giới hạn của tài khoản Google cá nhân, bạn có thể nhập API Key từ Google AI Studio bên dưới.
          </p>
          <p class="text-gray-500 font-mono text-[11px]">
            * Khóa được mã hóa bằng thuật toán <strong>AES-256</strong> trên cơ sở dữ liệu và chỉ giải mã tạm thời khi gọi Gemini AI.
          </p>
        </div>

        <div v-if="authStore.user?.masked_key" class="p-3 bg-gray-50 rounded-lg border border-gray-200 text-xs font-mono">
          <span class="text-gray-500">Key hiện tại: </span>
          <span class="font-bold text-blueprint">{{ authStore.user.masked_key }}</span>
        </div>

        <div class="space-y-4 max-w-lg">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">
              Nhập API Key mới (bắt đầu bằng AQ... hoặc AIzaSy...)
            </label>
            <input
              v-model="geminiKeyInput"
              type="text"
              placeholder="Nhập Google Gemini API Key của bạn..."
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs font-mono bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <div class="flex flex-wrap gap-2 pt-2">
            <button
              type="button"
              @click="testKey"
              :disabled="testingKey || (!geminiKeyInput.trim() && !authStore.hasCustomGeminiKey)"
              class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow disabled:opacity-40 flex items-center gap-1.5"
            >
              <span>🧪</span> {{ testingKey ? 'Đang kiểm tra...' : 'Kiểm tra Key' }}
            </button>

            <button
              type="button"
              @click="saveKey"
              :disabled="savingKey || !geminiKeyInput.trim()"
              class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow disabled:opacity-40"
            >
              {{ savingKey ? 'Đang lưu...' : '💾 Lưu Key cá nhân' }}
            </button>

            <button
              v-if="authStore.hasCustomGeminiKey"
              type="button"
              @click="resetKey"
              :disabled="savingKey"
              class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 font-semibold text-xs"
            >
              Dùng Key mặc định hệ thống
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const activeTab = ref('profile')
const statusMessage = ref({ type: '', text: '' })

const profileName = ref('')
const avatarFile = ref(null)
const avatarPreview = ref('')
const saving = ref(false)

const pwdCurrent = ref('')
const pwdNew = ref('')
const pwdConfirm = ref('')
const showPwd = ref(false)

const geminiKeyInput = ref('')
const testingKey = ref(false)
const savingKey = ref(false)

onMounted(async () => {
  await authStore.fetchUser()
  if (authStore.user) {
    profileName.value = authStore.user.name || ''
  }
})

const onAvatarFileSelected = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    avatarFile.value = file
    avatarPreview.value = URL.createObjectURL(file)
  }
}

const showMsg = (text, type = 'success') => {
  statusMessage.value = { text, type }
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const saveProfile = async () => {
  saving.value = true
  try {
    const formData = new FormData()
    formData.append('name', profileName.value)
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    const res = await authStore.updateProfile(formData)
    showMsg(res.message || 'Cập nhật hồ sơ thành công!', 'success')
  } catch (err) {
    showMsg(err.response?.data?.message || 'Cập nhật hồ sơ thất bại.', 'error')
  } finally {
    saving.value = false
  }
}

const savePassword = async () => {
  if (pwdNew.value !== pwdConfirm.value) {
    showMsg('Mật khẩu mới và xác nhận mật khẩu không khớp!', 'error')
    return
  }
  saving.value = true
  try {
    const res = await authStore.changePassword(pwdCurrent.value, pwdNew.value, pwdConfirm.value)
    showMsg(res.message || 'Đổi mật khẩu thành công!', 'success')
    pwdCurrent.value = ''
    pwdNew.value = ''
    pwdConfirm.value = ''
  } catch (err) {
    showMsg(err.response?.data?.message || 'Đổi mật khẩu thất bại.', 'error')
  } finally {
    saving.value = false
  }
}

const testKey = async () => {
  testingKey.value = true
  try {
    const key = geminiKeyInput.value.trim()
    const res = await authStore.testGeminiKey(key)
    showMsg(res.message || 'API Key hợp lệ và hoạt động tốt!', 'success')
  } catch (err) {
    showMsg(err.response?.data?.message || 'Kiểm tra thất bại: API Key không hợp lệ.', 'error')
  } finally {
    testingKey.value = false
  }
}

const saveKey = async () => {
  savingKey.value = true
  try {
    const key = geminiKeyInput.value.trim()
    const res = await authStore.updateGeminiKey(key)
    geminiKeyInput.value = ''
    showMsg(res.message || 'Đã lưu API Key thành công!', 'success')
  } catch (err) {
    showMsg(err.response?.data?.message || 'Lưu API Key thất bại.', 'error')
  } finally {
    savingKey.value = false
  }
}

const resetKey = async () => {
  if (!confirm('Bạn có chắc muốn xóa Key riêng và sử dụng Key mặc định của hệ thống?')) return
  savingKey.value = true
  try {
    const res = await authStore.updateGeminiKey('')
    geminiKeyInput.value = ''
    showMsg(res.message || 'Đã chuyển sang dùng Key mặc định!', 'success')
  } catch (err) {
    showMsg('Thao tác thất bại.', 'error')
  } finally {
    savingKey.value = false
  }
}

const handleLogout = () => {
  authStore.logout()
  router.push('/login')
}
</script>
