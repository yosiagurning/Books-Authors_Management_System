<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

import { ApiError, apiRequest, buildQuery } from '@/lib/api'
import { useToast } from '@/lib/toast'
import type {
  AuthorDetail,
  AuthorListItem,
  AuthorResponse,
  MutationResponse,
  PaginatedResponse,
  UndoableDeletePayload,
  ValidationErrors,
} from '@/types'
import {
  Plus,
  RefreshCw,
  Search,
  Eye,
  Pencil,
  Trash2,
  ChevronUp,
  ChevronLeft,
  ChevronRight,
  X,
  AlertTriangle,
  Users,
  UserCircle2,
  BookOpen,
} from 'lucide-vue-next'

type SortKey = 'name' | 'created_at' | 'updated_at' | 'books_count'

const loading = ref(false)
const detailLoading = ref(false)
const formSubmitting = ref(false)
const error = ref('')
const duplicateNotice = ref('')
const list = ref<PaginatedResponse<AuthorListItem> | null>(null)
const selectedAuthor = ref<AuthorDetail | null>(null)
const search = ref('')
const hasBooks = ref<'all' | 'with' | 'without'>('all')
const sortBy = ref<SortKey>('name')
const sortOrder = ref<'asc' | 'desc'>('asc')
const page = ref(1)
const mode = ref<'create' | 'edit'>('create')
const formOpen = ref(false)
const fieldErrors = ref<ValidationErrors>({})
const deleteDialogOpen = ref(false)
const deleteCandidate = ref<AuthorListItem | AuthorDetail | null>(null)
const deleteSubmitting = ref(false)
const deleteDialogError = ref('')
const UNDO_WINDOW_SECONDS = 7
let filterTimer: ReturnType<typeof setTimeout> | null = null
let undoDismissTimer: ReturnType<typeof setTimeout> | null = null
let undoCountdownTimer: ReturnType<typeof setInterval> | null = null

const toast = useToast()

const undoNotice = ref<{
  id: number
  label: string
  expiresAt: number
  durationMs: number
  secondsLeft: number
  progressPercent: number
} | null>(null)

const form = reactive({
  name: '',
  bio: '',
  birth_date: '',
  nationality: '',
})

const formatter = new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' })

function formatDate(value: string | null) {
  if (!value) return '-'
  return formatter.format(new Date(value))
}

function normalizeDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

function resetForm() {
  form.name = ''
  form.bio = ''
  form.birth_date = ''
  form.nationality = ''
  fieldErrors.value = {}
  duplicateNotice.value = ''
  mode.value = 'create'
}

function fillForm(author: AuthorDetail) {
  form.name = author.name
  form.bio = author.bio ?? ''
  form.birth_date = normalizeDateInput(author.birth_date)
  form.nationality = author.nationality ?? ''
  fieldErrors.value = {}
  mode.value = 'edit'
}

async function loadAuthors() {
  loading.value = true
  error.value = ''

  try {
    const response = await apiRequest<PaginatedResponse<AuthorListItem>>(
      `/authors${buildQuery({
        page: page.value,
        search: search.value,
        has_books: hasBooks.value === 'all' ? '' : hasBooks.value === 'with',
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
        per_page: 10,
      })}`,
    )
    list.value = response
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Daftar author gagal dimuat.'
    error.value = msg
  } finally {
    loading.value = false
  }
}

async function loadAuthorDetail(authorId: number) {
  detailLoading.value = true
  error.value = ''

  try {
    const response = await apiRequest<AuthorResponse>(`/authors/${authorId}`)
    selectedAuthor.value = response.data
    if (mode.value === 'edit') {
      fillForm(response.data)
    }
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Detail author gagal dimuat.'
    error.value = msg
    toast.error('Gagal memuat detail', msg)
  } finally {
    detailLoading.value = false
  }
}

function scheduleFilters() {
  if (filterTimer) clearTimeout(filterTimer)
  filterTimer = setTimeout(() => {
    page.value = 1
    void loadAuthors()
  }, 250)
}

function goToPage(nextPage: number) {
  if (!list.value || nextPage < 1 || nextPage > list.value.last_page) return
  page.value = nextPage
  void loadAuthors()
}

function startCreate() {
  selectedAuthor.value = null
  resetForm()
  formOpen.value = true
}

