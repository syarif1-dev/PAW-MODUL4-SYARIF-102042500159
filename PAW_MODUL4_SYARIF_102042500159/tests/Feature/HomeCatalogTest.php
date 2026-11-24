<?php

namespace Tests\Feature;

use App\Models\Buku;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_searches_book_title_author_and_publisher(): void
    {
        $this->createBook('Searchable title', 'Writer one', 'Publisher one');
        $this->createBook('Another title', 'Searchable author', 'Publisher two');
        $this->createBook('Third title', 'Writer three', 'Searchable publisher');

        foreach (['title', 'author', 'publisher'] as $term) {
            $response = $this->get(route('home', ['search' => 'Searchable ' . $term]));
            $response->assertOk();
            $this->assertSame(1, $response->viewData('buku')->total());
        }
    }

    public function test_catalog_filters_sorts_and_lists_publishers(): void
    {
        $this->createBook('Alpha', 'Writer', 'Press', 2020, 2);
        $this->createBook('Beta', 'Writer', 'Press', 2021, 1);
        $this->createBook('Unavailable', 'Writer', 'Press', 2022, 0);
        $this->createBook('Other publisher', 'Writer', 'Another press', 2023, 5);

        $response = $this->get(route('home', [
            'penerbit' => 'Press',
            'status' => 'tersedia',
            'sort' => 'judul_desc',
        ]));

        $response->assertOk();
        $this->assertSame(
            ['Beta', 'Alpha'],
            $response->viewData('buku')->pluck('judul')->all()
        );
        $this->assertSame(['Another press', 'Press'], $response->viewData('listPenerbit')->all());
    }

    public function test_catalog_paginates_eight_books_and_preserves_query_string(): void
    {
        for ($i = 1; $i <= 9; $i++) {
            $this->createBook(sprintf('Catalog %02d', $i), 'Writer', 'Press');
        }

        $response = $this->get(route('home', [
            'search' => 'Catalog',
            'sort' => 'judul_asc',
            'page' => 2,
        ]));

        $response->assertOk();
        $books = $response->viewData('buku');
        $this->assertSame(8, $books->perPage());
        $this->assertSame(2, $books->currentPage());
        $this->assertSame(1, $books->count());
        $this->assertStringContainsString('search=Catalog', $books->url(1));
        $this->assertStringContainsString('sort=judul_asc', $books->url(1));
    }

    private function createBook(
        string $title,
        string $author,
        string $publisher,
        int $year = 2020,
        int $stock = 1
    ): Buku {
        return Buku::create([
            'judul' => $title,
            'penulis' => $author,
            'penerbit' => $publisher,
            'tahun_terbit' => $year,
            'stok' => $stock,
        ]);
    }
}
