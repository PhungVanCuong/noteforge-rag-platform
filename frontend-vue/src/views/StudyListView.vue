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

      <div v-else-if="studyStore.studySets.length === 0" class="blueprint-card p-12 text-center">
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
          v-for="set in studyStore.studySets"
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

            <h3 class="text-lg font-bold text-blueprint-dark line-clamp-2 group-hover:text-blueprint transition-colors mb-2">
              {{ set.title }}
            </h3>

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

    <!-- AI Generate Modal with Multi-step Visual Progress -->
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-lg w-full p-6 sm:p-8 shadow-2xl">
        <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder pb-3">
          <h3 class="text-base font-bold text-blueprint-dark flex items-center gap-2">
            <span>✨</span> Sinh Bộ Đề AI Tự Động (Gemini 3.8 Flash)
          </h3>
          <button
            v-if="!generating"
            @click="showGenerateModal = false"
            class="text-gray-400 hover:text-gray-700 font-bold text-lg"
          >
            ✕
          </button>
        </div>

        <!-- Error Alert -->
        <div
          v-if="genError"
          class="p-3.5 mb-4 rounded-xl bg-red-100 border border-red-300 text-red-800 text-xs font-semibold flex items-center justify-between"
        >
          <span>❌ {{ genError }}</span>
          <button @click="genError = ''" class="text-red-500 font-bold ml-2">✕</button>
        </div>

        <!-- Progress Steps (Visible while generating) -->
        <div v-if="generating" class="py-6 space-y-6">
          <div class="space-y-4">
            <!-- Step 1 -->
            <div class="flex items-center gap-3">
              <div
                :class="genStep >= 1 ? 'bg-blueprint text-white' : 'bg-gray-200 text-gray-500'"
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
              >
                <span v-if="genStep > 1">✓</span>
                <span v-else class="animate-spin">⏳</span>
              </div>
              <div class="text-xs">
                <p class="font-bold text-blueprint-dark">Bước 1: Trích xuất nội dung tài liệu</p>
                <p class="text-[11px] text-gray-500">Đang đọc toàn bộ đoạn văn, bảng biểu (Tables) và mã nguồn...</p>
              </div>
            </div>

            <!-- Step 2 -->
            <div class="flex items-center gap-3">
              <div
                :class="genStep >= 2 ? 'bg-blueprint-accent text-blueprint-dark font-extrabold' : 'bg-gray-200 text-gray-400'"
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs shrink-0 transition-colors"
              >
                <span v-if="genStep > 2">✓</span>
                <span v-else-if="genStep === 2" class="animate-pulse">⚡</span>
                <span v-else>2</span>
              </div>
              <div class="text-xs">
                <p class="font-bold" :class="genStep >= 2 ? 'text-blueprint-dark' : 'text-gray-400'">
                  Bước 2: Gemini AI phân tích & đục lỗ code
                </p>
                <p class="text-[11px] text-gray-500">Sinh trắc nghiệm bao phủ kiến thức và tạo bài tập [[từ_khóa]]...</p>
              </div>
            </div>

            <!-- Step 3 -->
            <div class="flex items-center gap-3">
              <div
                :class="genStep >= 3 ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-400'"
                class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold shrink-0 transition-colors"
              >
                <span v-if="genStep >= 3">✓</span>
                <span v-else>3</span>
              </div>
              <div class="text-xs">
                <p class="font-bold" :class="genStep >= 3 ? 'text-blueprint-dark' : 'text-gray-400'">
                  Bước 3: Lưu trữ & Đồng bộ Kho Bộ Đề
                </p>
                <p class="text-[11px] text-gray-500">Kiểm tra tính hợp lệ dữ liệu và lưu vào hệ thống...</p>
              </div>
            </div>
          </div>

          <div class="w-full bg-gray-200 h-2 rounded-full overflow-hidden">
            <div
              class="bg-gradient-to-r from-blueprint to-blueprint-accent h-full transition-all duration-700 rounded-full"
              :style="{ width: genStep === 1 ? '35%' : genStep === 2 ? '75%' : '100%' }"
            ></div>
          </div>

          <p class="text-center text-[11px] text-gray-500 italic">
            Quá trình phân tích chuyên sâu có thể mất từ 10 - 25 giây tùy theo độ dài của tài liệu.
          </p>
        </div>

        <!-- Form (Visible when not generating) -->
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
            <label class="block text-xs font-bold text-blueprint-dark mb-1">
              Tải tệp đề cương / câu hỏi thô (Word .docx, PDF, TXT)
            </label>
            <input
              type="file"
              @change="onGenFileSelected"
              accept=".pdf,.docx,.doc,.txt"
              class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blueprint file:text-white hover:file:bg-blueprint-light cursor-pointer"
            />
            <p v-if="genFile" class="text-[11px] text-emerald-700 font-mono mt-1 font-semibold">
              ✓ Đã chọn: {{ genFile.name }}
            </p>
          </div>

          <div class="text-center text-xs text-gray-400 font-mono">HOẶC DÁN ĐOẠN VĂN / MÃ NGUỒN CẦN ĐỤC LỖ</div>

          <div>
            <textarea
              v-model="genRawText"
              rows="5"
              placeholder="Dán nội dung lý thuyết, ngân hàng câu hỏi hoặc đoạn code muốn đục lỗ [[từ_khóa]] vào đây..."
              class="w-full p-3 rounded-lg border border-gray-300 text-xs font-mono bg-white outline-none focus:ring-1 focus:ring-blueprint leading-relaxed"
            ></textarea>
          </div>

          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              @click="showGenerateModal = false"
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
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStudyStore } from '@/stores/study'

const studyStore = useStudyStore()
const router = useRouter()

const showGenerateModal = ref(false)
const genTitle = ref('')
const genFile = ref(null)
const genRawText = ref('')
const generating = ref(false)
const genStep = ref(1)
const genError = ref('')
const createdSuccessSet = ref(null)

onMounted(() => {
  studyStore.fetchStudySets()
})

const openCreateModal = () => {
  genError.value = ''
  showGenerateModal.value = true
}

const onGenFileSelected = (e) => {
  genFile.value = e.target.files?.[0]
}

const handleGenerateSet = async () => {
  generating.value = true
  genStep.value = 1
  genError.value = ''

  const stepTimer = setTimeout(() => {
    genStep.value = 2
  }, 2500)

  try {
    const newSet = await studyStore.generateStudySet({
      title: genTitle.value,
      file: genFile.value,
      raw_text: genRawText.value,
    })

    clearTimeout(stepTimer)
    genStep.value = 3

    // Success
    showGenerateModal.value = false
    createdSuccessSet.value = newSet
    genTitle.value = ''
    genFile.value = null
    genRawText.value = ''

    // Scroll to top to see success banner
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (err) {
    clearTimeout(stepTimer)
    genError.value = err.response?.data?.message || err.message || 'Sinh bộ đề bằng AI thất bại.'
  } finally {
    generating.value = false
  }
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
