<template>
  <div class="min-h-screen pb-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header Banner -->
      <div class="blueprint-card p-6 md:p-8 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
              EXAM & CLOZE GENERATOR
            </span>
            <span class="text-xs text-gray-500 font-mono">Gemini 3.8 Flash Structured JSON</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark">
            Kho Bộ Đề Ôn Tập Tương Tác
          </h1>
          <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed max-w-2xl">
            Tất cả bộ đề trắc nghiệm và bài tập gõ code [[...]] được AI bóc tách từ ngân hàng câu hỏi hoặc giáo trình lý thuyết.
          </p>
        </div>

        <button
          @click="openCreateModal"
          class="px-5 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs sm:text-sm shadow-md flex items-center gap-2 self-start md:self-center transition-all hover:scale-105"
        >
          <span>✨</span> Tạo Bộ Đề Mới Bằng AI
        </button>
      </div>

      <!-- Success Notification Banner -->
      <div
        v-if="createdSuccessSet"
        class="mb-8 p-6 rounded-2xl bg-gradient-to-r from-emerald-950/90 to-blue-950/90 border-2 border-emerald-500 text-white shadow-2xl animate-fade-in"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
              <span>🎉</span> Chúc Mừng! Sinh Bộ Đề Thành Công
            </div>
            <h3 class="text-lg font-extrabold text-white">
              {{ createdSuccessSet.title }}
            </h3>
            <p class="text-xs text-emerald-200 font-mono">
              Đã bóc tách thành công <strong>{{ createdSuccessSet.questions_json?.length || 0 }} câu trắc nghiệm</strong> và <strong>{{ createdSuccessSet.code_exercises_json?.length || 0 }} bài tập điền khuyết code</strong>!
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <router-link
              :to="`/study-player/${createdSuccessSet.id}`"
              class="px-5 py-2.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-extrabold text-xs shadow-md flex items-center gap-1.5 transition-transform hover:scale-105"
            >
              <span>🚀</span> Vào Luyện Ngay
            </router-link>

            <a
              :href="studyStore.getOfflineHtmlUrl(createdSuccessSet.id)"
              target="_blank"
              class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white border border-white/20 font-bold text-xs flex items-center gap-1.5 transition-colors"
            >
              <span>📥</span> Tải HTML Offline
            </a>

            <button
              @click="createdSuccessSet = null"
              class="p-2 text-gray-400 hover:text-white"
              title="Đóng thông báo"
            >
              ✕
            </button>
          </div>
        </div>
      </div>

      <!-- Study Sets Grid -->
      <div v-if="studyStore.loading" class="text-center py-16 text-gray-400 font-mono text-sm">
        <span class="inline-block animate-spin mr-2">⏳</span> Đang tải danh sách bộ đề ôn tập...
      </div>

      <div v-else-if="filteredStudySets.length === 0" class="blueprint-card p-12 text-center">
        <div class="text-4xl mb-3">🎯</div>
        <h3 class="text-base font-bold text-blueprint-dark mb-1">Chưa có bộ đề ôn tập nào</h3>
        <p class="text-xs text-gray-600 mb-5 max-w-sm mx-auto">
          Tải lên ngân hàng đề thi Word/PDF hoặc nhập đoạn mã nguồn để AI tự động đục lỗ [[từ_khóa]] và sinh trắc nghiệm.
        </p>
        <button
          @click="openCreateModal"
          class="px-5 py-2.5 rounded-lg bg-blueprint text-white font-bold text-xs shadow hover:bg-blueprint-light"
        >
          ✨ Tạo Bộ Đề Đầu Tiên
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="set in filteredStudySets"
          :key="set.id"
          class="blueprint-card p-6 flex flex-col justify-between hover:shadow-xl transition-shadow group relative"
        >
          <div>
            <div class="flex items-center justify-between text-xs text-gray-500 font-mono mb-2">
              <span v-if="set.notebook" class="px-2 py-0.5 rounded bg-blue-100 text-blueprint font-bold">
                {{ set.notebook.title }}
              </span>
              <span v-else class="text-gray-400">Độc lập</span>
              <span>{{ new Date(set.created_at).toLocaleDateString('vi-VN') }}</span>
            </div>

            <div class="flex items-start justify-between gap-2 mb-2">
              <h3 class="text-lg font-bold text-blueprint-dark line-clamp-2 group-hover:text-blueprint transition-colors flex-1">
                {{ set.title }}
              </h3>
              <button
                @click="openRenameModal(set)"
                class="p-1 rounded text-gray-400 hover:text-blueprint hover:bg-blueprint/10 transition-colors shrink-0"
                title="Đổi tên bộ đề"
              >
                ✏️
              </button>
            </div>

            <div class="flex flex-wrap gap-1 mb-4">
              <span
                v-for="(cat, cIdx) in (set.categories_json || []).slice(0, 3)"
                :key="cIdx"
                class="px-2 py-0.5 rounded bg-gray-200/80 text-gray-700 font-mono text-[10px]"
              >
                {{ cat }}
              </span>
            </div>
          </div>

          <div class="space-y-3 pt-4 border-t border-blueprint-paperBorder/60">
            <div class="flex items-center justify-between text-xs font-mono">
              <span class="text-gray-600">
                <strong>{{ (set.questions_json || []).length }}</strong> câu &bull; 
                <strong>{{ (set.code_exercises_json || []).length }}</strong> code
                <span v-if="(set.shorts_json || []).length || (set.essays_json || []).length">
                  &bull; <strong>{{ (set.shorts_json || []).length + (set.essays_json || []).length }}</strong> tự luận
                </span>
              </span>

              <div class="flex items-center gap-1.5">
                <!-- Delete Button -->
                <button
                  @click="handleDeleteSet(set)"
                  class="p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                  title="Xóa bộ đề"
                >
                  🗑️
                </button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-2">
              <a
                :href="studyStore.getOfflineHtmlUrl(set.id)"
                target="_blank"
                class="py-2 px-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-[11px] text-center flex items-center justify-center gap-1 transition-colors"
                title="Tải tệp HTML độc lập chạy offline"
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
    </div>

    <!-- AI Generate Modal with Real-time Dynamic Progress & Async Polling -->
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 z-50 bg-black/75 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-lg w-full p-6 sm:p-8 shadow-2xl relative">
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder pb-3">
          <h3 class="text-base font-bold text-blueprint-dark flex items-center gap-2">
            <span v-if="genFinished">🎉</span>
            <span v-else-if="generating" class="animate-spin text-blueprint">⚡</span>
            <span v-else>✨</span>
            <span>
              {{ genFinished ? 'Sinh Bộ Đề Hoàn Tất Thành Công!' : generating ? 'Đang Xử Lý Hàng Đợi AI (Background Queue)' : 'Sinh Bộ Đề AI Tự Động (Gemini 3.8 Flash)' }}
            </span>
          </h3>
          <button
            @click="closeGenerateModal"
            class="text-gray-400 hover:text-gray-700 font-bold text-lg p-1"
            title="Đóng cửa sổ"
          >
            ✕
          </button>
        </div>

        <!-- Error Alert -->
        <div
          v-if="genError"
          class="p-3.5 mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 text-xs font-semibold flex items-center justify-between"
        >
          <div class="flex items-center gap-2">
            <span>❌</span>
            <span>{{ genError }}</span>
          </div>
          <button @click="genError = ''" class="text-red-500 font-bold ml-2">✕</button>
        </div>

        <!-- State 1: Finished & Celebratory Success Screen -->
        <div v-if="genFinished && createdSuccessSet" class="py-4 space-y-5 text-center animate-fade-in">
          <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-3xl mx-auto shadow-inner border-2 border-emerald-400">
            🎯
          </div>

          <div>
            <h4 class="text-lg font-extrabold text-blueprint-dark mb-1">
              {{ createdSuccessSet.title }}
            </h4>
            <p class="text-xs text-gray-600">
              Đã bóc tách thành công <strong class="text-emerald-700 font-bold">{{ createdSuccessSet.questions_count || createdSuccessSet.questions_json?.length || 0 }} câu hỏi trắc nghiệm</strong> và <strong class="text-blueprint font-bold">{{ createdSuccessSet.code_count || createdSuccessSet.code_exercises_json?.length || 0 }} bài tập code</strong>.
            </p>
          </div>

          <!-- Action Buttons -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
            <button
              @click="router.push('/study-player/' + createdSuccessSet.id)"
              class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition-transform hover:scale-105"
            >
              <span>🚀</span> Vào Luyện Ngay
            </button>

            <a
              :href="studyStore.getOfflineHtmlUrl(createdSuccessSet.share_slug || createdSuccessSet.id)"
              target="_blank"
              download
              class="w-full py-2.5 px-4 bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition-transform hover:scale-105"
            >
              <span>📥</span> Tải File HTML Offline
            </a>
          </div>

          <button
            @click="closeGenerateModal"
            class="text-xs font-semibold text-gray-500 hover:text-gray-800 underline mt-2"
          >
            Đóng cửa sổ & xem trong danh sách
          </button>
        </div>

        <!-- State 2: Dynamic Real-time Progress Bar (Generating) -->
        <div v-else-if="generating" class="py-4 space-y-6 animate-fade-in">
          <!-- Percent & Visual Badge -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-blueprint-accent animate-ping"></span>
              <span class="text-xs font-bold text-blueprint-dark">Tiến độ xử lý AI</span>
            </div>
            <div class="text-2xl font-black font-mono text-blueprint">
              {{ genProgress }}%
            </div>
          </div>

          <!-- Smooth CSS Progress Bar -->
          <div class="w-full bg-gray-200 h-3.5 rounded-full overflow-hidden p-0.5 border border-gray-300 shadow-inner">
            <div
              class="bg-gradient-to-r from-blueprint via-indigo-500 to-blueprint-accent h-full rounded-full transition-all duration-500 ease-out shadow-sm"
              :style="{ width: `${Math.max(genProgress, 5)}%` }"
            ></div>
          </div>

          <!-- Dynamic Status Description -->
          <div class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-200/60 text-center space-y-1">
            <p class="text-xs font-bold text-blueprint-dark flex items-center justify-center gap-2">
              <span class="animate-pulse">🔄</span>
              <span>{{ displayStatusMessage }}</span>
            </p>
            <p class="text-[11px] text-gray-500 font-mono">
              Thời gian đã chạy: {{ elapsedSeconds }}s &bull; Đang xử lý qua Laravel Queue Worker (Không bao giờ timeout)
            </p>
          </div>

          <!-- Processing Milestones Indicator -->
          <div class="grid grid-cols-4 gap-1 text-[10px] text-center font-mono">
            <div :class="genProgress >= 20 ? 'text-blueprint font-bold' : 'text-gray-400'">
              <span>1. Đọc tệp</span>
              <div class="h-1 rounded-full mt-1" :class="genProgress >= 20 ? 'bg-blueprint' : 'bg-gray-200'"></div>
            </div>
            <div :class="genProgress >= 50 ? 'text-blueprint font-bold' : 'text-gray-400'">
              <span>2. Bóc tách</span>
              <div class="h-1 rounded-full mt-1" :class="genProgress >= 50 ? 'bg-blueprint' : 'bg-gray-200'"></div>
            </div>
            <div :class="genProgress >= 80 ? 'text-blueprint font-bold' : 'text-gray-400'">
              <span>3. Đục lỗ code</span>
              <div class="h-1 rounded-full mt-1" :class="genProgress >= 80 ? 'bg-blueprint' : 'bg-gray-200'"></div>
            </div>
            <div :class="genProgress >= 100 ? 'text-emerald-600 font-bold' : 'text-gray-400'">
              <span>4. Hoàn tất</span>
              <div class="h-1 rounded-full mt-1" :class="genProgress >= 100 ? 'bg-emerald-600' : 'bg-gray-200'"></div>
            </div>
          </div>

          <!-- Background notice & Hide Button -->
          <div class="flex items-center justify-between pt-2 border-t border-blueprint-paperBorder/60">
            <p class="text-[11px] text-gray-500 italic">
              Bạn có thể ẩn cửa sổ này, tiến trình vẫn tiếp tục xử lý ngầm.
            </p>
            <button
              type="button"
              @click="closeGenerateModal"
              class="px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-semibold text-gray-600 hover:bg-gray-100"
            >
              Ẩn cửa sổ
            </button>
          </div>
        </div>

        <!-- State 3: Input Form (Default) -->
        <form v-else @submit.prevent="handleGenerateSet" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Tiêu đề bộ đề (Tùy chọn)</label>
            <input
              v-model="genTitle"
              type="text"
              placeholder="VD: Đề cương Ôn thi cuối kỳ Lập trình .NET & WPF"
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <!-- File Upload Option -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-xs font-bold text-blueprint-dark">
                Tải tệp đề cương / câu hỏi thô (Word .docx, PDF, TXT)
              </label>
              <span class="text-[10px] text-gray-500 font-mono">Tối đa 10MB</span>
            </div>
            <input
              type="file"
              @change="onGenFileSelected"
              accept=".pdf,.docx,.doc,.txt"
              class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blueprint file:text-white hover:file:bg-blueprint-light cursor-pointer"
            />
            <p v-if="genFile" class="text-[11px] text-emerald-700 font-mono mt-1 font-semibold">
              ✓ Đã chọn: {{ genFile.name }} ({{ (genFile.size / (1024 * 1024)).toFixed(2) }} MB)
            </p>
          </div>

          <div class="text-center text-xs text-gray-400 font-mono">HOẶC DÁN ĐOẠN VĂN / MÃ NGUỒN CẦN ĐỤC LỖ</div>

          <div>
            <textarea
              v-model="genRawText"
              rows="5"
              maxlength="100000"
              placeholder="Dán nội dung lý thuyết, ngân hàng câu hỏi hoặc đoạn code muốn đục lỗ [[từ_khóa]] vào đây (tối đa 100,000 ký tự)..."
              class="w-full p-3 rounded-lg border border-gray-300 text-xs font-mono bg-white outline-none focus:ring-1 focus:ring-blueprint leading-relaxed"
            ></textarea>
            <div class="flex justify-between items-center text-[11px] text-gray-500 font-mono mt-1 px-1">
              <span>Hỗ trợ tối đa 100,000 ký tự</span>
              <span :class="genRawText.length > 90000 ? 'text-amber-600 font-bold' : ''">
                {{ genRawText.length.toLocaleString() }} / 100,000
              </span>
            </div>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="closeGenerateModal"
              class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-100"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="!genFile && !genRawText.trim()"
              class="px-5 py-2.5 rounded-lg bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark text-xs font-bold shadow disabled:opacity-40 transition-all hover:scale-105"
            >
              🚀 Bắt đầu sinh đề AI
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Rename Study Set Modal -->
    <div
      v-if="showRenameModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 animate-fade-in"
    >
      <div class="blueprint-card max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder pb-3">
          <h3 class="text-base font-bold text-blueprint-dark flex items-center gap-1.5">
            <span>✏️</span> Đổi Tên Bộ Đề
          </h3>
          <button @click="showRenameModal = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg">
            ✕
          </button>
        </div>

        <form @submit.prevent="handleSaveRename" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Tên bộ đề mới *</label>
            <input
              v-model="renameTitle"
              type="text"
              required
              class="w-full px-3.5 py-2.5 rounded-lg border border-gray-300 text-xs bg-white focus:ring-2 focus:ring-blueprint outline-none font-semibold text-gray-800"
            />
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showRenameModal = false"
              class="px-4 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-100"
            >
              Hủy
            </button>
            <button
              type="submit"
              :disabled="renaming || !renameTitle.trim()"
              class="px-5 py-2 rounded-lg bg-blueprint hover:bg-blueprint-light text-white text-xs font-bold shadow disabled:opacity-50"
            >
              {{ renaming ? 'Đang lưu...' : 'Lưu tên mới' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Mini Floating Badge (Shown when modal is minimized during generation) -->
    <div
      v-if="generating && !showGenerateModal"
      @click="showGenerateModal = true"
      class="fixed bottom-6 right-6 z-50 cursor-pointer bg-blueprint-dark border-2 border-blueprint-accent text-white px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-3 transition-transform hover:scale-105 active:scale-95 animate-fade-in"
      title="Nhấp để mở lại tiến độ sinh đề AI"
    >
      <div class="relative flex items-center justify-center">
        <span class="w-3.5 h-3.5 rounded-full bg-blueprint-accent animate-ping absolute"></span>
        <span class="w-3 h-3 rounded-full bg-blueprint-accent relative"></span>
      </div>
      <div>
        <div class="text-xs font-extrabold text-blueprint-accent flex items-center gap-1.5 font-mono">
          <span>⚡ AI ĐANG CHẠY</span>
          <span class="bg-blueprint px-1.5 py-0.2 rounded text-[11px] text-white">{{ genProgress }}%</span>
        </div>
        <div class="text-[11px] text-gray-300 max-w-[200px] truncate">
          {{ displayStatusMessage }}
        </div>
      </div>
      <span class="text-xs text-blueprint-accent font-bold ml-1">Mở ↗</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStudyStore } from '@/stores/study'

const studyStore = useStudyStore()
const router = useRouter()

const showGenerateModal = ref(false)
const genTitle = ref('')
const genFile = ref(null)
const genRawText = ref('')
const generating = ref(false)
const genFinished = ref(false)
const genProgress = ref(0)
const genStatusMessage = ref('')
const genError = ref('')
const createdSuccessSet = ref(null)

// Rename Study Set State
const showRenameModal = ref(false)
const editingSetId = ref(null)
const renameTitle = ref('')
const renaming = ref(false)

const openRenameModal = (set) => {
  editingSetId.value = set.id
  renameTitle.value = set.title || ''
  showRenameModal.value = true
}

const handleSaveRename = async () => {
  if (!renameTitle.value.trim() || !editingSetId.value) return
  renaming.value = true
  try {
    await studyStore.updateStudySet(editingSetId.value, {
      title: renameTitle.value.trim(),
    })
    showRenameModal.value = false
  } catch (err) {
    alert(err.response?.data?.message || 'Đổi tên bộ đề thất bại.')
  } finally {
    renaming.value = false
  }
}

let pollTimer = null
let elapsedTimer = null
const elapsedSeconds = ref(0)

const filteredStudySets = computed(() => {
  return studyStore.studySets.filter(set => {
    const qCount = set.questions_json?.length || 0
    const cCount = set.code_exercises_json?.length || 0
    const sCount = set.shorts_json?.length || 0
    const eCount = set.essays_json?.length || 0
    const total = qCount + cCount + sCount + eCount
    if (set.status === 'processing') return true
    return total > 0 && set.status === 'completed'
  })
})

const displayStatusMessage = computed(() => {
  if (genStatusMessage.value) return genStatusMessage.value
  const p = genProgress.value
  if (p <= 20) return 'Đang bóc tách và phân tích cấu trúc tài liệu...'
  if (p <= 80) return 'AI đang trích xuất câu hỏi và biên soạn bài tập điền khuyết...'
  if (p <= 99) return 'Đang tổng hợp kết quả và kiểm tra tính hợp lệ...'
  return 'Hoàn tất! Đang đồng bộ vào hệ thống.'
})

onMounted(() => {
  studyStore.fetchStudySets()
})

onUnmounted(() => {
  clearAllTimers()
})

const clearAllTimers = () => {
  if (pollTimer) {
    clearInterval(pollTimer)
    pollTimer = null
  }
  if (elapsedTimer) {
    clearInterval(elapsedTimer)
    elapsedTimer = null
  }
}

const openCreateModal = () => {
  genError.value = ''
  genFinished.value = false
  generating.value = false
  genProgress.value = 0
  genStatusMessage.value = ''
  showGenerateModal.value = true
}

const closeGenerateModal = () => {
  if (generating.value) {
    showGenerateModal.value = false
    return
  }
  clearAllTimers()
  showGenerateModal.value = false
  genFinished.value = false
  genError.value = ''
}

const onGenFileSelected = (e) => {
  const file = e.target.files?.[0]
  if (!file) {
    genFile.value = null
    return
  }
  const maxSize = 10 * 1024 * 1024 // 10MB
  if (file.size > maxSize) {
    alert(`Tệp "${file.name}" quá lớn (${(file.size / (1024 * 1024)).toFixed(1)} MB). Vui lòng chọn tệp dưới 10MB.`)
    e.target.value = ''
    genFile.value = null
    return
  }
  genFile.value = file
}

const handleGenerateSet = async () => {
  generating.value = true
  genFinished.value = false
  genProgress.value = 5
  genStatusMessage.value = 'Đang tiếp nhận và đưa vào hàng đợi...'
  genError.value = ''
  elapsedSeconds.value = 0

  clearAllTimers()

  elapsedTimer = setInterval(() => {
    elapsedSeconds.value += 1
  }, 1000)

  try {
    const initResponse = await studyStore.generateStudySet({
      title: genTitle.value,
      file: genFile.value,
      raw_text: genRawText.value,
    })

    const studySetId = initResponse.study_set_id || initResponse.id
    if (!studySetId) {
      throw new Error('Không nhận được mã tiến trình sinh đề từ máy chủ.')
    }

    startPolling(studySetId)

  } catch (err) {
    clearAllTimers()
    generating.value = false
    genError.value = err.response?.data?.message || err.message || 'Khởi tạo tác vụ sinh đề thất bại.'
  }
}

const startPolling = (studySetId) => {
  const pollStart = Date.now()

  pollTimer = setInterval(async () => {
    try {
      // 10-minute timeout protection (600,000ms)
      if (Date.now() - pollStart > 600000) {
        clearAllTimers()
        generating.value = false
        genError.value = 'Tài liệu quá lớn hoặc AI đang quá tải sau 10 phút. Vui lòng thử lại.'
        return
      }

      const statusData = await studyStore.pollStudySetStatus(studySetId)
      const currentProgress = Number(statusData.progress || 0)

      if (currentProgress > genProgress.value) {
        genProgress.value = currentProgress
      }

      if (statusData.status_message) {
        genStatusMessage.value = statusData.status_message
      }

      if (statusData.status === 'completed') {
        clearAllTimers()
        genProgress.value = 100
        genStatusMessage.value = 'Hoàn tất! Đang đồng bộ vào hệ thống.'
        generating.value = false
        genFinished.value = true
        showGenerateModal.value = true
        createdSuccessSet.value = statusData

        genTitle.value = ''
        genFile.value = null
        genRawText.value = ''

        await studyStore.fetchStudySets()

      } else if (statusData.status === 'failed') {
        clearAllTimers()
        generating.value = false
        showGenerateModal.value = true
        genError.value = statusData.error_message || 'Quá trình sinh bộ đề AI thất bại.'
      }

    } catch (pollErr) {
      console.warn('Polling error, will retry in 2s...', pollErr)
    }
  }, 2000)
}

const handleDeleteSet = async (set) => {
  if (!confirm(`Bạn có chắc muốn xóa bộ đề "${set.title}" không? Hành động này không thể hoàn tác.`)) {
    return
  }

  try {
    await studyStore.deleteStudySet(set.id)
    if (createdSuccessSet.value?.id === set.id) {
      createdSuccessSet.value = null
    }
  } catch (err) {
    alert('Không thể xóa bộ đề: ' + (err.response?.data?.message || err.message))
  }
}
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
  animation: fadeIn 0.4s ease-out forwards;
}
</style>