async function startEdit(authorId: number) {
  mode.value = 'edit'
  formOpen.value = true
  await loadAuthorDetail(authorId)
}

function closeForm() {
  formOpen.value = false
  resetForm()
}

function toggleSort(key: SortKey) {
  if (sortBy.value === key) {
    sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = key
    sortOrder.value = 'asc'
  }
}

function clearFilters() {
  search.value = ''
  hasBooks.value = 'all'
  sortBy.value = 'name'
  sortOrder.value = 'asc'
}

function validateForm() {
  const next: ValidationErrors = {}
  if (!form.name.trim()) next.name = ['Nama wajib diisi.']
  if (!form.nationality.trim()) next.nationality = ['Nationality wajib diisi.']
  if (!form.birth_date) next.birth_date = ['Tanggal lahir wajib diisi.']
  if (!form.bio.trim()) next.bio = ['Bio wajib diisi.']
  fieldErrors.value = next
  if (Object.keys(next).length) {
    toast.error('Form belum lengkap', 'Mohon lengkapi semua field yang ditandai.')
    return false
  }
  return true
}

function findDuplicateMessage(errors: ValidationErrors) {
  for (const messages of Object.values(errors)) {
    for (const message of messages) {
      if (/sudah ada|sudah digunakan/i.test(message)) {
        return message
      }
    }
  }

  return ''
}

async function submitForm() {
  formSubmitting.value = true
  fieldErrors.value = {}
  duplicateNotice.value = ''

  if (!validateForm()) {
    formSubmitting.value = false
    return
  }

  const payload = {
    name: form.name.trim(),
    bio: form.bio.trim(),
    birth_date: form.birth_date,
    nationality: form.nationality.trim(),
  }

  try {
    if (mode.value === 'create') {
      const response = await apiRequest<MutationResponse<AuthorDetail>>('/authors', {
        method: 'POST',
        body: payload,
      })
      toast.success('Author ditambahkan', response.message)
      resetForm()
    } else if (selectedAuthor.value) {
      const response = await apiRequest<MutationResponse<AuthorDetail>>(
        `/authors/${selectedAuthor.value.id}`,
        { method: 'PUT', body: payload },
      )
      toast.success('Author diperbarui', response.message)
      await loadAuthorDetail(selectedAuthor.value.id)
      if (selectedAuthor.value) fillForm(selectedAuthor.value)
    }
    await loadAuthors()
    closeForm()
  } catch (caughtError) {
    if (caughtError instanceof ApiError) {
      fieldErrors.value = caughtError.errors
      const duplicateMessage = findDuplicateMessage(caughtError.errors)
      if (duplicateMessage) {
        duplicateNotice.value = duplicateMessage
        toast.warning('Data duplikat terdeteksi', duplicateMessage, 6500)
      } else {
        toast.error('Gagal menyimpan', caughtError.message)
      }
    } else {
      toast.error('Gagal menyimpan', 'Author gagal disimpan.')
    }
  } finally {
    formSubmitting.value = false
  }
}

function requestDelete(author: AuthorListItem | AuthorDetail) {
  deleteCandidate.value = author
  deleteDialogError.value = ''
  deleteDialogOpen.value = true
}

function closeDeleteDialog() {
  deleteDialogOpen.value = false
  deleteCandidate.value = null
  deleteDialogError.value = ''
  deleteSubmitting.value = false
}

function clearUndoTimers() {
  if (undoDismissTimer) {
    clearTimeout(undoDismissTimer)
    undoDismissTimer = null
  }
  if (undoCountdownTimer) {
    clearInterval(undoCountdownTimer)
    undoCountdownTimer = null
  }
}

function dismissUndoNotice() {
  clearUndoTimers()
  undoNotice.value = null
}

