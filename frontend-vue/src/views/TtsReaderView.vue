<template>
  <div class="min-h-screen pb-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <!-- Header Banner -->
      <div class="blueprint-card p-6 md:p-8 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
              TEXT-TO-SPEECH STUDIO
            </span>
            <span class="text-xs text-gray-500 font-mono">Đa ngôn ngữ & Karaoke Highlight</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark">
            Trạm Đọc Văn Bản Thông Minh
          </h1>
          <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed max-w-2xl">
            Nghe đọc tài liệu tiếng Việt, tiếng Anh và nhiều ngôn ngữ khác. Hỗ trợ tải tệp Word, PDF, TXT và xuất file MP3 chất lượng cao.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <label class="px-4 py-2.5 rounded-xl bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-md flex items-center gap-2 cursor-pointer transition-transform hover:scale-105">
            <span>📂</span> Tải tệp (.txt, .docx, .pdf)
            <input
              type="file"
              @change="handleFileUpload"
              accept=".txt,.docx,.doc,.pdf"
              class="hidden"
            />
          </label>
        </div>
      </div>

      <!-- Main Studio Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Text Input & Karaoke View (Span 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="blueprint-card p-6">
            <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder/60 pb-3">
              <div class="flex items-center gap-2">
                <button
                  @click="viewMode = 'editor'"
                  :class="viewMode === 'editor' ? 'bg-blueprint text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                  class="px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5"
                >
                  <span>✏️</span> Soạn thảo
                </button>
                <button
                  @click="viewMode = 'karaoke'"
                  :class="viewMode === 'karaoke' ? 'bg-blueprint text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                  class="px-3 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5"
                >
                  <span>🎤</span> Đọc Karaoke
                </button>
              </div>

              <div class="text-[11px] font-mono text-gray-500">
                {{ characterCount.toLocaleString() }} ký tự &bull; {{ wordCount.toLocaleString() }} từ
              </div>
            </div>

            <!-- Editor View -->
            <div v-show="viewMode === 'editor'">
              <textarea
                v-model="textContent"
                rows="14"
                placeholder="Nhập hoặc dán đoạn văn bản cần đọc vào đây (tiếng Việt, tiếng Anh, v.v.). Bạn cũng có thể bấm nút 'Tải tệp' ở trên để nạp nhanh..."
                class="w-full p-4 rounded-xl border border-gray-300 text-xs sm:text-sm font-sans bg-white outline-none focus:ring-2 focus:ring-blueprint resize-y leading-relaxed text-gray-800"
              ></textarea>
            </div>

            <!-- Karaoke View -->
            <div v-show="viewMode === 'karaoke'" class="min-h-[350px] max-h-[500px] overflow-y-auto p-4 bg-white rounded-xl border border-gray-300 text-xs sm:text-sm leading-loose text-gray-800 space-y-2">
              <p v-if="sentences.length === 0" class="text-gray-400 italic text-center py-12">
                Chưa có văn bản. Hãy quay lại tab "Soạn thảo" để nhập nội dung.
              </p>
              <div v-else class="space-y-1">
                <span
                  v-for="(sentence, sIdx) in sentences"
                  :key="sIdx"
                  :id="`sentence-${sIdx}`"
                  @click="playFromSentence(sIdx)"
                  :class="currentSentenceIndex === sIdx ? 'bg-amber-200 text-blueprint-dark font-bold px-1.5 py-0.5 rounded shadow-sm border border-amber-400' : 'hover:bg-blue-50 cursor-pointer px-1 rounded transition-colors'"
                  class="inline-block mr-1.5"
                >
                  {{ sentence }}
                </span>
              </div>
            </div>

            <!-- Quick Action Toolbar -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-blueprint-paperBorder/60">
              <div class="flex items-center gap-2">
                <button
                  @click="clearText"
                  class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 text-xs"
                >
                  Xóa trắng
                </button>
                <button
                  @click="loadSampleText('vi')"
                  class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs"
                >
                  Mẫu tiếng Việt
                </button>
                <button
                  @click="loadSampleText('en')"
                  class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs"
                >
                  Mẫu tiếng Anh
                </button>
              </div>

              <span v-if="extractingFile" class="text-xs text-blueprint font-bold animate-pulse">
                ⏳ Đang trích xuất nội dung tệp...
              </span>
            </div>
          </div>
        </div>

        <!-- Right: Voice & Playback Controls (1 col) -->
        <div class="space-y-6">
          <div class="blueprint-card p-6 space-y-5">
            <h3 class="text-base font-bold text-blueprint-dark border-b border-blueprint-paperBorder/60 pb-3 flex items-center gap-2">
              <span>🎛️</span> Điều Khiển Giọng Đọc
            </h3>

            <!-- Language Selector -->
            <div>
              <label class="block text-xs font-bold text-blueprint-dark mb-1.5">Ngôn ngữ đọc</label>
              <select
                v-model="selectedLang"
                @change="onLangChange"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint font-semibold"
              >
                <option value="auto">✨ Tự động nhận diện</option>
                <option value="vi-VN">🇻🇳 Tiếng Việt (vi-VN)</option>
                <option value="en-US">🇺🇸 Tiếng Anh - Mỹ (en-US)</option>
                <option value="en-GB">🇬🇧 Tiếng Anh - Anh (en-GB)</option>
                <option value="ja-JP">🇯🇵 Tiếng Nhật (ja-JP)</option>
                <option value="ko-KR">🇰🇷 Tiếng Hàn (ko-KR)</option>
                <option value="zh-CN">🇨🇳 Tiếng Trung (zh-CN)</option>
              </select>
            </div>

            <!-- Voice Selector -->
            <div>
              <label class="block text-xs font-bold text-blueprint-dark mb-1.5">Giọng đọc (Voice)</label>
              <select
                v-model="selectedVoiceUri"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-1 focus:ring-blueprint"
              >
                <option value="">Giọng đọc mặc định của máy</option>
                <option
                  v-for="voice in filteredVoices"
                  :key="voice.voiceURI"
                  :value="voice.voiceURI"
                >
                  {{ voice.name }} ({{ voice.lang }})
                </option>
              </select>
            </div>

            <!-- Rate (Speed) Slider -->
            <div>
              <div class="flex items-center justify-between text-xs font-bold text-blueprint-dark mb-1">
                <span>Tốc độ đọc</span>
                <span class="font-mono text-blueprint">{{ speechRate.toFixed(1) }}x</span>
              </div>
              <input
                v-model.number="speechRate"
                type="range"
                min="0.5"
                max="2.0"
                step="0.1"
                class="w-full accent-blueprint cursor-pointer"
              />
              <div class="flex justify-between text-[10px] text-gray-400 font-mono">
                <span>0.5x (Chậm)</span>
                <span>1.0x (Chuẩn)</span>
                <span>2.0x (Nhanh)</span>
              </div>
            </div>

            <!-- Pitch Slider -->
            <div>
              <div class="flex items-center justify-between text-xs font-bold text-blueprint-dark mb-1">
                <span>Cao độ giọng (Pitch)</span>
                <span class="font-mono text-blueprint">{{ speechPitch.toFixed(1) }}</span>
              </div>
              <input
                v-model.number="speechPitch"
                type="range"
                min="0.5"
                max="1.5"
                step="0.1"
                class="w-full accent-blueprint cursor-pointer"
              />
            </div>

            <!-- Playback Buttons -->
            <div class="pt-4 border-t border-blueprint-paperBorder/60 space-y-2.5">
              <div class="grid grid-cols-2 gap-2">
                <button
                  v-if="!isPlaying"
                  @click="playSpeech"
                  :disabled="!textContent.trim()"
                  class="w-full py-3 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-xs shadow flex items-center justify-center gap-1.5 transition-all hover:scale-105 disabled:opacity-40"
                >
                  <span>▶️</span> Đọc toàn bộ
                </button>

                <button
                  v-else
                  @click="togglePause"
                  class="w-full py-3 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow flex items-center justify-center gap-1.5 transition-colors"
                >
                  <span>{{ isPaused ? '▶️ Tiếp tục' : '⏸️ Tạm dừng' }}</span>
                </button>

                <button
                  @click="stopSpeech"
                  :disabled="!isPlaying && !isPaused"
                  class="w-full py-3 rounded-xl bg-red-800 hover:bg-red-900 text-white font-bold text-xs shadow flex items-center justify-center gap-1.5 transition-colors disabled:opacity-40"
                >
                  <span>⏹️</span> Dừng hẳn
                </button>
              </div>

              <!-- Export MP3 Button -->
              <button
                @click="downloadMp3"
                :disabled="exportingMp3 || !textContent.trim()"
                class="w-full py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow flex items-center justify-center gap-2 transition-colors disabled:opacity-40"
              >
                <span>{{ exportingMp3 ? '⏳ Đang tạo file MP3...' : '📥 Tải file âm thanh (.mp3)' }}</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import apiClient from '@/api/axios'

const textContent = ref('')
const viewMode = ref('editor')
const selectedLang = ref('auto')
const selectedVoiceUri = ref('')
const speechRate = ref(1.0)
const speechPitch = ref(1.0)

const isPlaying = ref(false)
const isPaused = ref(false)
const currentSentenceIndex = ref(-1)
const availableVoices = ref([])
const extractingFile = ref(false)
const exportingMp3 = ref(false)

let synth = null
let currentUtterance = null

// Word & Character count
const characterCount = computed(() => textContent.value.length)
const wordCount = computed(() => {
  const words = textContent.value.trim().split(/\s+/)
  return words[0] === '' ? 0 : words.length
})

// Split into sentences for Karaoke
const sentences = computed(() => {
  if (!textContent.value.trim()) return []
  // Split on punctuation followed by space or newline
  const raw = textContent.value.match(/[^.!?\n]+[.!?\n]+/g) || [textContent.value]
  return raw.map((s) => s.trim()).filter((s) => s.length > 0)
})

// Filter browser voices
const filteredVoices = computed(() => {
  if (selectedLang.value === 'auto') {
    return availableVoices.value
  }
  const prefix = selectedLang.value.split('-')[0].toLowerCase()
  return availableVoices.value.filter((v) => v.lang.toLowerCase().startsWith(prefix))
})

onMounted(() => {
  if (typeof window !== 'undefined' && 'speechSynthesis' in window) {
    synth = window.speechSynthesis
    loadVoices()
    if (synth.onvoiceschanged !== undefined) {
      synth.onvoiceschanged = loadVoices
    }
  }
  loadSampleText('vi')
})

onUnmounted(() => {
  stopSpeech()
})

const loadVoices = () => {
  if (!synth) return
  availableVoices.value = synth.getVoices()
}

const onLangChange = () => {
  selectedVoiceUri.value = ''
}

const clearText = () => {
  stopSpeech()
  textContent.value = ''
  currentSentenceIndex.value = -1
}

const loadSampleText = (lang) => {
  if (lang === 'vi') {
    selectedLang.value = 'vi-VN'
    textContent.value =
      'Chào mừng bạn đến với NoteForge AI Platform. Đây là hệ thống trợ lý học tập cá nhân hóa thông minh, giúp sinh viên tự động chuyển hóa giáo trình thành bộ đề ôn thi trắc nghiệm và bài tập điền khuyết code. Bạn có thể nghe đọc toàn bộ tài liệu hoặc nhấn trực tiếp vào từng câu để nghe giảng lại!'
  } else {
    selectedLang.value = 'en-US'
    textContent.value =
      'Welcome to NoteForge AI Platform. This is a personal AI study assistant designed to transform academic documents into interactive quizzes and code cloze tests. Enjoy listening to your lectures and mastering your exam preparation!'
  }
}

// Handle File Upload (.txt, .docx, .pdf)
const handleFileUpload = async (e) => {
  const file = e.target.files?.[0]
  if (!file) return

  stopSpeech()
  const ext = file.name.split('.').pop().toLowerCase()

  if (ext === 'txt') {
    const reader = new FileReader()
    reader.onload = (event) => {
      textContent.value = event.target.result
      viewMode.value = 'editor'
    }
    reader.readAsText(file, 'UTF-8')
  } else if (['docx', 'doc', 'pdf'].includes(ext)) {
    extractingFile.value = true
    try {
      const formData = new FormData()
      formData.append('file', file)
      // Call Python extract text endpoint
      const aiBase = 'http://127.0.0.1:8001'
      const response = await fetch(`${aiBase}/api/extract-text`, {
        method: 'POST',
        body: formData,
      })
      const data = await response.json()
      if (data.success && data.text) {
        textContent.value = data.text
        viewMode.value = 'editor'
      } else {
        alert('Không thể đọc nội dung tệp.')
      }
    } catch (err) {
      alert('Lỗi kết nối khi trích xuất văn bản: ' + err.message)
    } finally {
      extractingFile.value = false
    }
  }
}

// Speech Playback
const playSpeech = () => {
  if (!synth || !textContent.value.trim()) return

  if (isPaused.value) {
    synth.resume()
    isPaused.value = false
    isPlaying.value = true
    return
  }

  stopSpeech()
  viewMode.value = 'karaoke'
  playSentenceList(sentences.value, 0)
}

const playFromSentence = (index) => {
  stopSpeech()
  viewMode.value = 'karaoke'
  playSentenceList(sentences.value, index)
}

const playSentenceList = (sentenceArray, startIndex) => {
  if (startIndex >= sentenceArray.length) {
    stopSpeech()
    return
  }

  isPlaying.value = true
  isPaused.value = false
  currentSentenceIndex.value = startIndex

  // Auto scroll to active sentence
  nextTick(() => {
    const el = document.getElementById(`sentence-${startIndex}`)
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    }
  })

  const sentenceText = sentenceArray[startIndex]
  currentUtterance = new SpeechSynthesisUtterance(sentenceText)
  currentUtterance.rate = speechRate.value
  currentUtterance.pitch = speechPitch.value

  // Assign voice
  if (selectedVoiceUri.value) {
    const found = availableVoices.value.find((v) => v.voiceURI === selectedVoiceUri.value)
    if (found) currentUtterance.voice = found
  } else if (selectedLang.value !== 'auto') {
    currentUtterance.lang = selectedLang.value
  }

  currentUtterance.onend = () => {
    if (isPlaying.value && !isPaused.value) {
      playSentenceList(sentenceArray, startIndex + 1)
    }
  }

  currentUtterance.onerror = (e) => {
    console.warn('SpeechSynthesis error:', e)
    if (isPlaying.value) {
      playSentenceList(sentenceArray, startIndex + 1)
    }
  }

  synth.speak(currentUtterance)
}

