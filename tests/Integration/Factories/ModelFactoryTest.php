<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Factories;

use OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder;
use OpenSearch\ScoutDriverPlus\Factories\ModelFactory;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(ModelFactory::class)]
#[UsesClass(DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class ModelFactoryTest extends TestCase
{
    private Author $author;
    private Book $book;
    private ModelFactory $modelFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = Author::factory()->create();
        $this->book = Book::factory()->create(['author_id' => $this->author->getKey()]);

        $this->modelFactory = new ModelFactory([
            $this->author->searchableAs() => new DatabaseQueryBuilder($this->author),
            $this->book->searchableAs() => new DatabaseQueryBuilder($this->book),
        ]);
    }

    public function test_empty_collection_returned_when_document_ids_are_empty(): void
    {
        $this->assertEmpty(
            $this->modelFactory->makeFromIndexNameAndDocumentIds(
                $this->author->searchableAs(),
                []
            )
        );
    }

    public function test_empty_collection_returned_when_document_ids_do_not_exist(): void
    {
        $this->assertEmpty(
            $this->modelFactory->makeFromIndexNameAndDocumentIds(
                $this->author->searchableAs(),
                ['0']
            )
        );
    }

    public function test_models_are_returned_when_index_name_is_used(): void
    {
        $this->assertEquals(
            [$this->author->toArray()],
            $this->modelFactory->makeFromIndexNameAndDocumentIds(
                $this->author->searchableAs(),
                [(string)$this->author->getScoutKey()]
            )->toArray()
        );

        $this->assertEquals(
            [$this->book->toArray()],
            $this->modelFactory->makeFromIndexNameAndDocumentIds(
                $this->book->searchableAs(),
                [(string)$this->book->getScoutKey()]
            )->toArray()
        );
    }

    public function test_models_are_returned_when_alias_name_is_used(): void
    {
        $this->assertEquals(
            [$this->author->toArray()],
            $this->modelFactory->makeFromIndexNameAndDocumentIds(
                'book-authors',
                [(string)$this->author->getScoutKey()]
            )->toArray()
        );
    }
}
