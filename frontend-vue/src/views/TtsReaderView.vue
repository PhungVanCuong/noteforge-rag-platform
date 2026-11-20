<template>
  <div class="min-h-screen pb-24">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8 space-y-8">

      <!-- ========================================== -->
      <!-- HEADER BANNER                              -->
      <!-- ========================================== -->
      <div class="blueprint-card p-6 md:p-8 flex flex-col md:flex-row md:items-center justify-between gap-6 shadow-xl">
        <div>
          <div class="flex items-center gap-2 mb-2 flex-wrap">
            <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
              AI NEURAL AUDIO STUDIO
            </span>
            <span class="text-xs text-emerald-700 bg-emerald-100 font-mono font-bold px-2 py-0.5 rounded border border-emerald-300 flex items-center gap-1">
              <span>⚡</span> Microsoft Edge Neural Engine
            </span>
            <span class="text-xs text-blue-800 bg-blue-100 font-mono px-2 py-0.5 rounded border border-blue-200">
              Song Ngữ Anh - Việt Chuẩn
            </span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark">
            Trạm Đọc Văn Bản & Luyện Phát Âm AI
          </h1>
          <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed max-w-2xl">
            Tự động nhận diện và đọc tiếng Anh xen kẽ tiếng Việt mượt mà. Tích hợp thanh tua thời gian kéo thả, đục lỗ Karaoke Highlight câu chữ, tải về đa định dạng (MP3, WAV) và phòng thu âm Microphone cá nhân.
          </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <label class="px-4 py-2.5 rounded-xl bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow-md flex items-center gap-2 cursor-pointer transition-transform hover:scale-105">
            <span>📂</span> Nạp tệp (.docx, .pdf, .txt)
            <input
              type="file"
              @change="handleFileUpload"
              accept=".txt,.docx,.doc,.pdf"
              class="hidden"
            />
          </label>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- MAIN STUDIO GRID: TEXT & VOICE CONFIG      -->
      <!-- ========================================== -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left Column: Editor & Karaoke View (Span 2) -->
        <div class="lg:col-span-2 space-y-6">
          <div class="blueprint-card p-6 shadow-lg">
            <!-- Tab & Stats Bar -->
            <div class="flex items-center justify-between mb-4 border-b border-blueprint-paperBorder/60 pb-3 flex-wrap gap-2">
              <div class="flex items-center gap-2">
                <button
                  @click="viewMode = 'editor'"
                  :class="viewMode === 'editor' ? 'bg-blueprint text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                  class="px-3.5 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5"
                >
                  <span>✏️</span> Soạn thảo
                </button>
                <button
                  @click="viewMode = 'karaoke'"
                  :class="viewMode === 'karaoke' ? 'bg-blueprint text-white font-bold shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                  class="px-3.5 py-1.5 rounded-lg text-xs transition-colors flex items-center gap-1.5"
                >
                  <span>🎤</span> Chế độ Karaoke
                  <span v-if="sentences.length" class="text-[10px] px-1.5 py-0.2 rounded-full bg-amber-400 text-blueprint-dark font-mono font-bold">
                    {{ sentences.length }} câu
                  </span>
                </button>
              </div>

              <div class="text-[11px] font-mono text-gray-500">
                {{ characterCount.toLocaleString() }} ký tự &bull; {{ wordCount.toLocaleString() }} từ
              </div>
            </div>

            <!-- Tab 1: Textarea Editor -->
            <div v-show="viewMode === 'editor'">
              <textarea
                v-model="textContent"
                rows="13"
                placeholder="Nhập hoặc dán văn bản cần đọc vào đây (hỗ trợ đọc cả tiếng Việt và thuật ngữ tiếng Anh xen kẽ như .NET, WPF, MVVM, Framework, Database mượt mà)..."
                class="w-full p-4 rounded-xl border border-gray-300 text-xs sm:text-sm font-sans bg-white outline-none focus:ring-2 focus:ring-blueprint resize-y leading-relaxed text-gray-800 shadow-inner"
              ></textarea>
            </div>

            <!-- Tab 2: Karaoke Highlight View -->
            <div v-show="viewMode === 'karaoke'" class="min-h-[320px] max-h-[460px] overflow-y-auto p-4 bg-white rounded-xl border border-gray-300 text-xs sm:text-sm leading-loose text-gray-800 space-y-2 select-text shadow-inner">
              <p v-if="sentences.length === 0" class="text-gray-400 italic text-center py-12">
                Chưa có văn bản. Hãy quay lại tab "Soạn thảo" để nhập nội dung.
              </p>
              <div v-else class="space-y-1">
                <span
                  v-for="(sentence, sIdx) in sentences"
                  :key="sIdx"
                  :id="`sentence-${sIdx}`"
                  @click="seekToSentence(sIdx)"
                  :class="currentSentenceIndex === sIdx
                    ? 'bg-amber-200 text-blueprint-dark font-bold px-2 py-0.5 rounded-lg border-2 border-amber-400 shadow-sm inline-block scale-[1.01] transition-all'
                    : 'hover:bg-blue-50 cursor-pointer px-1 py-0.5 rounded transition-colors inline-block'"
                  class="mr-1.5"
                  :title="'Nhấp để tua audio đến câu ' + (sIdx + 1)"
                >
                  {{ sentence }}
                </span>
              </div>
            </div>

            <!-- Bottom Quick Buttons -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-blueprint-paperBorder/60">
              <div class="flex items-center gap-2 flex-wrap">
                <button
                  @click="clearText"
                  class="px-2.5 py-1 rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-100 text-xs font-semibold"
                >
                  Xóa trắng
                </button>
                <button
                  @click="loadSampleText('bilingual')"
                  class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100 text-xs font-semibold"
                >
                  🇻🇳 Mẫu Song Ngữ IT
                </button>
                <button
                  @click="loadSampleText('en')"
                  class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100 text-xs font-semibold"
                >
                  🇺🇸 Mẫu Tiếng Anh
                </button>
                <button
                  @click="loadSampleText('vi')"
                  class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100 text-xs font-semibold"
                >
                  🇻🇳 Mẫu Tiếng Việt
                </button>
              </div>

              <span v-if="extractingFile" class="text-xs text-blueprint font-bold animate-pulse">
                ⏳ Đang trích xuất nội dung tệp...
              </span>
            </div>
          </div>
        </div>

        <!-- Right Column: Voice & Controls (Span 1) -->
        <div class="space-y-6">
          <div class="blueprint-card p-6 space-y-5 shadow-lg">
            <h3 class="text-base font-bold text-blueprint-dark border-b border-blueprint-paperBorder/60 pb-3 flex items-center gap-2">
              <span>🎛️</span> Cấu Hình Giọng Đọc AI
            </h3>

            <!-- Voice Selector -->
            <div>
              <label class="block text-xs font-bold text-blueprint-dark mb-1.5">Giọng đọc AI (Edge Neural)</label>
              <select
                v-model="selectedVoice"
                class="w-full px-3 py-2.5 rounded-lg border border-gray-300 text-xs bg-white outline-none focus:ring-2 focus:ring-blueprint font-semibold text-gray-800"
              >
                <option
                  v-for="v in voicesList"
                  :key="v.id"
                  :value="v.id"
                >
                  {{ v.name }}
                </option>
              </select>
              <p class="text-[11px] text-gray-500 mt-1">
                💡 Giọng <strong>Hoài My</strong> & <strong>Nam Minh</strong> tự động nhận biết và phát âm từ ngữ tiếng Anh trong câu tiếng Việt cực chuẩn.
              </p>
            </div>

            <!-- Reading Speed (Rate) -->
            <div>
              <div class="flex items-center justify-between text-xs font-bold text-blueprint-dark mb-1">
                <span>Tốc độ đọc giọng AI</span>
                <span class="font-mono text-blueprint font-black">{{ speechRate.toFixed(2) }}x</span>
              </div>
              <input
                v-model.number="speechRate"
                type="range"
                min="0.5"
                max="2.0"
                step="0.05"
                class="w-full accent-blueprint cursor-pointer"
              />
              <div class="flex justify-between gap-1 mt-1.5">
                <button
                  v-for="r in [0.75, 1.0, 1.25, 1.5]"
                  :key="r"
                  type="button"
                  @click="speechRate = r"
                  :class="speechRate === r ? 'bg-blueprint text-white font-bold' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                  class="px-2 py-0.5 rounded text-[10px] font-mono transition-colors"
                >
                  {{ r }}x
                </button>
              </div>
            </div>

            <!-- Synthesize Button -->
            <button
              @click="handleSynthesize"
              :disabled="synthesizing || !textContent.trim()"
              class="w-full py-3.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-black text-xs sm:text-sm shadow-md flex items-center justify-center gap-2 transition-all hover:scale-105 disabled:opacity-40 disabled:hover:scale-100"
            >
              <span v-if="synthesizing" class="inline-block animate-spin">⏳</span>
              <span v-else>⚡</span>
              <span>{{ synthesizing ? 'Đang tạo Audio AI Neural...' : 'Tạo Giọng Đọc AI Ngay' }}</span>
            </button>

            <div v-if="synthesizeError" class="p-3 rounded-lg bg-red-100 border border-red-300 text-red-800 text-xs font-semibold">
              ⚠️ {{ synthesizeError }}
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- ADVANCED AUDIO PLAYER & CONTROLS           -->
      <!-- ========================================== -->
      <div v-if="audioUrl" class="blueprint-card p-6 sm:p-8 shadow-2xl border-2 border-blueprint/30 animate-fade-in space-y-6">
        <!-- Audio Player Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-blueprint-paperBorder/60 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-blueprint-dark text-blueprint-accent flex items-center justify-center text-2xl shadow-md shrink-0">
              🎵
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold bg-blueprint/10 text-blueprint px-2 py-0.5 rounded">
                  AUDIO AI READY
                </span>
                <span class="text-xs text-gray-500 font-mono">{{ audioFileName }}</span>
              </div>
              <h3 class="text-base sm:text-lg font-bold text-blueprint-dark mt-0.5">
                Trình Phát Âm Thanh Thông Minh
              </h3>
            </div>
          </div>

          <!-- Download Audio Dropdown / Buttons -->
          <div class="flex items-center gap-2">
            <button
              @click="downloadAudio('mp3')"
              class="px-3.5 py-2 rounded-xl bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow flex items-center gap-1.5 transition-transform hover:scale-105"
              title="Tải file định dạng MP3"
            >
              <span>📥</span> Tải MP3
            </button>
            <button
              @click="downloadAudio('wav')"
              class="px-3.5 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-xs shadow-sm border border-gray-300 flex items-center gap-1.5 transition-colors"
              title="Tải file định dạng WAV Lossless"
            >
              <span>📥</span> Tải WAV
            </button>
          </div>
        </div>

        <!-- Custom Seek Bar & Timers -->
        <div class="space-y-2">
          <div class="relative flex items-center">
            <input
              type="range"
              min="0"
              :max="duration || 100"
              step="0.1"
              :value="currentTime"
              @input="onSeekInput"
              class="w-full accent-blueprint h-2 bg-gray-200 rounded-lg cursor-pointer"
            />
          </div>

          <div class="flex justify-between text-xs font-mono font-bold text-gray-600">
            <span class="text-blueprint font-black">{{ formatTime(currentTime) }}</span>
            <span>{{ formatTime(duration) }}</span>
          </div>
        </div>

        <!-- Player Controls Strip -->
        <div class="flex flex-wrap items-center justify-between gap-4 pt-2">
          <!-- Playback Controls -->
          <div class="flex items-center gap-2">
            <!-- Backward 5s -->
            <button
              @click="seekRelative(-5)"
              class="p-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold shadow-sm transition-colors"
              title="Lùi lại 5 giây"
            >
              ⏪ 5s
            </button>

            <!-- Play / Pause Main Button -->
            <button
              @click="togglePlay"
              class="px-6 py-2.5 rounded-xl bg-blueprint hover:bg-blueprint-light text-white font-black text-sm shadow-md flex items-center gap-2 transition-transform hover:scale-105"
            >
              <span>{{ isAudioPlaying ? '⏸️ Tạm dừng' : '▶️ Phát âm thanh' }}</span>
            </button>

            <!-- Forward 5s -->
            <button
              @click="seekRelative(5)"
              class="p-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold shadow-sm transition-colors"
              title="Tua tới 5 giây"
            >
              ⏩ 5s
            </button>

            <!-- Stop Button -->
            <button
              @click="stopAudio"
              class="p-2.5 rounded-xl bg-red-100 hover:bg-red-200 text-red-800 text-xs font-bold shadow-sm transition-colors"
              title="Dừng hẳn"
            >
              ⏹️ Dừng
            </button>
          </div>

          <!-- Playback Speed Controls -->
          <div class="flex items-center gap-1.5 bg-gray-100 p-1 rounded-xl">
            <span class="text-[10px] font-mono text-gray-500 px-2 font-bold">Tốc độ:</span>
            <button
              v-for="s in [0.75, 1.0, 1.25, 1.5, 2.0]"
              :key="s"
              @click="setPlaybackSpeed(s)"
              :class="playbackRate === s ? 'bg-blueprint text-white font-bold' : 'text-gray-700 hover:bg-gray-200'"
              class="px-2 py-1 rounded-lg text-xs font-mono transition-colors"
            >
              {{ s }}x
            </button>
          </div>

          <!-- Volume Slider -->
          <div class="flex items-center gap-2">
            <button @click="toggleMute" class="text-sm">
              {{ isMuted || volume === 0 ? '🔇' : '🔊' }}
            </button>
            <input
              type="range"
              min="0"
              max="1"
              step="0.05"
              v-model.number="volume"
              @input="onVolumeChange"
              class="w-20 sm:w-24 accent-blueprint h-1.5 bg-gray-200 rounded-lg cursor-pointer"
            />
          </div>
        </div>

        <!-- Hidden Native HTML5 Audio Tag -->
        <audio
          ref="audioRef"
          :src="audioUrl"
          @timeupdate="onTimeUpdate"
          @loadedmetadata="onLoadedMetadata"
          @ended="onAudioEnded"
          class="hidden"
        ></audio>
      </div>

      <!-- ========================================== -->
      <!-- MICROPHONE RECORDING STUDIO (PRACTICE)     -->
      <!-- ========================================== -->
      <div class="blueprint-card p-6 sm:p-8 shadow-xl space-y-5 border-2 border-emerald-600/30">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-blueprint-paperBorder/60 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-emerald-950 text-emerald-400 flex items-center justify-center text-2xl shadow-md shrink-0">
              🎙️
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded">
                  STUDENT RECORDING STUDIO
                </span>
              </div>
              <h3 class="text-base sm:text-lg font-bold text-blueprint-dark mt-0.5">
                Phòng Thu Âm & Luyện Giọng Cá Nhân
              </h3>
            </div>
          </div>

          <p class="text-xs text-gray-600 max-w-sm">
            Thu âm trực tiếp giọng đọc của bạn qua Microphone để so sánh phát âm tiếng Anh và ngữ điệu với AI.
          </p>
        </div>

        <div class="flex flex-col sm:flex-row items-center justify-between gap-6 bg-white p-5 rounded-2xl border border-gray-200">
          <div class="flex items-center gap-4">
            <!-- Record Button -->
            <button
              v-if="!isRecording"
              @click="startRecording"
              class="px-5 py-3 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-md flex items-center gap-2 transition-transform hover:scale-105"
            >
              <span class="w-3 h-3 rounded-full bg-white"></span>
              <span>Bắt Đầu Thu Âm</span>
            </button>

            <!-- Stop Recording Button -->
            <button
              v-else
              @click="stopRecording"
              class="px-5 py-3 rounded-xl bg-red-800 hover:bg-red-900 text-white font-bold text-xs shadow-md flex items-center gap-2 animate-pulse"
            >
              <span class="w-3 h-3 rounded bg-white"></span>
              <span>Dừng Thu Âm ({{ formatTime(recordingSeconds) }})</span>
            </button>

            <!-- Live Timer -->
            <div v-if="isRecording" class="flex items-center gap-2 text-red-600 font-mono text-sm font-bold">
              <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>
              <span>Đang thu: {{ formatTime(recordingSeconds) }}</span>
            </div>
          </div>

          <!-- Recorded Audio Preview -->
          <div v-if="recordedAudioUrl" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <audio :src="recordedAudioUrl" controls class="h-10"></audio>

            <a
              :href="recordedAudioUrl"
              download="my_recording.webm"
              class="px-3 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-xs shadow-sm flex items-center gap-1.5 transition-colors"
            >
              <span>📥</span> Tải Bản Thu
            </a>

            <button
              @click="clearRecording"
              class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
              title="Xóa bản thu"
            >
              🗑️
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import apiClient from '@/api/axios'

