<?php declare(strict_types=1);

namespace OpenSearch\ScoutDriverPlus\Tests\Integration\Factories;

use OpenSearch\Adapter\Documents\Routing;
use OpenSearch\ScoutDriverPlus\Factories\RoutingFactory;
use OpenSearch\ScoutDriverPlus\Tests\App\Book;
use OpenSearch\ScoutDriverPlus\Tests\Integration\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

#[CoversClass(RoutingFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Engine::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Factories\DocumentFactory::class)]
#[UsesClass(\OpenSearch\ScoutDriverPlus\Searchable::class)]
final class RoutingFactoryTest extends TestCase
{
    private RoutingFactory $routingFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->routingFactory = new RoutingFactory();
    }

    public function test_routing_can_be_made_from_models(): void
    {
        $models = Book::factory()->count(rand(2, 10))->belongsToAuthor()->create();
        $routing = new Routing();

        foreach ($models as $model) {
            $routing->add((string)$model->getScoutKey(), (string)$model->searchableRouting());
        }

        $this->assertEquals($routing, $this->routingFactory->makeFromModels($models));
    }
}
