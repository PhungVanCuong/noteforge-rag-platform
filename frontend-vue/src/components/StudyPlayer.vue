<template>
  <div class="max-w-5xl mx-auto py-6 px-3 sm:px-4">
    <!-- Top Action Card -->
    <div class="blueprint-card p-4 sm:p-6 mb-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-blueprint-paperBorder pb-5">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent px-2.5 py-0.5 rounded">
              STUDY ENGINE 4.0
            </span>
            <span v-if="studySet.notebook" class="text-xs text-gray-600 font-medium truncate max-w-[200px]">
              {{ studySet.notebook.title }}
            </span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-blueprint-dark">{{ studySet.title }}</h1>
          <p class="text-xs text-gray-600 mt-1 font-mono">
            Tổng: <strong>{{ allQuestions.length }}</strong> câu trắc nghiệm &bull; 
            <strong>{{ codeExercises.length }}</strong> bài tập điền khuyết code
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3">
          <!-- Timer for Exam Mode -->
          <div v-if="activeMode === 'exam' && examStarted && !examFinished" class="flex items-center gap-2">
            <div
              :class="[
                'font-mono text-lg sm:text-xl font-bold px-3 py-1.5 rounded-lg border shadow-inner',
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
            class="px-3.5 py-2 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-semibold text-xs flex items-center gap-1.5 shadow transition-colors"
            title="Tải về file .html duy nhất để học khi không có Internet"
          >
            <span>📥</span> Tải HTML Offline
          </a>

          <!-- Share Button -->
          <button
            @click="copyShareLink"
            class="px-3 py-2 rounded-lg bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium text-xs flex items-center gap-1.5 shadow-sm transition-colors"
          >
            <span>🔗</span> {{ shareCopied ? 'Đã copy!' : 'Chia sẻ' }}
          </button>
        </div>
      </div>

      <!-- Mode Switcher Tabs (4 Modes) -->
      <div class="flex overflow-x-auto gap-2 mt-4 pt-1 no-scrollbar pb-1">
        <button
          @click="switchMode('practice')"
          :class="[
            'px-4 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
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
            'px-4 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
            activeMode === 'exam'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>⏱️</span> 2. Thi thử tính giờ
        </button>

        <button
          @click="switchMode('cloze')"
          :class="[
            'px-4 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
            activeMode === 'cloze'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>💻</span> 3. Điền khuyết Code [[...]]
        </button>

        <button
          @click="switchMode('flashcard')"
          :class="[
            'px-4 py-2.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 shrink-0',
            activeMode === 'flashcard'
              ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
              : 'bg-white/80 hover:bg-white text-gray-700 border border-gray-200'
          ]"
        >
          <span>🃏</span> 4. Thẻ Flashcards 3D
        </button>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODE 1: LUYỆN TRẮC NGHIỆM -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'practice'" class="blueprint-card p-4 sm:p-6">
      <!-- Category & Status Filter Chips (Horizontal Scroll on Mobile) -->
      <div class="flex items-center gap-2 mb-6 pb-4 border-b border-blueprint-paperBorder/60 overflow-x-auto whitespace-nowrap no-scrollbar">
        <button
          @click="setCategory('ALL')"
          :class="[
            'px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors shrink-0',
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
            'px-3.5 py-1.5 rounded-full text-xs font-semibold transition-colors shrink-0',
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
            'ml-auto px-3.5 py-1.5 rounded-full text-xs font-bold transition-colors flex items-center gap-1.5 shrink-0',
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
        <p class="font-medium text-sm">Tuyệt vời! Không có câu hỏi nào thuộc bộ lọc này.</p>
      </div>

      <div v-else class="space-y-6 sm:space-y-8">
        <div
          v-for="(q, idx) in filteredQuestions"
          :key="q.id"
          class="bg-white p-4 sm:p-5 rounded-xl border border-gray-200 shadow-sm"
        >
          <div class="flex items-start justify-between gap-3 mb-4">
            <h3 class="text-sm sm:text-base font-bold text-blueprint-dark leading-relaxed">
              <span class="text-blueprint font-mono mr-1">#{{ idx + 1 }}.</span> {{ q.en }}
            </h3>
            <span v-if="isWrong(q.id)" class="text-[10px] sm:text-[11px] font-mono font-bold px-2 py-0.5 bg-red-100 text-red-700 rounded-full shrink-0">
              ❗ Làm sai
            </span>
          </div>

          <!-- Options Grid -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
            <button
              v-for="(opt, optIdx) in q.opts"
              :key="optIdx"
              @click="handlePracticeSelect(q, optIdx)"
              :disabled="practiceSelected[q.id] !== undefined"
              :class="[
                'p-3 sm:p-3.5 rounded-lg text-left text-xs sm:text-sm transition-all border flex items-start gap-3 min-h-[48px]',
                getOptionStyle(q, optIdx)
              ]"
            >
              <span class="font-mono font-bold shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs border">
                {{ ['A', 'B', 'C', 'D'][optIdx] || optIdx + 1 }}
              </span>
              <span class="leading-snug pt-0.5">{{ opt }}</span>
            </button>
          </div>

          <!-- Explanation Box & Deep AI Explain Button -->
          <div
            v-if="practiceSelected[q.id] !== undefined"
            class="mt-4 p-3.5 rounded-lg bg-blue-50/90 border-l-4 border-blue-600 text-xs text-blue-950 leading-relaxed animate-fadeIn"
          >
            <div class="font-bold flex items-center justify-between mb-1 text-blue-800">
              <span class="flex items-center gap-1">💡 Lời giải chi tiết:</span>
              <button
                @click="openAiExplainModal(q)"
                class="px-2.5 py-1 rounded bg-blueprint hover:bg-blueprint-light text-white font-mono text-[11px] flex items-center gap-1 shadow-sm transition-colors"
              >
                <span>🤖</span> Hỏi AI giải thích sâu hơn
              </button>
            </div>
            <div>{{ q.ex || 'Đáp án đúng theo giáo trình nội bộ.' }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- =================================================================== -->
    <!-- MODE 2: THI THỬ TÍNH GIỜ -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'exam'" class="blueprint-card p-4 sm:p-6">
      <!-- Exam Configuration Dialog (Pre-start) -->
      <div v-if="!examStarted" class="text-center py-8 sm:py-12 max-w-lg mx-auto">
        <div class="w-16 h-16 bg-blueprint text-blueprint-accent text-3xl flex items-center justify-center rounded-2xl mx-auto mb-4 shadow-lg">
          ⏱️
        </div>
        <h2 class="text-lg sm:text-xl font-bold text-blueprint-dark mb-2">Cấu Hình Bài Thi Thử</h2>
        <p class="text-xs text-gray-600 leading-relaxed mb-6">
          Luyện phản xạ phòng thi với bộ đề ngẫu nhiên, chấm điểm thang 10 và xem bài chữa chi tiết.
        </p>

        <!-- Config Grid -->
        <div class="grid grid-cols-2 gap-4 text-left mb-6 bg-white p-4 rounded-xl border">
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Số câu hỏi:</label>
            <select v-model.number="examConfigCount" class="w-full p-2 border rounded-lg text-xs bg-gray-50 font-mono">
              <option :value="10">10 câu hỏi</option>
              <option :value="20">20 câu hỏi (Chuẩn)</option>
              <option :value="40">40 câu hỏi</option>
              <option :value="allQuestions.length">Toàn bộ ({{ allQuestions.length }} câu)</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Thời gian thi:</label>
            <select v-model.number="examConfigMinutes" class="w-full p-2 border rounded-lg text-xs bg-gray-50 font-mono">
              <option :value="10">10 phút</option>
              <option :value="15">15 phút (Chuẩn)</option>
              <option :value="30">30 phút</option>
              <option :value="45">45 phút</option>
            </select>
          </div>
        </div>

        <button
          @click="startExam"
          class="px-8 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-sm shadow-md transition-all hover:scale-105"
        >
          🚀 Bắt đầu làm bài thi
        </button>
      </div>

      <!-- Active Exam Screen -->
      <div v-else-if="!examFinished" class="space-y-6">
        <!-- Question Palette Drawer Button (Mobile) -->
        <div class="bg-slate-900 text-white p-3 sm:p-4 rounded-xl font-mono text-xs flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span>Đã làm: <strong>{{ Object.keys(examAnswers).length }}/{{ examQuestions.length }}</strong></span>
            <button
              @click="showPaletteModal = true"
              class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-blueprint-accent font-bold"
            >
              📑 Bảng câu hỏi
            </button>
          </div>
          <span class="font-bold text-blueprint-accent text-sm">⏱️ {{ formattedTime }}</span>
        </div>

        <!-- Question List -->
        <div
          v-for="(q, idx) in examQuestions"
          :key="q.id"
          :id="`exam-q-${idx}`"
          class="bg-white p-4 sm:p-5 rounded-xl border border-gray-200 shadow-sm"
        >
          <h3 class="text-sm sm:text-base font-bold text-blueprint-dark mb-4 leading-relaxed">
            <span class="text-blueprint font-mono mr-1">Câu {{ idx + 1 }}:</span> {{ q.en }}
          </h3>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
            <button
              v-for="(opt, optIdx) in q.opts"
              :key="optIdx"
              @click="examAnswers[q.id] = optIdx"
              :class="[
                'p-3 sm:p-3.5 rounded-lg text-left text-xs sm:text-sm transition-all border flex items-start gap-3 min-h-[48px]',
                examAnswers[q.id] === optIdx
                  ? 'bg-blue-50 border-blue-600 font-semibold text-blue-900'
                  : 'bg-white border-gray-200 hover:bg-gray-50 text-gray-800'
              ]"
            >
              <span class="font-mono font-bold shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs border">
                {{ ['A', 'B', 'C', 'D'][optIdx] || optIdx + 1 }}
              </span>
              <span class="leading-snug pt-0.5">{{ opt }}</span>
            </button>
          </div>
        </div>

        <div class="text-center pt-4">
          <button
            @click="submitExam"
            class="px-10 py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-base shadow-lg transition-transform hover:scale-105"
          >
            ✅ Nộp bài thi
          </button>
        </div>
      </div>

      <!-- Exam Result Screen -->
      <div v-else class="text-center py-8 sm:py-10 max-w-lg mx-auto">
        <div
          :class="[
            'text-5xl font-extrabold mb-3 font-mono',
            examScore10 >= 5 ? 'text-emerald-600' : 'text-red-600'
          ]"
        >
          {{ examScore10 }} / 10
        </div>
        <h2 class="text-lg sm:text-xl font-bold text-blueprint-dark mb-2">
          {{ examScore10 >= 8 ? 'Xuất sắc! 🎉' : examScore10 >= 5 ? 'Đạt yêu cầu! 👍' : 'Cần cố gắng ôn thêm! 📚' }}
        </h2>
        <p class="text-xs text-gray-600 mb-6 font-mono">
          Đúng: <strong>{{ examCorrectCount }} / {{ examQuestions.length }}</strong> câu &bull; 
          Thời gian: <strong>{{ Math.floor(examDuration / 60) }}p {{ examDuration % 60 }}s</strong>
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
    <div v-if="activeMode === 'cloze'" class="blueprint-card p-4 sm:p-6">
      <div v-if="codeExercises.length === 0" class="text-center py-12 text-gray-500">
        <div class="text-3xl mb-2">💻</div>
        <p class="font-medium text-sm">Không có bài tập code điền khuyết nào trong học liệu này.</p>
      </div>

      <div v-else class="space-y-6 sm:space-y-8">
        <div
          v-for="(exercise, exIdx) in codeExercises"
          :key="exIdx"
          class="bg-white p-4 sm:p-6 rounded-xl border border-gray-200 shadow-sm"
        >
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm sm:text-base font-bold text-blueprint-dark">
              Bài {{ exIdx + 1 }}: {{ exercise.title }}
            </h3>
            <span class="text-xs font-mono text-gray-500">Cú pháp [[...]]</span>
          </div>

          <!-- Mobile-Optimized Code Box (#1e1e1e dark background) -->
          <div class="bg-[#1e1e1e] text-slate-100 p-4 sm:p-5 rounded-lg font-mono text-xs sm:text-sm leading-loose overflow-x-auto whitespace-pre-wrap border border-slate-700">
            <template v-for="(token, tIdx) in parseExerciseTokens(exercise.code, exIdx)" :key="tIdx">
              <span v-if="token.type === 'text'">{{ token.value }}</span>
              <input
                v-else
                type="text"
                v-model="clozeInputs[`${exIdx}_${token.index}`]"
                :style="{ width: `${token.answer.length * 10 + 28}px` }"
                :class="[
                  'px-2 py-0.5 mx-1 rounded border-2 font-bold font-mono outline-none text-center transition-colors',
                  clozeStatus[`${exIdx}_${token.index}`] === 'correct'
                    ? 'bg-emerald-950 border-emerald-500 text-emerald-300'
                    : clozeStatus[`${exIdx}_${token.index}`] === 'wrong'
                    ? 'bg-red-950 border-red-500 text-red-300'
                    : 'bg-slate-950 border-slate-600 text-sky-300 focus:border-blueprint-accent'
                ]"
                placeholder="..."
                autocapitalize="off"
                autocorrect="off"
                spellcheck="false"
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
              ✅ Kiểm tra kết quả
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

    <!-- =================================================================== -->
    <!-- MODE 4: THẺ GHI NHỚ FLASHCARDS 3D -->
    <!-- =================================================================== -->
    <div v-if="activeMode === 'flashcard'" class="blueprint-card p-4 sm:p-6 text-center">
      <div v-if="allQuestions.length === 0" class="py-12 text-gray-500 text-sm">
        Không có dữ liệu câu hỏi để tạo Flashcard.
      </div>

      <div v-else class="max-w-lg mx-auto py-6">
        <div class="text-xs font-mono text-gray-500 mb-4">
          Thẻ {{ currentFlashcardIndex + 1 }} / {{ allQuestions.length }} (Chạm hoặc Click để lật thẻ)
        </div>

        <!-- 3D Flip Card Container -->
        <div
          class="flashcard-scene h-72 w-full cursor-pointer select-none"
          @click="isFlipped = !isFlipped"
        >
          <div :class="['flashcard-card', isFlipped ? 'is-flipped' : '']">
            <!-- Front Face -->
            <div class="flashcard-face flashcard-front p-6 flex flex-col justify-between">
              <span class="text-xs font-mono text-blueprint font-bold uppercase tracking-wide">
                Mặt trước: Câu hỏi
              </span>
              <p class="text-base sm:text-lg font-bold text-blueprint-dark leading-relaxed">
                {{ currentFlashcard.en }}
              </p>
              <span class="text-[11px] text-gray-400 font-mono">Chạm để lật xem đáp án ↺</span>
            </div>

            <!-- Back Face -->
            <div class="flashcard-face flashcard-back p-6 flex flex-col justify-between">
              <span class="text-xs font-mono text-emerald-800 font-bold uppercase tracking-wide">
                Mặt sau: Đáp án & Giải thích
              </span>
              <div>
                <div class="text-base font-bold text-emerald-900 mb-2">
                  ✅ {{ currentFlashcard.opts[currentFlashcard.ans] }}
                </div>
                <p class="text-xs text-gray-700 leading-relaxed max-h-32 overflow-y-auto">
                  {{ currentFlashcard.ex || 'Đáp án theo giáo trình nội bộ.' }}
                </p>
              </div>
              <span class="text-[11px] text-gray-400 font-mono">Chạm để quay lại câu hỏi ↻</span>
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-center gap-4 mt-8">
          <button
            @click="prevFlashcard"
            class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold text-xs"
          >
            ◀ Thẻ trước
          </button>
          <button
            @click="isFlipped = !isFlipped"
            class="px-4 py-2 rounded-lg bg-blueprint-accent text-blueprint-dark font-bold text-xs shadow"
          >
            🔄 Lật mặt thẻ
          </button>
          <button
            @click="nextFlashcard"
            class="px-4 py-2 rounded-lg bg-blueprint text-white font-bold text-xs shadow hover:bg-blueprint-light"
          >
            Thẻ tiếp theo ▶
          </button>
        </div>
      </div>
    </div>

    <!-- Question Palette Drawer Modal (for Exam Mode) -->
    <div
      v-if="showPaletteModal"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-md w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
          <h3 class="font-bold text-sm text-blueprint-dark">Bảng Điều Hướng Câu Hỏi</h3>
          <button @click="showPaletteModal = false" class="text-gray-400 font-bold">✕</button>
        </div>

        <div class="grid grid-cols-5 gap-2 max-h-60 overflow-y-auto p-1">
          <button
            v-for="(q, idx) in examQuestions"
            :key="q.id"
            @click="scrollToExamQuestion(idx)"
            :class="[
              'h-9 rounded-lg font-mono text-xs font-bold border transition-colors',
              examAnswers[q.id] !== undefined
                ? 'bg-blue-600 text-white border-blue-700'
                : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100'
            ]"
          >
            {{ idx + 1 }}
          </button>
        </div>
      </div>
    </div>

    <!-- AI Deep Explanation Modal -->
    <div
      v-if="aiExplainModalOpen"
      class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4"
    >
      <div class="blueprint-card max-w-lg w-full p-6 shadow-2xl">
        <div class="flex items-center justify-between border-b pb-3 mb-3">
          <h3 class="font-bold text-sm text-blueprint-dark flex items-center gap-1.5">
            <span>🤖</span> Giải Thích Chuyên Sâu Cùng AI
          </h3>
          <button @click="aiExplainModalOpen = false" class="text-gray-400 font-bold">✕</button>
        </div>

        <div class="text-xs text-gray-600 mb-2">
          <strong>Câu hỏi:</strong> {{ currentExplainQuestion?.en }}
        </div>
        <div class="text-xs text-emerald-800 font-bold mb-4">
          Đáp án: {{ currentExplainQuestion?.opts[currentExplainQuestion?.ans] }}
        </div>

        <div class="bg-white p-4 rounded-lg border text-xs text-gray-800 leading-relaxed font-mono whitespace-pre-wrap max-h-64 overflow-y-auto">
          {{ aiExplainText || 'Đang tải phân tích chuyên sâu từ AI...' }}
        </div>

        <div class="mt-4 text-right">
          <button
            @click="aiExplainModalOpen = false"
            class="px-4 py-2 bg-blueprint text-white rounded-lg text-xs font-bold"
          >
            Đóng
          </button>
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
const examConfigCount = ref(20)
const examConfigMinutes = ref(15)
const examQuestions = ref([])
const examAnswers = ref({})
const timeLeft = ref(15 * 60)
const timerInterval = ref(null)
const examScore10 = ref(0)
const examCorrectCount = ref(0)
const examDuration = ref(0)
const showPaletteModal = ref(false)