// State
const textContent = ref('')
const viewMode = ref('editor')
const selectedVoice = ref('vi-VN-HoaiMyNeural')
const speechRate = ref(1.0)
const synthesizing = ref(false)
const synthesizeError = ref('')
const extractingFile = ref(false)

// Audio Player State
const audioRef = ref(null)
const audioUrl = ref('')
const audioFileName = ref('')
const isAudioPlaying = ref(false)
const currentTime = ref(0)
const duration = ref(0)
const playbackRate = ref(1.0)
const volume = ref(1.0)
const isMuted = ref(false)
const currentSentenceIndex = ref(-1)

// Microphone Recording State
const isRecording = ref(false)
const recordingSeconds = ref(0)
const recordedAudioUrl = ref('')
let mediaRecorder = null
let recordedChunks = []
let recordingTimer = null

// Curated voices list
const voicesList = [
  { id: 'vi-VN-HoaiMyNeural', name: 'Hoài My (Nữ VN - Chuẩn Song Ngữ Anh-Việt ⭐)', lang: 'vi-VN' },
  { id: 'vi-VN-NamMinhNeural', name: 'Nam Minh (Nam VN - Trầm Ấm, Truyền Cảm ⭐)', lang: 'vi-VN' },
  { id: 'en-US-JennyNeural', name: 'Jenny (Nữ US - Tự Nhiên, Trôi Chảy)', lang: 'en-US' },
  { id: 'en-US-GuyNeural', name: 'Guy (Nam US - Chuẩn Giảng Viên)', lang: 'en-US' },
  { id: 'en-US-AriaNeural', name: 'Aria (Nữ US - Thuyết Trình Tin Tức)', lang: 'en-US' },
  { id: 'en-GB-SoniaNeural', name: 'Sonia (Nữ UK - Giọng Anh-Anh Quý Tộc)', lang: 'en-GB' },
  { id: 'ja-JP-NanamiNeural', name: 'Nanami (Nữ Nhật Bản)', lang: 'ja-JP' },
  { id: 'ko-KR-SunHiNeural', name: 'Sun-Hi (Nữ Hàn Quốc)', lang: 'ko-KR' },
  { id: 'zh-CN-XiaoxiaoNeural', name: 'Xiaoxiao (Nữ Trung Quốc)', lang: 'zh-CN' },
  { id: 'fr-FR-DeniseNeural', name: 'Denise (Nữ Pháp)', lang: 'fr-FR' },
]

