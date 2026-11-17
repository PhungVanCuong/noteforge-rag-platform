<template>
  <div class="h-[calc(100vh-64px)] flex flex-col bg-slate-950 overflow-hidden">
    <!-- Workspace Top Header -->
    <div class="bg-blueprint-dark border-b border-blueprint-paperBorder/30 px-4 py-2.5 flex items-center justify-between text-white shrink-0">
      <div class="flex items-center gap-3">
        <router-link to="/dashboard" class="text-xs text-gray-300 hover:text-white flex items-center gap-1 font-mono">
          ◀ <span>Môn học</span>
        </router-link>
        <span class="text-gray-500">|</span>
        <div class="flex items-center gap-2">
          <span v-if="notebook?.subject_code" class="px-2 py-0.5 rounded bg-blue-900/80 text-blue-200 font-mono text-[11px] font-bold">
            {{ notebook.subject_code }}
          </span>
          <h1 class="text-sm font-bold text-white truncate max-w-xs sm:max-w-md">{{ notebook?.title }}</h1>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex items-center gap-2">
        <!-- Generate Study Set Button -->
        <button
          @click="openQuizModal"
          :disabled="!selectedDocument"
          class="px-3 py-1.5 rounded-md bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs flex items-center gap-1.5 shadow disabled:opacity-40 transition-all"
        >
          <span>🎯</span> Sinh Bộ Đề AI
        </button>

        <!-- Upload File Button -->
        <label class="px-3 py-1.5 rounded-md bg-blueprint hover:bg-blueprint-light text-white font-semibold text-xs flex items-center gap-1.5 shadow cursor-pointer transition-colors">
          <span>📤</span> Tải giáo trình
          <input type="file" @change="handleFileUpload" class="hidden" accept=".pdf,.docx,.doc,.pptx,.txt" />
        </label>
      </div>
    </div>

    <!-- Workspace Main Split View -->
    <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
      <!-- Left Pane: PDF Viewer -->
      <div class="w-full md:w-1/2 h-1/2 md:h-full border-b md:border-b-0 md:border-r border-slate-800 flex flex-col">
        <!-- Document Selector Bar -->
        <div class="bg-slate-900 px-4 py-2 flex items-center justify-between border-b border-slate-800 text-xs">
          <div class="flex items-center gap-2 truncate">
            <span class="text-gray-400 font-mono">Tài liệu:</span>
            <select
              v-model="selectedDocumentId"
              class="bg-slate-800 text-white rounded px-2.5 py-1 border border-slate-700 outline-none text-xs max-w-xs truncate"
            >
              <option v-for="doc in notebookStore.documents" :key="doc.id" :value="doc.id">
                {{ doc.file_name }} ({{ doc.total_pages }} trang) - [{{ doc.rag_status }}]
              </option>
            </select>
          </div>

          <button
            v-if="selectedDocument"
            @click="deleteCurrentDoc"
            class="text-[11px] text-red-400 hover:text-red-300 ml-2"
            title="Xóa tài liệu này"
          >
            🗑️ Xóa tệp
          </button>
        </div>

        <!-- PDF Reader Component -->
        <div class="flex-1 overflow-hidden">
          <PdfViewer
            ref="pdfViewerRef"
            :doc-url="currentDocDownloadUrl"
            :title="selectedDocument?.file_name"
            :initial-page="targetPage"
            :total-pages="selectedDocument?.total_pages || 1"
          />
        </div>
      </div>

      <!-- Right Pane: Strict Grounded RAG Chat -->
      <div class="w-full md:w-1/2 h-1/2 md:h-full flex flex-col bg-slate-900/90 text-white">
        <!-- Chat Header -->
        <div class="bg-slate-800/80 px-4 py-2 border-b border-slate-700/80 flex items-center justify-between text-xs">
          <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="font-bold text-gray-200">Trợ Lý RAG Chống Bịa Đặt</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-blue-950 text-blue-300 border border-blue-800">
              Cosine &ge; 0.65
            </span>
          </div>

          <button
            @click="notebookStore.clearChat"
            class="text-gray-400 hover:text-gray-200 text-xs font-mono"
          >
            Xóa lịch sử chat
          </button>
        </div>

        <!-- Chat Message Log -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4" ref="chatScroll">
          <!-- Empty State -->
          <div v-if="notebookStore.chatMessages.length === 0" class="text-center py-12 text-slate-400 max-w-sm mx-auto">
            <div class="w-12 h-12 rounded-xl bg-blueprint/60 text-blueprint-accent text-2xl flex items-center justify-center mx-auto mb-3">
              🤖
            </div>
            <h4 class="font-bold text-white text-sm mb-1">Trợ Lý Giáo Trình Sẵn Sàng</h4>
            <p class="text-xs text-slate-400 leading-relaxed mb-4">
              Đặt bất kỳ câu hỏi nào về môn học. AI chỉ trả lời dựa trên tài liệu bạn đã tải lên và đính kèm trích dẫn số trang chính xác.
            </p>
            <div class="space-y-1.5 text-left text-xs">
              <button
                v-for="(sample, sIdx) in sampleQuestions"
                :key="sIdx"
                @click="sendPrompt(sample)"
                class="w-full text-left p-2 rounded bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-300 truncate"
              >
                💬 {{ sample }}
              </button>
            </div>
          </div>

          <!-- Messages -->
          <div
            v-for="msg in notebookStore.chatMessages"
            :key="msg.id"
            :class="['flex flex-col', msg.role === 'user' ? 'items-end' : 'items-start']"
          >
            <div
              :class="[
                'max-w-[85%] rounded-2xl p-4 text-xs sm:text-sm leading-relaxed shadow',
                msg.role === 'user'
                  ? 'bg-blueprint text-white rounded-tr-none'
                  : 'bg-slate-800 border border-slate-700 text-slate-100 rounded-tl-none'
              ]"
            >
              <!-- Content -->
              <div class="whitespace-pre-wrap">{{ msg.content }}</div>

              <!-- Citations Badges (Clickable: jumps PDF viewer to page!) -->
              <div v-if="msg.citations && msg.citations.length > 0" class="mt-3 pt-3 border-t border-slate-700">
                <div class="text-[11px] font-mono text-blueprint-accent mb-1.5 font-bold flex items-center gap-1">
                  <span>📌</span> Trích dẫn nguồn tài liệu:
                </div>
                <div class="flex flex-wrap gap-1.5">
                  <button
                    v-for="(cite, cIdx) in msg.citations"
                    :key="cIdx"
                    @click="onCitationClick(cite)"
                    class="px-2.5 py-1 rounded bg-slate-900 hover:bg-blueprint border border-slate-600 hover:border-blueprint-accent text-[11px] font-mono text-cyan-300 hover:text-white flex items-center gap-1 transition-all"
                    :title="cite.preview"
                  >
                    <span>📑</span>
                    <span>{{ cite.file_name }}</span>
                    <span class="font-bold text-blueprint-accent">[Trang {{ cite.page_number }}]</span>
                  </button>
                </div>
              </div>
            </div>

            <span class="text-[10px] text-slate-500 font-mono mt-1 px-1">
              {{ msg.timestamp }}
            </span>
          </div>

          <!-- Loading Indicator -->
          <div v-if="notebookStore.isChatLoading" class="flex items-center gap-2 text-xs text-blueprint-accent p-2">
            <span class="w-2 h-2 rounded-full bg-blueprint-accent animate-ping"></span>
            Đang truy xuất Vector DB & tổng hợp câu trả lời có căn cứ...
          </div>
        </div>

        <!-- Chat Input Bar -->
        <div class="p-3 bg-slate-950 border-t border-slate-800">
          <form @submit.prevent="handleChatSubmit" class="flex items-center gap-2">
            <input
              v-model="questionInput"
              type="text"
              :disabled="notebookStore.isChatLoading"
              placeholder="Đặt câu hỏi về nội dung giáo trình môn học..."
              class="flex-1 bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-xs sm:text-sm text-white focus:border-blueprint-accent focus:ring-1 focus:ring-blueprint-accent outline-none"
            />
            <button
              type="submit"
              :disabled="!questionInput.trim() || notebookStore.isChatLoading"
              class="px-5 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs shadow disabled:opacity-40 transition-transform active:scale-95"
            >
              Gửi
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useNotebookStore } from '@/stores/notebook'
import { useStudyStore } from '@/stores/study'
import PdfViewer from '@/components/PdfViewer.vue'

