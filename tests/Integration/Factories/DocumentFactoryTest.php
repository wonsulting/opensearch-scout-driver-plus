<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Factories;

use Illuminate\Support\Facades\DB;
use OpenSearch\ScoutDriverPlus\Factories\DocumentFactory;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class DocumentFactoryTest extends TestCase
{
    private DocumentFactory $documentFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->documentFactory = new DocumentFactory();
    }

    public function test_relations_can_be_preloaded(): void
    {
        $models = Book::factory()->count(rand(2, 5))
            ->belongsToAuthor()
            ->create()
            ->fresh();

        DB::enableQueryLog();
        $this->documentFactory->makeFromModels($models);
        $queryLog = DB::getQueryLog();

        $this->assertCount(1, $queryLog);
    }
}
