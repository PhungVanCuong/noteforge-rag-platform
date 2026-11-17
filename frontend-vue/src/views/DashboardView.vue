<template>
  <div class="min-h-screen pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Welcome Hero Card -->
      <div class="blueprint-card p-6 md:p-8 mb-8 relative overflow-hidden">
        <div class="max-w-2xl">
          <div class="flex items-center gap-2 mb-2">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
              DASHBOARD
            </span>
            <span class="text-xs text-gray-500">Xin chào, {{ authStore.user?.name }}</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark leading-tight">
            Trợ lý Học tập & Chuyển hóa Học liệu AI
          </h1>
          <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed">
            Hệ thống RAG cá nhân hóa dựa trên giáo trình nội bộ trường học. Tự động bóc tách tài liệu, hỏi đáp có trích dẫn số trang chính xác và biên soạn bộ đề tương tác.
          </p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button
            @click="showCreateModal = true"
            class="px-4 py-2.5 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs flex items-center gap-2 shadow-md transition-all"
          >
            <span>➕</span> Thêm Môn Học Mới
          </button>
          <router-link
            to="/converter"
            class="px-4 py-2.5 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-semibold text-xs flex items-center gap-2 shadow-sm"
          >
            <span>🔄</span> Trạm chuyển đổi tệp
          </router-link>
          <router-link
            to="/study-sets"
            class="px-4 py-2.5 rounded-lg bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs flex items-center gap-2 shadow-sm"
          >
            <span>🎯</span> Kho Bộ Đề Ôn Tập
          </router-link>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="blueprint-card p-4">
          <div class="text-xs text-gray-500 font-mono">KHÔNG GIAN MÔN HỌC</div>
          <div class="text-2xl font-bold text-blueprint-dark mt-1">{{ notebookStore.notebooks.length }}</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-xs text-gray-500 font-mono">TÀI LIỆU RAG NỘI BỘ</div>
          <div class="text-2xl font-bold text-blueprint-dark mt-1">{{ totalDocuments }}</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-xs text-gray-500 font-mono">BỘ ĐỀ ÔN TẬP AI</div>
          <div class="text-2xl font-bold text-blueprint-accent mt-1">{{ totalStudySets }}</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-xs text-gray-500 font-mono">GEMINI API STATUS</div>
          <div class="text-sm font-bold text-emerald-600 mt-2 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
            {{ authStore.hasCustomGeminiKey ? 'API Key Cá nhân' : 'API Key Hệ thống' }}
          </div>
        </div>
      </div>

      <!-- Notebooks Grid -->
      <div class="mb-6 flex items-center justify-between">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
          <span>📚</span> Không Gian Môn Học Của Bạn
        </h2>
        <span class="text-xs font-mono text-gray-300">{{ notebookStore.notebooks.length }} môn học</span>
      </div>

      <div v-if="notebookStore.loading" class="text-center py-16 text-gray-400">
        Đang tải dữ liệu môn học...
      </div>

      <div v-else-if="notebookStore.notebooks.length === 0" class="blueprint-card p-12 text-center">
        <div class="text-4xl mb-3">📁</div>
        <h3 class="text-base font-bold text-blueprint-dark mb-1">Chưa có không gian môn học nào</h3>
        <p class="text-xs text-gray-600 mb-5 max-w-sm mx-auto">
          Tạo môn học đầu tiên (ví dụ: Lập trình .NET, Cơ sở dữ liệu) để tải giáo trình và kích hoạt trợ lý RAG.
        </p>
        <button
          @click="showCreateModal = true"
          class="px-5 py-2.5 rounded-lg bg-blueprint text-white font-bold text-xs shadow hover:bg-blueprint-light"
        >
          ➕ Tạo Môn Học Ngay
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="nb in notebookStore.notebooks"
          :key="nb.id"
          class="blueprint-card p-5 flex flex-col justify-between hover:shadow-xl transition-shadow relative group"
        >
          <div>
            <div class="flex items-center justify-between gap-2 mb-2">
              <span v-if="nb.subject_code" class="px-2 py-0.5 rounded bg-blue-100 text-blueprint font-mono text-[11px] font-bold border border-blue-200">
                {{ nb.subject_code }}
              </span>
              <span class="text-[11px] text-gray-500 font-mono ml-auto">
                {{ new Date(nb.created_at).toLocaleDateString('vi-VN') }}
              </span>
            </div>

            <h3 class="text-lg font-bold text-blueprint-dark line-clamp-1 group-hover:text-blueprint transition-colors">
              {{ nb.title }}
            </h3>

            <p class="text-xs text-gray-600 mt-2 line-clamp-2 leading-relaxed">
              {{ nb.description || 'Chưa có mô tả cho môn học này.' }}
            </p>
          </div>

          <div class="mt-6 pt-4 border-t border-blueprint-paperBorder/60 flex items-center justify-between">
            <div class="text-[11px] font-mono text-gray-600 flex items-center gap-3">
              <span>📄 {{ nb.documents_count || 0 }} tệp</span>
              <span>🎯 {{ nb.study_sets_count || 0 }} đề</span>
            </div>

            <router-link
              :to="`/workspace/${nb.id}`"
              class="px-3.5 py-1.5 rounded-md bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-sm flex items-center gap-1 transition-colors"
            >
              Vào học <span>→</span>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Notebook Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder pb-3">
          <h3 class="text-base font-bold text-blueprint-dark">Tạo Không Gian Môn Học Mới</h3>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg">
            ✕
          </button>
        </div>

        <form @submit.prevent="handleCreateNotebook" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Tên môn học *</label>
            <input
              v-model="newTitle"
              type="text"
              required
              placeholder="VD: Lập trình .NET & C#"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blueprint outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mã môn học</label>
            <input
              v-model="newCode"
              type="text"
              placeholder="VD: IT4020"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blueprint outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mô tả môn học</label>
            <textarea
              v-model="newDesc"
              rows="3"
              placeholder="Tóm tắt nội dung giáo trình và mục tiêu ôn thi..."
              class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-blueprint outline-none"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-100"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="creating"
              class="px-5 py-2 rounded-lg bg-blueprint hover:bg-blueprint-light text-white text-xs font-bold shadow disabled:opacity-50"
            >
              {{ creating ? 'Đang tạo...' : 'Tạo môn học' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useNotebookStore } from '@/stores/notebook'

const authStore = useAuthStore()
const notebookStore = useNotebookStore()

const showCreateModal = ref(false)
const newTitle = ref('')
const newCode = ref('')
const newDesc = ref('')
const creating = ref(false)

onMounted(() => {
  notebookStore.fetchNotebooks()
})

const totalDocuments = computed(() => {
  return notebookStore.notebooks.reduce((sum, n) => sum + (n.documents_count || 0), 0)
})

const totalStudySets = computed(() => {
  return notebookStore.notebooks.reduce((sum, n) => sum + (n.study_sets_count || 0), 0)
})

const handleCreateNotebook = async () => {
  creating.value = true
  try {
    await notebookStore.createNotebook({
      title: newTitle.value,
      subject_code: newCode.value,
      description: newDesc.value,
    })
    showCreateModal.value = false
    newTitle.value = ''
    newCode.value = ''
    newDesc.value = ''
  } catch (err) {
    alert(err.response?.data?.message || 'Tạo môn học thất bại.')
  } finally {
    creating.value = false
  }
}
</script>
