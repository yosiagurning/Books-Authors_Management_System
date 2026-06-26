<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'

import { ApiError, apiRequest, buildQuery } from '@/lib/api'
import type {
  AuthorListItem,
  BookDetail,
  BookListItem,
  BookResponse,
  MutationResponse,
  PaginatedResponse,
  ValidationErrors,
} from '@/types'

const props = defineProps<{
  refreshToken: number
}>()

const emit = defineEmits<{
  changed: []
}>()

const loading = ref(false)
const detailLoading = ref(false)
const formSubmitting = ref(false)
const authorsLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const list = ref<PaginatedResponse<BookListItem> | null>(null)
const selectedBook = ref<BookDetail | null>(null)
const authorOptions = ref<AuthorListItem[]>([])
const search = ref('')
const authorId = ref<number | ''>('')
const publishedFrom = ref('')
const publishedTo = ref('')
const sortBy = ref<'title' | 'created_at' | 'updated_at' | 'published_date' | 'author'>('title')
const sortOrder = ref<'asc' | 'desc'>('asc')
const page = ref(1)
const mode = ref<'create' | 'edit'>('create')
const fieldErrors = ref<ValidationErrors>({})

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
    const response = await apiRequest<PaginatedResponse<AuthorListItem>>(
      `/authors${buildQuery({ per_page: 50, sort_by: 'name', sort_order: 'asc' })}`,
    )

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
        published_from: publishedFrom.value,
        published_to: publishedTo.value,
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

function applyFilters() {
  page.value = 1
  void loadBooks()
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
}

async function startEdit(bookId: number) {
  resetMessages()
  mode.value = 'edit'
  await loadBookDetail(bookId)
}

async function submitForm() {
  formSubmitting.value = true
  resetMessages()
  fieldErrors.value = {}

  const payload = {
    title: form.title,
    author_id: form.author_id,
    description: form.description || null,
    isbn: form.isbn || null,
    published_date: form.published_date || null,
    page_count: form.page_count || null,
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
    emit('changed')
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

async function deleteBook(book: BookListItem | BookDetail) {
  const confirmed = window.confirm(`Hapus book "${book.title}"?`)

  if (!confirmed) {
    return
  }

  resetMessages()

  try {
    const response = await apiRequest<MutationResponse<null>>(`/books/${book.id}`, {
      method: 'DELETE',
    })

    successMessage.value = response.message

    if (selectedBook.value?.id === book.id) {
      selectedBook.value = null
      resetForm()
    }

    await loadBooks()
    emit('changed')
  } catch (caughtError) {
    error.value = caughtError instanceof ApiError ? caughtError.message : 'Book gagal dihapus.'
  }
}

onMounted(() => {
  void loadBooks()
  void loadAuthorsForSelect()
})

watch([sortBy, sortOrder, authorId], () => {
  applyFilters()
})

watch(
  () => props.refreshToken,
  () => {
    void loadBooks()
    void loadAuthorsForSelect()

    if (selectedBook.value) {
      void loadBookDetail(selectedBook.value.id)
    }
  },
)
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

      <div v-if="error" class="message message-error">
        {{ error }}
      </div>

      <div class="toolbar">
        <div class="toolbar-group">
          <div class="field">
            <label for="book-search">Cari book</label>
            <input
              id="book-search"
              v-model="search"
              class="input"
              type="search"
              placeholder="Judul book..."
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="field">
            <label for="book-author">Filter author</label>
            <select id="book-author" v-model="authorId" class="select">
              <option value="">Semua author</option>
              <option v-for="author in authorOptions" :key="author.id" :value="author.id">
                {{ author.name }}
              </option>
            </select>
          </div>
        </div>

        <div class="toolbar-group">
          <div class="field">
            <label for="published-from">Terbit dari</label>
            <input id="published-from" v-model="publishedFrom" class="input" type="date" />
          </div>

          <div class="field">
            <label for="published-to">Sampai</label>
            <input id="published-to" v-model="publishedTo" class="input" type="date" />
          </div>
        </div>

        <div class="toolbar-group">
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

          <div class="field">
            <label>&nbsp;</label>
            <button class="button button-secondary" type="button" @click="applyFilters">
              Terapkan
            </button>
          </div>
        </div>
      </div>

      <div v-if="list?.data.length">
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
                <div class="actions">
                  <button class="button button-ghost" type="button" @click="loadBookDetail(book.id)">
                    Detail
                  </button>
                  <button class="button button-secondary" type="button" @click="startEdit(book.id)">
                    Edit
                  </button>
                  <button class="button button-danger" type="button" @click="deleteBook(book)">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

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
            <h3 class="panel-title">{{ mode === 'create' ? 'Tambah Book' : 'Edit Book' }}</h3>
            <p class="panel-subtitle">Hubungkan book ke author yang valid lalu simpan.</p>
          </div>
          <button class="button button-ghost" type="button" @click="startCreate">Reset</button>
        </div>

        <div v-if="authorsLoading" class="message message-info">
          Mengambil daftar author untuk form...
        </div>

        <form class="form-grid" @submit.prevent="submitForm">
          <div class="field">
            <label for="book-title">Judul</label>
            <input id="book-title" v-model="form.title" class="input" type="text" />
            <div v-if="fieldErrors.title?.length" class="error-text">{{ fieldErrors.title[0] }}</div>
          </div>

          <div class="field">
            <label for="book-author-id">Author</label>
            <select id="book-author-id" v-model="form.author_id" class="select">
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
            />
            <div v-if="fieldErrors.page_count?.length" class="error-text">
              {{ fieldErrors.page_count[0] }}
            </div>
          </div>

          <div class="field">
            <label for="book-isbn">ISBN</label>
            <input id="book-isbn" v-model="form.isbn" class="input" type="text" />
            <div v-if="fieldErrors.isbn?.length" class="error-text">{{ fieldErrors.isbn[0] }}</div>
          </div>

          <div class="field field-full">
            <label for="book-description">Deskripsi</label>
            <textarea id="book-description" v-model="form.description" class="textarea" />
            <div v-if="fieldErrors.description?.length" class="error-text">
              {{ fieldErrors.description[0] }}
            </div>
          </div>

          <div class="field field-full">
            <div class="actions">
              <button class="button button-primary" type="submit" :disabled="formSubmitting">
                {{ formSubmitting ? 'Menyimpan...' : mode === 'create' ? 'Simpan Book' : 'Update Book' }}
              </button>
              <button class="button button-secondary" type="button" @click="startCreate">
                Batal
              </button>
            </div>
          </div>
        </form>
      </div>

      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">Detail Book</h3>
            <p class="panel-subtitle">Pilih book dari tabel untuk meninjau detail dan author terkait.</p>
          </div>
        </div>

        <div v-if="detailLoading" class="message message-info">Memuat detail book...</div>

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
</template>
