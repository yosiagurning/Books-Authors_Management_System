<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApiWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_and_book_workflow_returns_expected_payloads(): void
    {
        $authorResponse = $this->postJson('/api/authors', [
            'name' => 'Dewi Lestari',
            'bio' => 'Penulis dan musisi Indonesia.',
            'birth_date' => '1976-01-20',
            'nationality' => 'Indonesia',
        ]);

        $authorResponse
            ->assertCreated()
            ->assertJsonPath('data.name', 'Dewi Lestari')
            ->assertJsonPath('data.books_count', 0);

        $authorId = $authorResponse->json('data.id');

        $bookResponse = $this->postJson('/api/books', [
            'author_id' => $authorId,
            'title' => 'Supernova',
            'description' => 'Novel fiksi populer.',
            'isbn' => '9789799625700',
            'published_date' => '2001-02-01',
            'page_count' => 320,
        ]);

        $bookResponse
            ->assertCreated()
            ->assertJsonPath('data.title', 'Supernova')
            ->assertJsonPath('data.author.id', $authorId);

        $this->getJson('/api/authors?search=Dewi')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/books?author_id='.$authorId)
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('data.totals.authors', 1)
            ->assertJsonPath('data.totals.books', 1);
    }

    public function test_author_cannot_be_deleted_while_books_still_exist(): void
    {
        $author = Author::query()->create([
            'name' => 'Ahmad Tohari',
            'slug' => 'ahmad-tohari',
            'bio' => 'Penulis Indonesia.',
            'nationality' => 'Indonesia',
        ]);

        Book::query()->create([
            'author_id' => $author->id,
            'title' => 'Ronggeng Dukuh Paruk',
            'slug' => 'ronggeng-dukuh-paruk',
        ]);

        $this->deleteJson('/api/authors/'.$author->id)
            ->assertStatus(422)
            ->assertJsonPath('message', 'Author tidak dapat dihapus karena masih memiliki book.');
    }

    public function test_author_can_be_restored_within_seven_seconds_after_soft_delete(): void
    {
        Carbon::setTestNow('2026-06-26 23:00:00');

        $author = Author::query()->create([
            'name' => 'Tere Liye',
            'slug' => 'tere-liye',
            'bio' => 'Penulis novel Indonesia.',
            'birth_date' => '1979-05-21',
            'nationality' => 'Indonesia',
        ]);

        $this->deleteJson('/api/authors/'.$author->id)
            ->assertOk()
            ->assertJsonPath('data.id', $author->id);

        $this->assertSoftDeleted('authors', ['id' => $author->id]);

        Carbon::setTestNow('2026-06-26 23:00:06');

        $this->postJson('/api/authors/'.$author->id.'/restore')
            ->assertOk()
            ->assertJsonPath('data.id', $author->id)
            ->assertJsonPath('message', 'Author berhasil dipulihkan.');

        $this->assertDatabaseHas('authors', [
            'id' => $author->id,
            'deleted_at' => null,
        ]);

        Carbon::setTestNow();
    }

    public function test_author_creation_generates_new_slug_when_soft_deleted_slug_already_exists(): void
    {
        $author = Author::query()->create([
            'name' => 'Yosia',
            'slug' => 'yosia',
            'bio' => 'Author lama.',
            'birth_date' => '2000-01-01',
            'nationality' => 'Indonesia',
        ]);

        $author->delete();

        $this->postJson('/api/authors', [
            'name' => 'Yosia',
            'bio' => 'Author baru.',
            'birth_date' => '2005-06-24',
            'nationality' => 'Indonesia',
        ])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'yosia-2')
            ->assertJsonPath('data.name', 'Yosia');
    }

    public function test_author_creation_rejects_duplicate_identity(): void
    {
        Author::query()->create([
            'name' => 'Andrea Hirata',
            'slug' => 'andrea-hirata',
            'bio' => 'Penulis aktif.',
            'birth_date' => '1967-10-24',
            'nationality' => 'Indonesia',
        ]);

        $this->postJson('/api/authors', [
            'name' => 'Andrea Hirata',
            'bio' => 'Penulis lain.',
            'birth_date' => '1967-10-24',
            'nationality' => 'Indonesia',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Author dengan nama, tanggal lahir, dan nationality yang sama sudah ada.');
    }

    public function test_author_creation_rejects_duplicate_identity_when_original_is_soft_deleted(): void
    {
        $author = Author::query()->create([
            'name' => 'Andrea Hirata',
            'slug' => 'andrea-hirata',
            'bio' => 'Penulis aktif.',
            'birth_date' => '1967-10-24',
            'nationality' => 'Indonesia',
        ]);

        $author->delete();

        $this->postJson('/api/authors', [
            'name' => 'Andrea Hirata',
            'bio' => 'Penulis lain.',
            'birth_date' => '1967-10-24',
            'nationality' => 'Indonesia',
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Data author yang sama sudah ada di sampah. Pulihkan data lama jika diperlukan.');
    }

    public function test_book_restore_fails_after_seven_second_undo_window_expires(): void
    {
        Carbon::setTestNow('2026-06-26 23:10:00');

        $author = Author::query()->create([
            'name' => 'Pramoedya Ananta Toer',
            'slug' => 'pramoedya-ananta-toer',
            'bio' => 'Penulis Indonesia.',
            'birth_date' => '1925-02-06',
            'nationality' => 'Indonesia',
        ]);

        $book = Book::query()->create([
            'author_id' => $author->id,
            'title' => 'Bumi Manusia',
            'slug' => 'bumi-manusia',
            'description' => 'Novel sejarah Indonesia.',
            'isbn' => '9789799731234',
            'published_date' => '1980-01-01',
            'page_count' => 535,
        ]);

        $this->deleteJson('/api/books/'.$book->id)
            ->assertOk()
            ->assertJsonPath('data.id', $book->id);

        $this->assertSoftDeleted('books', ['id' => $book->id]);

        Carbon::setTestNow('2026-06-26 23:10:08');

        $this->postJson('/api/books/'.$book->id.'/restore')
            ->assertStatus(410)
            ->assertJsonPath('message', 'Batas waktu undo untuk book ini sudah habis.');

        Carbon::setTestNow();
    }

    public function test_book_creation_rejects_duplicate_isbn(): void
    {
        $author = Author::query()->create([
            'name' => 'Tere Liye',
            'slug' => 'tere-liye',
            'bio' => 'Penulis novel Indonesia.',
            'birth_date' => '1979-05-21',
            'nationality' => 'Indonesia',
        ]);

        Book::query()->create([
            'author_id' => $author->id,
            'title' => 'Hujan',
            'slug' => 'hujan',
            'description' => 'Novel pertama.',
            'isbn' => '9786020332956',
            'published_date' => '2016-01-01',
            'page_count' => 320,
        ]);

        $this->postJson('/api/books', [
            'author_id' => $author->id,
            'title' => 'Hujan Edisi Baru',
            'description' => 'Novel kedua.',
            'isbn' => '9786020332956',
            'published_date' => '2017-01-01',
            'page_count' => 330,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn.0', 'ISBN ini sudah digunakan oleh book lain.');
    }

    public function test_book_creation_rejects_duplicate_catalog_entry_when_original_is_soft_deleted(): void
    {
        $author = Author::query()->create([
            'name' => 'Tere Liye',
            'slug' => 'tere-liye',
            'bio' => 'Penulis novel Indonesia.',
            'birth_date' => '1979-05-21',
            'nationality' => 'Indonesia',
        ]);

        $book = Book::query()->create([
            'author_id' => $author->id,
            'title' => 'Hujan',
            'slug' => 'hujan',
            'description' => 'Novel pertama.',
            'isbn' => '9786020332956',
            'published_date' => '2016-01-01',
            'page_count' => 320,
        ]);

        $book->delete();

        $this->postJson('/api/books', [
            'author_id' => $author->id,
            'title' => 'Hujan',
            'description' => 'Novel kedua.',
            'isbn' => '9786020332956',
            'published_date' => '2016-01-01',
            'page_count' => 320,
        ])
            ->assertStatus(422)
            ->assertJsonPath('errors.isbn.0', 'ISBN ini sudah digunakan oleh book yang ada di sampah. Pulihkan data lama jika diperlukan.');
    }
}
