# Product Requirements Document

## Informasi Dokumen

- Nama Produk: Book & Author Management System
- Sumber Brief: `Redcomm Test for Full Stack Developer (Intern).pdf`
- Jenis Dokumen: Product Requirements Document (PRD)
- Status: Draft v2
- Bahasa: Indonesia
- Revisi Utama: Requirement produk diperluas dan frontend direvisi menjadi Vue.js

## Ringkasan Produk

Book & Author Management System adalah aplikasi web full-stack untuk mengelola data author dan book tanpa autentikasi. Sistem ini tidak hanya mendukung CRUD dasar, tetapi juga pengalaman pengelolaan data yang lebih matang melalui pencarian, filter, sorting, halaman detail, statistik ringkas, validasi yang kuat, serta feedback UI yang konsisten.

Backend dibangun menggunakan Laravel sebagai REST API, sedangkan frontend menggunakan Vue.js dan Tailwind CSS untuk menghadirkan antarmuka yang cepat, modular, dan mudah dipelihara.

## Latar Belakang

Brief teknikal Redcomm meminta kandidat membangun sistem manajemen buku dan penulis yang sederhana namun solid. Walaupun requirement inti dalam brief berfokus pada CRUD dan pagination, produk ini dapat dibuat lebih komprehensif tanpa melenceng jauh dari ruang lingkup utama, sehingga lebih merepresentasikan kemampuan full-stack secara menyeluruh.

Fokus evaluasi tetap berada pada:

- kualitas struktur kode,
- desain database dan relasi data,
- kualitas REST API,
- kualitas UI/UX dan state management,
- kejelasan dokumentasi dan penjelasan keputusan teknis.

Karena itu, PRD revisi ini mendefinisikan produk yang lebih lengkap, namun tetap realistis untuk implementasi bertahap.

## Visi Produk

Menyediakan aplikasi internal sederhana namun polished untuk mengelola author dan book secara efisien, dengan pengalaman pengguna yang cepat, jelas, dan minim friction.

## Tujuan Produk

### Tujuan Utama

- Menyediakan sistem pengelolaan data author dan book yang lengkap dan intuitif.
- Menampilkan relasi one-to-many antara author dan book secara jelas pada UI dan API.
- Mempermudah user melakukan pencarian, penyaringan, dan navigasi data yang jumlahnya terus bertambah.
- Menjaga implementasi tetap modular, maintainable, dan mudah dijalankan secara lokal.

### Tujuan Bisnis / Evaluasi

- Menunjukkan kemampuan membangun aplikasi full-stack end-to-end dengan kualitas produksi.
- Menunjukkan pemahaman desain API RESTful yang konsisten.
- Menunjukkan kedewasaan dalam menyusun state management dan UX flow.
- Menunjukkan kemampuan dokumentasi, komunikasi asumsi, dan keputusan arsitektural.

## Success Metrics

Produk dianggap berhasil jika memenuhi indikator berikut:

- User dapat menyelesaikan seluruh flow CRUD author dan book tanpa blocker.
- User dapat menemukan data lebih cepat melalui search, filter, dan sorting.
- Halaman daftar tetap nyaman digunakan saat data bertambah karena pagination berjalan baik.
- Halaman detail author dan book menampilkan relasi data yang jelas.
- Validasi backend dan error handling frontend konsisten dan mudah dipahami.
- Frontend Vue.js berjalan stabil dengan struktur komponen yang rapi.
- Backend API berjalan lokal dan terdokumentasi dengan baik.
- README cukup jelas untuk setup dari nol tanpa instruksi tambahan lisan.

## Persona Utama

### 1. Evaluator Teknis

Evaluator menilai kualitas implementasi, arsitektur, dan dokumentasi. Mereka membutuhkan aplikasi yang:

- mudah dijalankan,
- mudah dipahami secara struktur,
- memiliki flow utama yang stabil,
- menunjukkan perhatian pada detail UX dan maintainability.

### 2. Operator Data

Operator data bertugas mengelola master data author dan book. Mereka membutuhkan kemampuan untuk:

- melihat daftar data secara cepat,
- menemukan data tertentu melalui pencarian dan filter,
- menambah dan memperbarui data tanpa error membingungkan,
- memeriksa relasi author-book dari halaman detail,
- menghapus data dengan aman.

### 3. Reviewer Produk Internal

Reviewer produk ingin menilai apakah sistem sudah layak dipresentasikan sebagai mini internal tool. Mereka peduli pada:

- konsistensi antar halaman,
- kejelasan navigasi,
- keterbacaan data,
- kualitas feedback sistem.

## Prinsip Produk

- Sederhana untuk digunakan, kuat di implementasi.
- Cepat dipahami oleh user baru.
- Konsisten antara backend dan frontend.
- Aman secara data meski tanpa autentikasi.
- Mudah dikembangkan untuk iterasi berikutnya.

## Scope Produk

### In Scope

- CRUD author melalui backend API dan frontend UI.
- CRUD book melalui backend API dan frontend UI.
- Relasi one-to-many antara author dan book.
- Pagination pada endpoint list dan tampilan daftar.
- Search, filter, dan sorting pada list author dan book.
- Halaman detail author yang menampilkan daftar book terkait.
- Halaman detail book yang menampilkan informasi author terkait.
- Statistik ringkas pada dashboard atau section overview.
- Validasi input dan business rules yang tegas.
- Empty state, loading state, dan error state pada frontend.
- Dokumentasi setup lokal dan keputusan arsitektur.

### Out of Scope

- Login, register, logout, atau role-based authorization.
- Upload file, image cover, atau media management.
- Import/export CSV atau Excel.
- Integrasi pembayaran atau third-party marketplace.
- Audit log lengkap tingkat enterprise.
- Notifikasi email atau push notification.

## Asumsi Produk

Karena brief tidak menentukan detail domain secara rinci, PRD ini menggunakan asumsi yang masih relevan dan masuk akal:

- Sistem dipakai oleh user internal tanpa autentikasi.
- Author adalah entitas master yang dapat memiliki banyak book.
- Book wajib terhubung ke satu author aktif/valid.
- Search, filter, dan statistik termasuk dalam perluasan produk, bukan keharusan dari brief asli.
- Frontend resmi untuk implementasi revisi ini adalah Vue.js, bukan Nuxt.js.

## Scope Rilis

### MVP

- Dashboard ringkas.
- CRUD author.
- CRUD book.
- Search dasar.
- Filter dasar.
- Sorting dasar.
- Pagination.
- Halaman detail.
- Validasi dan error handling.

### Post-MVP Opsional

- Caching granular.
- Bulk actions.
- Soft delete dan restore.
- Export data.
- Analytics tambahan.

## Requirement Fungsional

### Modul Dashboard

#### FR-01: Melihat Ringkasan Sistem

Sistem harus menyediakan ringkasan cepat agar user memahami kondisi data tanpa membuka semua halaman daftar.

Kriteria:

- Menampilkan total author.
- Menampilkan total book.
- Menampilkan author terbaru atau book terbaru.
- Menampilkan shortcut menuju halaman author dan book.

### Modul Author

#### FR-02: Melihat Daftar Author

Sistem harus memungkinkan user melihat daftar author dalam bentuk list atau table yang informatif.

Kriteria:

- Menampilkan data author secara paginated.
- Menampilkan informasi utama author seperti nama, bio singkat, jumlah book, dan tanggal update terakhir.
- Menyediakan aksi detail, edit, dan delete untuk setiap author.
- Menyediakan state kosong jika belum ada data.

#### FR-03: Mencari Author

Sistem harus memungkinkan user mencari author berdasarkan nama.

Kriteria:

- Search dapat dilakukan dari halaman daftar author.
- Input search menggunakan debounce atau trigger eksplisit yang efisien.
- Hasil search tetap kompatibel dengan pagination.

#### FR-04: Filter dan Sort Author

Sistem harus memungkinkan user memfilter dan mengurutkan daftar author.

Kriteria:

- User dapat mengurutkan author minimal berdasarkan nama dan tanggal dibuat/diubah.
- User dapat memfilter author berdasarkan status memiliki book atau tidak.
- Parameter filter dan sort tersinkron ke URL query atau state yang konsisten.

#### FR-05: Melihat Detail Author

Sistem harus memungkinkan user membuka halaman detail author.

Kriteria:

- Menampilkan informasi lengkap author.
- Menampilkan daftar book milik author.
- Menampilkan jumlah total book author tersebut.
- Menyediakan tombol edit author dan tambah book dari konteks author.

#### FR-06: Menambahkan Author

Sistem harus memungkinkan user menambahkan author baru.

Kriteria:

- User dapat membuka form tambah author.
- Sistem memvalidasi field wajib.
- Setelah berhasil, user menerima notifikasi sukses.
- Data baru langsung tampil di daftar atau detail sesuai flow yang dipilih.

#### FR-07: Mengubah Data Author

Sistem harus memungkinkan user mengubah data author yang sudah ada.

Kriteria:

- Form edit terisi data author saat ini.
- Sistem menolak data invalid.
- Setelah berhasil, halaman yang aktif menampilkan data terbaru.

#### FR-08: Menghapus Author

Sistem harus memungkinkan user menghapus author dengan kontrol yang aman.

Kriteria:

- Terdapat modal atau dialog konfirmasi sebelum delete.
- Sistem menolak penghapusan jika author masih memiliki book aktif.
- User menerima pesan yang jelas bila delete gagal karena constraint relasi.

### Modul Book

#### FR-09: Melihat Daftar Book

Sistem harus memungkinkan user melihat daftar book dengan konteks author yang jelas.

Kriteria:

- Menampilkan data book secara paginated.
- Menampilkan judul, author, tanggal terbit, status ketersediaan data, dan tanggal update.
- Menyediakan aksi detail, edit, dan delete.

#### FR-10: Mencari Book

Sistem harus memungkinkan user mencari book berdasarkan judul.

Kriteria:

- Search dapat dilakukan dari halaman daftar book.
- Search dapat digabung dengan filter dan pagination.

#### FR-11: Filter dan Sort Book

Sistem harus memungkinkan user memfilter dan mengurutkan daftar book.

Kriteria:

- User dapat filter berdasarkan author.
- User dapat filter berdasarkan rentang tanggal terbit bila field tersedia.
- User dapat sort berdasarkan judul, author, atau tanggal dibuat.

#### FR-12: Melihat Detail Book

Sistem harus memungkinkan user melihat detail book.

Kriteria:

- Menampilkan informasi utama book secara lengkap.
- Menampilkan informasi author terkait.
- Menyediakan navigasi ke detail author.

#### FR-13: Menambahkan Book

Sistem harus memungkinkan user menambahkan book baru dan mengaitkannya ke author.

Kriteria:

- User dapat memilih author dari dropdown atau searchable select.
- Sistem memastikan `author_id` valid.
- Setelah berhasil, user menerima feedback sukses.

#### FR-14: Mengubah Data Book

Sistem harus memungkinkan user mengubah data book.

Kriteria:

- User dapat mengubah field book dan author terkait.
- Validasi tetap diterapkan saat update.
- Bila author berubah, relasi pada detail author ikut konsisten.

#### FR-15: Menghapus Book

Sistem harus memungkinkan user menghapus book.

Kriteria:

- Terdapat konfirmasi sebelum delete.
- List dan detail terkait diperbarui setelah delete berhasil.

### Modul Navigasi dan UX

#### FR-16: Navigasi Antar Modul

Sistem harus menyediakan navigasi yang jelas antar halaman utama.

Kriteria:

- Terdapat menu atau tab untuk `Dashboard`, `Authors`, dan `Books`.
- Terdapat breadcrumb atau indikator halaman aktif bila diperlukan.
- User dapat kembali ke halaman sebelumnya tanpa kehilangan konteks penting.

#### FR-17: Persistensi State List

Sistem harus menjaga pengalaman list tetap nyaman saat user berpindah halaman.

Kriteria:

- Search, filter, sort, dan page aktif dipertahankan saat user kembali dari halaman detail atau edit.
- Query state dapat direkonstruksi dari URL atau state manager frontend.

#### FR-18: Feedback Operasi

Sistem harus memberikan feedback yang jelas untuk seluruh operasi.

Kriteria:

- Menampilkan toast, alert, atau inline message untuk sukses, gagal, dan warning.
- Menampilkan loading indicator pada submit form dan fetch list.
- Menampilkan empty state yang informatif ketika data tidak ditemukan.

#### FR-19: Perlindungan Unsaved Changes

Sistem sebaiknya mencegah user kehilangan input form secara tidak sengaja.

Kriteria:

- Jika user mencoba keluar dari form dengan perubahan yang belum disimpan, sistem memberi peringatan.

### Modul API dan Integrasi

#### FR-20: Pagination API

Sistem harus menyediakan pagination pada endpoint list author dan list book.

Kriteria:

- API mengembalikan item dan metadata pagination.
- Frontend dapat menggunakan metadata untuk kontrol pagination.
- Parameter page dan per_page diproses secara konsisten.