const route = useRoute()
const router = useRouter()
const notebookStore = useNotebookStore()
const studyStore = useStudyStore()

const notebookId = Number(route.params.id)
const notebook = ref(null)
const selectedDocumentId = ref(null)
const pdfViewerRef = ref(null)
const targetPage = ref(1)
const questionInput = ref('')
const chatScroll = ref(null)

const sampleQuestions = [
  'Tóm tắt các kiến thức cốt lõi trong chương 1?',
  'Nguyên lý hoạt động và kiến trúc của hệ thống là gì?',
  'Liệt kê các bước triển khai thuật toán được đề cập trong giáo trình?',
]

onMounted(async () => {
  notebook.value = await notebookStore.fetchNotebook(notebookId)
  if (notebookStore.documents.length > 0) {
    selectedDocumentId.value = notebookStore.documents[0].id
  }
})

const selectedDocument = computed(() => {
  return notebookStore.documents.find((d) => d.id === selectedDocumentId.value)
})

const currentDocDownloadUrl = computed(() => {
  if (!selectedDocument.value) return null
  const apiBase = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  return `${apiBase}/documents/${selectedDocument.value.id}/download`
})

const onCitationClick = (citation) => {
  if (citation.page_number && pdfViewerRef.value) {
    targetPage.value = citation.page_number
    pdfViewerRef.value.jumpToPage(citation.page_number)
  }
}

