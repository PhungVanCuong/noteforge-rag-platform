<template>
  <div class="min-h-screen blueprint-grid-bg flex items-center justify-center p-4">
    <div class="blueprint-card max-w-md w-full p-8 shadow-2xl text-center">
      <!-- Icon -->
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blueprint text-blueprint-accent text-3xl shadow-lg mb-3">
        ✉️
      </div>

      <h1 class="text-2xl font-bold text-blueprint-dark">Xác Thực Mã OTP</h1>
      <p class="text-xs text-gray-600 mt-1 mb-6">
        Mã xác nhận 6 số đã được gửi đến email: <br>
        <strong class="text-blueprint-dark font-mono text-sm">{{ email }}</strong>
      </p>

      <!-- Alert -->
      <div v-if="errorMessage" class="mb-5 p-3 rounded-lg bg-red-100 border border-red-300 text-red-700 text-xs font-medium">
        {{ errorMessage }}
      </div>
      <div v-if="successMessage" class="mb-5 p-3 rounded-lg bg-green-100 border border-green-300 text-green-800 text-xs font-medium">
        {{ successMessage }}
      </div>

      <!-- 6 Digits Input Box -->
      <div class="flex justify-center gap-2 sm:gap-3 mb-6" @paste="handlePaste">
        <input
          v-for="(_, idx) in 6"
          :key="idx"
          :ref="(el) => (inputRefs[idx] = el)"
          type="text"
          maxlength="1"
          v-model="digits[idx]"
          @input="onDigitInput(idx)"
          @keydown.backspace="onBackspace(idx)"
          class="w-12 h-14 text-center font-mono text-2xl font-bold rounded-lg border-2 border-gray-300 focus:border-blueprint focus:bg-blue-50/50 outline-none text-blueprint-dark shadow-inner transition-all"
        />
      </div>

      <!-- Countdown -->
      <div class="text-xs text-gray-500 font-mono mb-6">
        Mã có hiệu lực trong:
        <span :class="['font-bold', countdown <= 60 ? 'text-red-600 animate-pulse' : 'text-blueprint']">
          {{ formattedCountdown }}
        </span>
      </div>

      <!-- Submit Button -->
      <button
        @click="submitOtp"
        :disabled="loading || digits.join('').length !== 6"
        class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-sm shadow-md transition-all disabled:opacity-50 mb-4"
      >
        {{ loading ? 'Đang xác thực...' : 'Kích hoạt tài khoản' }}
      </button>

      <!-- Resend Button -->
      <div class="text-xs text-gray-600">
        Chưa nhận được mã?
        <button
          @click="resendOtp"
          :disabled="resendCooldown > 0"
          class="font-bold text-blueprint hover:underline disabled:text-gray-400 disabled:no-underline ml-1"
        >
          {{ resendCooldown > 0 ? `Gửi lại sau (${resendCooldown}s)` : 'Gửi lại mã OTP' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const router = useRouter()

const email = ref(authStore.pendingEmail || localStorage.getItem('noteforge_pending_email') || '')
const digits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

const countdown = ref(300) // 5 minutes
const resendCooldown = ref(0)
let timer = null
let resendTimer = null

const formattedCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60).toString().padStart(2, '0')
  const s = (countdown.value % 60).toString().padStart(2, '0')
  return `${m}:${s}`
})

onMounted(() => {
  if (!email.value) {
    router.push('/register')
    return
  }

  // Focus first input
  if (inputRefs.value[0]) {
    inputRefs.value[0].focus()
  }

  timer = setInterval(() => {
    if (countdown.value > 0) {
      countdown.value--
    } else {
      clearInterval(timer)
    }
  }, 1000)
})

onUnmounted(() => {
  if (timer) clearInterval(timer)
  if (resendTimer) clearInterval(resendTimer)
})

const onDigitInput = (idx) => {
  const val = digits.value[idx]
  if (val && !/^[0-9]$/.test(val)) {
    digits.value[idx] = ''
    return
  }

  if (val && idx < 5) {
    inputRefs.value[idx + 1]?.focus()
  }

  if (digits.value.join('').length === 6) {
    submitOtp()
  }
}

const onBackspace = (idx) => {
  if (!digits.value[idx] && idx > 0) {
    inputRefs.value[idx - 1]?.focus()
  }
}

const handlePaste = (e) => {
  const pasteData = e.clipboardData.getData('text').trim()
  if (/^\d{6}$/.test(pasteData)) {
    pasteData.split('').forEach((char, i) => {
      digits.value[i] = char
    })
    submitOtp()
  }
}

const submitOtp = async () => {
  const fullCode = digits.value.join('')
  if (fullCode.length !== 6) return

  loading.value = true
  errorMessage.value = ''
  try {
    await authStore.verifyOtp(email.value, fullCode)
    router.push('/dashboard')
  } catch (err) {
    errorMessage.value = err.response?.data?.message || 'Mã OTP không chính xác hoặc đã hết hạn.'
  } finally {
    loading.value = false
  }
}

const resendOtp = async () => {
  if (resendCooldown.value > 0) return
  errorMessage.value = ''
  try {
    await authStore.resendOtp(email.value)
    successMessage.value = 'Đã gửi lại mã OTP mới đến email của bạn.'
    countdown.value = 300
    resendCooldown.value = 60

    resendTimer = setInterval(() => {
      if (resendCooldown.value > 0) {
        resendCooldown.value--
      } else {
        clearInterval(resendTimer)
      }
    }, 1000)
  } catch (err) {
    errorMessage.value = 'Gửi lại OTP thất bại.'
  }
}
</script>
