<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Decorators;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use OpenSearch\Adapter\Search\Suggestion as BaseSuggestion;
use OpenSearch\ScoutDriverPlus\Builders\DatabaseQueryBuilder;
use OpenSearch\ScoutDriverPlus\Decorators\Suggestion;
use OpenSearch\ScoutDriverPlus\Factories\ModelFactory;
use OpenSearch\ScoutDriverPlus\Tests\App\Author;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(Suggestion::class)]
#[UsesClass(DatabaseQueryBuilder::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(ModelFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class SuggestionTest extends TestCase
{
    private Collection $models;
    private Suggestion $suggestion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->models = Author::factory()->count(5)->create();

        $baseSuggestion = new BaseSuggestion([
            'text' => 'tes',
            'offset' => 0,
            'length' => 3,
            'options' => $this->models->map(
                static fn (Model $model) => [
                    'text' => 'test' . $model->getScoutKey(),
                    '_index' => $model->searchableAs(),
                    '_id' => (string)$model->getScoutKey(),
                ]
            ),
        ]);

        $modelFactory = new ModelFactory([
            $this->models->first()->searchableAs() => new DatabaseQueryBuilder($this->models->first()),
        ]);

        $this->suggestion = new Suggestion($baseSuggestion, $modelFactory);
    }

    public function test_models_can_be_retrieved(): void
    {
        $this->assertEquals(
            $this->models->toArray(),
            $this->suggestion->models()->toArray()
        );
    }
}
