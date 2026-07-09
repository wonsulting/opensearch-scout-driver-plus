<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Queries;

use Carbon\Carbon;
use OpenSearch\ScoutDriverPlus\Builders\BoolQueryBuilder;
use OpenSearch\ScoutDriverPlus\Builders\RangeQueryBuilder;
use OpenSearch\ScoutDriverPlus\Support\Query;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

use const SORT_NUMERIC;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Builders\AbstractParameterizedQueryBuilder::class)]
#[CoversClass(BoolQueryBuilder::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\LazyModelFactory::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Factories\ModelFactory::class)]
#[CoversClass(Query::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\MatchAllQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\MatchQueryBuilder::class)]
#[UsesClass(RangeQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\SearchParametersBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Builders\TermQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Decorators\Hit::class)]
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
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\CompoundValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\QueryParameters\Validators\OneOfValidator::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Support\Arr::class)]
final class BoolQueryTest extends TestCase
{
    public function test_models_can_be_found_using_must(): void
    {
        // additional mixin
        Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create();

        $target = Book::factory()
            ->belongsToAuthor()
            ->create(['title' => uniqid('test')]);

        $query = Query::bool()->must(
            Query::match()
                ->field('title')
                ->query($target->title)
        );

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_models_can_be_found_using_must_not(): void
    {
        $mixin = Book::factory()
            ->belongsToAuthor()
            ->create(['title' => uniqid('test')]);

        $target = Book::factory()
            ->belongsToAuthor()
            ->create();

        $query = Query::bool()->mustNot(
            Query::match()
                ->field('title')
                ->query($mixin->title)
        );

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_models_can_be_found_using_should(): void
    {
        $source = collect(['2018-04-23', '2003-01-14', '2020-03-07'])->map(
            static fn (string $published) => Book::factory()
                ->belongsToAuthor()
                ->create(['published' => Carbon::createFromFormat('Y-m-d', $published)])
        );

        $target = $source->filter(
            static fn (Book $model) => $model->published->year > 2003
        )->sortBy('id', SORT_NUMERIC);

        $query = Query::bool()
            ->should(
                Query::term()
                    ->field('published')
                    ->value('2018-04-23')
            )
            ->should(
                Query::term()
                    ->field('published')
                    ->value('2020-03-07')
            );

        $found = Book::searchQuery($query)
            ->sort('id')
            ->execute();

        $this->assertFoundModels($target, $found);
    }

    public function test_models_can_be_found_using_filter(): void
    {
        // additional mixin
        Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create(['published' => Carbon::create(2010, 5, 10)]);

        $target = Book::factory()
            ->belongsToAuthor()
            ->create(['published' => Carbon::create(2020, 6, 7)]);

        $query = Query::bool()->filter(
            Query::term()
                ->field('published')
                ->value('2020-06-07')
        );

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_not_trashed_models_can_be_found(): void
    {
        $this->config->set('scout.soft_delete', true);

        $source = Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create();

        $target = $source->first();

        $source->where('id', '!=', $target->id)->each(static function (Book $model) {
            $model->delete();
        });

        $query = Query::bool()->must(
            Query::matchAll()
        );

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_trashed_models_can_be_found(): void
    {
        $this->config->set('scout.soft_delete', true);

        $target = Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create()
            ->sortBy('id', SORT_NUMERIC);

        // soft delete some models
        $target->first()->delete();

        $query = Query::bool()
            ->must(Query::matchAll())
            ->withTrashed();

        $found = Book::searchQuery($query)
            ->sort('id')
            ->execute();

        $this->assertFoundModels($target, $found);
    }

    public function test_only_trashed_models_can_be_found(): void
    {
        $this->config->set('scout.soft_delete', true);

        $source = Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create();

        $target = $source->first();
        $target->delete();

        $query = Query::bool()
            ->must(Query::matchAll())
            ->onlyTrashed();

        $found = Book::searchQuery($query)->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_only_trashed_models_can_be_found_in_multiple_indices(): void
    {
        $this->config->set('scout.soft_delete', true);

        $target = Book::factory()
            ->belongsToAuthor()
            ->create();

        $target->delete();

        $query = Query::bool()
            ->must(Query::matchAll())
            ->onlyTrashed();

        $found = Author::searchQuery($query)
            ->join(Book::class)
            ->execute();

        $this->assertFoundModel($target, $found);
    }

    public function test_models_can_be_found_in_multiple_indices(): void
    {
        // additional mixins
        Book::factory()->count(rand(2, 10))
            ->belongsToAuthor()
            ->create();

        $firstTarget = Author::factory()
            ->withBooks()
            ->create(['name' => uniqid('author', true)]);

        $secondTarget = Book::factory()
            ->belongsToAuthor()
            ->create(['title' => uniqid('book', true)]);

        $query = Query::bool()
            ->should(
                Query::match()
                    ->field('name')
                    ->query($firstTarget->name)
            )
            ->should(
                Query::match()
                    ->field('title')
                    ->query($secondTarget->title)
            )
            ->minimumShouldMatch(1);

        $found = Author::searchQuery($query)
            ->join(Book::class)
            ->sort('_index')
            ->execute();

        $this->assertFoundModels(collect([$firstTarget, $secondTarget]), $found);
    }

    public function test_models_can_be_found_using_query_builder(): void
    {
        // additional mixin
        Book::factory()->count(rand(2, 5))
            ->belongsToAuthor()
            ->create(['published' => '2019-03-07']);

        $target = Book::factory()
            ->belongsToAuthor()
            ->create(['published' => '2020-12-07']);

        $builder = (new BoolQueryBuilder())->must(
            (new RangeQueryBuilder())
                ->field('published')
                ->gte('2020')
                ->format('yyyy')
        );

        $found = Book::searchQuery($builder)->execute();

        $this->assertFoundModel($target, $found);
    }
}
