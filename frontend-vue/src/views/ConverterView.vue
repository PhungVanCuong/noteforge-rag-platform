<template>
  <div class="min-h-screen pb-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
      <!-- Header Banner -->
      <div class="blueprint-card p-6 md:p-8 mb-8">
        <div class="flex items-center gap-2 mb-2">
          <span class="px-2.5 py-0.5 rounded text-[11px] font-mono font-bold bg-blueprint-dark text-blueprint-accent">
            CONVERTER HUB
          </span>
          <span class="text-xs text-gray-500 font-mono">Microservice Python + FFmpeg + LibreOffice</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-blueprint-dark">
          Trạm Chuyển Hóa Đa Định Dạng Học Liệu
        </h1>
        <p class="text-xs sm:text-sm text-gray-700 mt-2 leading-relaxed max-w-3xl">
          Công cụ xử lý tệp tốc độ cao: chuyển đổi văn phòng, gộp/tách PDF theo thứ tự tùy chỉnh, xử lý nén ảnh và bóc băng video bài giảng kèm mốc thời gian.
        </p>

        <!-- Navigation Tabs -->
        <div class="flex flex-wrap gap-2 mt-6">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            :class="[
              'px-4 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2',
              activeTab === tab.id
                ? 'bg-blueprint-dark text-blueprint-accent shadow-md'
                : 'bg-white/90 hover:bg-white text-gray-700 border border-gray-200'
            ]"
          >
            <span>{{ tab.icon }}</span> {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Result Banner -->
      <div v-if="converterStore.activeResult" class="mb-8 p-5 rounded-xl bg-emerald-50 border-2 border-emerald-400 text-emerald-900 shadow-md animate-fadeIn flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <div class="font-bold text-sm flex items-center gap-2 text-emerald-800">
            <span>🎉</span> {{ converterStore.activeResult.message || 'Xử lý tệp thành công!' }}
          </div>
          <p class="text-xs text-emerald-700 mt-1 font-mono">
            Kết quả đã sẵn sàng để tải xuống hoặc đưa vào ôn tập RAG.
          </p>
        </div>

        <a
          v-if="converterStore.activeResult.download_url"
          :href="getAbsoluteDownloadUrl(converterStore.activeResult.download_url)"
          target="_blank"
          download
          class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow flex items-center gap-2 shrink-0 transition-colors"
        >
          <span>📥</span> Tải tệp kết quả
        </a>
      </div>

      <!-- TAB 1: TÀI LIỆU VĂN PHÒNG -->
      <div v-if="activeTab === 'office'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- PDF to Word -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 1</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">PDF sang Word (.docx)</h3>
            <p class="text-xs text-gray-600 mb-6 leading-relaxed">
              Sử dụng thuật ngữ và parser chuyên sâu <code class="font-mono bg-gray-200 px-1 rounded">pdf2docx</code> để giữ nguyên 100% định dạng bảng biểu, hình ảnh và font chữ tiếng Việt.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-white hover:border-blueprint transition-colors mb-4">
              <input type="file" ref="pdfToWordInput" accept=".pdf" class="hidden" @change="onPdfToWordSelected" />
              <div v-if="!selectedPdfToWordFile" @click="$refs.pdfToWordInput.click()" class="cursor-pointer">
                <div class="text-3xl mb-2">📄</div>
                <div class="text-xs font-bold text-gray-700">Chọn hoặc kéo thả tệp PDF vào đây</div>
                <div class="text-[11px] text-gray-400 mt-1">Dung lượng tối đa 100MB</div>
              </div>
              <div v-else class="flex items-center justify-between text-xs font-mono">
                <span class="truncate font-bold text-blueprint-dark">{{ selectedPdfToWordFile.name }}</span>
                <button @click="selectedPdfToWordFile = null" class="text-red-500 font-bold ml-2">✕</button>
              </div>
            </div>
          </div>

          <button
            @click="handlePdfToWord"
            :disabled="!selectedPdfToWordFile || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang chuyển đổi PDF sang Word...' : '🚀 Bắt đầu chuyển sang .docx' }}
          </button>
        </div>

        <!-- Word/Office to PDF -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 2</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">Word / PPTX / Excel sang PDF</h3>
            <p class="text-xs text-gray-600 mb-6 leading-relaxed">
              Chạy công cụ LibreOffice Headless trên máy chủ đảm bảo xuất PDF chuẩn mực từng layout và công thức học thuật.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-6 text-center bg-white hover:border-blueprint transition-colors mb-4">
              <input type="file" ref="officeToPdfInput" accept=".docx,.doc,.pptx,.ppt,.xlsx,.xls" class="hidden" @change="onOfficeToPdfSelected" />
              <div v-if="!selectedOfficeFile" @click="$refs.officeToPdfInput.click()" class="cursor-pointer">
                <div class="text-3xl mb-2">📑</div>
                <div class="text-xs font-bold text-gray-700">Chọn tệp Word, PPTX hoặc Excel</div>
                <div class="text-[11px] text-gray-400 mt-1">Hỗ trợ .docx, .pptx, .xlsx</div>
              </div>
              <div v-else class="flex items-center justify-between text-xs font-mono">
                <span class="truncate font-bold text-blueprint-dark">{{ selectedOfficeFile.name }}</span>
                <button @click="selectedOfficeFile = null" class="text-red-500 font-bold ml-2">✕</button>
              </div>
            </div>
          </div>

          <button
            @click="handleOfficeToPdf"
            :disabled="!selectedOfficeFile || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang kết xuất PDF bằng LibreOffice...' : '🚀 Bắt đầu chuyển sang PDF' }}
          </button>
        </div>
      </div>

      <!-- TAB 2: GỘP & TÁCH PDF -->
      <div v-if="activeTab === 'pdf-ops'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Merge PDF with ordering -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 3</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">Gộp Nhiều Tệp PDF (Merge PDF)</h3>
            <p class="text-xs text-gray-600 mb-4 leading-relaxed">
              Kéo thả các tệp PDF để sắp xếp thứ tự trước khi gộp thành 1 tệp giáo trình duy nhất để nạp vào RAG.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center bg-white mb-4">
              <input type="file" ref="mergePdfInput" multiple accept=".pdf" class="hidden" @change="onMergeFilesSelected" />
              <button @click="$refs.mergePdfInput.click()" class="text-xs font-bold text-blueprint hover:underline">
                ➕ Thêm các tệp PDF cần gộp
              </button>

              <!-- File List with Order Adjustment -->
              <div v-if="mergeFiles.length > 0" class="mt-4 space-y-2 text-left">
                <div
                  v-for="(f, fIdx) in mergeFiles"
                  :key="fIdx"
                  class="flex items-center justify-between p-2 rounded bg-gray-50 border text-xs font-mono"
                >
                  <div class="flex items-center gap-2 truncate">
                    <span class="font-bold text-blueprint">{{ fIdx + 1 }}.</span>
                    <span class="truncate">{{ f.name }}</span>
                  </div>
                  <div class="flex items-center gap-1 shrink-0">
                    <button @click="moveMergeFile(fIdx, -1)" :disabled="fIdx === 0" class="px-1.5 py-0.5 bg-gray-200 rounded disabled:opacity-30">▲</button>
                    <button @click="moveMergeFile(fIdx, 1)" :disabled="fIdx === mergeFiles.length - 1" class="px-1.5 py-0.5 bg-gray-200 rounded disabled:opacity-30">▼</button>
                    <button @click="removeMergeFile(fIdx)" class="px-1.5 py-0.5 text-red-600 font-bold ml-1">✕</button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <button
            @click="handleMergePdf"
            :disabled="mergeFiles.length < 2 || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang gộp PDF...' : `🚀 Gộp ${mergeFiles.length} tệp PDF` }}
          </button>
        </div>

        <!-- Split PDF -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 4</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">Tách Trang PDF (Split PDF)</h3>
            <p class="text-xs text-gray-600 mb-4 leading-relaxed">
              Trích xuất chính xác các trang bạn muốn (Ví dụ: <code class="font-mono bg-gray-200 px-1 rounded">1-5,8,12-15</code>).
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center bg-white mb-4">
              <input type="file" ref="splitPdfInput" accept=".pdf" class="hidden" @change="onSplitFileSelected" />
              <div v-if="!splitFile" @click="$refs.splitPdfInput.click()" class="cursor-pointer py-3">
                <div class="text-xs font-bold text-gray-700">Chọn tệp PDF cần tách trang</div>
              </div>
              <div v-else class="flex items-center justify-between text-xs font-mono">
                <span class="truncate font-bold text-blueprint-dark">{{ splitFile.name }}</span>
                <button @click="splitFile = null" class="text-red-500 font-bold ml-2">✕</button>
              </div>
            </div>

            <div class="mb-4">
              <label class="block text-xs font-bold text-blueprint-dark mb-1">Dải trang cần tách *</label>
              <input
                v-model="splitRanges"
                type="text"
                placeholder="VD: 1-10, 15, 20-25"
                class="w-full px-3 py-2 rounded-lg border border-gray-300 text-xs font-mono bg-white outline-none focus:ring-1 focus:ring-blueprint"
              />
            </div>
          </div>

          <button
            @click="handleSplitPdf"
            :disabled="!splitFile || !splitRanges.trim() || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang trích xuất trang...' : '🚀 Bắt đầu tách trang PDF' }}
          </button>
        </div>
      </div>

      <!-- TAB 3: HÌNH ẢNH & SLIDE SANG PDF -->
      <div v-if="activeTab === 'image'" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Convert image format / compress -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 5</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">Chuyển Đổi & Nén Ảnh (Pillow)</h3>
            <p class="text-xs text-gray-600 mb-4 leading-relaxed">
              Chuyển đổi qua lại giữa PNG, JPG, WEBP và tùy chỉnh tỷ lệ nén dung lượng.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center bg-white mb-4">
              <input type="file" ref="singleImgInput" accept="image/*" class="hidden" @change="onSingleImgSelected" />
              <div v-if="!singleImgFile" @click="$refs.singleImgInput.click()" class="cursor-pointer py-3">
                <div class="text-xs font-bold text-gray-700">Chọn tệp ảnh (PNG, JPG, WEBP)</div>
              </div>
              <div v-else class="flex items-center justify-between text-xs font-mono">
                <span class="truncate font-bold text-blueprint-dark">{{ singleImgFile.name }}</span>
                <button @click="singleImgFile = null" class="text-red-500 font-bold ml-2">✕</button>
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3 mb-4">
              <div>
                <label class="block text-xs font-bold text-blueprint-dark mb-1">Định dạng đích</label>
                <select v-model="targetImgFormat" class="w-full px-3 py-2 rounded-lg border text-xs bg-white">
                  <option value="PNG">PNG (Chất lượng gốc)</option>
                  <option value="JPG">JPG (Nén nhẹ)</option>
                  <option value="WEBP">WEBP (Web siêu nhẹ)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-blueprint-dark mb-1">Chất lượng: {{ imgQuality }}%</label>
                <input type="range" min="30" max="100" v-model.number="imgQuality" class="w-full mt-2" />
              </div>
            </div>
          </div>

          <button
            @click="handleConvertImage"
            :disabled="!singleImgFile || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang xử lý ảnh...' : '🚀 Bắt đầu chuyển đổi ảnh' }}
          </button>
        </div>

        <!-- Images to PDF -->
        <div class="blueprint-card p-6 flex flex-col justify-between">
          <div>
            <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 6</div>
            <h3 class="text-lg font-bold text-blueprint-dark mb-2">Ghép Nhiều Ảnh Chụp Thành 1 PDF</h3>
            <p class="text-xs text-gray-600 mb-4 leading-relaxed">
              Tổng hợp ảnh chụp slide bài giảng hoặc bài tập chép tay thành một tệp PDF hoàn chỉnh để lập chỉ mục RAG.
            </p>

            <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center bg-white mb-4">
              <input type="file" ref="multiImgInput" multiple accept="image/*" class="hidden" @change="onMultiImgSelected" />
              <button @click="$refs.multiImgInput.click()" class="text-xs font-bold text-blueprint hover:underline">
                ➕ Chọn các tệp ảnh chụp slide
              </button>

              <div v-if="multiImgFiles.length > 0" class="mt-3 text-xs font-mono text-gray-600">
                Đã chọn <strong>{{ multiImgFiles.length }}</strong> ảnh
              </div>
            </div>
          </div>

          <button
            @click="handleImagesToPdf"
            :disabled="multiImgFiles.length === 0 || converterStore.isProcessing"
            class="w-full py-3 rounded-lg bg-blueprint hover:bg-blueprint-light text-white font-bold text-xs shadow disabled:opacity-40"
          >
            {{ converterStore.isProcessing ? 'Đang tạo PDF từ ảnh...' : `🚀 Xuất PDF từ ${multiImgFiles.length} ảnh` }}
          </button>
        </div>
      </div>

      <!-- TAB 4: BÓC BĂNG BÀI GIẢNG (TRANSCRIPTION) -->
      <div v-if="activeTab === 'transcribe'" class="blueprint-card p-6 md:p-8">
        <div class="max-w-2xl mb-6">
          <div class="text-xs font-bold font-mono text-blueprint mb-1">CÔNG CỤ 7 &bull; AI MULTIMODAL</div>
          <h3 class="text-xl font-bold text-blueprint-dark">Bóc Băng Bài Giảng (Lecture Transcriber)</h3>
          <p class="text-xs text-gray-600 mt-2 leading-relaxed">
            Tải lên video bài giảng (.mp4, .mkv) hoặc âm thanh (.mp3, .m4a). FFmpeg sẽ tự động tách kênh âm thanh và gọi Gemini Multimodal API bóc băng toàn bộ lời giảng kèm mốc thời gian chi tiết <code class="font-mono bg-gray-200 px-1 rounded">[MM:SS]</code>.
          </p>
        </div>

        <div class="border-2 border-dashed border-gray-300 rounded-xl p-8 text-center bg-white mb-6">
          <input type="file" ref="mediaInput" accept="video/*,audio/*" class="hidden" @change="onMediaSelected" />
          <div v-if="!selectedMediaFile" @click="$refs.mediaInput.click()" class="cursor-pointer">
            <div class="text-4xl mb-3">🎙️</div>
            <div class="text-sm font-bold text-gray-800">Chọn tệp Video hoặc Audio bài giảng</div>
            <div class="text-xs text-gray-500 mt-1">Hỗ trợ MP4, MKV, AVI, MP3, WAV, M4A (Tối đa 200MB)</div>
          </div>
          <div v-else class="flex items-center justify-center gap-4 text-xs font-mono">
            <span class="font-bold text-blueprint-dark">Tệp đã chọn: {{ selectedMediaFile.name }}</span>
            <button @click="selectedMediaFile = null" class="text-red-500 font-bold">✕ Hủy</button>
          </div>
        </div>

        <button
          @click="handleTranscribe"
          :disabled="!selectedMediaFile || converterStore.isProcessing"
          class="px-8 py-3.5 rounded-xl bg-blueprint-accent hover:bg-amber-400 text-blueprint-dark font-bold text-sm shadow-md transition-all disabled:opacity-40"
        >
          {{ converterStore.isProcessing ? 'Đang trích xuất audio và bóc băng AI...' : '🎙️ Bắt đầu bóc băng bài giảng' }}
        </button>

        <!-- Transcript Preview Box -->
        <div v-if="transcriptionResult" class="mt-8 pt-6 border-t border-blueprint-paperBorder">
          <h4 class="font-bold text-sm text-blueprint-dark mb-3 flex items-center gap-2">
            <span>📝</span> Kết quả bóc băng có mốc thời gian:
          </h4>
          <div class="bg-white p-5 rounded-xl border border-gray-200 font-mono text-xs text-gray-800 leading-relaxed whitespace-pre-wrap max-h-96 overflow-y-auto">
            {{ transcriptionResult }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useConverterStore } from '@/stores/converter'

const converterStore = useConverterStore()

const tabs = [
  { id: 'office', label: 'Tài liệu văn phòng', icon: '📄' },
  { id: 'pdf-ops', label: 'Gộp & Tách PDF', icon: '📑' },
  { id: 'image', label: 'Xử lý hình ảnh', icon: '🖼️' },
  { id: 'transcribe', label: 'Bóc băng bài giảng', icon: '🎙️' },
]

const activeTab = ref('office')

// Tab 1 state
const selectedPdfToWordFile = ref(null)
const selectedOfficeFile = ref(null)

// Tab 2 state
const mergeFiles = ref([])
const splitFile = ref(null)
const splitRanges = ref('1-5')

// Tab 3 state
const singleImgFile = ref(null)
const targetImgFormat = ref('WEBP')
const imgQuality = ref(85)
const multiImgFiles = ref([])

// Tab 4 state
const selectedMediaFile = ref(null)
const transcriptionResult = ref('')

const getAbsoluteDownloadUrl = (path) => {
  if (path.startsWith('http')) return path
  const host = import.meta.env.VITE_AI_URL || 'http://localhost:8001'
  return `${host}${path}`
}

// Handlers
const onPdfToWordSelected = (e) => {
  selectedPdfToWordFile.value = e.target.files?.[0]
}
const handlePdfToWord = async () => {
  if (!selectedPdfToWordFile.value) return
  try {
    await converterStore.convertPdfToWord(selectedPdfToWordFile.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Chuyển đổi thất bại.')
  }
}

const onOfficeToPdfSelected = (e) => {
  selectedOfficeFile.value = e.target.files?.[0]
}
const handleOfficeToPdf = async () => {
  if (!selectedOfficeFile.value) return
  try {
    await converterStore.convertWordToPdf(selectedOfficeFile.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Chuyển đổi thất bại.')
  }
}

const onMergeFilesSelected = (e) => {
  const newFiles = Array.from(e.target.files || [])
  mergeFiles.value.push(...newFiles)
}
const moveMergeFile = (index, delta) => {
  const target = index + delta
  if (target >= 0 && target < mergeFiles.value.length) {
    const item = mergeFiles.value.splice(index, 1)[0]
    mergeFiles.value.splice(target, 0, item)
  }
}
const removeMergeFile = (index) => {
  mergeFiles.value.splice(index, 1)
}
const handleMergePdf = async () => {
  if (mergeFiles.value.length < 2) return
  try {
    await converterStore.mergePdfs(mergeFiles.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Gộp PDF thất bại.')
  }
}

const onSplitFileSelected = (e) => {
  splitFile.value = e.target.files?.[0]
}
const handleSplitPdf = async () => {
  if (!splitFile.value || !splitRanges.value) return
  try {
    await converterStore.splitPdf(splitFile.value, splitRanges.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Tách PDF thất bại.')
  }
}

const onSingleImgSelected = (e) => {
  singleImgFile.value = e.target.files?.[0]
}
const handleConvertImage = async () => {
  if (!singleImgFile.value) return
  try {
    await converterStore.convertImage(singleImgFile.value, targetImgFormat.value, imgQuality.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Chuyển đổi ảnh thất bại.')
  }
}

const onMultiImgSelected = (e) => {
  multiImgFiles.value = Array.from(e.target.files || [])
}
const handleImagesToPdf = async () => {
  if (multiImgFiles.value.length === 0) return
  try {
    await converterStore.imagesToPdf(multiImgFiles.value)
  } catch (err) {
    alert(err.response?.data?.message || 'Ghép PDF thất bại.')
  }
}

const onMediaSelected = (e) => {
  selectedMediaFile.value = e.target.files?.[0]
}
const handleTranscribe = async () => {
  if (!selectedMediaFile.value) return
  try {
    const res = await converterStore.transcribeLecture(selectedMediaFile.value)
    transcriptionResult.value = res.transcription || ''
  } catch (err) {
    alert(err.response?.data?.message || 'Bóc băng thất bại.')
  }
}
</script>
