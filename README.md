# Book & Author Management System

Book & Author Management System adalah aplikasi full-stack untuk mengelola data `author` dan `book` tanpa autentikasi. Proyek ini dibangun untuk technical assessment Redcomm Full Stack Developer (Intern) dengan backend Laravel API dan frontend Vue.js.

## Fitur Utama

- Dashboard ringkas untuk total author dan total book.
- CRUD author lengkap dengan validasi dan detail relasi book.
- CRUD book lengkap dengan relasi ke author.
- Search, filter, sorting, dan pagination pada data utama.
- Rule bisnis untuk mencegah penghapusan author yang masih memiliki book.
- Seeder data contoh agar aplikasi bisa langsung dicoba.
- Feature test backend dan build frontend yang telah diverifikasi.

## Stack

### Backend

- Laravel 12
- PHP 8.2+
- SQLite

### Frontend

- Vue 3
- Vue Router
- Vite
- Tailwind CSS v4
- TypeScript

## Struktur Folder

```text
.
├── backend/        # Laravel REST API
├── frontend/       # Vue.js client app
├── PRD.md          # Product Requirements Document
├── README.md       # Setup dan overview proyek
├── EXPLANATION.md  # Penjelasan keputusan teknis dan UI/UX
└── AI_USAGE.md     # Ringkasan penggunaan AI
```

## Cara Menjalankan

### 1. Backend

Masuk ke folder backend:

```bash
cd backend
```

Install dependency jika belum:

```bash
composer install
```

Pastikan `.env` sudah ada. Jika belum:

```bash
copy .env.example .env
php artisan key:generate
```

Jalankan migrasi dan seed:

```bash
php artisan migrate:fresh --seed
```

Jalankan server:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Backend aktif di:

- `http://127.0.0.1:8000`

### 2. Frontend

Masuk ke folder frontend:

```bash
cd frontend
```

Install dependency:

```bash
npm install
```

Jalankan dev server:

```bash
npm run dev -- --host 127.0.0.1 --port 5173
```

Jika shell Windows mengalami masalah karena path project mengandung karakter `&`, jalankan langsung binary Vite:

```bash
node ./node_modules/vite/bin/vite.js --host 127.0.0.1 --port 5173
```

Frontend aktif di:

- `http://127.0.0.1:5173`

## Verifikasi

### Backend Tests

```bash
cd backend
php artisan test
```

### Frontend Build

```bash
cd frontend
npm run build
```

## API Utama

### Dashboard

- `GET /api/dashboard/summary`

### Authors

- `GET /api/authors`
- `POST /api/authors`
- `GET /api/authors/{id}`
- `PUT /api/authors/{id}`
- `DELETE /api/authors/{id}`

### Books

- `GET /api/books`
- `POST /api/books`
- `GET /api/books/{id}`
- `PUT /api/books/{id}`
- `DELETE /api/books/{id}`

## Query Parameters yang Didukung

### Authors

- `page`
- `per_page`
- `search`
- `sort_by`
- `sort_order`
- `has_books`

### Books

- `page`
- `per_page`
- `search`
- `sort_by`
- `sort_order`
- `author_id`
- `published_from`
- `published_to`

## Seed Data

Seeder backend akan menambahkan beberapa data author dan book contoh, termasuk:

- Andrea Hirata
- Tere Liye
- Pramoedya Ananta Toer

Tujuannya agar reviewer bisa langsung mencoba dashboard, list, detail, dan relasi data tanpa setup manual tambahan.