function startUndoNotice(payload: UndoableDeletePayload, label: string) {
  clearUndoTimers()
  const expiresAt = new Date(payload.undo_expires_at).getTime()
  const durationMs = Math.max(1, expiresAt - Date.now())
  undoNotice.value = {
    id: payload.id,
    label,
    expiresAt,
    durationMs,
    secondsLeft: UNDO_WINDOW_SECONDS,
    progressPercent: 100,
  }
  const update = () => {
    if (!undoNotice.value) return
    const remainingMs = Math.max(0, undoNotice.value.expiresAt - Date.now())
    undoNotice.value.secondsLeft = Math.max(0, Math.ceil(remainingMs / 1000))
    undoNotice.value.progressPercent = Math.max(
      0,
      Math.min(100, (remainingMs / undoNotice.value.durationMs) * 100),
    )
  }
  update()
  undoCountdownTimer = setInterval(update, 250)
  undoDismissTimer = setTimeout(dismissUndoNotice, Math.max(0, expiresAt - Date.now()))
}

async function undoDelete() {
  if (!undoNotice.value) return
  try {
    const response = await apiRequest<MutationResponse<AuthorDetail>>(
      `/authors/${undoNotice.value.id}/restore`,
      { method: 'POST' },
    )
    toast.success('Author dipulihkan', response.message)
    dismissUndoNotice()
    await loadAuthors()
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Author gagal dipulihkan.'
    toast.error('Gagal undo', msg)
    dismissUndoNotice()
    await loadAuthors()
  }
}

async function confirmDelete() {
  if (!deleteCandidate.value || deleteSubmitting.value) return
  deleteSubmitting.value = true
  deleteDialogError.value = ''
  try {
    const authorToDelete = deleteCandidate.value
    const deletedLabel = authorToDelete.name
    const response = await apiRequest<MutationResponse<UndoableDeletePayload>>(
      `/authors/${authorToDelete.id}`,
      { method: 'DELETE' },
    )
    closeDeleteDialog()
    if (response.data) startUndoNotice(response.data, deletedLabel)
    if (selectedAuthor.value?.id === authorToDelete.id) {
      selectedAuthor.value = null
      resetForm()
    }
    await loadAuthors()
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Author gagal dihapus.'
    deleteDialogError.value = msg
  } finally {
    deleteSubmitting.value = false
  }
}

onMounted(() => { void loadAuthors() })

onBeforeUnmount(() => {
  if (filterTimer) clearTimeout(filterTimer)
  dismissUndoNotice()
})

watch(search, () => scheduleFilters())
watch([hasBooks, sortBy, sortOrder], () => scheduleFilters())
</script>