const sendPrompt = (text) => {
  questionInput.value = text
  handleChatSubmit()
}

const handleChatSubmit = async () => {
  const q = questionInput.value.trim()
  if (!q) return

  questionInput.value = ''
  await nextTick()
  scrollToBottom()

  try {
    await notebookStore.askRagChat(notebookId, q)
    await nextTick()
    scrollToBottom()
  } catch (err) {
    console.error('Chat error:', err)
  }
}

const scrollToBottom = () => {
  if (chatScroll.value) {
    chatScroll.value.scrollTop = chatScroll.value.scrollHeight
  }
}

const handleFileUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  try {
    const doc = await notebookStore.uploadDocument(notebookId, file)
    selectedDocumentId.value = doc.id
    alert('Tải lên và lập chỉ mục Vector RAG thành công!')
  } catch (err) {
    alert(err.response?.data?.message || 'Tải lên tài liệu thất bại.')
  } finally {
    event.target.value = ''
  }
}

const deleteCurrentDoc = async () => {
  if (!selectedDocument.value) return
  if (!confirm(`Bạn có chắc muốn xóa tài liệu ${selectedDocument.value.file_name}?`)) return

  try {
    await notebookStore.deleteDocument(selectedDocument.value.id)
    if (notebookStore.documents.length > 0) {
      selectedDocumentId.value = notebookStore.documents[0].id
    } else {
      selectedDocumentId.value = null
    }
  } catch (err) {
    alert('Xóa tài liệu thất bại.')
  }
}

const openQuizModal = async () => {
  if (!selectedDocument.value) return
  if (!confirm(`Sinh bộ đề ôn tập AI từ tài liệu "${selectedDocument.value.file_name}"?`)) return

  try {
    const set = await studyStore.generateStudySet({
      notebook_id: notebookId,
      document_id: selectedDocument.value.id,
      title: `Bộ đề: ${selectedDocument.value.file_name}`,
    })
    router.push(`/study-player/${set.id}`)
  } catch (err) {
    alert(err.response?.data?.message || 'Sinh bộ đề thất bại.')
  }
}
</script>
