import { defineStore } from 'pinia'
import apiClient from '@/api/axios'

export const useStudyStore = defineStore('study', {
  state: () => ({
    studySets: [],
    currentSet: null,
    progressMap: {}, // question_uid => { status, wrong_count }
    attempts: [],
    loading: false,
    generating: false,
    error: null,
  }),

  actions: {
    async fetchStudySets() {
      this.loading = true
      try {
        const response = await apiClient.get('/study-sets')
        this.studySets = response.data.data
        return this.studySets
      } catch (err) {
        this.error = err.response?.data?.message || 'Lỗi khi tải danh sách bộ đề.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async generateStudySet(payload) {
      this.generating = true
      this.error = null
      const formData = new FormData()

      if (payload.notebook_id) formData.append('notebook_id', payload.notebook_id)
      if (payload.document_id) formData.append('document_id', payload.document_id)
      if (payload.file) formData.append('file', payload.file)
      if (payload.raw_text) formData.append('raw_text', payload.raw_text)
      if (payload.title) formData.append('title', payload.title)

      try {
        const response = await apiClient.post('/study-sets/generate', formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        return response.data.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Khởi tạo tác vụ sinh đề thất bại.'
        throw err
      } finally {
        this.generating = false
      }
    },

    async pollStudySetStatus(studySetId) {
      try {
        const response = await apiClient.get(`/study-sets/${studySetId}/status`)
        return response.data.data
      } catch (err) {
        console.warn('Failed to poll study set status:', err)
        throw err
      }
    },

    async fetchStudySet(idOrSlug) {
      this.loading = true
      try {
        const response = await apiClient.get(`/study-sets/${idOrSlug}`)
        this.currentSet = response.data.data
        await this.fetchProgress(this.currentSet.id)
        return this.currentSet
      } catch (err) {
        // Fallback to public route if unauthenticated
        try {
          const pubResponse = await apiClient.get(`/study-sets/public/${idOrSlug}`)
          this.currentSet = pubResponse.data.data
          return this.currentSet
        } catch (pubErr) {
          this.error = 'Không thể tìm thấy bộ đề.'
          throw pubErr
        }
      } finally {
        this.loading = false
      }
    },

    async fetchProgress(studySetId) {
      try {
        const response = await apiClient.get(`/study-sets/${studySetId}/progress`)
        const list = response.data.data || []
        const map = {}
        list.forEach((item) => {
          map[item.question_uid] = item
        })
        this.progressMap = map
      } catch (err) {
        console.warn('Failed to load progress from server:', err)
      }
    },

    async updateQuestionProgress(studySetId, questionUid, status) {
      // Optimistic update
      this.progressMap[questionUid] = {
        question_uid: questionUid,
        status: status,
        wrong_count: status === 'wrong' ? ((this.progressMap[questionUid]?.wrong_count || 0) + 1) : 0,
      }

      try {
        await apiClient.post(`/study-sets/${studySetId}/progress`, {
          question_uid: questionUid,
          status,
        })
      } catch (err) {
        console.warn('Failed to persist progress to DB:', err)
      }
    },

    async submitExamAttempt(studySetId, payload) {
      try {
        const response = await apiClient.post(`/study-sets/${studySetId}/exam-attempt`, payload)
        return response.data.data
      } catch (err) {
        throw err
      }
    },

    async fetchAttempts(studySetId) {
      try {
        const response = await apiClient.get(`/study-sets/${studySetId}/attempts`)
        this.attempts = response.data.data
        return this.attempts
      } catch (err) {
        console.warn('Failed to load exam attempts:', err)
      }
    },

    async deleteStudySet(id) {
      try {
        await apiClient.delete(`/study-sets/${id}`)
        this.studySets = this.studySets.filter((s) => s.id !== id)
      } catch (err) {
        throw err
      }
    },

    getOfflineHtmlUrl(idOrSlug) {
      const rawBase = import.meta.env.VITE_API_BASE_URL || import.meta.env.VITE_API_URL || 'http://localhost:8000'
      const apiBase = rawBase.endsWith('/api') ? rawBase : `${rawBase.replace(/\/+$/, '')}/api`
      return `${apiBase}/study-sets/${idOrSlug}/export-html`
    },
  },
})
