<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

import { ApiError, apiRequest, buildQuery } from '@/lib/api'
import { useToast } from '@/lib/toast'
import type {
  AuthorOption,
  AuthorOptionsResponse,
  BookDetail,
  BookListItem,
  BookResponse,
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
  BookOpen,
  BookMarked,
  User,
  CalendarRange,
  Hash,
} from 'lucide-vue-next'

type SortKey = 'title' | 'created_at' | 'updated_at' | 'published_date' | 'author'

const loading = ref(false)
const detailLoading = ref(false)
const formSubmitting = ref(false)
const authorsLoading = ref(false)
const error = ref('')
const duplicateNotice = ref('')
const list = ref<PaginatedResponse<BookListItem> | null>(null)
const selectedBook = ref<BookDetail | null>(null)
const authorOptions = ref<AuthorOption[]>([])
const search = ref('')
const authorId = ref<number | ''>('')
const publishedDate = ref('')
const sortBy = ref<SortKey>('title')
const sortOrder = ref<'asc' | 'desc'>('asc')
const page = ref(1)
const mode = ref<'create' | 'edit'>('create')
const formOpen = ref(false)
const fieldErrors = ref<ValidationErrors>({})
const deleteDialogOpen = ref(false)
const deleteCandidate = ref<BookListItem | BookDetail | null>(null)
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
  title: '',
  author_id: '' as number | '',
  description: '',
  isbn: '',
  published_date: '',
  page_count: '' as number | '',
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
  form.title = ''
  form.author_id = ''
  form.description = ''
  form.isbn = ''
  form.published_date = ''
  form.page_count = ''
  fieldErrors.value = {}
  duplicateNotice.value = ''
  mode.value = 'create'
}

function fillForm(book: BookDetail) {
  form.title = book.title
  form.author_id = book.author_id
  form.description = book.description ?? ''
  form.isbn = book.isbn ?? ''
  form.published_date = normalizeDateInput(book.published_date)
  form.page_count = book.page_count ?? ''
  fieldErrors.value = {}
  mode.value = 'edit'
}

async function loadAuthorsForSelect() {
  authorsLoading.value = true
  try {
    const response = await apiRequest<AuthorOptionsResponse>('/authors/options')
    authorOptions.value = response.data
  } catch (caughtError) {
    const msg =
      caughtError instanceof ApiError
        ? caughtError.message
        : 'Daftar author untuk select gagal dimuat.'
    error.value = msg
  } finally {
    authorsLoading.value = false
  }
}

async function loadBooks() {
  loading.value = true
  error.value = ''
  try {
    const response = await apiRequest<PaginatedResponse<BookListItem>>(
      `/books${buildQuery({
        page: page.value,
        search: search.value,
        author_id: authorId.value,
        published_from: publishedDate.value,
        published_to: publishedDate.value,
        sort_by: sortBy.value,
        sort_order: sortOrder.value,
        per_page: 10,
      })}`,
    )
    list.value = response
  } catch (caughtError) {
    error.value = caughtError instanceof ApiError ? caughtError.message : 'Daftar book gagal dimuat.'
  } finally {
    loading.value = false
  }
}

async function loadBookDetail(bookId: number) {
  detailLoading.value = true
  error.value = ''
  try {
    const response = await apiRequest<BookResponse>(`/books/${bookId}`)
    selectedBook.value = response.data
    if (mode.value === 'edit') fillForm(response.data)
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Detail book gagal dimuat.'
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
    void loadBooks()
  }, 250)
}

function goToPage(nextPage: number) {
  if (!list.value || nextPage < 1 || nextPage > list.value.last_page) return
  page.value = nextPage
  void loadBooks()
}

function startCreate() {
  selectedBook.value = null
  resetForm()
  formOpen.value = true
}

async function startEdit(bookId: number) {
  mode.value = 'edit'
  formOpen.value = true
  await loadBookDetail(bookId)
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
  authorId.value = ''
  publishedDate.value = ''
  sortBy.value = 'title'
  sortOrder.value = 'asc'
}

