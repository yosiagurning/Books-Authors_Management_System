<script setup lang="ts">
import { onMounted, reactive, ref, watch } from 'vue'

import { ApiError, apiRequest, buildQuery } from '@/lib/api'
import type {
  AuthorDetail,
  AuthorListItem,
  AuthorResponse,
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
const error = ref('')
const successMessage = ref('')
const list = ref<PaginatedResponse<AuthorListItem> | null>(null)
const selectedAuthor = ref<AuthorDetail | null>(null)
const search = ref('')
const hasBooks = ref<'all' | 'with' | 'without'>('all')
const sortBy = ref<'name' | 'created_at' | 'updated_at' | 'books_count'>('name')
const sortOrder = ref<'asc' | 'desc'>('asc')
const page = ref(1)
const mode = ref<'create' | 'edit'>('create')
const fieldErrors = ref<ValidationErrors>({})

const form = reactive({
  name: '',
  bio: '',
  birth_date: '',
  nationality: '',
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
  form.name = ''
  form.bio = ''
  form.birth_date = ''
  form.nationality = ''
  fieldErrors.value = {}
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
    error.value =
      caughtError instanceof ApiError ? caughtError.message : 'Daftar author gagal dimuat.'
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
    error.value =
      caughtError instanceof ApiError ? caughtError.message : 'Detail author gagal dimuat.'
  } finally {
    detailLoading.value = false
  }
}

function applyFilters() {
  page.value = 1
  void loadAuthors()
}

function goToPage(nextPage: number) {
  if (!list.value || nextPage < 1 || nextPage > list.value.last_page) {
    return
  }

  page.value = nextPage
  void loadAuthors()
}

function startCreate() {
  selectedAuthor.value = null
  resetMessages()
  resetForm()
}

async function startEdit(authorId: number) {
  resetMessages()
  mode.value = 'edit'
  await loadAuthorDetail(authorId)
}

async function submitForm() {
  formSubmitting.value = true
  resetMessages()
  fieldErrors.value = {}

  const payload = {
    name: form.name,
    bio: form.bio || null,
    birth_date: form.birth_date || null,
    nationality: form.nationality || null,
  }

  try {
    if (mode.value === 'create') {
      const response = await apiRequest<MutationResponse<AuthorDetail>>('/authors', {
        method: 'POST',
        body: payload,
      })

      successMessage.value = response.message
      resetForm()
    } else if (selectedAuthor.value) {
      const response = await apiRequest<MutationResponse<AuthorDetail>>(
        `/authors/${selectedAuthor.value.id}`,
        {
          method: 'PUT',
          body: payload,
        },
      )

      successMessage.value = response.message
      await loadAuthorDetail(selectedAuthor.value.id)
      if (selectedAuthor.value) {
        fillForm(selectedAuthor.value)
      }
    }

    await loadAuthors()
    emit('changed')
  } catch (caughtError) {
    if (caughtError instanceof ApiError) {
      error.value = caughtError.message
      fieldErrors.value = caughtError.errors
    } else {
      error.value = 'Author gagal disimpan.'
    }
  } finally {
    formSubmitting.value = false
  }
}

async function deleteAuthor(author: AuthorListItem | AuthorDetail) {
  const confirmed = window.confirm(`Hapus author "${author.name}"?`)

  if (!confirmed) {
    return
  }

  resetMessages()

  try {
    const response = await apiRequest<MutationResponse<null>>(`/authors/${author.id}`, {
      method: 'DELETE',
    })

    successMessage.value = response.message

    if (selectedAuthor.value?.id === author.id) {
      selectedAuthor.value = null
      resetForm()
    }

    await loadAuthors()
    emit('changed')
  } catch (caughtError) {
    error.value =
      caughtError instanceof ApiError ? caughtError.message : 'Author gagal dihapus.'
  }
}

onMounted(() => {
  void loadAuthors()
})

watch([hasBooks, sortBy, sortOrder], () => {
  applyFilters()
})

watch(
  () => props.refreshToken,
  () => {
    void loadAuthors()

    if (selectedAuthor.value) {
      void loadAuthorDetail(selectedAuthor.value.id)
    }
  },
)
</script>

<template>
  <section class="columns">
    <div class="panel">
      <div class="panel-header">
        <div>
          <h3 class="panel-title">Manage Authors</h3>
          <p class="panel-subtitle">
            Cari, filter, lihat detail, lalu kelola master data author dari satu tempat.
          </p>
        </div>
        <div class="actions">
          <button class="button button-ghost" type="button" @click="loadAuthors" :disabled="loading">
            {{ loading ? 'Memuat...' : 'Refresh' }}
          </button>
          <button class="button button-primary" type="button" @click="startCreate">Tambah Author</button>
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
            <label for="author-search">Cari author</label>
            <input
              id="author-search"
              v-model="search"
              class="input"
              type="search"
              placeholder="Nama author..."
              @keyup.enter="applyFilters"
            />
          </div>

          <div class="field">
            <label for="author-has-books">Status book</label>
            <select id="author-has-books" v-model="hasBooks" class="select">
              <option value="all">Semua</option>
              <option value="with">Punya book</option>
              <option value="without">Belum punya book</option>
            </select>
          </div>
        </div>

        <div class="toolbar-group">
          <div class="field">
            <label for="author-sort">Urutkan</label>
            <select id="author-sort" v-model="sortBy" class="select">
              <option value="name">Nama</option>
              <option value="books_count">Jumlah book</option>
              <option value="updated_at">Terakhir diubah</option>
              <option value="created_at">Tanggal dibuat</option>
            </select>
          </div>

          <div class="field">
            <label for="author-order">Arah</label>
            <select id="author-order" v-model="sortOrder" class="select">
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
              <th>Nama</th>
              <th>Nationality</th>
              <th>Books</th>
              <th>Updated</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="author in list.data" :key="author.id">
              <td>
                <strong>{{ author.name }}</strong>
                <div class="muted">{{ author.bio || 'Bio belum diisi' }}</div>
              </td>
              <td>{{ author.nationality || '-' }}</td>
              <td>{{ author.books_count }}</td>
              <td>{{ formatDate(author.updated_at) }}</td>
              <td>
                <div class="actions">
                  <button
                    class="button button-ghost"
                    type="button"
                    @click="loadAuthorDetail(author.id)"
                  >
                    Detail
                  </button>
                  <button class="button button-secondary" type="button" @click="startEdit(author.id)">
                    Edit
                  </button>
                  <button class="button button-danger" type="button" @click="deleteAuthor(author)">
                    Hapus
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="pagination">
          <div class="pagination-meta">
            Menampilkan {{ list.from ?? 0 }}-{{ list.to ?? 0 }} dari {{ list.total }} author
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
        {{ loading ? 'Memuat author...' : 'Belum ada author yang cocok dengan filter saat ini.' }}
      </div>
    </div>

    <div class="stack">
      <div class="panel">
        <div class="panel-header">
          <div>
            <h3 class="panel-title">
              {{ mode === 'create' ? 'Tambah Author' : 'Edit Author' }}
            </h3>
            <p class="panel-subtitle">Isi data author lalu simpan ke database.</p>
          </div>
          <button class="button button-ghost" type="button" @click="startCreate">Reset</button>
        </div>

        <form class="form-grid" @submit.prevent="submitForm">
          <div class="field">
            <label for="author-name">Nama</label>
            <input id="author-name" v-model="form.name" class="input" type="text" />
            <div v-if="fieldErrors.name?.length" class="error-text">{{ fieldErrors.name[0] }}</div>
          </div>

          <div class="field">
            <label for="author-nationality">Nationality</label>
            <input
              id="author-nationality"
              v-model="form.nationality"
              class="input"
              type="text"
            />
            <div v-if="fieldErrors.nationality?.length" class="error-text">
              {{ fieldErrors.nationality[0] }}
            </div>
          </div>

          <div class="field">
            <label for="author-birth-date">Tanggal lahir</label>
            <input
              id="author-birth-date"
              v-model="form.birth_date"
              class="input"
              type="date"
            />
            <div v-if="fieldErrors.birth_date?.length" class="error-text">
              {{ fieldErrors.birth_date[0] }}
            </div>
          </div>

          <div class="field field-full">
            <label for="author-bio">Bio</label>
            <textarea id="author-bio" v-model="form.bio" class="textarea" />
            <div v-if="fieldErrors.bio?.length" class="error-text">{{ fieldErrors.bio[0] }}</div>
          </div>

          <div class="field field-full">
            <div class="actions">
              <button class="button button-primary" type="submit" :disabled="formSubmitting">
                {{ formSubmitting ? 'Menyimpan...' : mode === 'create' ? 'Simpan Author' : 'Update Author' }}
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
            <h3 class="panel-title">Detail Author</h3>
            <p class="panel-subtitle">Pilih author dari tabel untuk melihat relasi book-nya.</p>
          </div>
        </div>

        <div v-if="detailLoading" class="message message-info">Memuat detail author...</div>

        <div v-else-if="selectedAuthor" class="detail-card">
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

          <div class="panel" style="margin-top: 16px; padding: 16px">
            <div class="panel-header">
              <div>
                <h4 class="panel-title">Books by Author</h4>
                <p class="panel-subtitle">Relasi book yang tersimpan pada author ini.</p>
              </div>
            </div>

            <ul v-if="selectedAuthor.books.length" class="list-reset mini-list">
              <li v-for="book in selectedAuthor.books" :key="book.id" class="mini-list-item">
                <strong>{{ book.title }}</strong>
                <div class="muted">
                  {{ book.published_date ? formatDate(book.published_date) : 'Tanggal terbit belum diisi' }}
                </div>
              </li>
            </ul>

            <div v-else class="empty-state">Author ini belum memiliki book.</div>
          </div>
        </div>

        <div v-else class="empty-state">
          Belum ada author yang dipilih. Klik tombol Detail pada tabel author.
        </div>
      </div>
    </div>
  </section>
</template>