#### FR-21: Search / Filter / Sort API

Sistem harus mendukung query parameter untuk search, filter, dan sorting.

Kriteria:

- Endpoint list author dan book menerima parameter query yang terdokumentasi.
- API mengembalikan hasil yang konsisten terhadap kombinasi query parameter.

#### FR-22: Error Response API

Sistem harus memiliki struktur error response yang konsisten.

Kriteria:

- Validation error mengembalikan field dan pesan yang jelas.
- Business rule error mengembalikan message yang dapat ditampilkan langsung ke user.
- Unexpected error memiliki fallback message yang aman.

## User Stories

- Sebagai operator data, saya ingin melihat ringkasan jumlah author dan book agar saya cepat memahami kondisi sistem.
- Sebagai operator data, saya ingin mencari author berdasarkan nama agar saya tidak perlu membuka banyak halaman.
- Sebagai operator data, saya ingin memfilter author yang belum memiliki book agar saya dapat melengkapi datanya.
- Sebagai operator data, saya ingin melihat detail author beserta daftar book miliknya agar relasi data lebih jelas.
- Sebagai operator data, saya ingin menambahkan author baru agar master data selalu up-to-date.
- Sebagai operator data, saya ingin mengedit author agar informasi yang salah bisa diperbaiki.
- Sebagai operator data, saya ingin mencegah penghapusan author yang masih memiliki book agar data tidak rusak.
- Sebagai operator data, saya ingin melihat daftar book lengkap dengan author-nya agar data mudah ditinjau.
- Sebagai operator data, saya ingin mencari dan memfilter book agar saya cepat menemukan item tertentu.
- Sebagai operator data, saya ingin menambahkan book dengan memilih author yang valid agar relasi tetap benar.
- Sebagai operator data, saya ingin mendapat notifikasi yang jelas saat submit berhasil atau gagal agar saya paham apa yang terjadi.
- Sebagai evaluator, saya ingin melihat struktur produk yang lebih lengkap agar kualitas engineering kandidat terlihat lebih nyata.

## Business Rules

- Satu author dapat memiliki banyak book.
- Satu book hanya boleh dimiliki oleh satu author.
- Book tidak boleh dibuat tanpa `author_id` yang valid.
- Nama author wajib diisi.
- Judul book wajib diisi.
- Nilai field wajib tidak boleh hanya berisi spasi kosong.
- Author tidak boleh dihapus jika masih memiliki book yang terhubung.
- Search, filter, dan sorting tidak boleh merusak hasil pagination.
- Data detail harus konsisten dengan hasil list setelah create, update, atau delete.

## Kebutuhan Data

### Entitas Author

Field minimum yang direkomendasikan:

- `id`
- `name` (required)
- `slug` (optional atau generated)
- `bio` (optional)
- `birth_date` (optional)
- `nationality` (optional)
- `created_at`
- `updated_at`

Derived data:

- `books_count`
- `latest_book_title` (opsional untuk list atau detail)

### Entitas Book

Field minimum yang direkomendasikan:

- `id`
- `author_id` (required, foreign key)
- `title` (required)
- `slug` (optional atau generated)
- `description` (optional)
- `isbn` (optional)
- `published_date` (optional)
- `page_count` (optional)
- `created_at`
- `updated_at`

### Relasi

- `Author hasMany Books`
- `Book belongsTo Author`

## Kebutuhan API

### Prinsip Umum

- API mengikuti pola RESTful.
- Response menggunakan JSON.
- Endpoint list mendukung pagination, search, filter, dan sorting.
- Response error konsisten antar endpoint.
- Endpoint detail dapat mengembalikan relasi yang diperlukan frontend.

### Endpoint Inti Author

- `GET /api/authors`
- `POST /api/authors`
- `GET /api/authors/{id}`
- `PUT /api/authors/{id}`
- `PATCH /api/authors/{id}`
- `DELETE /api/authors/{id}`

### Endpoint Inti Book

- `GET /api/books`
- `POST /api/books`
- `GET /api/books/{id}`
- `PUT /api/books/{id}`
- `PATCH /api/books/{id}`
- `DELETE /api/books/{id}`

### Endpoint Tambahan yang Direkomendasikan

- `GET /api/dashboard/summary`
- `GET /api/authors/{id}/books`

### Query Parameter yang Direkomendasikan

