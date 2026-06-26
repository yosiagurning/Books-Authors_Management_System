<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'

import { ApiError, apiRequest } from '@/lib/api'
import type { SummaryResponse } from '@/types'

const props = defineProps<{
  refreshToken: number
}>()

const loading = ref(false)
const error = ref('')
const summary = ref<SummaryResponse['data'] | null>(null)

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
})

function formatDate(value: string | null) {
  if (!value) {
    return '-'
  }

  return formatter.format(new Date(value))
}

async function loadSummary() {
  loading.value = true
  error.value = ''

  try {
    const response = await apiRequest<SummaryResponse>('/dashboard/summary')
    summary.value = response.data
  } catch (caughtError) {
    error.value =
      caughtError instanceof ApiError
        ? caughtError.message
        : 'Ringkasan dashboard gagal dimuat.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadSummary()
})

watch(
  () => props.refreshToken,
  () => {
    void loadSummary()
  },
)
</script>

<template>
  <section class="stack">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h3 class="panel-title">Dashboard Overview</h3>
          <p class="panel-subtitle">
            Ringkasan data utama untuk memantau kondisi katalog author dan book.
          </p>
        </div>
        <button class="button button-ghost" type="button" @click="loadSummary" :disabled="loading">
          {{ loading ? 'Memuat...' : 'Refresh' }}
        </button>
      </div>

      <div v-if="error" class="message message-error">
        {{ error }}
      </div>

      <div v-if="summary" class="stats-grid">
        <article class="stat-card">
          <span>Total Authors</span>
          <strong>{{ summary.totals.authors }}</strong>
        </article>

        <article class="stat-card">
          <span>Total Books</span>
          <strong>{{ summary.totals.books }}</strong>
        </article>
      </div>

      <div v-else-if="loading" class="message message-info">
        Mengambil data dashboard dari backend...
      </div>
    </div>

    <div class="columns">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Author Terbaru</h3>
            <p class="panel-subtitle">Daftar author yang paling akhir ditambahkan atau diperbarui.</p>
          </div>
          <span class="badge">{{ summary?.recent_authors.length ?? 0 }} item</span>
        </div>

        <ul v-if="summary?.recent_authors.length" class="list-reset mini-list">
          <li
            v-for="author in summary.recent_authors"
            :key="author.id"
            class="mini-list-item"
          >
            <strong>{{ author.name }}</strong>
            <div class="muted">{{ author.nationality || 'Nationality belum diisi' }}</div>
            <div class="muted">
              {{ author.books_count }} book • Update {{ formatDate(author.updated_at) }}
            </div>
          </li>
        </ul>

        <div v-else class="empty-state">
          Belum ada author. Tambahkan author pertama dari modul Authors.
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Book Terbaru</h3>
            <p class="panel-subtitle">Item terbaru yang siap dicek ulang atau diperbarui.</p>
          </div>
          <span class="badge">{{ summary?.recent_books.length ?? 0 }} item</span>
        </div>

        <ul v-if="summary?.recent_books.length" class="list-reset mini-list">
          <li
            v-for="book in summary.recent_books"
            :key="book.id"
            class="mini-list-item"
          >
            <strong>{{ book.title }}</strong>
            <div class="muted">{{ book.author?.name || 'Author tidak tersedia' }}</div>
            <div class="muted">
              Terbit {{ formatDate(book.published_date) }} • Update {{ formatDate(book.updated_at) }}
            </div>
          </li>
        </ul>

        <div v-else class="empty-state">
          Belum ada book. Tambahkan book pertama dari modul Books.
        </div>
      </div>
    </div>
  </section>
</template>
