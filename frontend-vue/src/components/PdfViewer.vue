<template>
  <div class="h-full flex flex-col bg-slate-900 border-r border-blueprint-paperBorder/30">
    <!-- Toolbar -->
    <div class="bg-slate-800 border-b border-slate-700 px-4 py-2.5 flex items-center justify-between text-xs text-slate-200">
      <div class="flex items-center gap-2 truncate">
        <span class="font-bold text-blueprint-accent truncate max-w-[200px]">{{ title || 'Tài liệu giáo trình' }}</span>
        <span class="px-2 py-0.5 rounded bg-slate-700 text-[10px] font-mono">PDF Viewer</span>
      </div>

      <div class="flex items-center gap-3">
        <div class="flex items-center gap-1.5 bg-slate-900 px-2.5 py-1 rounded border border-slate-700 font-mono">
          <button
            @click="prevPage"
            :disabled="currentPage <= 1"
            class="hover:text-blueprint-accent disabled:opacity-30 disabled:hover:text-inherit"
          >
            ◀
          </button>
          <span>Trang {{ currentPage }} / {{ totalPages }}</span>
          <button
            @click="nextPage"
            :disabled="currentPage >= totalPages"
            class="hover:text-blueprint-accent disabled:opacity-30 disabled:hover:text-inherit"
          >
            ▶
          </button>
        </div>

        <button
          @click="openExternal"
          v-if="docUrl"
          class="px-2 py-1 bg-slate-700 hover:bg-slate-600 rounded text-[11px]"
          title="Mở trong tab mới"
        >
          ↗
        </button>
      </div>
    </div>

    <!-- Viewer Body -->
    <div class="flex-1 bg-slate-950 overflow-y-auto p-4 flex flex-col items-center" ref="scrollContainer">
      <div v-if="!docUrl" class="my-auto text-center text-slate-500 py-12">
        <div class="text-4xl mb-3">📄</div>
        <p class="text-sm">Chọn một tài liệu trong danh sách để xem nội dung</p>
      </div>

      <!-- PDF Canvas / Native Embed with Anchor support -->
      <div v-else class="w-full h-full flex flex-col items-center">
        <!-- Interactive PDF Iframe with page fragment -->
        <iframe
          :src="iframeSrc"
          class="w-full h-full rounded shadow-2xl border border-slate-800 bg-white"
          title="PDF Document"
        ></iframe>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  docUrl: {
    type: String,
    default: null,
  },
  title: {
    type: String,
    default: '',
  },
  initialPage: {
    type: Number,
    default: 1,
  },
  totalPages: {
    type: Number,
    default: 1,
  },
})

const currentPage = ref(props.initialPage || 1)
const scrollContainer = ref(null)

const iframeSrc = computed(() => {
  if (!props.docUrl) return ''
  return `${props.docUrl}#page=${currentPage.value}&zoom=page-fit`
})

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--
  }
}

const nextPage = () => {
  if (currentPage.value < props.totalPages) {
    currentPage.value++
  }
}

const jumpToPage = (pageNum) => {
  if (pageNum >= 1) {
    currentPage.value = pageNum
  }
}

const openExternal = () => {
  if (props.docUrl) {
    window.open(props.docUrl, '_blank')
  }
}

watch(
  () => props.initialPage,
  (newVal) => {
    if (newVal) {
      currentPage.value = newVal
    }
  }
)

defineExpose({
  jumpToPage,
})
</script>
