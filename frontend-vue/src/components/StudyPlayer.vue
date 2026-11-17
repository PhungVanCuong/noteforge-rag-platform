<template>
  <div class="max-w-5xl mx-auto py-6 px-4">
    <!-- Top Action Card -->
    <div class="blueprint-card p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-blueprint-paperBorder pb-5">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs font-mono font-bold bg-blueprint-dark text-blueprint-accent px-2.5 py-0.5 rounded">
              STUDY ENGINE 3.0
            </span>
            <span v-if="studySet.notebook" class="text-xs text-gray-500 font-medium">
              Môn: {{ studySet.notebook.title }}
            </span>
          </div>
          <h1 class="text-2xl font-bold text-blueprint-dark">{{ studySet.title }}</h1>
          <p class="text-xs text-gray-600 mt-1">
            Tổng: <strong>{{ allQuestions.length }}</strong> câu trắc nghiệm &bull; 
            <strong>{{ codeExercises.length }}</strong> bài tập điền khuyết code
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Timer for Exam Mode -->
          <div v-if="activeMode === 'exam' && examStarted && !examFinished" class="flex items-center gap-2">
            <div
              :class="[
                'font-mono text-xl font-bold px-4 py-2 rounded-lg border shadow-inner',
                timeLeft <= 60 
                  ? 'bg-red-950 text-red-400 border-red-700 animate-pulse' 
                  : 'bg-slate-900 text-emerald-400 border-slate-700'
              ]"
            >
              ⏱️ {{ formattedTime }}
            </div>
          </div>

          <!-- Download Offline HTML Button -->
          <a
            :href="offlineDownloadUrl"
            target="_blank"
            class="px-4 py-2 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-semibold text-xs flex items-center gap-2 shadow transition-colors"
            title="Tải về file .html duy nhất để học khi không có Internet"
          >
            <span>📥</span> Tải bản HTML Offline
          </a>

          <!-- Share Button -->
          <button
            @click="copyShareLink"
            class="px-3.5 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium text-xs flex items-center gap-1.5 shadow-sm transition-colors"
          >
            <span>🔗</span> {{ shareCopied ? 'Đã sao chép!' : 'Chia sẻ' }}
          </button>
        </div>
      </div>

      <!-- Mode Switcher Tabs -->
      <div class="flex flex-wrap gap-2 mt-5">
        <button
          @click="switchMode('practice')"
          :class="[
            'px-5 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2',
            activeMode === 'practice'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>📝</span> 1. Luyện trắc nghiệm
        </button>

        <button
          @click="switchMode('exam')"
          :class="[
            'px-5 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2',
            activeMode === 'exam'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>⏱️</span> 2. Thi thử tính giờ (20 câu / 15p)
        </button>

        <button
          @click="switchMode('cloze')"
          :class="[
            'px-5 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2',
            activeMode === 'cloze'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>💻</span> 3. Luyện gõ code / Điền khuyết [[...]]
        </button>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODE 1: LUYỆN TRẮC NGHIỆM -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'practice'" class="blueprint-card p-6">
      <!-- Category & Status Filters -->
      <div class="flex flex-wrap items-center gap-2 mb-6 pb-4 border-b border-blueprint-paperBorder/60">
        <button
          @click="setCategory('ALL')"
          :class="[
            'px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors',
            selectedCategory === 'ALL' && !filterWrongOnly
              ? 'bg-blueprint text-white shadow-sm'
              : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-100'
          ]"
        >
          Tất cả ({{ allQuestions.length }})
        </button>

        <button
          v-for="(cat, idx) in categories"
          :key="idx"
          @click="setCategory(idx)"
          :class="[
            'px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors',
            selectedCategory === idx && !filterWrongOnly
              ? 'bg-blueprint text-white shadow-sm'
              : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-100'
          ]"
        >
          {{ cat }} ({{ getQuestionCountByCategory(idx) }})
        </button>

        <!-- Wrong-Only Filter -->
        <button
          @click="toggleWrongOnly"
          :class="[
            'ml-auto px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors flex items-center gap-1.5',
            filterWrongOnly
              ? 'bg-red-600 text-white shadow'
              : 'bg-red-50 text-red-700 border border-red-300 hover:bg-red-100'
          ]"
        >
          <span>❗</span> Đã làm sai ({{ wrongCount }})
        </button>
      </div>

      <!-- Question List -->
      <div v-if="filteredQuestions.length === 0" class="text-center py-12 text-gray-500">
        <div class="text-3xl mb-2">🎉</div>
        <p class="font-medium">Tuyệt vời! Không có câu hỏi nào thuộc bộ lọc này hoặc chưa có câu làm sai.</p>
      </div>

      <div v-else class="space-y-8">
        <div
          v-for="(q, idx) in filteredQuestions"
          :key="q.id"
          class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm"
        >
          <div class="flex items-start justify-between gap-3 mb-4">
            <h3 class="text-base font-bold text-blueprint-dark leading-relaxed">
              <span class="text-blueprint font-mono mr-1">#{{ idx + 1 }}.</span> {{ q.en }}
            </h3>
            <span v-if="isWrong(q.id)" class="text-[11px] font-mono font-bold px-2 py-0.5 bg-red-100 text-red-700 rounded-full shrink-0">
              ❗ Làm sai
            </span>
          </div>

          <!-- Options Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button
              v-for="(opt, optIdx) in q.opts"
              :key="optIdx"
              @click="handlePracticeSelect(q, optIdx)"
              :disabled="practiceSelected[q.id] !== undefined"
              :class="[
                'p-3.5 rounded-lg text-left text-sm transition-all border flex items-start gap-3',
                getOptionStyle(q, optIdx)
              ]"
            >
              <span class="font-mono font-bold shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs border">
                {{ ['A', 'B', 'C', 'D'][optIdx] || optIdx + 1 }}
              </span>
              <span class="leading-snug">{{ opt }}</span>
            </button>
          </div>

          <!-- Explanation Box -->
          <div
            v-if="practiceSelected[q.id] !== undefined"
            class="mt-4 p-4 rounded-lg bg-blue-50/80 border-l-4 border-blue-600 text-xs text-blue-900 leading-relaxed animate-fadeIn"
          >
            <div class="font-bold flex items-center gap-1 mb-1 text-blue-800">
              <span>💡</span> Lời giải chi tiết:
            </div>
            <div>{{ q.ex || 'Đáp án theo giáo trình nội bộ.' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODE 2: THI THỬ TÍNH GIỜ -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'exam'" class="blueprint-card p-6">
      <!-- Pre-start Screen -->
      <div v-if="!examStarted" class="text-center py-12 max-w-lg mx-auto">
        <div class="w-16 h-16 bg-blueprint text-blueprint-accent text-3xl flex items-center justify-center rounded-2xl mx-auto mb-4 shadow-lg">
          ⏱️
        </div>
        <h2 class="text-xl font-bold text-blueprint-dark mb-2">Chế độ Thi Thử Tính Giờ</h2>
        <p class="text-xs text-gray-600 leading-relaxed mb-6">
          Hệ thống sẽ bốc ngẫu nhiên <strong>20 câu hỏi</strong> từ bộ đề. Bạn có <strong>15 phút</strong> để hoàn thành bài thi.
          Điểm thi được chấm trên thang điểm 10 và lưu vào lịch sử học tập.
        </p>
        <button
          @click="startExam"
          class="px-8 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-sm shadow-md transition-all hover:scale-105"
        >
          🚀 Bắt đầu làm bài thi
        </button>
      </div>

      <!-- Active Exam Screen -->
      <div v-else-if="!examFinished" class="space-y-8">
        <div class="flex items-center justify-between bg-slate-900 text-white p-4 rounded-xl font-mono text-xs">
          <span>Tiến độ: {{ Object.keys(examAnswers).length }} / {{ examQuestions.length }} câu đã chọn</span>
          <span class="font-bold text-blueprint-accent">Thời gian còn lại: {{ formattedTime }}</span>
        </div>

        <div
          v-for="(q, idx) in examQuestions"
          :key="q.id"
          class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm"
        >
          <h3 class="text-base font-bold text-blueprint-dark mb-4 leading-relaxed">
            <span class="text-blueprint font-mono mr-1">Câu {{ idx + 1 }}:</span> {{ q.en }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <button
              v-for="(opt, optIdx) in q.opts"
              :key="optIdx"
              @click="examAnswers[q.id] = optIdx"
              :class="[
                'p-3.5 rounded-lg text-left text-sm transition-all border flex items-start gap-3',
                examAnswers[q.id] === optIdx
                  ? 'bg-blue-50 border-blue-600 font-semibold text-blue-900'
                  : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-800'
              ]"
            >
              <span class="font-mono font-bold shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs border">
                {{ ['A', 'B', 'C', 'D'][optIdx] || optIdx + 1 }}
              </span>
              <span class="leading-snug">{{ opt }}</span>
            </button>
          </div>
        </div>

        <div class="text-center pt-6">
          <button
            @click="submitExam"
            class="px-10 py-3.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-base shadow-lg transition-transform hover:scale-105"
          >
            ✅ Nộp bài thi
          </button>
        </div>
      </div>

      <!-- Exam Result Screen -->
      <div v-else class="text-center py-10 max-w-lg mx-auto">
        <div
          :class="[
            'text-5xl font-extrabold mb-3 font-mono',
            examScore10 >= 5 ? 'text-emerald-600' : 'text-red-600'
          ]"
        >
          {{ examScore10 }} / 10
        </div>
        <h2 class="text-xl font-bold text-blueprint-dark mb-2">
          {{ examScore10 >= 8 ? 'Xuất sắc! 🎉' : examScore10 >= 5 ? 'Đạt yêu cầu! 👍' : 'Cần cố gắng ôn thêm! 📚' }}
        </h2>
        <p class="text-xs text-gray-600 mb-6">
          Bạn đã trả lời đúng <strong>{{ examCorrectCount }} / {{ examQuestions.length }}</strong> câu.
          Thời gian làm bài: <strong>{{ Math.floor(examDuration / 60) }} phút {{ examDuration % 60 }} giây</strong>.
        </p>

        <div class="flex items-center justify-center gap-3">
          <button
            @click="startExam"
            class="px-5 py-2.5 rounded-lg bg-blueprint text-white font-semibold text-xs shadow hover:bg-blueprint-light"
          >
            🔄 Thi lại đề khác
          </button>
          <button
            @click="switchMode('practice'); filterWrongOnly = true;"
            class="px-5 py-2.5 rounded-lg bg-red-600 text-white font-semibold text-xs shadow hover:bg-red-700"
          >
            ❗ Xem câu làm sai
          </button>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODE 3: LUYỆN GÕ CODE / ĐIỀN KHUYẾT [[...]] -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'cloze'" class="blueprint-card p-6">
      <div v-if="codeExercises.length === 0" class="text-center py-12 text-gray-500">
        <div class="text-3xl mb-2">💻</div>
        <p class="font-medium">Không có bài tập code điền khuyết nào trong học liệu này.</p>
      </div>

      <div v-else class="space-y-8">
        <div
          v-for="(exercise, exIdx) in codeExercises"
          :key="exIdx"
          class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm"
        >
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-bold text-blueprint-dark">
              Bài {{ exIdx + 1 }}: {{ exercise.title }}
            </h3>
            <span class="text-xs font-mono text-gray-500">Cú pháp [[...]]</span>
          </div>

          <!-- Code Box with Dynamic Inputs -->
          <div class="bg-slate-900 text-slate-100 p-5 rounded-lg font-mono text-xs sm:text-sm leading-loose overflow-x-auto whitespace-pre-wrap">
            <template v-for="(token, tIdx) in parseExerciseTokens(exercise.code, exIdx)" :key="tIdx">
              <span v-if="token.type === 'text'">{{ token.value }}</span>
              <input
                v-else
                type="text"
                v-model="clozeInputs[`${exIdx}_${token.index}`]"
                :style="{ width: `${Math.max(token.answer.length + 2, 7)}ch` }"
                :class="[
                  'px-2 py-0.5 mx-1 rounded border-2 font-bold font-mono outline-none text-center transition-colors',
                  clozeStatus[`${exIdx}_${token.index}`] === 'correct'
                    ? 'bg-emerald-950 border-emerald-500 text-emerald-300'
                    : clozeStatus[`${exIdx}_${token.index}`] === 'wrong'
                    ? 'bg-red-950 border-red-500 text-red-300'
                    : 'bg-slate-950 border-slate-600 text-sky-300 focus:border-blueprint-accent'
                ]"
                placeholder="..."
                autocomplete="off"
              />
            </template>
          </div>

          <!-- Cloze Actions -->
          <div class="flex items-center gap-3 mt-4">
            <button
              @click="checkCloze(exIdx, exercise.code)"
              class="px-4 py-2 rounded-lg bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs shadow"
            >
              Kiểm tra kết quả
            </button>
            <button
              @click="revealCloze(exIdx, exercise.code)"
              class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold text-xs"
            >
              💡 Xem đáp án
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useStudyStore } from '@/stores/study'

