<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use OpenSearch\ScoutDriverPlus\Builders\NestedQueryBuilder;
use OpenSearch\ScoutDriverPlus\Builders\TermQueryBuilder;
use OpenSearch\ScoutDriverPlus\Decorators\Hit;
use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(NestedQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\LazyModelFactory::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\ModelFactory::class)]
#[CoversClass(Query::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\MatchQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\SearchParametersBuilder::class)]
#[UsesClass(TermQueryBuilder::class)]
#[UsesClass(Hit::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\SearchResult::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\ParameterFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Shared\FieldParameter::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Shared\QueryStringParameter::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Shared\ValueParameter::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\FlatArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Transformers\GroupedArrayTransformer::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\AllOfValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class NestedQueryTest extends TestCase
{
    public function test_models_can_be_found_using_path_and_query(): void
    {
        // additional mixin
        Book::factory()->count(rand(2, 10))->create([
            'author_id' => Author::factory()->create([
                'name' => 'John',
            ]),
        ]);

        $target = Book::factory()->create([
            'author_id' => Author::factory()->create([
                'name' => 'Steven',
            ]),
        ]);

        $query = Query::nested()
            ->path('author')
            ->query(
                Query::match()
                    ->field('author.name')
                    ->query('Steven')
            )
            ->innerHits(['name' => 'authors']);

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);

        /** @var Hit $hit */
        foreach ($found->hits() as $hit) {
            $this->assertCount(1, $hit->innerHits()->get('authors'));
        }
    }

    public function test_models_can_be_found_using_path_and_query_builder(): void
    {
        // additional mixin
        Book::factory()->create([
            'author_id' => Author::factory()->create([
                'phone_number' => '202-555-0165',
            ]),
        ]);

        $target = Book::factory()->create([
            'author_id' => Author::factory()->create([
                'phone_number' => '202-555-0139',
            ]),
        ]);

        $builder = (new NestedQueryBuilder())
            ->path('author')
            ->query(
                (new TermQueryBuilder())
                    ->field('author.phone_number')
                    ->value('202-555-0139')
            );

        $found = Book::searchQuery($builder)->execute();

        $this->assertFoundModel($target, $found);
    }
}
