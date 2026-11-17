<template>
  <div class="min-h-screen pb-16">
    <div v-if="loading" class="text-center py-24 text-white font-mono">
      <div class="w-10 h-10 border-4 border-blueprint-accent border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
      Đang tải dữ liệu bộ đề tương tác...
    </div>

    <div v-else-if="error" class="max-w-md mx-auto my-20 p-6 blueprint-card text-center">
      <div class="text-4xl mb-3">⚠️</div>
      <h3 class="text-base font-bold text-red-600 mb-2">{{ error }}</h3>
      <router-link to="/study-sets" class="px-4 py-2 bg-blueprint text-white rounded text-xs font-bold inline-block mt-3">
        Quay lại danh sách bộ đề
      </router-link>
    </div>

    <StudyPlayer v-else-if="studyStore.currentSet" :study-set="studyStore.currentSet" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useStudyStore } from '@/stores/study'
import StudyPlayer from '@/components/StudyPlayer.vue'

const route = useRoute()
const studyStore = useStudyStore()

const loading = ref(true)
const error = ref(null)

onMounted(async () => {
  const id = route.params.id
  try {
    await studyStore.fetchStudySet(id)
  } catch (err) {
    error.value = 'Không tìm thấy bộ đề học tập này.'
  } finally {
    loading.value = false
  }
})
</script>