function validateForm() {
  const next: ValidationErrors = {}
  if (!form.title.trim()) next.title = ['Judul wajib diisi.']
  if (form.author_id === '') next.author_id = ['Author wajib dipilih.']
  if (!form.published_date) next.published_date = ['Tanggal terbit wajib diisi.']
  if (form.page_count === '' || Number(form.page_count) < 1) {
    next.page_count = ['Jumlah halaman wajib diisi (minimal 1).']
  }
  const isbn = form.isbn.trim()
  if (!isbn) {
    next.isbn = ['ISBN wajib diisi.']
  } else if (!/^\d{13}$/.test(isbn)) {
    next.isbn = ['ISBN wajib 13 digit angka.']
  }
  if (!form.description.trim()) next.description = ['Deskripsi wajib diisi.']
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
    title: form.title.trim(),
    author_id: form.author_id,
    description: form.description.trim(),
    isbn: form.isbn.trim(),
    published_date: form.published_date,
    page_count: form.page_count,
  }

  try {
    if (mode.value === 'create') {
      const response = await apiRequest<MutationResponse<BookDetail>>('/books', {
        method: 'POST',
        body: payload,
      })
      toast.success('Book ditambahkan', response.message)
      resetForm()
    } else if (selectedBook.value) {
      const response = await apiRequest<MutationResponse<BookDetail>>(
        `/books/${selectedBook.value.id}`,
        { method: 'PUT', body: payload },
      )
      toast.success('Book diperbarui', response.message)
      await loadBookDetail(selectedBook.value.id)
      if (selectedBook.value) fillForm(selectedBook.value)
    }
    await loadBooks()
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
      toast.error('Gagal menyimpan', 'Book gagal disimpan.')
    }
  } finally {
    formSubmitting.value = false
  }
}

function requestDelete(book: BookListItem | BookDetail) {
  deleteCandidate.value = book
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
  if (undoDismissTimer) { clearTimeout(undoDismissTimer); undoDismissTimer = null }
  if (undoCountdownTimer) { clearInterval(undoCountdownTimer); undoCountdownTimer = null }
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
    const response = await apiRequest<MutationResponse<BookDetail>>(
      `/books/${undoNotice.value.id}/restore`,
      { method: 'POST' },
    )
    toast.success('Book dipulihkan', response.message)
    dismissUndoNotice()
    await loadBooks()
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Book gagal dipulihkan.'
    toast.error('Gagal undo', msg)
    dismissUndoNotice()
    await loadBooks()
  }
}

async function confirmDelete() {
  if (!deleteCandidate.value || deleteSubmitting.value) return
  deleteSubmitting.value = true
  deleteDialogError.value = ''
  try {
    const bookToDelete = deleteCandidate.value
    const deletedLabel = bookToDelete.title
    const response = await apiRequest<MutationResponse<UndoableDeletePayload>>(
      `/books/${bookToDelete.id}`,
      { method: 'DELETE' },
    )
    closeDeleteDialog()
    if (response.data) startUndoNotice(response.data, deletedLabel)
    if (selectedBook.value?.id === bookToDelete.id) {
      selectedBook.value = null
      resetForm()
    }
    await loadBooks()
  } catch (caughtError) {
    const msg = caughtError instanceof ApiError ? caughtError.message : 'Book gagal dihapus.'
    deleteDialogError.value = msg
  } finally {
    deleteSubmitting.value = false
  }
}

onMounted(() => {
  void loadBooks()
  void loadAuthorsForSelect()
})

onBeforeUnmount(() => {
  if (filterTimer) clearTimeout(filterTimer)
  dismissUndoNotice()
})

watch([search, authorId, publishedDate, sortBy, sortOrder], () => scheduleFilters())
</script>