- `page`
- `per_page`
- `search`
- `sort_by`
- `sort_order`
- `author_id`
- `has_books`

### Ekspektasi Response

- List response menyertakan `data`, `meta`, dan `links` atau struktur pagination setara.
- Detail author menyertakan ringkasan relasi bila dibutuhkan.
- Detail book menyertakan author terkait.
- Error response menyertakan `message` dan detail field error saat validasi gagal.

## Kebutuhan Frontend

### Teknologi Frontend

- Framework: Vue.js
- Build Tool: Vite
- Styling: Tailwind CSS
- Routing: Vue Router
- State Management: Pinia atau pendekatan state terstruktur setara
- HTTP Client: Axios atau Fetch wrapper terpusat

### Halaman / View Minimum

- Dashboard
- Author list
- Author detail
- Author create
- Author edit
- Book list
- Book detail
- Book create
- Book edit

### Komponen UI Minimum

- Data table atau responsive list
- Search bar
- Filter panel
- Sort control
- Pagination control
- Reusable form fields
- Confirmation modal
- Toast / alert system
- Empty state component
- Loading skeleton atau spinner

### Ekspektasi UI/UX

- Interface bersih, ringan, dan konsisten.
- Layout dapat digunakan nyaman di desktop dan mobile.
- Aksi utama seperti tambah, simpan, edit, dan hapus mudah ditemukan.
- Form memiliki validasi inline atau message yang jelas.
- Halaman detail membantu user memahami konteks data, bukan sekadar menampilkan raw fields.

### State Management

- Data list, detail, loading state, pagination state, filter state, dan error state dikelola secara konsisten.
- Refresh data setelah create/update/delete terasa mulus.
- Query state idealnya dapat direstore saat reload halaman.

## Non-Functional Requirements

### NFR-01: Code Quality

- Kode backend dan frontend harus modular, mudah dibaca, dan konsisten.
- Naming harus jelas dan seragam.
- Struktur project mengikuti best practice Laravel dan Vue.js.

### NFR-02: Maintainability

- Komponen UI dapat digunakan ulang.
- Layer API client dipisahkan dari komponen presentasional.
- Controller, request validation, dan service/helper dipisahkan secara masuk akal.

### NFR-03: Performance

- Query list book + author dioptimalkan dengan eager loading.
- Pagination wajib digunakan untuk list utama.
- Search dan filter tidak boleh menimbulkan query yang tidak efisien secara berlebihan.

### NFR-04: Reliability

- Validasi request wajib diterapkan untuk create dan update.
- Error handling frontend memiliki fallback yang aman.
- Aplikasi tetap stabil pada kondisi data kosong, data besar, dan input invalid.

### NFR-05: Accessibility Dasar

- Form field memiliki label.
- Tombol dan control utama dapat dibedakan dengan jelas.
- Kontras warna cukup untuk dibaca.

### NFR-06: Documentation

- README menjelaskan setup backend dan frontend dari nol.
- EXPLANATION.md menjelaskan alasan pemilihan Vue.js, struktur proyek, dan keputusan UX.
- AI usage dapat dijelaskan secara transparan bila digunakan.

## Deliverables

Dokumen dan output minimum yang harus tersedia:

- Source code backend Laravel.
- Source code frontend Vue.js.
- `README.md` berisi setup lokal lengkap.
- `EXPLANATION.md` berisi penjelasan UI/UX dan keputusan arsitektur.

Dokumen opsional namun direkomendasikan:

- `AI_USAGE.md` untuk transparansi penggunaan AI-assisted tools.

Output bonus:

- URL repository publik.
- URL aplikasi live yang dapat diakses publik.

## Acceptance Criteria MVP

MVP dianggap selesai jika seluruh poin berikut terpenuhi:

- Dashboard menampilkan ringkasan data utama.
- CRUD author berjalan end-to-end dari UI ke API ke database.
- CRUD book berjalan end-to-end dari UI ke API ke database.
- Relasi author-book tersimpan dan tampil dengan benar di list dan detail.
- List author mendukung pagination, search, dan sorting minimum.
- List book mendukung pagination, search, filter author, dan sorting minimum.
- Delete author ditangani aman ketika masih memiliki book.
- Validasi input wajib berjalan dan pesannya dapat dipahami user.
- Empty, loading, success, dan error state tampil konsisten.
- README dapat dipakai untuk menjalankan project tanpa tebakan tambahan.

## Bonus Opportunities