const props = defineProps({
  studySet: {
    type: Object,
    required: true,
  },
})

const studyStore = useStudyStore()

const activeMode = ref('practice')
const selectedCategory = ref('ALL')
const filterWrongOnly = ref(false)
const shareCopied = ref(false)

// Data shortcuts
const categories = computed(() => props.studySet.categories_json || [])
const allQuestions = computed(() => props.studySet.questions_json || [])
const codeExercises = computed(() => props.studySet.code_exercises_json || [])

// Practice state
const practiceSelected = ref({})

// Exam state
const examStarted = ref(false)
const examFinished = ref(false)
const examQuestions = ref([])
const examAnswers = ref({})
const timeLeft = ref(15 * 60)
const timerInterval = ref(null)
const examScore10 = ref(0)
const examCorrectCount = ref(0)
const examDuration = ref(0)

// Cloze state
const clozeInputs = ref({})
const clozeStatus = ref({})

const offlineDownloadUrl = computed(() => {
  return studyStore.getOfflineHtmlUrl(props.studySet.share_slug || props.studySet.id)
})

const wrongCount = computed(() => {
  return Object.values(studyStore.progressMap).filter((p) => p.status === 'wrong').length
})

const isWrong = (qId) => {
  return studyStore.progressMap[qId]?.status === 'wrong'
}

