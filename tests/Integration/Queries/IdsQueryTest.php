<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use Illuminate\Database\Eloquent\Model;
use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use const SORT_NUMERIC;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\IdsQueryBuilder::class)]
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
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\FlatArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class IdsQueryTest extends TestCase
{
    public function test_models_can_be_found_by_ids(): void
    {
        $models = collect(range(1, 10))->map(
            static fn (int $i): Model => Book::factory()
                ->belongsToAuthor()
                ->create(['id' => $i])
        );

        $target = $models->where('id', '>', 7)->sortBy('id', SORT_NUMERIC);

        $query = Query::ids()->values(['8', '9', '10']);

        $found = Book::searchQuery($query)
            ->sort('id')
            ->execute();

        $this->assertFoundModels($target, $found);
    }
}