<template>
  <div>
    <section class="columns">
    <div class="panel" data-testid="authors-panel">
      <div class="panel-header">
        <div>
          <h3 class="panel-title">Manage Authors</h3>
          <p class="panel-subtitle">
            Cari, filter, lihat detail, lalu kelola master data author dari satu tempat.
          </p>
        </div>
        <div class="actions">
          <button
            class="button button-secondary button-sm"
            type="button"
            :disabled="loading"
            data-testid="authors-refresh"
            @click="loadAuthors"
          >
            <RefreshCw :size="13" :stroke-width="2" :class="{ 'animate-spin': loading }" />
            {{ loading ? 'Memuat…' : 'Refresh' }}
          </button>
          <button
            class="button button-primary"
            type="button"
            data-testid="authors-create"
            @click="startCreate"
          >
            <Plus :size="14" :stroke-width="2.25" />
            Tambah Author
          </button>
        </div>
      </div>

      <div v-if="error" class="message message-error">
        <AlertTriangle :size="14" :stroke-width="2" />
        {{ error }}
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="toolbar-group toolbar-group-primary">
          <div class="field field-search">
            <label for="author-search">Cari author</label>
            <div class="input-with-icon">
              <span class="input-with-icon-prefix">
                <Search :size="14" :stroke-width="2" />
              </span>
              <input
                id="author-search"
                v-model="search"
                class="input"
                type="search"
                placeholder="Nama author…"
                data-testid="authors-search"
              />
            </div>
          </div>

          <div class="field">
            <label for="author-has-books">Status book</label>
            <select id="author-has-books" v-model="hasBooks" class="select" data-testid="authors-status">
              <option value="all">Semua</option>
              <option value="with">Punya book</option>
              <option value="without">Belum punya book</option>
            </select>
          </div>
        </div>

        <div class="toolbar-group toolbar-group-secondary">
          <div class="field">
            <label>Tindakan</label>
            <button
              class="button button-ghost"
              type="button"
              data-testid="authors-clear"
              @click="clearFilters"
            >
              <X :size="13" :stroke-width="2" />
              Reset filter
            </button>
          </div>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-if="loading && !list" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Nationality</th>
              <th>Books</th>
              <th>Updated</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in 6" :key="n">
              <td>
                <div class="skeleton h-4 w-48" />
                <div class="skeleton mt-2 h-3 w-64" />
              </td>
              <td><div class="skeleton h-4 w-32" /></td>
              <td><div class="skeleton h-4 w-10" /></td>
              <td><div class="skeleton h-4 w-24" /></td>
              <td>
                <div class="flex flex-wrap justify-end gap-2">
                  <div class="skeleton h-8 w-16" />
                  <div class="skeleton h-8 w-14" />
                  <div class="skeleton h-8 w-16" />
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Data -->
      <div v-else-if="list?.data.length">
        <div class="table-wrap">
          <table class="data-table" data-testid="authors-table">
            <thead>
              <tr>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'name', 'is-desc': sortBy === 'name' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-name"
                    @click="toggleSort('name')"
                    @keydown.enter.prevent="toggleSort('name')"
                  >
                    Nama
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th>Nationality</th>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'books_count', 'is-desc': sortBy === 'books_count' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-books-count"
                    @click="toggleSort('books_count')"
                    @keydown.enter.prevent="toggleSort('books_count')"
                  >
                    Books
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'updated_at', 'is-desc': sortBy === 'updated_at' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-updated"
                    @click="toggleSort('updated_at')"
                    @keydown.enter.prevent="toggleSort('updated_at')"
                  >
                    Updated
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th class="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="author in list.data" :key="author.id">
                <td>
                  <div class="flex items-start gap-3">
                    <span class="stat-card-icon" style="height: 34px; width: 34px;">
                      <UserCircle2 :size="16" :stroke-width="1.75" />
                    </span>
                    <div class="min-w-0">
                      <strong>{{ author.name }}</strong>
                      <div class="muted text-[12.5px] mt-0.5 line-clamp-2">
                        {{ author.bio || 'Bio belum diisi' }}
                      </div>
                    </div>
                  </div>
                </td>
                <td>
                  <span v-if="author.nationality" class="badge badge-neutral">{{ author.nationality }}</span>
                  <span v-else class="muted">-</span>
                </td>
                <td>
                  <span class="num">{{ author.books_count }}</span>
                </td>
                <td><span class="num">{{ formatDate(author.updated_at) }}</span></td>
                <td>
                  <div class="actions table-actions">
                    <button
                      class="button button-ghost button-sm"
                      type="button"
                      :data-testid="`detail-${author.id}`"
                      @click="loadAuthorDetail(author.id)"
                    >
                      <Eye :size="13" :stroke-width="2" />
                      Detail
                    </button>
                    <button
                      class="button button-secondary button-sm"
                      type="button"
                      :data-testid="`edit-${author.id}`"
                      @click="startEdit(author.id)"
                    >
                      <Pencil :size="13" :stroke-width="2" />
                      Edit
                    </button>
                    <button
                      class="button button-danger button-sm"
                      type="button"
                      :data-testid="`delete-${author.id}`"
                      @click="requestDelete(author)"
                    >
                      <Trash2 :size="13" :stroke-width="2" />
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <div class="pagination-meta">
            Menampilkan
            <strong>{{ list.from ?? 0 }}–{{ list.to ?? 0 }}</strong>
            dari <strong>{{ list.total }}</strong> author
          </div>

          <div class="pager" role="navigation" aria-label="Pagination">
            <button
              type="button"
              :disabled="list.current_page <= 1"
              aria-label="Sebelumnya"
              data-testid="page-prev"
              @click="goToPage(list.current_page - 1)"
            >
              <ChevronLeft :size="14" :stroke-width="2.25" />
            </button>
            <span class="pager-page is-active" :data-testid="`page-${list.current_page}`">
              {{ list.current_page }}
            </span>
            <span class="pager-page muted">/ {{ list.last_page }}</span>
            <button
              type="button"
              :disabled="list.current_page >= list.last_page"
              aria-label="Berikutnya"
              data-testid="page-next"
              @click="goToPage(list.current_page + 1)"
            >
              <ChevronRight :size="14" :stroke-width="2.25" />
            </button>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        <span class="empty-state-icon">
          <Users :size="18" :stroke-width="1.75" />
        </span>
        <strong>{{ loading ? 'Memuat author…' : 'Belum ada author' }}</strong>
        <span>
          {{ loading ? 'Mohon tunggu sebentar.' : 'Tidak ada author yang cocok dengan filter saat ini.' }}
        </span>
      </div>
    </div>

    <!-- Right column: detail -->
    <div class="stack">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Detail Author</h3>
            <p class="panel-subtitle">Pilih author dari tabel untuk melihat relasi book-nya.</p>
          </div>
        </div>

        <div v-if="detailLoading" class="detail-card">
          <div class="detail-grid">
            <div class="detail-item">
              <span>Nama</span>
              <div class="skeleton mt-2 h-4 w-56" />
            </div>
            <div class="detail-item">
              <span>Nationality</span>
              <div class="skeleton mt-2 h-4 w-40" />
            </div>
            <div class="detail-item">
              <span>Tanggal lahir</span>
              <div class="skeleton mt-2 h-4 w-32" />
            </div>
            <div class="detail-item">
              <span>Jumlah book</span>
              <div class="skeleton mt-2 h-4 w-20" />
            </div>
            <div class="detail-item">
              <span>Bio</span>
              <div class="skeleton mt-2 h-4 w-64" />
            </div>
          </div>
        </div>

        <div v-else-if="selectedAuthor" class="detail-card" data-testid="author-detail">
          <div class="detail-grid">
            <div class="detail-item">
              <span>Nama</span>
              <strong>{{ selectedAuthor.name }}</strong>
            </div>
            <div class="detail-item">
              <span>Nationality</span>
              <strong>{{ selectedAuthor.nationality || '-' }}</strong>
            </div>
            <div class="detail-item">
              <span>Tanggal lahir</span>
              <strong>{{ formatDate(selectedAuthor.birth_date) }}</strong>
            </div>
            <div class="detail-item">
              <span>Jumlah book</span>
              <strong>{{ selectedAuthor.books_count }}</strong>
            </div>
            <div class="detail-item">
              <span>Bio</span>
              <strong>{{ selectedAuthor.bio || 'Belum ada bio.' }}</strong>
            </div>
          </div>

          <div class="divider" />

          <div class="flex items-center justify-between">
            <h4 class="panel-title text-[15px]">Books by Author</h4>
            <span class="badge badge-neutral">{{ selectedAuthor.books.length }} item</span>
          </div>

          <ul v-if="selectedAuthor.books.length" class="list-reset mini-list mt-3 stagger">
            <li v-for="book in selectedAuthor.books" :key="book.id" class="mini-list-item">
              <div class="flex items-start gap-3">
                <span class="stat-card-icon" style="height: 32px; width: 32px;">
                  <BookOpen :size="14" :stroke-width="1.75" />
                </span>
                <div class="min-w-0">
                  <strong>{{ book.title }}</strong>
                  <div class="meta">
                    {{ book.published_date ? formatDate(book.published_date) : 'Tanggal terbit belum diisi' }}
                  </div>
                </div>
              </div>
            </li>
          </ul>

          <div v-else class="empty-state mt-3">
            <span class="empty-state-icon">
              <BookOpen :size="16" :stroke-width="1.75" />
            </span>
            <span>Author ini belum memiliki book.</span>
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="empty-state-icon">
            <Eye :size="18" :stroke-width="1.75" />
          </span>
          <strong>Belum ada author dipilih</strong>
          <span>Klik tombol Detail pada tabel author untuk melihat ringkasan lengkap.</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Undo toast -->
  <transition name="modal">
    <div v-if="undoNotice" class="undo-toast" role="status" aria-live="polite">
      <div class="undo-toast-content">
        <div class="undo-toast-copy">
          <strong>Author dihapus sementara</strong>
          <p>
            <span>{{ undoNotice.label }}</span> bisa dipulihkan dalam
            <span>{{ undoNotice.secondsLeft }}s</span>.
          </p>
        </div>
        <button class="button button-secondary button-sm" type="button" data-testid="undo-delete" @click="undoDelete">
          Undo
        </button>
      </div>
      <div class="undo-toast-progress">
        <div class="undo-toast-progress-bar" :style="{ width: `${undoNotice.progressPercent}%` }" />
      </div>
    </div>
    </transition>

    <!-- Drawer Form -->
    <transition name="drawer">
      <div v-if="formOpen" class="scrim" data-testid="author-form-scrim" @click.self="closeForm">
        <aside class="drawer-shell" role="dialog" aria-modal="true">
        <div class="drawer-head">
          <div>
            <h3 class="panel-title">
              {{ mode === 'create' ? 'Tambah Author' : 'Edit Author' }}
            </h3>
            <p class="panel-subtitle">Isi data author lalu simpan ke database.</p>
          </div>
          <button class="button button-ghost button-icon" type="button" aria-label="Tutup" data-testid="author-form-close" @click="closeForm">
            <X :size="16" :stroke-width="2" />
          </button>
        </div>

        <div class="drawer-body">
          <div v-if="duplicateNotice" class="message message-warning">
            <AlertTriangle :size="14" :stroke-width="2" />
            {{ duplicateNotice }}
          </div>
          <form class="form-grid" novalidate @submit.prevent="submitForm">
            <div class="field">
              <label for="author-name">Nama</label>
              <input id="author-name" v-model="form.name" class="input" type="text" data-testid="form-author-name" />
              <div v-if="fieldErrors.name?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.name[0] }}
              </div>
            </div>

            <div class="field">
              <label for="author-nationality">Nationality</label>
              <input id="author-nationality" v-model="form.nationality" class="input" type="text" data-testid="form-author-nationality" />
              <div v-if="fieldErrors.nationality?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.nationality[0] }}
              </div>
            </div>

            <div class="field">
              <label for="author-birth-date">Tanggal lahir</label>
              <input id="author-birth-date" v-model="form.birth_date" class="input" type="date" data-testid="form-author-birth" />
              <div v-if="fieldErrors.birth_date?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.birth_date[0] }}
              </div>
            </div>

            <div class="field field-full">
              <label for="author-bio">Bio</label>
              <textarea id="author-bio" v-model="form.bio" class="textarea" data-testid="form-author-bio" />
              <div v-if="fieldErrors.bio?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.bio[0] }}
              </div>
            </div>

            <div class="field field-full">
              <div class="actions justify-end">
                <button class="button button-secondary" type="button" data-testid="form-cancel" @click="closeForm">
                  Batal
                </button>
                <button class="button button-primary" type="submit" :disabled="formSubmitting" data-testid="form-submit">
                  {{ formSubmitting ? 'Menyimpan…' : mode === 'create' ? 'Simpan Author' : 'Update Author' }}
                </button>
              </div>
            </div>
          </form>
        </div>
        </aside>
      </div>
    </transition>

    <!-- Delete confirmation -->
    <transition name="modal">
      <div v-if="deleteDialogOpen" class="modal-shell" @click.self="closeDeleteDialog">
        <div class="scrim" />
        <div class="modal-card" role="alertdialog" aria-modal="true" data-testid="delete-modal">
        <div class="flex items-start gap-3">
          <span class="stat-card-icon" style="background: var(--color-danger-50); color: var(--color-danger-500); border-color: var(--color-danger-200);">
            <Trash2 :size="16" :stroke-width="2" />
          </span>
          <div class="min-w-0 flex-1">
            <h3 class="panel-title">Konfirmasi Hapus</h3>
            <p class="panel-subtitle mt-1">
              Anda yakin ingin menghapus author <strong>{{ deleteCandidate?.name }}</strong>?
              Tindakan ini dapat di-undo dalam 7 detik.
            </p>
          </div>
        </div>

        <div v-if="deleteDialogError" class="message message-error mt-5">
          {{ deleteDialogError }}
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-end gap-2">
          <button class="button button-secondary" type="button" :disabled="deleteSubmitting" data-testid="delete-cancel" @click="closeDeleteDialog">
            Batal
          </button>
          <button class="button button-danger" type="button" :disabled="deleteSubmitting" data-testid="delete-confirm" @click="confirmDelete">
            <Trash2 :size="13" :stroke-width="2" />
            {{ deleteSubmitting ? 'Menghapus…' : 'Hapus Author' }}
          </button>
        </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.animate-spin { animation: spin 1s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