const getQuestionCountByCategory = (catIdx) => {
  return allQuestions.value.filter((q) => q.cat === catIdx).length
}

const filteredQuestions = computed(() => {
  return allQuestions.value.filter((q) => {
    if (filterWrongOnly.value) {
      return isWrong(q.id)
    }
    if (selectedCategory.value !== 'ALL') {
      return q.cat === selectedCategory.value
    }
    return true
  })
})

const switchMode = (mode) => {
  activeMode.value = mode
  if (mode !== 'exam' && timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
}

const setCategory = (cat) => {
  selectedCategory.value = cat
  filterWrongOnly.value = false
}

const toggleWrongOnly = () => {
  filterWrongOnly.value = !filterWrongOnly.value
}

const handlePracticeSelect = async (question, optIdx) => {
  practiceSelected.value[question.id] = optIdx
  const isCorrect = optIdx === question.ans

  await studyStore.updateQuestionProgress(
    props.studySet.id,
    question.id,
    isCorrect ? 'right' : 'wrong'
  )
}

const getOptionStyle = (q, optIdx) => {
  const chosen = practiceSelected.value[q.id]
  if (chosen === undefined) {
    return 'bg-white border-gray-200 hover:border-blueprint-accent text-gray-800'
  }
  if (optIdx === q.ans) {
    return 'bg-emerald-50 border-emerald-500 text-emerald-900 font-semibold'
  }
  if (optIdx === chosen) {
    return 'bg-red-50 border-red-500 text-red-900 font-semibold'
  }
  return 'bg-gray-50 border-gray-200 text-gray-400 opacity-60'
}

// -------------------------------------------------------------
// Exam Mode Logic
// -------------------------------------------------------------
const formattedTime = computed(() => {
  const mins = Math.floor(timeLeft.value / 60).toString().padStart(2, '0')
  const secs = (timeLeft.value % 60).toString().padStart(2, '0')
  return `${mins}:${secs}`
})

const startExam = () => {
  const shuffled = [...allQuestions.value].sort(() => 0.5 - Math.random())
  examQuestions.value = shuffled.slice(0, Math.min(20, shuffled.length))
  examAnswers.value = {}
  timeLeft.value = 15 * 60
  examStarted.value = true
  examFinished.value = false

  if (timerInterval.value) clearInterval(timerInterval.value)
  timerInterval.value = setInterval(() => {
    timeLeft.value--
    if (timeLeft.value <= 0) {
      clearInterval(timerInterval.value)
      submitExam()
    }
  }, 1000)
}

const submitExam = async () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  examDuration.value = 15 * 60 - timeLeft.value

  let correct = 0
  const detail = {}

  for (const q of examQuestions.value) {
    const userPick = examAnswers.value[q.id]
    const isRight = userPick === q.ans
    if (isRight) correct++
    detail[q.id] = { user_pick: userPick, correct_ans: q.ans, is_right: isRight }

    // Sync to progress table in DB
    await studyStore.updateQuestionProgress(
      props.studySet.id,
      q.id,
      isRight ? 'right' : 'wrong'
    )
  }

  examCorrectCount.value = correct
  examScore10.value = Number(((correct / examQuestions.value.length) * 10).toFixed(2))
  examFinished.value = true

  // Record exam attempt in backend
  await studyStore.submitExamAttempt(props.studySet.id, {
    score_10: examScore10.value,
    correct_count: correct,
    total_questions: examQuestions.value.length,
    duration_seconds: examDuration.value,
    answers_detail: detail,
  })
}

