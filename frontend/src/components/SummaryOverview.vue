<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'

import { ApiError, apiRequest } from '@/lib/api'
import { useToast } from '@/lib/toast'
import type { SummaryResponse } from '@/types'
import {
  Users,
  BookOpen,
  TrendingUp,
  Sparkles,
  RefreshCw,
  ArrowUpRight,
  UserCircle2,
  BookMarked,
  CalendarRange,
} from 'lucide-vue-next'

const loading = ref(false)
const error = ref('')
const summary = ref<SummaryResponse['data'] | null>(null)
const toast = useToast()

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
})

function formatDate(value: string | null) {
  if (!value) return '-'
  return formatter.format(new Date(value))
}

const avgBooksPerAuthor = computed(() => {
  if (!summary.value) return '0'
  const a = summary.value.totals.authors
  if (!a) return '0'
  return (summary.value.totals.books / a).toFixed(1)
})

async function loadSummary(silent = false) {
  loading.value = true
  if (!silent) error.value = ''

  try {
    const response = await apiRequest<SummaryResponse>('/dashboard/summary')
    summary.value = response.data
    if (silent) toast.success('Ringkasan diperbarui', 'Data terbaru sudah dimuat.')
  } catch (caughtError) {
    const msg =
      caughtError instanceof ApiError
        ? caughtError.message
        : 'Ringkasan dashboard gagal dimuat.'
    error.value = msg
    if (silent) toast.error('Gagal memuat', msg)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  void loadSummary()
})
</script>

