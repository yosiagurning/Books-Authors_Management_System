<script setup lang="ts">
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'

import { ApiError, apiRequest, buildQuery } from '@/lib/api'
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

const loading = ref(false)
const detailLoading = ref(false)
const formSubmitting = ref(false)
const authorsLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const list = ref<PaginatedResponse<BookListItem> | null>(null)
const selectedBook = ref<BookDetail | null>(null)
const authorOptions = ref<AuthorOption[]>([])
const search = ref('')
const authorId = ref<number | ''>('')
const publishedDate = ref('')
const sortBy = ref<'title' | 'created_at' | 'updated_at' | 'published_date' | 'author'>('title')
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

const formatter = new Intl.DateTimeFormat('id-ID', {
  dateStyle: 'medium',
})

function formatDate(value: string | null) {
  if (!value) {
    return '-'
  }

  return formatter.format(new Date(value))
}

function normalizeDateInput(value: string | null) {
  return value ? value.slice(0, 10) : ''
}

function resetMessages() {
  error.value = ''
  successMessage.value = ''
}

function resetForm() {
  form.title = ''
  form.author_id = ''
  form.description = ''
  form.isbn = ''
  form.published_date = ''
  form.page_count = ''
  fieldErrors.value = {}
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
    error.value =
      caughtError instanceof ApiError
        ? caughtError.message
        : 'Daftar author untuk select gagal dimuat.'
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

    if (mode.value === 'edit') {
      fillForm(response.data)
    }
  } catch (caughtError) {
    error.value =
      caughtError instanceof ApiError ? caughtError.message : 'Detail book gagal dimuat.'
  } finally {
    detailLoading.value = false
  }
}

function scheduleFilters() {
  if (filterTimer) {
    clearTimeout(filterTimer)
  }

  filterTimer = setTimeout(() => {
    page.value = 1
    void loadBooks()
  }, 250)
}

function goToPage(nextPage: number) {
  if (!list.value || nextPage < 1 || nextPage > list.value.last_page) {
    return
  }

  page.value = nextPage
  void loadBooks()
}

function startCreate() {
  selectedBook.value = null
  resetMessages()
  resetForm()
  formOpen.value = true
}

async function startEdit(bookId: number) {
  resetMessages()
  mode.value = 'edit'
  formOpen.value = true
  await loadBookDetail(bookId)
}

function closeForm() {
  formOpen.value = false
  resetForm()
}

function validateForm() {
  const next: ValidationErrors = {}

  if (!form.title.trim()) {
    next.title = ['Judul wajib diisi.']
  }

  if (form.author_id === '') {
    next.author_id = ['Author wajib dipilih.']
  }

  if (!form.published_date) {
    next.published_date = ['Tanggal terbit wajib diisi.']
  }

  if (form.page_count === '' || Number(form.page_count) < 1) {
    next.page_count = ['Jumlah halaman wajib diisi (minimal 1).']
  }

  const isbn = form.isbn.trim()
  if (!isbn) {
    next.isbn = ['ISBN wajib diisi.']
  } else if (!/^\d{13}$/.test(isbn)) {
    next.isbn = ['ISBN wajib 13 digit angka.']
  }

  if (!form.description.trim()) {
    next.description = ['Deskripsi wajib diisi.']
  }

  fieldErrors.value = next

  if (Object.keys(next).length) {
    error.value = 'Mohon lengkapi semua field.'
    return false
  }

  return true
}

