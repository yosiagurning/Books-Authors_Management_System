# Backend

Dokumentasi utama proyek ada di [README.md](file:///d:/Internship/Books&Authors_Management_System/README.md).

Folder ini berisi Laravel REST API untuk Book & Author Management System.

Command yang paling sering dipakai:

```bash
composer install
php artisan migrate:fresh --seed
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```

Endpoint utama:

- `GET /api/dashboard/summary`
- `GET /api/authors`
- `POST /api/authors`
- `GET /api/books`
- `POST /api/books`