<template>
  <section class="stack">
    <!-- Stats grid -->
    <div class="stats-grid stagger" data-testid="stats-grid">
      <article class="stat-card" data-testid="stat-authors">
        <div class="stat-card-head">
          <span>Total Authors</span>
          <span class="stat-card-icon"><Users :size="16" :stroke-width="2" /></span>
        </div>
        <strong v-if="summary" class="stat-card-value">{{ summary.totals.authors }}</strong>
        <div v-else-if="loading" class="skeleton mt-3 h-9 w-24" />
        <strong v-else class="stat-card-value">—</strong>
        <span class="stat-card-meta">Penulis aktif dalam katalog</span>
      </article>

      <article class="stat-card" data-testid="stat-books">
        <div class="stat-card-head">
          <span>Total Books</span>
          <span class="stat-card-icon"><BookOpen :size="16" :stroke-width="2" /></span>
        </div>
        <strong v-if="summary" class="stat-card-value">{{ summary.totals.books }}</strong>
        <div v-else-if="loading" class="skeleton mt-3 h-9 w-24" />
        <strong v-else class="stat-card-value">—</strong>
        <span class="stat-card-meta">Judul tersimpan di database</span>
      </article>

      <article class="stat-card" data-testid="stat-avg">
        <div class="stat-card-head">
          <span>Avg Books / Author</span>
          <span class="stat-card-icon"><TrendingUp :size="16" :stroke-width="2" /></span>
        </div>
        <strong v-if="summary" class="stat-card-value">{{ avgBooksPerAuthor }}</strong>
        <div v-else-if="loading" class="skeleton mt-3 h-9 w-24" />
        <strong v-else class="stat-card-value">—</strong>
        <span class="stat-card-meta">Rata-rata produktivitas penulis</span>
      </article>

      <article class="stat-card" data-testid="stat-recent">
        <div class="stat-card-head">
          <span>Recently Added</span>
          <span class="stat-card-icon"><Sparkles :size="16" :stroke-width="2" /></span>
        </div>
        <strong v-if="summary" class="stat-card-value">
          {{ (summary.recent_authors.length + summary.recent_books.length) }}
        </strong>
        <div v-else-if="loading" class="skeleton mt-3 h-9 w-24" />
        <strong v-else class="stat-card-value">—</strong>
        <span class="stat-card-meta">Item baru dalam 14 hari terakhir</span>
      </article>
    </div>

    <div v-if="error" class="message message-error">
      <span class="font-semibold">Gagal memuat:</span> {{ error }}
    </div>

    <!-- Two-column recent activity -->
    <div class="columns">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Author Terbaru</h3>
            <p class="panel-subtitle">Penulis yang baru ditambahkan atau diperbarui.</p>
          </div>
          <span class="badge">{{ summary?.recent_authors.length ?? 0 }} item</span>
        </div>

        <ul v-if="loading && !summary" class="list-reset mini-list stagger">
          <li v-for="n in 4" :key="n" class="mini-list-item">
            <div class="skeleton h-4 w-48" />
            <div class="skeleton mt-2 h-3 w-40" />
            <div class="skeleton mt-2 h-3 w-56" />
          </li>
        </ul>

        <ul v-else-if="summary?.recent_authors.length" class="list-reset mini-list stagger">
          <li
            v-for="author in summary.recent_authors"
            :key="author.id"
            class="mini-list-item"
            data-testid="recent-author-item"
          >
            <div class="flex items-start gap-3">
              <span class="stat-card-icon" style="height: 36px; width: 36px;">
                <UserCircle2 :size="18" :stroke-width="1.75" />
              </span>
              <div class="min-w-0 flex-1">
                <strong>{{ author.name }}</strong>
                <div class="meta">{{ author.nationality || 'Nationality belum diisi' }}</div>
                <div class="meta flex items-center gap-2 flex-wrap">
                  <span class="badge badge-neutral">{{ author.books_count }} book</span>
                  <span class="inline-flex items-center gap-1">
                    <CalendarRange :size="11" :stroke-width="2" />
                    {{ formatDate(author.updated_at) }}
                  </span>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else class="empty-state">
          <span class="empty-state-icon">
            <Users :size="18" :stroke-width="1.75" />
          </span>
          <strong>Belum ada author</strong>
          <span>Tambahkan author pertama dari modul Authors.</span>
        </div>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Book Terbaru</h3>
            <p class="panel-subtitle">Item terbaru yang siap dicek ulang atau diperbarui.</p>
          </div>
          <button
            class="button button-ghost button-sm"
            type="button"
            :disabled="loading"
            data-testid="refresh-summary"
            @click="loadSummary(true)"
          >
            <RefreshCw :size="13" :stroke-width="2" :class="{ 'animate-spin': loading }" />
            {{ loading ? 'Memuat…' : 'Refresh' }}
          </button>
        </div>

        <ul v-if="loading && !summary" class="list-reset mini-list stagger">
          <li v-for="n in 4" :key="n" class="mini-list-item">
            <div class="skeleton h-4 w-56" />
            <div class="skeleton mt-2 h-3 w-40" />
            <div class="skeleton mt-2 h-3 w-64" />
          </li>
        </ul>

        <ul v-else-if="summary?.recent_books.length" class="list-reset mini-list stagger">
          <li
            v-for="book in summary.recent_books"
            :key="book.id"
            class="mini-list-item"
            data-testid="recent-book-item"
          >
            <div class="flex items-start gap-3">
              <span class="stat-card-icon" style="height: 36px; width: 36px;">
                <BookMarked :size="18" :stroke-width="1.75" />
              </span>
              <div class="min-w-0 flex-1">
                <strong>{{ book.title }}</strong>
                <div class="meta">{{ book.author?.name || 'Author tidak tersedia' }}</div>
                <div class="meta flex items-center gap-2 flex-wrap">
                  <span class="inline-flex items-center gap-1">
                    <CalendarRange :size="11" :stroke-width="2" />
                    Terbit {{ formatDate(book.published_date) }}
                  </span>
                  <ArrowUpRight :size="11" :stroke-width="2" />
                  <span>Update {{ formatDate(book.updated_at) }}</span>
                </div>
              </div>
            </div>
          </li>
        </ul>

        <div v-else class="empty-state">
          <span class="empty-state-icon">
            <BookOpen :size="18" :stroke-width="1.75" />
          </span>
          <strong>Belum ada book</strong>
          <span>Tambahkan book pertama dari modul Books.</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}
</style>
