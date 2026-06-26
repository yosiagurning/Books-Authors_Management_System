<script setup lang="ts">
import BooksManager from '@/components/BooksManager.vue'
import { useAppRefresh } from '@/composables/useAppRefresh'

const { refreshToken, notifyDataChanged } = useAppRefresh()
</script>

<template>
  <BooksManager :refresh-token="refreshToken" @changed="notifyDataChanged" />
</template>
