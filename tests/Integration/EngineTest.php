<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration;

use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[CoversClass(\OpenSearch\ScoutDriverPlus\Jobs\RemoveFromSearch::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class EngineTest extends TestCase
{
    public function test_models_can_be_found_using_default_search(): void
    {
        Book::factory()->count(rand(2, 10))->belongsToAuthor()->create();

        $target = Book::factory()->belongsToAuthor()->create(['title' => uniqid('test')]);
        $found = Book::search($target->title)->orderBy('id')->get();

        $this->assertCount(1, $found);
        $this->assertEquals($target->toArray(), $found->first()->toArray());
    }

    public static function queueConfigProvider(): array
    {
        return [
            [['scout.queue' => true]],
            [['scout.queue' => false]],
        ];
    }

    #[DataProvider('queueConfigProvider')]
    public function test_models_can_be_indexed(array $config): void
    {
        config($config);

        $source = Book::factory()->count(rand(2, 10))->belongsToAuthor()->create();
        $found = Book::search()->get();

        // assert that the amount of created models corresponds number of found models
        $this->assertSame($source->count(), $found->count());
        // assert that all source models are found
        $this->assertCount(0, $source->pluck('id')->diff($found->pluck('id')));
    }

    #[DataProvider('queueConfigProvider')]
    public function test_models_can_be_deleted(array $config): void
    {
        config($config);

        $source = Book::factory()->count(rand(2, 10))->belongsToAuthor()->create();

        // delete newly created models
        $source->each(static function (Book $model) {
            $model->delete();
        });

        // assert that there are no documents in the index
        $found = Book::search()->get();
        $this->assertSame(0, $found->count());
    }
}
