<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\TermQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\LazyModelFactory::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\ModelFactory::class)]
#[CoversClass(Query::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\SearchParametersBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\Hit::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\SearchResult::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\ParameterFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\GroupedArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class TermQueryTest extends TestCase
{
    public function test_models_can_be_found_using_field_and_value(): void
    {
        // additional mixin
        Book::factory()
            ->belongsToAuthor()
            ->create(['price' => 150]);

        $target = Book::factory()
            ->belongsToAuthor()
            ->create(['price' => 300]);

        $query = Query::term()
            ->field('price')
            ->value(300);

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }
}