// Word & Character count
const characterCount = computed(() => textContent.value.length)
const wordCount = computed(() => {
  const words = textContent.value.trim().split(/\s+/)
  return words[0] === '' ? 0 : words.length
})

// Split into sentences for Karaoke
const sentences = computed(() => {
  if (!textContent.value.trim()) return []
  const matches = textContent.value.match(/[^.!?\n]+[.!?\n]+|[^.!?\n]+$/g) || [textContent.value]
  return matches.map((s) => s.trim()).filter((s) => s.length > 0)
})

onMounted(() => {
  loadSampleText('bilingual')
})

onUnmounted(() => {
  if (recordingTimer) clearInterval(recordingTimer)
  if (audioRef.value) {
    audioRef.value.pause()
    audioRef.value.src = ''
  }
})

// Synthesize Text using Python Edge-TTS
const handleSynthesize = async () => {
  if (!textContent.value.trim()) return
  synthesizing.value = true
  synthesizeError.value = ''

  try {
    const res = await apiClient.post('/tts/synthesize', {
      text: textContent.value.trim(),
      voice: selectedVoice.value,
      rate: speechRate.value,
    })

    const data = res.data?.data
    if (data && data.playback_url) {
      audioUrl.value = data.playback_url
      audioFileName.value = data.file_name || 'speech.mp3'
      currentTime.value = 0
      currentSentenceIndex.value = -1

      await nextTick()
      if (audioRef.value) {
        audioRef.value.load()
        audioRef.value.play().then(() => {
          isAudioPlaying.value = true
        }).catch(() => {})
      }
    } else {
      throw new Error('Máy chủ không trả về URL âm thanh.')
    }
  } catch (err) {
    synthesizeError.value = err.response?.data?.message || err.message || 'Lỗi khi tạo giọng đọc AI.'
  } finally {
    synthesizing.value = false
  }
}

