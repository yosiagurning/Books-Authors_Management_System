export interface PaginationLink {
  url: string | null
  label: string
  active: boolean
}

export interface PaginatedResponse<T> {
  current_page: number
  data: T[]
  first_page_url: string
  from: number | null
  last_page: number
  last_page_url: string
  links: PaginationLink[]
  next_page_url: string | null
  path: string
  per_page: number
  prev_page_url: string | null
  to: number | null
  total: number
}

export interface AuthorListItem {
  id: number
  name: string
  slug: string
  bio: string | null
  birth_date: string | null
  nationality: string | null
  books_count: number
  created_at: string
  updated_at: string
}

export interface AuthorDetail extends AuthorListItem {
  books: BookListItem[]
}

export interface BookListItem {
  id: number
  author_id: number
  title: string
  slug: string
  description: string | null
  isbn: string | null
  published_date: string | null
  page_count: number | null
  created_at: string
  updated_at: string
  author?: {
    id: number
    name: string
    slug: string
  }
}

export interface BookDetail extends BookListItem {
  author: {
    id: number
    name: string
    slug: string
  }
}

export interface SummaryResponse {
  data: {
    totals: {
      authors: number
      books: number
    }
    recent_authors: AuthorListItem[]
    recent_books: BookListItem[]
  }
}

export interface AuthorResponse {
  data: AuthorDetail
  message?: string
}

export interface BookResponse {
  data: BookDetail
  message?: string
}

export interface MutationResponse<T> {
  data?: T
  message: string
}

export interface ValidationErrors {
  [key: string]: string[]
}
