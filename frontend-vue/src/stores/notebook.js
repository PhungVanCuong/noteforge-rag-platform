import { defineStore } from 'pinia'
import apiClient from '@/api/axios'

export const useNotebookStore = defineStore('notebook', {
  state: () => ({
    notebooks: [],
    currentNotebook: null,
    documents: [],
    chatMessages: [],
    isChatLoading: false,
    loading: false,
    error: null,
  }),

  actions: {
    async fetchNotebooks() {
      this.loading = true
      try {
        const response = await apiClient.get('/notebooks')
        this.notebooks = response.data.data
        return this.notebooks
      } catch (err) {
        this.error = err.response?.data?.message || 'Lỗi khi tải danh sách môn học.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async createNotebook(payload) {
      try {
        const response = await apiClient.post('/notebooks', payload)
        this.notebooks.unshift(response.data.data)
        return response.data.data
      } catch (err) {
        throw err
      }
    },

    async fetchNotebook(id) {
      this.loading = true
      try {
        const response = await apiClient.get(`/notebooks/${id}`)
        this.currentNotebook = response.data.data
        this.documents = response.data.data.documents || []
        return this.currentNotebook
      } catch (err) {
        this.error = err.response?.data?.message || 'Không tìm thấy môn học.'
        throw err
      } finally {
        this.loading = false
      }
    },

    async deleteNotebook(id) {
      try {
        await apiClient.delete(`/notebooks/${id}`)
        this.notebooks = this.notebooks.filter((n) => n.id !== id)
      } catch (err) {
        throw err
      }
    },

    async uploadDocument(notebookId, file) {
      const formData = new FormData()
      formData.append('file', file)

      try {
        const response = await apiClient.post(`/notebooks/${notebookId}/upload`, formData, {
          headers: { 'Content-Type': 'multipart/form-data' },
        })
        const newDoc = response.data.data
        this.documents.push(newDoc)
        return newDoc
      } catch (err) {
        throw err
      }
    },

    async deleteDocument(docId) {
      try {
        await apiClient.delete(`/documents/${docId}`)
        this.documents = this.documents.filter((d) => d.id !== docId)
      } catch (err) {
        throw err
      }
    },

    async askRagChat(notebookId, question) {
      this.isChatLoading = true
      // Push user message immediately
      this.chatMessages.push({
        id: Date.now(),
        role: 'user',
        content: question,
        timestamp: new Date().toLocaleTimeString(),
      })

      try {
        const response = await apiClient.post('/rag/chat', {
          notebook_id: notebookId,
          question,
        })
        const result = response.data.data

        this.chatMessages.push({
          id: Date.now() + 1,
          role: 'assistant',
          content: result.answer,
          isGrounded: result.is_grounded,
          citations: result.citations || [],
          timestamp: new Date().toLocaleTimeString(),
        })

        return result
      } catch (err) {
        const errMsg = err.response?.data?.message || 'Không thể kết nối đến trợ lý RAG.'
        this.chatMessages.push({
          id: Date.now() + 1,
          role: 'assistant',
          content: `❌ Lỗi: ${errMsg}`,
          isError: true,
          citations: [],
          timestamp: new Date().toLocaleTimeString(),
        })
        throw err
      } finally {
        this.isChatLoading = false
      }
    },

    clearChat() {
      this.chatMessages = []
    },
  },
})
