<template>
  <div class="min-h-screen pb-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-10">

      <!-- ========================================== -->
      <!-- TOP HERO & WELCOME BANNER                  -->
      <!-- ========================================== -->
      <div class="blueprint-card p-6 sm:p-8 relative overflow-hidden shadow-xl border-2 border-blueprint/20">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-start gap-4">
            <!-- User Avatar / Initials with link to profile -->
            <router-link
              to="/profile"
              class="relative w-14 h-14 sm:w-16 sm:h-16 rounded-2xl overflow-hidden border-2 border-blueprint shadow-md bg-white flex items-center justify-center shrink-0 group hover:ring-2 hover:ring-blueprint-accent transition-all"
              title="Quản lý hồ sơ & đổi ảnh đại diện"
            >
              <img
                v-if="authStore.user?.avatar"
                :src="authStore.user.avatar"
                alt="Avatar"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform"
              />
              <span v-else class="text-xl sm:text-2xl font-black text-blueprint">
                {{ authStore.user?.name?.charAt(0) || 'U' }}
              </span>
              <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-[10px] font-bold transition-opacity">
                Đổi ảnh
              </div>
            </router-link>

            <div>
              <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
                  WORKSPACE CHÍNH
                </span>
                <span class="text-xs text-gray-500 font-mono">Xin chào, {{ authStore.user?.name }}</span>
              </div>
              <h1 class="text-xl sm:text-3xl font-extrabold text-blueprint-dark leading-tight">
                Trợ Lý RAG & Ôn Tập Học Liệu Thông Minh
              </h1>
              <p class="text-xs sm:text-sm text-gray-700 mt-1 max-w-2xl leading-relaxed">
                Kho học tập tích hợp: Bóc tách giáo trình trường học, hỏi đáp trợ lý RAG chống bịa đặt có trích dẫn số trang và tự động sinh đề thi trắc nghiệm & đục lỗ code.
              </p>
            </div>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            <router-link
              to="/study-sets"
              class="px-4 py-2.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-extrabold text-xs shadow-md flex items-center gap-2 transition-transform hover:scale-105"
            >
              <span>✨</span> Tạo Bộ Đề Mới Bằng AI
            </router-link>
            <button
              @click="showCreateModal = true"
              class="px-4 py-2.5 rounded-xl bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-md flex items-center gap-2 transition-transform hover:scale-105"
            >
              <span>➕</span> Thêm Môn Học Mới
            </button>
            <router-link
              to="/converter"
              class="px-3.5 py-2.5 rounded-xl bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-semibold text-xs shadow-sm flex items-center gap-1.5"
            >
              <span>🔄</span> Trạm Chuyển Đổi
            </router-link>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- METRICS OVERVIEW STRIP                     -->
      <!-- ========================================== -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="blueprint-card p-4">
          <div class="text-[11px] text-gray-500 font-mono font-bold">KHÔNG GIAN MÔN HỌC</div>
          <div class="text-2xl font-black text-blueprint-dark mt-1">{{ notebookStore.notebooks.length }}</div>
          <div class="text-[10px] text-gray-500 mt-0.5">Môn học đang theo dõi</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-[11px] text-gray-500 font-mono font-bold">BỘ ĐỀ SẴN SÀNG</div>
          <div class="text-2xl font-black text-blueprint-accent mt-1">{{ validStudySets.length }}</div>
          <div class="text-[10px] text-gray-500 mt-0.5">Trắc nghiệm & code [[...]]</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-[11px] text-gray-500 font-mono font-bold">GIÁO TRÌNH & ĐỀ CƯƠNG</div>
          <div class="text-2xl font-black text-blueprint mt-1">{{ totalDocuments }}</div>
          <div class="text-[10px] text-gray-500 mt-0.5">Đã lập chỉ mục Vector DB</div>
        </div>
        <div class="blueprint-card p-4">
          <div class="text-[11px] text-gray-500 font-mono font-bold">AI ENGINE STATUS</div>
          <div class="text-xs font-bold text-emerald-600 mt-2 flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            {{ authStore.hasCustomGeminiKey ? 'Personal Key' : 'System Key' }}
          </div>
          <router-link to="/profile" class="text-[10px] text-blueprint hover:underline mt-0.5 inline-block">
            Cấu hình API Key →
          </router-link>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- PHẦN 1: KHO BỘ ĐỀ ÔN TẬP TƯƠNG TÁC (TOP)   -->
      <!-- ========================================== -->
      <section class="space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">🎯</span>
            <h2 class="text-lg sm:text-xl font-extrabold text-white">
              Kho Bộ Đề Ôn Tập Tương Tác
            </h2>
            <span class="px-2 py-0.5 rounded-full bg-blueprint-accent/20 text-blueprint-accent text-[11px] font-mono font-bold border border-blueprint-accent/40">
              {{ validStudySets.length }} bộ đề khả dụng
            </span>
          </div>

          <router-link
            to="/study-sets"
            class="text-xs font-bold text-blueprint-accent hover:text-amber-300 flex items-center gap-1 transition-colors"
          >
            Quản lý tất cả bộ đề <span>→</span>
          </router-link>
        </div>

        <div v-if="studyStore.loading" class="text-center py-12 text-gray-400 font-mono text-xs">
          <span class="inline-block animate-spin mr-2">⏳</span> Đang đồng bộ kho bộ đề ôn tập...
        </div>

        <!-- Empty State (No valid study sets) -->
        <div v-else-if="validStudySets.length === 0" class="blueprint-card p-8 sm:p-12 text-center">
          <div class="text-4xl mb-3">🎯</div>
          <h3 class="text-base font-bold text-blueprint-dark mb-1">Chưa có bộ đề ôn tập nào sẵn sàng</h3>
          <p class="text-xs text-gray-600 mb-5 max-w-md mx-auto leading-relaxed">
            Hệ thống tự động ẩn những đề nháp chưa có câu hỏi. Hãy tải file Word/PDF đề thi để AI bóc tách trắc nghiệm và đục lỗ mã nguồn ngay!
          </p>
          <router-link
            to="/study-sets"
            class="px-5 py-2.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-extrabold text-xs shadow inline-flex items-center gap-2"
          >
            <span>✨</span> Tạo Bộ Đề Mới Bằng AI Ngay
          </router-link>
        </div>

        <!-- Study Sets Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="set in validStudySets"
            :key="set.id"
            class="blueprint-card p-5 flex flex-col justify-between hover:shadow-2xl transition-all group relative border hover:border-blueprint"
          >
            <div>
              <div class="flex items-center justify-between text-[11px] text-gray-500 font-mono mb-2">
                <span v-if="set.notebook" class="px-2 py-0.5 rounded bg-blue-100 text-blueprint font-bold">
                  {{ set.notebook.title }}
                </span>
                <span v-else class="text-gray-400 font-semibold">Độc lập</span>
                <span>{{ new Date(set.created_at).toLocaleDateString('vi-VN') }}</span>
              </div>

              <h3 class="text-base font-bold text-blueprint-dark line-clamp-2 group-hover:text-blueprint transition-colors mb-2">
                {{ set.title }}
              </h3>

              <!-- Categories Tags -->
              <div class="flex flex-wrap gap-1 mb-4">
                <span
                  v-for="(cat, cIdx) in (set.categories_json || []).slice(0, 3)"
                  :key="cIdx"
                  class="px-2 py-0.5 rounded bg-gray-200/90 text-gray-700 font-mono text-[10px]"
                >
                  {{ cat }}
                </span>
              </div>
            </div>

            <div class="space-y-3 pt-3 border-t border-blueprint-paperBorder/60">
              <div class="flex items-center justify-between text-xs font-mono">
                <span class="text-gray-700 font-semibold">
                  <strong>{{ (set.questions_json || []).length }}</strong> câu &bull; 
                  <strong>{{ (set.code_exercises_json || []).length }}</strong> code
                  <span v-if="(set.shorts_json || []).length || (set.essays_json || []).length">
                    &bull; <strong>{{ (set.shorts_json || []).length + (set.essays_json || []).length }}</strong> tự luận
                  </span>
                </span>

                <button
                  @click="handleDeleteStudySet(set)"
                  class="p-1 rounded text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Xóa bộ đề"
                >
                  🗑️
                </button>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <a
                  :href="studyStore.getOfflineHtmlUrl(set.id)"
                  target="_blank"
                  class="py-2 px-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] text-center flex items-center justify-center gap-1 transition-colors"
                  title="Tải tệp HTML độc lập chạy offline không cần mạng"
                >
                  <span>📥</span> Tải HTML
                </a>

                <router-link
                  :to="`/study-player/${set.id}`"
                  class="py-2 px-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs text-center flex items-center justify-center gap-1 shadow-sm transition-colors"
                >
                  Luyện ngay <span>→</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ========================================== -->
      <!-- PHẦN 2: KHÔNG GIAN MÔN HỌC (RAG WORKSPACES) -->
      <!-- ========================================== -->
      <section class="space-y-4">
        <div class="flex items-center justify-between border-b border-white/10 pb-3">
          <div class="flex items-center gap-2">
            <span class="text-xl">📚</span>
            <h2 class="text-lg sm:text-xl font-extrabold text-white">
              Không Gian Môn Học (RAG Workspaces)
            </h2>
            <span class="px-2 py-0.5 rounded-full bg-blue-500/20 text-cyan-300 text-[11px] font-mono font-bold border border-cyan-500/30">
              {{ notebookStore.notebooks.length }} môn học
            </span>
          </div>

          <button
            @click="showCreateModal = true"
            class="text-xs font-bold text-blueprint-accent hover:text-amber-300 flex items-center gap-1 transition-colors"
          >
            <span>➕</span> Thêm môn học mới
          </button>
        </div>

        <div v-if="notebookStore.loading" class="text-center py-12 text-gray-400 font-mono text-xs">
          <span class="inline-block animate-spin mr-2">⏳</span> Đang tải danh sách môn học...
        </div>

        <!-- Empty State -->
        <div v-else-if="notebookStore.notebooks.length === 0" class="blueprint-card p-8 sm:p-12 text-center">
          <div class="text-4xl mb-3">📁</div>
          <h3 class="text-base font-bold text-blueprint-dark mb-1">Chưa có không gian môn học nào</h3>
          <p class="text-xs text-gray-600 mb-5 max-w-sm mx-auto leading-relaxed">
            Tạo môn học đầu tiên (ví dụ: Lập trình .NET & WPF, Cơ sở dữ liệu) để tải giáo trình và kích hoạt trợ lý RAG trả lời có trích dẫn.
          </p>
          <button
            @click="showCreateModal = true"
            class="px-5 py-2.5 rounded-xl bg-blueprint text-white font-bold text-xs shadow hover:bg-blueprint-light"
          >
            ➕ Tạo Môn Học Đầu Tiên
          </button>
        </div>

        <!-- Notebooks Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="nb in notebookStore.notebooks"
            :key="nb.id"
            class="blueprint-card p-5 flex flex-col justify-between hover:shadow-2xl transition-all relative group border hover:border-blueprint"
          >
            <div>
              <div class="flex items-center justify-between gap-2 mb-2">
                <span v-if="nb.subject_code" class="px-2 py-0.5 rounded bg-blue-100 text-blueprint font-mono text-[11px] font-bold border border-blue-200">
                  {{ nb.subject_code }}
                </span>
                <span v-else class="px-2 py-0.5 rounded bg-gray-100 text-gray-600 font-mono text-[11px]">
                  Môn học
                </span>
                <span class="text-[11px] text-gray-500 font-mono ml-auto">
                  {{ new Date(nb.created_at).toLocaleDateString('vi-VN') }}
                </span>
              </div>

              <h3 class="text-base sm:text-lg font-bold text-blueprint-dark line-clamp-1 group-hover:text-blueprint transition-colors">
                {{ nb.title }}
              </h3>

              <p class="text-xs text-gray-600 mt-2 line-clamp-2 leading-relaxed min-h-[32px]">
                {{ nb.description || 'Chưa có mô tả cho môn học này.' }}
              </p>
            </div>

            <div class="mt-5 pt-4 border-t border-blueprint-paperBorder/60 flex items-center justify-between">
              <div class="text-[11px] font-mono text-gray-600 flex items-center gap-3">
                <span>📄 {{ nb.documents_count || 0 }} tệp</span>
                <span>🎯 {{ nb.study_sets_count || 0 }} đề</span>
              </div>

              <div class="flex items-center gap-2">
                <button
                  @click="handleDeleteNotebook(nb)"
                  class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Xóa môn học này"
                >
                  🗑️
                </button>

                <router-link
                  :to="`/workspace/${nb.id}`"
                  class="px-3.5 py-1.5 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-sm flex items-center gap-1 transition-colors"
                >
                  Vào học <span>→</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>

    <!-- Create Notebook Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
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
              placeholder="VD: Lập trình .NET & WPF Kiến Trúc MVVM"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white focus:ring-2 focus:ring-blueprint outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mã môn học (Tùy chọn)</label>
            <input
              v-model="newCode"
              type="text"
              placeholder="VD: PRN211"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white focus:ring-2 focus:ring-blueprint outline-none"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Mô tả môn học</label>
            <textarea
              v-model="newDesc"
              rows="3"
              placeholder="Tóm tắt nội dung giáo trình và mục tiêu ôn thi..."
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white focus:ring-2 focus:ring-blueprint outline-none"
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
import { useStudyStore } from '@/stores/study'

