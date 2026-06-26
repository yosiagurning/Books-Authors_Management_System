<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $authors = [
            [
                'name' => 'Andrea Hirata',
                'slug' => 'andrea-hirata',
                'bio' => 'Penulis Indonesia yang dikenal lewat karya bertema pendidikan dan sosial.',
                'birth_date' => '1967-10-24',
                'nationality' => 'Indonesia',
            ],
            [
                'name' => 'Tere Liye',
                'slug' => 'tere-liye',
                'bio' => 'Penulis novel populer Indonesia dengan karya lintas genre.',
                'birth_date' => '1979-05-21',
                'nationality' => 'Indonesia',
            ],
            [
                'name' => 'Pramoedya Ananta Toer',
                'slug' => 'pramoedya-ananta-toer',
                'bio' => 'Sastrawan besar Indonesia dengan karya-karya klasik berpengaruh.',
                'birth_date' => '1925-02-06',
                'nationality' => 'Indonesia',
            ],
        ];

        foreach ($authors as $authorData) {
            Author::query()->updateOrCreate(
                ['slug' => $authorData['slug']],
                $authorData,
            );
        }

        $books = [
            [
                'author_slug' => 'andrea-hirata',
                'title' => 'Laskar Pelangi',
                'slug' => 'laskar-pelangi',
                'description' => 'Novel tentang semangat belajar dan perjuangan anak-anak Belitung.',
                'isbn' => '9789793062792',
                'published_date' => '2005-09-01',
                'page_count' => 529,
            ],
            [
                'author_slug' => 'tere-liye',
                'title' => 'Hujan',
                'slug' => 'hujan',
                'description' => 'Novel fiksi ilmiah romantis berlatar dunia pascabencana.',
                'isbn' => '9786020351179',
                'published_date' => '2016-01-28',
                'page_count' => 320,
            ],
            [
                'author_slug' => 'pramoedya-ananta-toer',
                'title' => 'Bumi Manusia',
                'slug' => 'bumi-manusia',
                'description' => 'Novel sejarah yang mengangkat pergulatan identitas dan kolonialisme.',
                'isbn' => '9789799731234',
                'published_date' => '1980-01-01',
                'page_count' => 535,
            ],
        ];

        foreach ($books as $bookData) {
            $author = Author::query()->where('slug', $bookData['author_slug'])->first();

            if (! $author) {
                continue;
            }

            Book::query()->updateOrCreate(
                ['slug' => $bookData['slug']],
                [
                    'author_id' => $author->id,
                    'title' => $bookData['title'],
                    'slug' => $bookData['slug'],
                    'description' => $bookData['description'],
                    'isbn' => $bookData['isbn'],
                    'published_date' => $bookData['published_date'],
                    'page_count' => $bookData['page_count'],
                ],
            );
        }
    }
}
