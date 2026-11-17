import { defineStore } from 'pinia'
import apiClient from '@/api/axios'

export const useConverterStore = defineStore('converter', {
  state: () => ({
    jobs: [],
    isProcessing: false,
    activeResult: null,
    error: null,
  }),

  actions: {
    async fetchJobs() {
      try {
        const response = await apiClient.get('/converters/jobs')
        this.jobs = response.data.data.data || []
      } catch (err) {
        console.error('Fetch jobs failed:', err)
      }
    },

    async convertPdfToWord(file) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      formData.append('file', file)

      try {
        const response = await apiClient.post('/converters/pdf-to-word', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Chuyển đổi PDF sang Word thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async convertWordToPdf(file) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      formData.append('file', file)

      try {
        const response = await apiClient.post('/converters/word-to-pdf', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Chuyển đổi Word sang PDF thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async mergePdfs(files) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      files.forEach((f) => formData.append('files[]', f))

      try {
        const response = await apiClient.post('/converters/merge-pdf', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Gộp PDF thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async splitPdf(file, pageRanges) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      formData.append('file', file)
      formData.append('page_ranges', pageRanges)

      try {
        const response = await apiClient.post('/converters/split-pdf', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Tách PDF thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async convertImage(file, format, quality = 90) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      formData.append('file', file)
      formData.append('format', format)
      formData.append('quality', quality)

      try {
        const response = await apiClient.post('/converters/image', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Chuyển đổi định dạng ảnh thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async imagesToPdf(files) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      files.forEach((f) => formData.append('files[]', f))

      try {
        const response = await apiClient.post('/converters/images-to-pdf', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Gộp ảnh thành PDF thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },

    async transcribeLecture(file) {
      this.isProcessing = true
      this.error = null
      this.activeResult = null
      const formData = new FormData()
      formData.append('file', file)

      try {
        const response = await apiClient.post('/converters/transcribe', formData)
        this.activeResult = response.data
        return response.data
      } catch (err) {
        this.error = err.response?.data?.message || 'Bóc băng bài giảng thất bại.'
        throw err
      } finally {
        this.isProcessing = false
      }
    },
  },
})