const authStore = useAuthStore()
const notebookStore = useNotebookStore()
const studyStore = useStudyStore()

const showCreateModal = ref(false)
const newTitle = ref('')
const newCode = ref('')
const newDesc = ref('')
const creating = ref(false)

onMounted(async () => {
  await Promise.allSettled([
    notebookStore.fetchNotebooks(),
    studyStore.fetchStudySets(),
  ])
})

const totalDocuments = computed(() => {
  return notebookStore.notebooks.reduce((sum, n) => sum + (n.documents_count || 0), 0)
})

// Strictly filter out any empty study sets (0 questions and 0 code)
const validStudySets = computed(() => {
  return (studyStore.studySets || []).filter(set => {
    const qCount = set.questions_json?.length || 0
    const cCount = set.code_exercises_json?.length || 0
    const sCount = set.shorts_json?.length || 0
    const eCount = set.essays_json?.length || 0
    const total = qCount + cCount + sCount + eCount
    if (set.status === 'processing') return true
    return total > 0 && set.status === 'completed'
  })
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

const handleDeleteNotebook = async (nb) => {
  if (!confirm(`Bạn có chắc muốn xóa môn học "${nb.title}"? Tất cả tài liệu liên quan sẽ bị xóa khỏi không gian học.`)) {
    return
  }
  try {
    await notebookStore.deleteNotebook(nb.id)
  } catch (err) {
    alert(err.response?.data?.message || 'Xóa môn học thất bại.')
  }
}

const handleDeleteStudySet = async (set) => {
  if (!confirm(`Bạn có chắc muốn xóa bộ đề "${set.title}"?`)) {
    return
  }
  try {
    await studyStore.deleteStudySet(set.id)
  } catch (err) {
    alert('Xóa bộ đề thất bại.')
  }
}
</script>