// Audio Player Handlers
const togglePlay = () => {
  if (!audioRef.value) return
  if (audioRef.value.paused) {
    audioRef.value.play()
    isAudioPlaying.value = true
  } else {
    audioRef.value.pause()
    isAudioPlaying.value = false
  }
}

const stopAudio = () => {
  if (!audioRef.value) return
  audioRef.value.pause()
  audioRef.value.currentTime = 0
  isAudioPlaying.value = false
  currentTime.value = 0
  currentSentenceIndex.value = -1
}

const seekRelative = (seconds) => {
  if (!audioRef.value) return
  audioRef.value.currentTime = Math.max(0, Math.min(duration.value, audioRef.value.currentTime + seconds))
}

const onSeekInput = (e) => {
  const target = parseFloat(e.target.value)
  if (audioRef.value) {
    audioRef.value.currentTime = target
    currentTime.value = target
  }
}

const setPlaybackSpeed = (speed) => {
  playbackRate.value = speed
  if (audioRef.value) {
    audioRef.value.playbackRate = speed
  }
}

const onVolumeChange = () => {
  if (audioRef.value) {
    audioRef.value.volume = volume.value
    isMuted.value = volume.value === 0
  }
}

const toggleMute = () => {
  if (!audioRef.value) return
  isMuted.value = !isMuted.value
  audioRef.value.muted = isMuted.value
}

