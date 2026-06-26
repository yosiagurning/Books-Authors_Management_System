import type { ValidationErrors } from '@/types'

const apiBaseUrl = (import.meta.env.VITE_API_BASE_URL ?? '/api').replace(/\/$/, '')

export class ApiError extends Error {
  status: number
  errors: ValidationErrors

  constructor(message: string, status: number, errors: ValidationErrors = {}) {
    super(message)
    this.name = 'ApiError'
    this.status = status
    this.errors = errors
  }
}

export function buildQuery(params: Record<string, string | number | boolean | null | undefined>) {
  const searchParams = new URLSearchParams()

  Object.entries(params).forEach(([key, value]) => {
    if (value === undefined || value === null || value === '') {
      return
    }

    searchParams.set(key, String(value))
  })

  const queryString = searchParams.toString()

  return queryString ? `?${queryString}` : ''
}

export async function apiRequest<T>(
  path: string,
  options: Omit<RequestInit, 'body'> & { body?: unknown } = {},
): Promise<T> {
  const { body, ...requestOptions } = options
  const headers = new Headers(options.headers ?? {})
  headers.set('Accept', 'application/json')

  const config: RequestInit = {
    ...requestOptions,
    headers,
  }

  if (body !== undefined) {
    headers.set('Content-Type', 'application/json')
    config.body = JSON.stringify(body)
  }

  const response = await fetch(`${apiBaseUrl}${path}`, config)
  const rawText = await response.text()
  const payload = rawText ? JSON.parse(rawText) : null

  if (!response.ok) {
    throw new ApiError(
      payload?.message ?? 'Terjadi kesalahan saat menghubungi server.',
      response.status,
      payload?.errors ?? {},
    )
  }

  return payload as T
}
