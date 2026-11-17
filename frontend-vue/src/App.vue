<template>
  <div class="min-h-screen blueprint-grid-bg flex flex-col font-sans">
    <Navbar v-if="!isPureSharedStudyView" />
    <main class="flex-1">
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Navbar from '@/components/Navbar.vue'

const route = useRoute()

// Do not show full navbar if viewing standalone public share without auth
const isPureSharedStudyView = computed(() => {
  return route.name === 'shared-study' && !localStorage.getItem('noteforge_token')
})
</script>