async function submitForm() {
  formSubmitting.value = true
  resetMessages()
  fieldErrors.value = {}

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

      successMessage.value = response.message
      resetForm()
    } else if (selectedBook.value) {
      const response = await apiRequest<MutationResponse<BookDetail>>(
        `/books/${selectedBook.value.id}`,
        {
          method: 'PUT',
          body: payload,
        },
      )

      successMessage.value = response.message
      await loadBookDetail(selectedBook.value.id)
      if (selectedBook.value) {
        fillForm(selectedBook.value)
      }
    }

    await loadBooks()
    closeForm()
  } catch (caughtError) {
    if (caughtError instanceof ApiError) {
      error.value = caughtError.message
      fieldErrors.value = caughtError.errors
    } else {
      error.value = 'Book gagal disimpan.'
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

  const updateCountdown = () => {
    if (!undoNotice.value) {
      return
    }

    const remainingMs = Math.max(0, undoNotice.value.expiresAt - Date.now())
    undoNotice.value.secondsLeft = Math.max(0, Math.ceil(remainingMs / 1000))
    undoNotice.value.progressPercent = Math.max(
      0,
      Math.min(100, (remainingMs / undoNotice.value.durationMs) * 100),
    )
  }

  updateCountdown()
  undoCountdownTimer = setInterval(updateCountdown, 250)
  undoDismissTimer = setTimeout(() => {
    dismissUndoNotice()
  }, Math.max(0, expiresAt - Date.now()))
}

async function undoDelete() {
  if (!undoNotice.value) {
    return
  }

  try {
    const response = await apiRequest<MutationResponse<BookDetail>>(`/books/${undoNotice.value.id}/restore`, {
      method: 'POST',
    })

    successMessage.value = response.message
    dismissUndoNotice()
    await loadBooks()
  } catch (caughtError) {
    error.value =
      caughtError instanceof ApiError ? caughtError.message : 'Book gagal dipulihkan.'
    dismissUndoNotice()
    await loadBooks()
  }
}

async function confirmDelete() {
  if (!deleteCandidate.value || deleteSubmitting.value) {
    return
  }

  deleteSubmitting.value = true
  deleteDialogError.value = ''

  try {
    const bookToDelete = deleteCandidate.value
    const response = await apiRequest<MutationResponse<UndoableDeletePayload>>(`/books/${bookToDelete.id}`, {
      method: 'DELETE',
    })

    successMessage.value = ''
    error.value = ''
    if (response.data) {
      startUndoNotice(response.data, bookToDelete.title)
    }

    if (selectedBook.value?.id === bookToDelete.id) {
      selectedBook.value = null
      resetForm()
    }

    await loadBooks()
    closeDeleteDialog()
  } catch (caughtError) {
    deleteDialogError.value =
      caughtError instanceof ApiError ? caughtError.message : 'Book gagal dihapus.'
  } finally {
    deleteSubmitting.value = false
  }
}

onMounted(() => {
  void loadBooks()
  void loadAuthorsForSelect()
})

onBeforeUnmount(() => {
  if (filterTimer) {
    clearTimeout(filterTimer)
  }

  dismissUndoNotice()
})

watch([search, authorId, publishedDate, sortBy, sortOrder], () => {
  scheduleFilters()
})
</script>

<template>
  <section class="columns">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h3 class="panel-title">Manage Books</h3>
          <p class="panel-subtitle">
            Kelola katalog book, filter berdasarkan author, dan cek relasi detail-nya.
          </p>
        </div>
        <div class="actions">
          <button class="button button-ghost" type="button" @click="loadBooks" :disabled="loading">
            {{ loading ? 'Memuat...' : 'Refresh' }}
          </button>
          <button class="button button-primary" type="button" @click="startCreate">Tambah Book</button>
        </div>
      </div>

      <div v-if="successMessage" class="message message-success">
        {{ successMessage }}
      </div>

      <div v-if="undoNotice" class="undo-toast" role="status" aria-live="polite">
        <div class="undo-toast-content">
          <div class="undo-toast-copy">
            <strong>Book dihapus sementara</strong>
            <p>
              <span>{{ undoNotice.label }}</span> bisa dipulihkan dalam
              {{ undoNotice.secondsLeft }} detik.
            </p>
          </div>
          <button class="button button-secondary button-sm" type="button" @click="undoDelete">
            Undo
          </button>
        </div>
        <div class="undo-toast-progress">
          <div
            class="undo-toast-progress-bar"
            :style="{ width: `${undoNotice.progressPercent}%` }"
          />
        </div>
      </div>

      <div v-if="error" class="message message-error">
        {{ error }}
      </div>

      <div class="toolbar">
        <div class="toolbar-group toolbar-group-primary">
          <div class="field field-search">
            <label for="book-search">Cari book</label>
            <input
              id="book-search"
              v-model="search"
              class="input"
              type="search"
              placeholder="Judul book..."
            />
          </div>

          <div class="field">
            <label for="book-author">Filter author</label>
            <select id="book-author" v-model="authorId" class="select" :disabled="authorsLoading">
              <option value="">Semua author</option>
              <option v-for="author in authorOptions" :key="author.id" :value="author.id">
                {{ author.name }}
              </option>
            </select>
          </div>

          <div class="field">
            <label for="published-date">Tanggal terbit</label>
            <input id="published-date" v-model="publishedDate" class="input" type="date" />
          </div>
        </div>

        <div class="toolbar-group toolbar-group-secondary">
          <div class="field">
            <label for="book-sort">Urutkan</label>
            <select id="book-sort" v-model="sortBy" class="select">
              <option value="title">Judul</option>
              <option value="author">Author</option>
              <option value="published_date">Tanggal terbit</option>
              <option value="updated_at">Terakhir diubah</option>
              <option value="created_at">Tanggal dibuat</option>
            </select>
          </div>

          <div class="field">
            <label for="book-order">Arah</label>
            <select id="book-order" v-model="sortOrder" class="select">
              <option value="asc">Ascending</option>
              <option value="desc">Descending</option>
            </select>
          </div>
        </div>
      </div>

      <div v-if="loading && !list" class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Judul</th>
              <th>Author</th>
              <th>Terbit</th>
              <th>Pages</th>
              <th>Aksi</th>
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
                <div class="flex flex-wrap gap-2">
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
          <table class="data-table">
            <thead>
              <tr>
                <th>Judul</th>
                <th>Author</th>
                <th>Terbit</th>
                <th>Pages</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="book in list.data" :key="book.id">
                <td>
                  <strong>{{ book.title }}</strong>
                  <div class="muted">{{ book.isbn || 'ISBN belum diisi' }}</div>
                </td>
                <td>{{ book.author?.name || '-' }}</td>
                <td>{{ formatDate(book.published_date) }}</td>
                <td>{{ book.page_count || '-' }}</td>
                <td>
                  <div class="actions table-actions">
                    <button class="button button-ghost" type="button" @click="loadBookDetail(book.id)">
                      Detail
                    </button>
                    <button class="button button-secondary" type="button" @click="startEdit(book.id)">
                      Edit
                    </button>
                  <button class="button button-danger" type="button" @click="requestDelete(book)">
                      Hapus
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="pagination">
          <div class="pagination-meta">
            Menampilkan {{ list.from ?? 0 }}-{{ list.to ?? 0 }} dari {{ list.total }} book
          </div>

          <div class="actions">
            <button
              class="button button-ghost"
              type="button"
              :disabled="list.current_page <= 1"
              @click="goToPage(list.current_page - 1)"
            >
              Sebelumnya
            </button>
            <button
              class="button button-ghost"
              type="button"
              :disabled="list.current_page >= list.last_page"
              @click="goToPage(list.current_page + 1)"
            >
              Berikutnya
            </button>
          </div>
        </div>
      </div>

      <div v-else class="empty-state">
        {{ loading ? 'Memuat book...' : 'Belum ada book yang cocok dengan filter saat ini.' }}
      </div>
    </div>

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
            <div class="detail-item">
              <span>Judul</span>
              <div class="skeleton mt-2 h-4 w-56" />
            </div>
            <div class="detail-item">
              <span>Author</span>
              <div class="skeleton mt-2 h-4 w-40" />
            </div>
            <div class="detail-item">
              <span>Tanggal terbit</span>
              <div class="skeleton mt-2 h-4 w-28" />
            </div>
            <div class="detail-item">
              <span>ISBN</span>
              <div class="skeleton mt-2 h-4 w-36" />
            </div>
            <div class="detail-item">
              <span>Jumlah halaman</span>
              <div class="skeleton mt-2 h-4 w-20" />
            </div>
            <div class="detail-item">
              <span>Deskripsi</span>
              <div class="skeleton mt-2 h-4 w-64" />
            </div>
          </div>
        </div>

        <div v-else-if="selectedBook" class="detail-card">
          <div class="detail-grid">
            <div class="detail-item">
              <span>Judul</span>
              <strong>{{ selectedBook.title }}</strong>
            </div>
            <div class="detail-item">
              <span>Author</span>
              <strong>{{ selectedBook.author.name }}</strong>
            </div>
            <div class="detail-item">
              <span>Tanggal terbit</span>
              <strong>{{ formatDate(selectedBook.published_date) }}</strong>
            </div>
            <div class="detail-item">
              <span>ISBN</span>
              <strong>{{ selectedBook.isbn || '-' }}</strong>
            </div>
            <div class="detail-item">
              <span>Jumlah halaman</span>
              <strong>{{ selectedBook.page_count || '-' }}</strong>
            </div>
            <div class="detail-item">
              <span>Deskripsi</span>
              <strong>{{ selectedBook.description || 'Belum ada deskripsi.' }}</strong>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          Belum ada book yang dipilih. Klik tombol Detail pada tabel book.
        </div>
      </div>
    </div>
  </section>

  <transition name="drawer">
    <div
      v-if="formOpen"
      class="fixed inset-0 z-50 flex justify-end bg-slate-950/40 p-5 backdrop-blur-sm"
      @click.self="closeForm"
    >
      <div class="drawer h-full w-full max-w-[560px] overflow-y-auto">
        <div class="panel form-panel">
          <div class="panel-header">
            <div>
              <h3 class="panel-title">{{ mode === 'create' ? 'Tambah Book' : 'Edit Book' }}</h3>
              <p class="panel-subtitle">Hubungkan book ke author yang valid lalu simpan.</p>
            </div>
            <button class="button button-ghost" type="button" @click="closeForm">Tutup</button>
          </div>

          <div v-if="authorsLoading" class="message message-info">
            <div class="skeleton h-3 w-56" />
            <div class="skeleton mt-2 h-3 w-40" />
          </div>

          <form class="form-grid" @submit.prevent="submitForm">
            <div class="field">
              <label for="book-title">Judul</label>
              <input id="book-title" v-model="form.title" class="input" type="text" required />
              <div v-if="fieldErrors.title?.length" class="error-text">{{ fieldErrors.title[0] }}</div>
            </div>

            <div class="field">
              <label for="book-author-id">Author</label>
              <select
                id="book-author-id"
                v-model="form.author_id"
                class="select"
                :disabled="authorsLoading"
                required
              >
                <option value="">Pilih author</option>
                <option v-for="author in authorOptions" :key="author.id" :value="author.id">
                  {{ author.name }}
                </option>
              </select>
              <div v-if="fieldErrors.author_id?.length" class="error-text">
                {{ fieldErrors.author_id[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-published-date">Tanggal terbit</label>
              <input
                id="book-published-date"
                v-model="form.published_date"
                class="input"
                type="date"
                required
              />
              <div v-if="fieldErrors.published_date?.length" class="error-text">
                {{ fieldErrors.published_date[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-page-count">Jumlah halaman</label>
              <input
                id="book-page-count"
                v-model="form.page_count"
                class="input"
                type="number"
                min="1"
                required
              />
              <div v-if="fieldErrors.page_count?.length" class="error-text">
                {{ fieldErrors.page_count[0] }}
              </div>
            </div>

            <div class="field">
              <label for="book-isbn">ISBN</label>
              <input
                id="book-isbn"
                v-model="form.isbn"
                class="input"
                type="text"
                inputmode="numeric"
                autocomplete="off"
                pattern="[0-9]{13}"
                minlength="13"
                maxlength="13"
                required
              />
              <div v-if="fieldErrors.isbn?.length" class="error-text">{{ fieldErrors.isbn[0] }}</div>
            </div>

            <div class="field field-full">
              <label for="book-description">Deskripsi</label>
              <textarea id="book-description" v-model="form.description" class="textarea" required />
              <div v-if="fieldErrors.description?.length" class="error-text">
                {{ fieldErrors.description[0] }}
              </div>
            </div>

            <div class="field field-full">
              <div class="actions">
                <button class="button button-primary" type="submit" :disabled="formSubmitting">
                  {{ formSubmitting ? 'Menyimpan...' : mode === 'create' ? 'Simpan Book' : 'Update Book' }}
                </button>
                <button class="button button-secondary" type="button" @click="closeForm">
                  Batal
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </transition>

  <transition name="modal">
    <div
      v-if="deleteDialogOpen"
      class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-5 backdrop-blur-sm"
      @click.self="closeDeleteDialog"
    >
      <div class="modal max-h-full w-full max-w-[520px] overflow-y-auto">
        <div class="panel form-panel">
          <div class="panel-header">
            <div>
              <h3 class="panel-title">Konfirmasi Hapus</h3>
              <p class="panel-subtitle">Pastikan data yang dipilih sudah benar sebelum menghapus.</p>
            </div>
            <button class="button button-ghost" type="button" @click="closeDeleteDialog" :disabled="deleteSubmitting">
              Tutup
            </button>
          </div>

          <p class="muted">
            Anda yakin ingin menghapus book <strong>{{ deleteCandidate?.title }}</strong>? Tindakan ini tidak dapat dibatalkan.
          </p>

          <div v-if="deleteDialogError" class="message message-error mt-4">
            {{ deleteDialogError }}
          </div>

          <div class="mt-5 flex flex-wrap items-center justify-end gap-2">
            <button class="button button-secondary" type="button" @click="closeDeleteDialog" :disabled="deleteSubmitting">
              Batal
            </button>
            <button class="button button-danger" type="button" @click="confirmDelete" :disabled="deleteSubmitting">
              {{ deleteSubmitting ? 'Menghapus...' : 'Hapus' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>