const onLoadedMetadata = () => {
  if (audioRef.value) {
    duration.value = audioRef.value.duration || 0
    audioRef.value.playbackRate = playbackRate.value
  }
}

const onAudioEnded = () => {
  isAudioPlaying.value = false
  currentSentenceIndex.value = -1
}

// Live karaoke sentence sync
const onTimeUpdate = () => {
  if (!audioRef.value) return
  currentTime.value = audioRef.value.currentTime

  if (sentences.value.length > 0 && duration.value > 0) {
    const progressRatio = Math.min(1, currentTime.value / duration.value)
    const targetIdx = Math.min(
      sentences.value.length - 1,
      Math.floor(progressRatio * sentences.value.length)
    )

    if (currentSentenceIndex.value !== targetIdx) {
      currentSentenceIndex.value = targetIdx
      // Auto-scroll in karaoke mode
      if (viewMode.value === 'karaoke') {
        const el = document.getElementById(`sentence-${targetIdx}`)
        if (el) {
          el.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
        }
      }
    }
  }
}

const seekToSentence = (idx) => {
  if (!audioRef.value || sentences.value.length === 0 || duration.value === 0) return
  const targetTime = (idx / sentences.value.length) * duration.value
  audioRef.value.currentTime = targetTime
  if (audioRef.value.paused) {
    audioRef.value.play()
    isAudioPlaying.value = true
  }
}

