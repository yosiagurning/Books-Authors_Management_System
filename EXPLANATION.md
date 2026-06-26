# EXPLANATION

## Ringkasan

Dokumen ini menjelaskan keputusan desain, arsitektur, dan UI/UX yang digunakan pada Book & Author Management System.

## Kenapa Struktur Produk Dibuat Seperti Ini

Brief awal meminta sistem CRUD sederhana untuk `author` dan `book` dengan pagination. Saya memilih untuk tetap menjaga inti brief tersebut, tetapi memperluas experience agar aplikasi terasa lebih realistis sebagai internal management tool.

Karena itu, implementasi tidak berhenti di CRUD dasar saja. Saya menambahkan:

- dashboard ringkas,
- detail view untuk author dan book,
- search dan filter,
- sorting,
- validasi yang lebih jelas,
- feedback sukses/gagal yang konsisten.

Tujuannya adalah menunjukkan kemampuan full-stack secara lebih utuh tanpa membuat scope menjadi terlalu jauh dari brief.

## Keputusan UI/UX

### 1. Layout Sidebar + Content

Saya menggunakan layout sidebar untuk navigasi utama karena aplikasi ini memiliki tiga area kerja yang jelas:

- Dashboard
- Authors
- Books

Pola ini memudahkan user berpindah modul tanpa kehilangan konteks.

### 2. Dashboard Ringkas

Dashboard dibuat sangat ringan dan fokus pada:

- total author,
- total book,
- daftar author terbaru,
- daftar book terbaru.

Alasan utamanya adalah memberi user gambaran cepat tentang kondisi data tanpa menjadikan dashboard terlalu berat.

### 3. List + Form + Detail dalam Satu Context

Untuk modul Authors dan Books, saya memilih pola:

- list di sisi utama,
- form create/edit,
- panel detail.

Alasan keputusan ini:

- user tidak perlu bolak-balik halaman hanya untuk melakukan operasi CRUD,
- reviewer bisa langsung melihat alur data dari list ke detail ke form,
- flow terasa cepat untuk aplikasi internal.

### 4. Feedback State yang Jelas

Saya menambahkan:

- loading state,
- success message,
- error message,
- empty state.

Ini penting karena sistem CRUD tanpa feedback yang jelas terasa kasar dan rawan membingungkan user.

## Keputusan Arsitektur Backend

### 1. Laravel REST API

Backend dibangun sebagai REST API murni agar frontend Vue bisa berdiri terpisah dan konsumsi data secara konsisten.

Endpoint utama dipisahkan menjadi:

- `/api/dashboard/summary`
- `/api/authors`
- `/api/books`

### 2. Validasi dengan Form Request

Saya menggunakan `FormRequest` untuk create dan update author/book karena:

- validation rules lebih rapi,
- controller lebih fokus ke flow aplikasi,
- perubahan aturan bisnis lebih mudah dikelola.

### 3. Relasi Database yang Tegas

Relasi yang dipakai:

- `Author hasMany Books`
- `Book belongsTo Author`

Saya juga menetapkan aturan bahwa author tidak bisa dihapus jika masih memiliki book. Ini lebih aman dibanding cascade delete untuk konteks management system sederhana, karena mengurangi risiko kehilangan data tidak sengaja.

### 4. Search, Filter, dan Sorting di Query Layer

Search, filter, sorting, dan pagination diletakkan di query controller agar:

- frontend tetap sederhana,
- API reusable,
- perilaku data konsisten untuk semua consumer.

## Keputusan Arsitektur Frontend

### 1. Vue 3 + Vite

Walaupun brief menyebut Nuxt.js, implementasi final menggunakan Vue 3 + Vite. Alasan utamanya:

- aplikasi ini tidak membutuhkan SSR,
- setup lebih ringan dan cepat,
- cukup kuat untuk kebutuhan dashboard CRUD internal,
- mempercepat iterasi implementasi dan debugging.

Keputusan ini tetap saya dokumentasikan secara eksplisit agar reviewer memahami deviasi dari brief awal.

### 2. Vue Router

Frontend awal masih berbasis satu halaman dengan conditional rendering. Saya kemudian meng-upgrade struktur menjadi multi-page menggunakan Vue Router agar:

- arsitektur lebih bersih,
- URL lebih representatif,
- navigasi lebih natural,
- setiap area kerja punya entry point yang jelas.

### 3. Shared Refresh State Sederhana

Saya menggunakan composable global kecil untuk refresh token bersama, bukan state management yang lebih berat, karena saat ini kebutuhan state lintas halaman masih sederhana:

- sinkronisasi dashboard,
- refresh list setelah create/update/delete,
- menjaga implementasi tetap ringan.

## Styling dan Tailwind

Brief mensyaratkan Tailwind CSS, sehingga frontend akhirnya saya migrasikan ke Tailwind CSS v4.

Namun saya tidak melakukan rewrite HTML besar-besaran ke utility classes inline. Sebagai gantinya, saya memakai pendekatan:

- komponen tetap menggunakan class semantik,
- `styles.css` diubah menjadi layer Tailwind dengan `@apply`.

Keuntungan pendekatan ini:

- markup Vue tetap rapi,
- konsistensi desain tetap terjaga,
- migrasi lebih aman,
- maintainability tetap bagus.

## Trade-Off yang Diambil

### 1. Tidak Menggunakan Pinia

Saya tidak menambahkan Pinia karena kebutuhan state saat ini belum cukup kompleks untuk membenarkan tambahan dependency tersebut.

Trade-off:

- implementasi lebih ringan,
- tetapi bila aplikasi berkembang besar, state management formal bisa lebih tepat.

### 2. Tidak Menggunakan Modal CRUD Penuh

Saya memilih panel form di halaman yang sama daripada modal-heavy UI.

Trade-off:

- lebih sederhana untuk di-maintain,
- lebih nyaman untuk input form yang panjang,
- tetapi sedikit lebih memakan ruang layar.

### 3. Belum Menambahkan Automated Test Frontend

Saya memprioritaskan stabilitas backend dan build frontend lebih dulu.

Trade-off:

- delivery lebih cepat,
- tetapi regression coverage di frontend masih bisa ditingkatkan.

## Fitur Tambahan yang Sudah Masuk

Di luar requirement paling minimal, implementasi saat ini sudah mencakup:

- dashboard summary,
- author detail,
- book detail,
- search/filter/sort,
- seed data,
- feature test API untuk workflow utama,
- Tailwind-based design system,
- multi-page frontend dengan router.

## Hal yang Masih Bisa Ditingkatkan

Jika proyek ini diteruskan, prioritas berikutnya yang paling bernilai adalah:

- menambahkan Vue Router guards atau URL sync yang lebih kaya,
- menambahkan test frontend,
- menambah soft delete,
- menambah export data,