Fitur di bawah ini bukan requirement inti, tetapi bernilai tambah tinggi:

- Caching pada endpoint ringkasan atau list yang sering diakses.
- Soft delete dan restore.
- Bulk delete book.
- URL state sync penuh untuk filter dan pagination.
- Skeleton loading yang polished.
- Unit test atau feature test tambahan pada API.
- Deployment publik untuk frontend dan backend.

## Risiko dan Mitigasi

### Risiko 1: Scope Terlalu Besar

Perluasan requirement dapat membuat implementasi melebar dan mengganggu stabilitas core flow.

Mitigasi:

- Prioritaskan MVP dan tandai fitur non-esensial sebagai post-MVP.

### Risiko 2: Inkonsistensi State Frontend

Search, filter, sorting, detail, dan edit dapat menimbulkan state yang tidak sinkron.

Mitigasi:

- Gunakan pola state management yang jelas dan konsisten di Vue.js.

### Risiko 3: Constraint Relasi Tidak Jelas

Delete author dapat menimbulkan inkonsistensi bila policy relasi tidak ditetapkan.

Mitigasi:

- Blok delete author ketika masih memiliki book dan tampilkan pesan yang eksplisit.

### Risiko 4: UX Kaya tapi Membingungkan

Penambahan filter, statistik, dan detail view berpotensi membuat UI terlalu padat.

Mitigasi:

- Terapkan hierarchy visual yang baik dan pertahankan alur utama tetap sederhana.

### Risiko 5: Dokumentasi Tidak Mengikuti Implementasi

Perubahan dari brief awal ke Vue.js dapat menimbulkan kebingungan bila tidak dijelaskan.

Mitigasi:

- Dokumentasikan deviasi teknologi secara eksplisit di README dan EXPLANATION.md.

## Dependency dan Teknologi

- Backend: Laravel (PHP)
- Frontend: Vue.js
- Build Tool Frontend: Vite
- Styling: Tailwind CSS
- Database: MySQL / PostgreSQL / SQLite

## Rencana Implementasi Tingkat Tinggi

### Phase 1: Setup Dasar

- Inisialisasi backend Laravel.
- Inisialisasi frontend Vue.js dengan Vite.
- Setup database, environment, dan komunikasi API.

### Phase 2: Domain dan API

- Buat migration author dan book.
- Buat model, relasi, controller, request validation, resource/transformer, dan endpoint CRUD.
- Tambahkan search, filter, sorting, pagination, dan summary endpoint.

### Phase 3: Frontend UI

- Buat layout, router, dan state management.
- Buat dashboard, list, detail, create, dan edit page untuk author dan book.
- Integrasikan loading, error, confirmation, dan success feedback.

### Phase 4: Dokumentasi dan Finishing

- Tulis README.
- Tulis EXPLANATION.md.
- Tambahkan AI_USAGE.md bila diperlukan.
- Lakukan smoke test flow utama dan verifikasi build.

## Keputusan Produk yang Direkomendasikan

Untuk menjaga proyek tetap kuat sekaligus menunjukkan kemampuan full-stack secara lebih utuh, rekomendasi keputusan produk adalah:

- Gunakan Vue.js sebagai frontend utama.
- Jadikan list, detail, dan form sebagai tiga flow utama tiap entitas.
- Tambahkan search, filter, sort, dan dashboard agar produk terasa lebih matang.
- Pertahankan tanpa autentikasi agar fokus tetap pada kualitas domain, API, dan UX.
- Hindari fitur lanjutan yang tidak mendukung evaluasi inti.

## Pertanyaan Terbuka

Beberapa detail masih dapat diputuskan saat implementasi:

- Apakah field `isbn`, `nationality`, dan `birth_date` akan masuk MVP atau hanya nice-to-have?
- Apakah dashboard cukup berisi angka ringkas atau perlu section recent activity?
- Apakah search menggunakan debounce otomatis atau tombol submit?
- Apakah detail author memerlukan pagination untuk daftar book jika jumlahnya besar?

## Kesimpulan

PRD revisi ini menerjemahkan brief Redcomm menjadi produk yang lebih kompleks, lebih siap dipresentasikan, dan lebih kuat dari sisi experience maupun technical depth. Dengan frontend berbasis Vue.js, sistem ini tetap menjaga fokus pada author dan book management, tetapi menawarkan requirement yang lebih matang untuk menunjukkan kualitas engineering secara lebih nyata.