const togglePause = () => {
  if (!synth) return
  if (isPaused.value) {
    synth.resume()
    isPaused.value = false
    isPlaying.value = true
  } else {
    synth.pause()
    isPaused.value = true
    isPlaying.value = false
  }
}

const stopSpeech = () => {
  if (synth) {
    synth.cancel()
  }
  isPlaying.value = false
  isPaused.value = false
  currentSentenceIndex.value = -1
}

// Download MP3 via Backend Python Microservice
const downloadMp3 = async () => {
  if (!textContent.value.trim()) return
  exportingMp3.value = true
  try {
    const aiBase = 'http://127.0.0.1:8001'
    const res = await fetch(`${aiBase}/api/tts/synthesize`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        text: textContent.value.trim(),
        lang: selectedLang.value,
        rate: speechRate.value,
      }),
    })
    const data = await res.json()
    if (data.success && data.data?.url) {
      // Trigger download
      const downloadLink = `${aiBase}${data.data.url}`
      const a = document.createElement('a')
      a.href = downloadLink
      a.download = data.data.file_name || 'noteforge_tts.mp3'
      document.body.appendChild(a)
      a.click()
      document.body.removeChild(a)
    } else {
      alert('Không thể tạo file âm thanh: ' + (data.detail || 'Lỗi không xác định'))
    }
  } catch (err) {
    alert('Lỗi kết nối tới AI Microservice để tạo MP3: ' + err.message)
  } finally {
    exportingMp3.value = false
  }
}
</script>
