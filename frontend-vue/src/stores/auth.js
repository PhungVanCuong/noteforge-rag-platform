import { defineStore } from 'pinia'
import apiClient from '@/api/axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('noteforge_user') || 'null'),
    token: localStorage.getItem('noteforge_token') || null,
    pendingEmail: localStorage.getItem('noteforge_pending_email') || null,
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    hasCustomGeminiKey: (state) => state.user?.has_custom_key || false,
  },

  actions: {
    async register(name, email, password) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post('/auth/register', { name, email, password })
        this.pendingEmail = email
        localStorage.setItem('noteforge_pending_email', email)
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Đăng ký thất bại. Vui lòng thử lại.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async verifyOtp(email, otpCode) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post('/auth/verify-otp', {
          email,
          otp_code: otpCode,
        })
        const { token, user } = response.data
        this.token = token
        this.user = user
        localStorage.setItem('noteforge_token', token)
        localStorage.setItem('noteforge_user', JSON.stringify(user))
        localStorage.removeItem('noteforge_pending_email')
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Mã OTP không hợp lệ.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async resendOtp(email) {
      try {
        const response = await apiClient.post('/auth/resend-otp', { email })
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Gửi lại OTP thất bại.'
        throw err
      }
    },

    async login(email, password) {
      this.loading = true
      this.error = null
      try {
        const response = await apiClient.post('/auth/login', { email, password })
        const { token, user } = response.data
        this.token = token
        this.user = user
        localStorage.setItem('noteforge_token', token)
        localStorage.setItem('noteforge_user', JSON.stringify(user))
        return response.data
      } catch (err) {
        if (err.response?.data?.needs_verification) {
          this.pendingEmail = email
          localStorage.setItem('noteforge_pending_email', email)
        }
        this.error = err.response?.data?.message || 'Đăng nhập không thành công.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async fetchUser() {
      if (!this.token) return null
      try {
        const response = await apiClient.get('/auth/me')
        this.user = response.data.user
        localStorage.setItem('noteforge_user', JSON.stringify(this.user))
        return this.user
      } catch (err) {
        this.logout()
        return null
      }
    },

    async updateProfile(formData) {
      this.loading = true
      try {
        const response = await apiClient.post('/user/profile', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
            'X-HTTP-Method-Override': 'PUT',
          },
        })
        if (response.data.user) {
          this.user = { ...this.user, ...response.data.user }
          localStorage.setItem('noteforge_user', JSON.stringify(this.user))
        }
        return response.data
      } finally {
        this.loading = false
      }
    },

    async changePassword(currentPassword, newPassword, confirmPassword) {
      this.loading = true
      try {
        const response = await apiClient.put('/user/password', {
          current_password: currentPassword,
          new_password: newPassword,
          new_password_confirmation: confirmPassword,
        })
        return response.data
      } finally {
        this.loading = false
      }
    },

    async testGeminiKey(apiKey) {
      const response = await apiClient.post('/user/gemini-key/test', { gemini_key: apiKey })
      return response.data
    },

    async updateGeminiKey(apiKey) {
      this.loading = true
      try {
        const response = await apiClient.put('/user/gemini-key', { gemini_key: apiKey })
        if (this.user) {
          this.user.has_custom_key = response.data.has_custom_key
          this.user.masked_key = response.data.masked_key
          localStorage.setItem('noteforge_user', JSON.stringify(this.user))
        }
        return response.data
      } finally {
        this.loading = false
      }
    },

    logout() {
      if (this.token) {
        apiClient.post('/logout').catch(() => {})
      }
      this.user = null
      this.token = null
      localStorage.removeItem('noteforge_token')
      localStorage.removeItem('noteforge_user')
    },
  },
})
