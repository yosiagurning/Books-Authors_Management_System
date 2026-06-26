<script setup lang="ts">
import AuthorsManager from '@/components/AuthorsManager.vue'
import { useAppRefresh } from '@/composables/useAppRefresh'

const { refreshToken, notifyDataChanged } = useAppRefresh()
</script>

<template>
  <AuthorsManager :refresh-token="refreshToken" @changed="notifyDataChanged" />
</template>