<template>
  <div>
    <section class="columns">
    <div class="panel" data-testid="books-panel">
      <div class="panel-header">
        <div>
          <h3 class="panel-title">Manage Books</h3>
          <p class="panel-subtitle">
            Kelola katalog book, filter berdasarkan author, dan cek relasi detail-nya.
          </p>
        </div>
        <div class="actions">
          <button
            class="button button-secondary button-sm"
            type="button"
            :disabled="loading"
            data-testid="books-refresh"
            @click="loadBooks"
          >
            <RefreshCw :size="13" :stroke-width="2" :class="{ 'animate-spin': loading }" />
            {{ loading ? 'Memuat…' : 'Refresh' }}
          </button>
          <button class="button button-primary" type="button" data-testid="books-create" @click="startCreate">
            <Plus :size="14" :stroke-width="2.25" />
            Tambah Book
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
            <label for="book-search">Cari book</label>
            <div class="input-with-icon">
              <span class="input-with-icon-prefix">
                <Search :size="14" :stroke-width="2" />
              </span>
              <input
                id="book-search"
                v-model="search"
                class="input"
                type="search"
                placeholder="Judul book…"
                data-testid="books-search"
              />
            </div>
          </div>

          <div class="field">
            <label for="book-author">Filter author</label>
            <select id="book-author" v-model="authorId" class="select" :disabled="authorsLoading" data-testid="books-filter-author">
              <option value="">Semua author</option>
              <option v-for="author in authorOptions" :key="author.id" :value="author.id">
                {{ author.name }}
              </option>
            </select>
          </div>

          <div class="field">
            <label for="published-date">Tanggal terbit</label>
            <input id="published-date" v-model="publishedDate" class="input" type="date" data-testid="books-filter-date" />
          </div>
        </div>

        <div class="toolbar-group toolbar-group-secondary">
          <div class="field">
            <label>Tindakan</label>
            <button
              class="button button-ghost"
              type="button"
              data-testid="books-clear"
              @click="clearFilters"
            >
              <X :size="13" :stroke-width="2" />
              Reset filter
            </button>
          </div>
        </div>
      </div>

      <!-- Loading -->
      <div v-if="loading && !list" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Author</th>
              <th>Terbit</th>
              <th>Pages</th>
              <th class="text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="n in 6" :key="n">
              <td>
                <div class="skeleton h-4 w-52" />
                <div class="skeleton mt-2 h-3 w-28" />
              </td>
              <td><div class="skeleton h-4 w-32" /></td>
              <td><div class="skeleton h-4 w-24" /></td>
              <td><div class="skeleton h-4 w-14" /></td>
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

      <div v-else-if="list?.data.length">
        <div class="table-wrap">
          <table class="data-table" data-testid="books-table">
            <thead>
              <tr>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'title', 'is-desc': sortBy === 'title' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-title"
                    @click="toggleSort('title')"
                    @keydown.enter.prevent="toggleSort('title')"
                  >
                    Judul
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'author', 'is-desc': sortBy === 'author' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-author"
                    @click="toggleSort('author')"
                    @keydown.enter.prevent="toggleSort('author')"
                  >
                    Author
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th>
                  <span
                    :class="['th-sort', { 'is-active': sortBy === 'published_date', 'is-desc': sortBy === 'published_date' && sortOrder === 'desc' }]"
                    role="button"
                    tabindex="0"
                    data-testid="sort-published"
                    @click="toggleSort('published_date')"
                    @keydown.enter.prevent="toggleSort('published_date')"
                  >
                    Terbit
                    <ChevronUp :size="12" :stroke-width="2.5" class="th-sort-icon" />
                  </span>
                </th>
                <th>Pages</th>
                <th class="text-right">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="book in list.data" :key="book.id">
                <td>
                  <div class="flex items-start gap-3">
                    <span class="stat-card-icon" style="height: 34px; width: 34px;">
                      <BookMarked :size="16" :stroke-width="1.75" />
                    </span>
                    <div class="min-w-0">
                      <strong>{{ book.title }}</strong>
                      <div class="muted text-[12.5px] mt-0.5 font-mono">{{ book.isbn || 'ISBN belum diisi' }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <span v-if="book.author?.name" class="inline-flex items-center gap-1.5">
                    <User :size="12" :stroke-width="2" class="muted" />
                    {{ book.author.name }}
                  </span>
                  <span v-else class="muted">-</span>
                </td>
                <td><span class="num">{{ formatDate(book.published_date) }}</span></td>
                <td><span class="num">{{ book.page_count || '-' }}</span></td>
                <td>
                  <div class="actions table-actions">
                    <button class="button button-ghost button-sm" type="button" :data-testid="`detail-${book.id}`" @click="loadBookDetail(book.id)">
                      <Eye :size="13" :stroke-width="2" />
                      Detail
                    </button>
                    <button class="button button-secondary button-sm" type="button" :data-testid="`edit-${book.id}`" @click="startEdit(book.id)">
                      <Pencil :size="13" :stroke-width="2" />
                      Edit
                    </button>
                    <button class="button button-danger button-sm" type="button" :data-testid="`delete-${book.id}`" @click="requestDelete(book)">
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
            dari <strong>{{ list.total }}</strong> book
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
          <BookOpen :size="18" :stroke-width="1.75" />
        </span>
        <strong>{{ loading ? 'Memuat book…' : 'Belum ada book' }}</strong>
        <span>
          {{ loading ? 'Mohon tunggu sebentar.' : 'Tidak ada book yang cocok dengan filter saat ini.' }}
        </span>
      </div>
    </div>

    <!-- Right column -->
    <div class="stack">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Detail Book</h3>
            <p class="panel-subtitle">Pilih book dari tabel untuk meninjau detail dan author terkait.</p>
          </div>
        </div>

        <div v-if="detailLoading" class="detail-card">
          <div class="detail-grid">
            <div class="detail-item"><span>Judul</span><div class="skeleton mt-2 h-4 w-56" /></div>
            <div class="detail-item"><span>Author</span><div class="skeleton mt-2 h-4 w-40" /></div>
            <div class="detail-item"><span>Tanggal terbit</span><div class="skeleton mt-2 h-4 w-28" /></div>
            <div class="detail-item"><span>ISBN</span><div class="skeleton mt-2 h-4 w-36" /></div>
            <div class="detail-item"><span>Jumlah halaman</span><div class="skeleton mt-2 h-4 w-20" /></div>
            <div class="detail-item"><span>Deskripsi</span><div class="skeleton mt-2 h-4 w-64" /></div>
          </div>
        </div>

        <div v-else-if="selectedBook" class="detail-card" data-testid="book-detail">
          <div class="detail-grid">
            <div class="detail-item">
              <span>Judul</span>
              <strong>{{ selectedBook.title }}</strong>
            </div>
            <div class="detail-item">
              <span>Author</span>
              <strong class="inline-flex items-center gap-1.5">
                <User :size="13" :stroke-width="2" class="muted" />
                {{ selectedBook.author.name }}
              </strong>
            </div>
            <div class="detail-item">
              <span>Tanggal terbit</span>
              <strong class="inline-flex items-center gap-1.5">
                <CalendarRange :size="13" :stroke-width="2" class="muted" />
                {{ formatDate(selectedBook.published_date) }}
              </strong>
            </div>
            <div class="detail-item">
              <span>ISBN</span>
              <strong class="font-mono">{{ selectedBook.isbn || '-' }}</strong>
            </div>
            <div class="detail-item">
              <span>Jumlah halaman</span>
              <strong class="inline-flex items-center gap-1.5">
                <Hash :size="13" :stroke-width="2" class="muted" />
                {{ selectedBook.page_count || '-' }}
              </strong>
            </div>
            <div class="detail-item">
              <span>Deskripsi</span>
              <strong style="font-weight: 400; font-size: 13.5px; line-height: 1.55;">
                {{ selectedBook.description || 'Belum ada deskripsi.' }}
              </strong>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <span class="empty-state-icon">
            <Eye :size="18" :stroke-width="1.75" />
          </span>
          <strong>Belum ada book dipilih</strong>
          <span>Klik tombol Detail pada tabel book untuk melihat ringkasan lengkap.</span>
        </div>
      </div>
    </div>
  </section>

  <!-- Undo toast -->
  <transition name="modal">
    <div v-if="undoNotice" class="undo-toast" role="status" aria-live="polite">
      <div class="undo-toast-content">
        <div class="undo-toast-copy">
          <strong>Book dihapus sementara</strong>
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
      <div v-if="formOpen" class="scrim" data-testid="book-form-scrim" @click.self="closeForm">
        <aside class="drawer-shell" role="dialog" aria-modal="true">
        <div class="drawer-head">
          <div>
            <h3 class="panel-title">{{ mode === 'create' ? 'Tambah Book' : 'Edit Book' }}</h3>
            <p class="panel-subtitle">Hubungkan book ke author yang valid lalu simpan.</p>
          </div>
          <button class="button button-ghost button-icon" type="button" aria-label="Tutup" data-testid="book-form-close" @click="closeForm">
            <X :size="16" :stroke-width="2" />
          </button>
        </div>

        <div class="drawer-body">
          <div v-if="authorsLoading" class="message message-info">
            Memuat daftar author…
          </div>

          <div v-if="duplicateNotice" class="message message-warning">
            <AlertTriangle :size="14" :stroke-width="2" />
            {{ duplicateNotice }}
          </div>

          <form class="form-grid" novalidate @submit.prevent="submitForm">
            <div class="field field-full">
              <label for="book-title">Judul</label>
              <input id="book-title" v-model="form.title" class="input" type="text" data-testid="form-book-title" />
              <div v-if="fieldErrors.title?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.title[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-author-id">Author</label>
              <select id="book-author-id" v-model="form.author_id" class="select" :disabled="authorsLoading" data-testid="form-book-author">
                <option value="">Pilih author</option>
                <option v-for="author in authorOptions" :key="author.id" :value="author.id">
                  {{ author.name }}
                </option>
              </select>
              <div v-if="fieldErrors.author_id?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.author_id[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-published-date">Tanggal terbit</label>
              <input id="book-published-date" v-model="form.published_date" class="input" type="date" data-testid="form-book-date" />
              <div v-if="fieldErrors.published_date?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.published_date[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-page-count">Jumlah halaman</label>
              <input id="book-page-count" v-model="form.page_count" class="input" type="number" min="1" data-testid="form-book-pages" />
              <div v-if="fieldErrors.page_count?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.page_count[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-isbn">ISBN <span class="font-mono normal-case text-[10.5px] opacity-70">(13 digit)</span></label>
              <input
                id="book-isbn"
                v-model="form.isbn"
                class="input font-mono"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                pattern="[0-9]{13}"
                minlength="13"
                maxlength="13"
                data-testid="form-book-isbn"
              />
              <div v-if="fieldErrors.isbn?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.isbn[0] }}
              </div>
            </div>

            <div class="field field-full">
              <label for="book-description">Deskripsi</label>
              <textarea id="book-description" v-model="form.description" class="textarea" data-testid="form-book-desc" />
              <div v-if="fieldErrors.description?.length" class="error-text">
                <AlertTriangle :size="12" :stroke-width="2" />
                {{ fieldErrors.description[0] }}
              </div>
            </div>

            <div class="field field-full">
              <div class="actions justify-end">
                <button class="button button-secondary" type="button" data-testid="form-cancel" @click="closeForm">
                  Batal
                </button>
                <button class="button button-primary" type="submit" :disabled="formSubmitting" data-testid="form-submit">
                  {{ formSubmitting ? 'Menyimpan…' : mode === 'create' ? 'Simpan Book' : 'Update Book' }}
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
              Anda yakin ingin menghapus book <strong>{{ deleteCandidate?.title }}</strong>?
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
            {{ deleteSubmitting ? 'Menghapus…' : 'Hapus Book' }}
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
.normal-case { text-transform: none; letter-spacing: 0; }
</style>
