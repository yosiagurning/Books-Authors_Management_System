<?php

namespace Tests\Feature;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