// Cloze state
const clozeInputs = ref({})
const clozeStatus = ref({})

// Flashcard state
const currentFlashcardIndex = ref(0)
const isFlipped = ref(false)

// AI Deep Explain Modal
const aiExplainModalOpen = ref(false)
const currentExplainQuestion = ref(null)
const aiExplainText = ref('')

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

const currentFlashcard = computed(() => {
  return allQuestions.value[currentFlashcardIndex.value] || {}
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
  const count = Math.min(examConfigCount.value, shuffled.length)
  examQuestions.value = shuffled.slice(0, count)
  examAnswers.value = {}
  timeLeft.value = examConfigMinutes.value * 60
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

const scrollToExamQuestion = (idx) => {
  showPaletteModal.value = false
  const el = document.getElementById(`exam-q-${idx}`)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
  }
}

const submitExam = async () => {
  if (timerInterval.value) {
    clearInterval(timerInterval.value)
    timerInterval.value = null
  }
  examDuration.value = examConfigMinutes.value * 60 - timeLeft.value

  let correct = 0
  const detail = {}

  for (const q of examQuestions.value) {
    const userPick = examAnswers.value[q.id]
    const isRight = userPick === q.ans
    if (isRight) correct++
    detail[q.id] = { user_pick: userPick, correct_ans: q.ans, is_right: isRight }

    await studyStore.updateQuestionProgress(
      props.studySet.id,
      q.id,
      isRight ? 'right' : 'wrong'
    )
  }

  examCorrectCount.value = correct
  examScore10.value = Number(((correct / examQuestions.value.length) * 10).toFixed(2))
  examFinished.value = true

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

// -------------------------------------------------------------
// Flashcard Logic
// -------------------------------------------------------------
const nextFlashcard = () => {
  isFlipped.value = false
  if (currentFlashcardIndex.value < allQuestions.value.length - 1) {
    currentFlashcardIndex.value++
  } else {
    currentFlashcardIndex.value = 0
  }
}

const prevFlashcard = () => {
  isFlipped.value = false
  if (currentFlashcardIndex.value > 0) {
    currentFlashcardIndex.value--
  } else {
    currentFlashcardIndex.value = allQuestions.value.length - 1
  }
}

// -------------------------------------------------------------
// AI Deep Explain Modal
// -------------------------------------------------------------
const openAiExplainModal = (q) => {
  currentExplainQuestion.value = q
  aiExplainModalOpen.value = true
  aiExplainText.value = q.ex 
    ? `${q.ex}\n\n💡 Nhắc nhở trọng tâm: Nắm vững bản chất khái niệm này để ứng dụng trong các bài toán thực tế.` 
    : 'Kiến thức cốt lõi theo giáo trình nội bộ.'
}

const copyShareLink = () => {
  const url = `${window.location.origin}/study/${props.studySet.share_slug}`
  navigator.clipboard.writeText(url)
  shareCopied.value = true
  setTimeout(() => {
    shareCopied.value = false
  }, 2500)
}

// Keyboard shortcuts for desktop (1,2,3,4 or A,B,C,D)
const handleKeydown = (e) => {
  if (activeMode.value !== 'practice') return
  const key = e.key.toUpperCase()
  let optIndex = -1
  if (['1', 'A'].includes(key)) optIndex = 0
  else if (['2', 'B'].includes(key)) optIndex = 1
  else if (['3', 'C'].includes(key)) optIndex = 2
  else if (['4', 'D'].includes(key)) optIndex = 3

  if (optIndex !== -1 && filteredQuestions.value.length > 0) {
    const firstUnanswered = filteredQuestions.value.find((q) => practiceSelected.value[q.id] === undefined)
    if (firstUnanswered && firstUnanswered.opts[optIndex]) {
      handlePracticeSelect(firstUnanswered, optIndex)
    }
  }
}

onMounted(() => {
  studyStore.fetchProgress(props.studySet.id)
  window.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  if (timerInterval.value) clearInterval(timerInterval.value)
  window.removeEventListener('keydown', handleKeydown)
})
</script>

<style scoped>
/* Flashcard 3D CSS */
.flashcard-scene {
  perspective: 1000px;
}
.flashcard-card {
  width: 100%;
  height: 100%;
  position: relative;
  transition: transform 0.6s;
  transform-style: preserve-3d;
}
.flashcard-card.is-flipped {
  transform: rotateY(180deg);
}
.flashcard-face {
  position: absolute;
  width: 100%;
  height: 100%;
  backface-visibility: hidden;
  border-radius: 1rem;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
  border: 2px solid #D1C7B7;
}
.flashcard-front {
  background: #F7F4EC;
}
.flashcard-back {
  background: #ECFDF5;
  border-color: #10B981;
  transform: rotateY(180deg);
}
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