// Download MP3 or WAV
const downloadAudio = async (format = 'mp3') => {
  if (!audioUrl.value) return

  if (format === 'mp3') {
    const link = document.createElement('a')
    link.href = audioUrl.value
    link.download = audioFileName.value || 'speech.mp3'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
  } else if (format === 'wav') {
    try {
      // Decode MP3 audio into AudioBuffer via Web Audio API and encode to clean WAV
      const response = await fetch(audioUrl.value)
      const arrayBuffer = await response.arrayBuffer()
      const audioCtx = new (window.AudioContext || window.webkitAudioContext)()
      const audioBuffer = await audioCtx.decodeAudioData(arrayBuffer)
      const wavBlob = audioBufferToWav(audioBuffer)
      const wavUrl = URL.createObjectURL(wavBlob)

      const link = document.createElement('a')
      link.href = wavUrl
      link.download = (audioFileName.value.replace(/\.[^/.]+$/, '') || 'speech') + '.wav'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      URL.revokeObjectURL(wavUrl)
    } catch (err) {
      alert('Chuyển đổi sang định dạng WAV thất bại: ' + err.message)
    }
  }
}

// Client-Side WAV Encoder
const audioBufferToWav = (buffer) => {
  const numChannels = buffer.numberOfChannels
  const sampleRate = buffer.sampleRate
  const format = 1 // PCM
  const bitDepth = 16

  let result
  if (numChannels === 2) {
    const ch0 = buffer.getChannelData(0)
    const ch1 = buffer.getChannelData(1)
    result = new Float32Array(ch0.length + ch1.length)
    let idx = 0
    let chIdx = 0
    while (idx < result.length) {
      result[idx++] = ch0[chIdx]
      result[idx++] = ch1[chIdx]
      chIdx++
    }
  } else {
    result = buffer.getChannelData(0)
  }

  const bytesPerSample = bitDepth / 8
  const blockAlign = numChannels * bytesPerSample
  const bufferLen = 44 + result.length * bytesPerSample
  const outBuffer = new ArrayBuffer(bufferLen)
  const view = new DataView(outBuffer)

  const writeStr = (v, offset, str) => {
    for (let i = 0; i < str.length; i++) {
      v.setUint8(offset + i, str.charCodeAt(i))
    }
  }

  writeStr(view, 0, 'RIFF')
  view.setUint32(4, 36 + result.length * bytesPerSample, true)
  writeStr(view, 8, 'WAVE')
  writeStr(view, 12, 'fmt ')
  view.setUint32(16, 16, true)
  view.setUint16(20, format, true)
  view.setUint16(22, numChannels, true)
  view.setUint32(24, sampleRate, true)
  view.setUint32(28, sampleRate * blockAlign, true)
  view.setUint16(32, blockAlign, true)
  view.setUint16(34, bitDepth, true)
  writeStr(view, 36, 'data')
  view.setUint32(40, result.length * bytesPerSample, true)

  let offset = 44
  for (let i = 0; i < result.length; i++, offset += 2) {
    const s = Math.max(-1, Math.min(1, result[i]))
    view.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7fff, true)
  }

  return new Blob([outBuffer], { type: 'audio/wav' })
}