// -------------------------------------------------------------
// Cloze Code Logic
// -------------------------------------------------------------
const parseExerciseTokens = (code, exIdx) => {
  const regex = /\[\[(.*?)\]\]/g
  const tokens = []
  let lastIndex = 0
  let match
  let holeIndex = 0

  while ((match = regex.exec(code)) !== null) {
    if (match.index > lastIndex) {
      tokens.push({ type: 'text', value: code.substring(lastIndex, match.index) })
    }
    const answer = match[1].trim()
    tokens.push({
      type: 'hole',
      answer: answer,
      index: holeIndex,
    })
    holeIndex++
    lastIndex = regex.lastIndex
  }

  if (lastIndex < code.length) {
    tokens.push({ type: 'text', value: code.substring(lastIndex) })
  }

  return tokens
}

const checkCloze = (exIdx, code) => {
  const tokens = parseExerciseTokens(code, exIdx).filter((t) => t.type === 'hole')
  tokens.forEach((t) => {
    const key = `${exIdx}_${t.index}`
    const userVal = (clozeInputs.value[key] || '').trim().toLowerCase()
    const correctVal = t.answer.trim().toLowerCase()
    clozeStatus.value[key] = userVal === correctVal ? 'correct' : 'wrong'
  })
}

const revealCloze = (exIdx, code) => {
  const tokens = parseExerciseTokens(code, exIdx).filter((t) => t.type === 'hole')
  tokens.forEach((t) => {
    const key = `${exIdx}_${t.index}`
    clozeInputs.value[key] = t.answer
    clozeStatus.value[key] = 'correct'
  })
}

const copyShareLink = () => {
  const url = `${window.location.origin}/study/${props.studySet.share_slug}`
  navigator.clipboard.writeText(url)
  shareCopied.value = true
  setTimeout(() => {
    shareCopied.value = false
  }, 2500)
}

onMounted(() => {
  studyStore.fetchProgress(props.studySet.id)
})

onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value)
})
</script>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s ease-out;
}
</style>
