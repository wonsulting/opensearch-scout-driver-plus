<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use OpenSearch\Adapter\Search\Suggestion;
use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\MatchNoneQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\LazyModelFactory::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\ModelFactory::class)]
#[CoversClass(Query::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\SearchParametersBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\Hit::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\SearchResult::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\Suggestion::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\ParameterFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\ParameterCollection::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class MatchNoneQueryTest extends TestCase
{
    public function test_none_models_can_be_found(): void
    {
        Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create();

        $found = Book::searchQuery(Query::matchNone())->execute();

        $this->assertSame(0, $found->total());
    }

    public function test_terms_can_be_suggested(): void
    {
        $target = Book::factory()
            ->belongsToAuthor()
            ->create(['title' => 'world']);

        $found = Book::searchQuery(Query::matchNone())
            ->suggest('title', [
                'text' => 'word',
                'term' => [
                    'field' => 'title',
                ],
            ])
            ->execute();

        /** @var Suggestion $suggestion */
        $suggestion = $found->suggestions()->get('title')->first();

        $this->assertSame('word', $suggestion->text());
        $this->assertSame($target->title, $suggestion->options()->first()['text']);
        $this->assertSame(0, $found->total());
    }
}
