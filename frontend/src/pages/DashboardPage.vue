<script setup lang="ts">
import SummaryOverview from '@/components/SummaryOverview.vue'
import { useAppRefresh } from '@/composables/useAppRefresh'

const { refreshToken } = useAppRefresh()
</script>

<template>
  <SummaryOverview :refresh-token="refreshToken" />
</template>
