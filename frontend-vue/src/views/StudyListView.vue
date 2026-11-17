<template>
  <div class="min-h-screen pb-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header Banner -->
      <div class="blueprint-card p-6 md:p-8 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
              EXAM & CLOZE GENERATOR
            </span>
            <span class="text-xs text-gray-500 font-mono">Gemini 2.5 Flash Structured JSON Output</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark">
            Kho Bộ Đề Ôn Tập Tương Tác
          </h1>
          <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed max-w-2xl">
            Tất cả bộ đề trắc nghiệm và bài tập gõ code [[...]] được AI bóc tách từ ngân hàng câu hỏi hoặc giáo trình lý thuyết.
          </p>
        </div>

        <button
          @click="showGenerateModal = true"
          class="px-5 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs sm:text-sm shadow-md flex items-center gap-2 self-start md:self-center transition-all hover:scale-105"
        >
          <span>✨</span> Tạo Bộ Đề Mới Bằng AI
        </button>
      </div>

      <!-- Study Sets Grid -->
      <div v-if="studyStore.loading" class="text-center py-16 text-gray-400">
        Đang tải danh sách bộ đề...
      </div>

      <div v-else-if="studyStore.studySets.length === 0" class="blueprint-card p-12 text-center">
        <div class="text-4xl mb-3">🎯</div>
        <h3 class="text-base font-bold text-blueprint-dark mb-1">Chưa có bộ đề ôn tập nào</h3>
        <p class="text-xs text-gray-600 mb-5 max-w-sm mx-auto">
          Tải lên ngân hàng đề thi Word/PDF hoặc nhập đoạn mã nguồn để AI tự động đục lỗ [[từ_khóa]] và sinh trắc nghiệm.
        </p>
        <button
          @click="showGenerateModal = true"
          class="px-5 py-2.5 rounded-lg bg-blueprint text-white font-bold text-xs shadow hover:bg-blueprint-light"
        >
          ✨ Tạo Bộ Đề Đầu Tiên
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div
          v-for="set in studyStore.studySets"
          :key="set.id"
          class="blueprint-card p-6 flex flex-col justify-between hover:shadow-xl transition-shadow group"
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

          <div class="pt-4 border-t border-blueprint-paperBorder/60 flex items-center justify-between text-xs font-mono">
            <span class="text-gray-600">
              <strong>{{ (set.questions_json || []).length }}</strong> câu &bull; 
              <strong>{{ (set.code_exercises_json || []).length }}</strong> code
            </span>

            <router-link
              :to="`/study-player/${set.id}`"
              class="px-4 py-2 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-sm transition-colors"
            >
              Luyện ngay <span>→</span>
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- AI Generate Modal -->
    <div
      v-if="showGenerateModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-lg w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder pb-3">
          <h3 class="text-base font-bold text-blueprint-dark flex items-center gap-2">
            <span>✨</span> Sinh Bộ Đề AI Tự Động (Gemini 2.5 Flash)
          </h3>
          <button @click="showGenerateModal = false" class="text-gray-400 hover:text-gray-700 font-bold text-lg">
            ✕
          </button>
        </div>

        <form @submit.prevent="handleGenerateSet" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Tiêu đề bộ đề</label>
            <input
              v-model="genTitle"
              type="text"
              placeholder="VD: Đề cương Ôn thi cuối kỳ Lập trình Web"
              class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
            />
          </div>

          <!-- File Upload Option -->
          <div>
            <label class="block text-xs font-bold text-blueprint-dark mb-1">Tải tệp đề cương / câu hỏi thô (Word / PDF / TXT)</label>
            <input
              type="file"
              @change="onGenFileSelected"
              accept=".pdf,.docx,.doc,.txt"
              class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blueprint file:text-white hover:file:bg-blueprint-light cursor-pointer"
            />
          </div>

          <div class="text-center text-xs text-gray-400 font-mono">HOẶC DÁN ĐOẠN VĂN / MÃ NGUỒN CẦN ĐỤC LỖ</div>

          <div>
            <textarea
              v-model="genRawText"
              rows="5"
              placeholder="Dán nội dung lý thuyết hoặc đoạn code muốn đục lỗ [[từ_khóa]] vào đây..."
              class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs font-mono bg-white outline-none focus:ring-1 focus:ring-blueprint"
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
              :disabled="studyStore.generating || (!genFile && !genRawText.trim())"
              class="px-5 py-2.5 rounded-lg bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark text-xs font-bold shadow disabled:opacity-40"
            >
              {{ studyStore.generating ? 'Gemini đang phân tích và đục lỗ code...' : '🚀 Bắt đầu sinh đề AI' }}
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

onMounted(() => {
  studyStore.fetchStudySets()
})

const onGenFileSelected = (e) => {
  genFile.value = e.target.files?.[0]
}

const handleGenerateSet = async () => {
  try {
    const newSet = await studyStore.generateStudySet({
      title: genTitle.value,
      file: genFile.value,
      raw_text: genRawText.value,
    })
    showGenerateModal.value = false
    router.push(`/study-player/${newSet.id}`)
  } catch (err) {
    alert(err.response?.data?.message || 'Sinh bộ đề thất bại.')
  }
}
</script>