// Microphone Recording
const startRecording = async () => {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    recordedChunks = []
    mediaRecorder = new MediaRecorder(stream)

    mediaRecorder.ondataavailable = (e) => {
      if (e.data.size > 0) recordedChunks.push(e.data)
    }

    mediaRecorder.onstop = () => {
      const blob = new Blob(recordedChunks, { type: 'audio/webm' })
      recordedAudioUrl.value = URL.createObjectURL(blob)
      stream.getTracks().forEach((track) => track.stop())
    }

    mediaRecorder.start()
    isRecording.value = true
    recordingSeconds.value = 0
    recordingTimer = setInterval(() => {
      recordingSeconds.value++
    }, 1000)
  } catch (err) {
    alert('Không thể truy cập Microphone: ' + err.message)
  }
}

const stopRecording = () => {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()
  }
  isRecording.value = false
  if (recordingTimer) {
    clearInterval(recordingTimer)
    recordingTimer = null
  }
}

const clearRecording = () => {
  if (recordedAudioUrl.value) {
    URL.revokeObjectURL(recordedAudioUrl.value)
    recordedAudioUrl.value = ''
  }
}

// File Upload Extraction
const handleFileUpload = async (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  extractingFile.value = true
  const formData = new FormData()
  formData.append('file', file)

  try {
    const res = await apiClient.post('/extract-text', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    if (res.data?.text) {
      textContent.value = res.data.text
      viewMode.value = 'editor'
    }
  } catch (err) {
    alert(err.response?.data?.message || 'Trích xuất văn bản từ tệp thất bại.')
  } finally {
    extractingFile.value = false
    event.target.value = ''
  }
}

const clearText = () => {
  textContent.value = ''
  stopAudio()
}

const formatTime = (secs) => {
  if (isNaN(secs) || secs < 0) return '00:00'
  const m = Math.floor(secs / 60)
  const s = Math.floor(secs % 60)
  return `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`
}

const loadSampleText = (type) => {
  if (type === 'bilingual') {
    textContent.value = `Chào mừng bạn đến với NoteForge AI. Trong môn học Lập trình .NET & WPF, kiến trúc Model View ViewModel hay MVVM đóng vai trò then chốt trong việc phân tách giao diện người dùng và logic xử lý. Chúng ta sử dụng Data Binding và Command Pattern để liên kết giữa View và ViewModel mà không cần viết code-behind. Ngoài ra, Dependency Injection Container và Entity Framework Core giúp quản lý cơ sở dữ liệu và dependency một cách tối ưu. Hãy tạo audio và luyện phát âm ngay!`
  } else if (type === 'en') {
    textContent.value = `Welcome to the NoteForge AI Audio Studio. Modern software engineering requires a deep understanding of Design Patterns, Dependency Injection, and Asynchronous Programming. With Microsoft Edge Neural Text-to-Speech, you can improve your listening and pronunciation skills with crystal clear accuracy. Try recording your voice below to compare with the AI voice.`
  } else {
    textContent.value = `NoteForge AI là nền tảng trợ lý học tập và chuyển hóa tài liệu nội bộ dựa trên công nghệ RAG chống bịa đặt. Hệ thống hỗ trợ sinh viên trường học ôn tập hiệu quả với ngân hàng câu hỏi trắc nghiệm, bài tập điền khuyết đục lỗ mã nguồn và trích dẫn số trang chính xác từ giáo trình gốc.`
  }
}
</script>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-in-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
